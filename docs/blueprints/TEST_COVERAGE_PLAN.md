# local_entities — Test-Coverage-Plan (Lücken schließen)

Status: Proposal (2026-06-28). Reine Plan-/Analyse-Doku, keine Tests/Code geändert.
Ziel: die im Coverage-Audit gefundenen Lücken systematisch schließen — Schwerpunkt auf dem
jüngsten, komplexesten und unit-untestesteten Kern (Equipment/Relations, Geocoder-Regression,
External-Services).

---

## 1. Ausgangslage (Audit-Kurzfassung)

**Gut abgedeckt:** View-Templates (`entity_view_test`), Feld-Vorlagen-Seeder (`template_seeder_test`),
Overview-Tabelle (`entities_table_test`), CSV-Import (`csv_import_test`), External `update_entity`
(`external_test`), Geocoder-Grundfunktionen, 4 Behat-Features.

**Lücken (PHPUnit):**
- `entitiesrelation_handler` (Equipment-Multi-Relation-Save + Re-Verifikation, dates-cache, Validation) — **0**.
- `osm_geocoder` **Regression** für die 3 jüngsten Bugfixes (RFC3986-`%20`, negatives Caching, Non-Array-Guard) — nur Grundfunktionen getestet.
- External `search_entities` + `get_entity_calendardata` — **0** (nur update_entity).
- `entity.php` CRUD direkt, Kalender (`fullcalendar_helper`, `reoccuringevent`) — **0**.

> **Cross-Plugin-Hinweis:** „Allocation-Modi / Conflict-Detection" (Occupancy) ist **nicht** in
> `local_entities/classes/dates.php` (dort nur `prettify_dates_start_end`). Die eigentliche
> Belegungs-/Konflikt-Auswertung liegt überwiegend **mod_booking-seitig** (Availability-Condition mit
> `entitytype/allocationmode/capacitysource`). Deren Unit-Tests gehören in die **mod_booking-Suite**,
> nicht hierher. `local_entities` deckt die **Relations-Persistenz + Cache + Geocoder + External** ab.

---

## 2. Schritt 0 (zuerst): die „fehlenden 15/15 Equipment-Tests" klären

Projekt-Notiz nannte „PHPUnit 15/15" fürs Equipment-Feature; im aktuellen Testset existieren sie nicht
(Squash hat nichts gelöscht — End-Tree identisch). **Bevor neu geschrieben wird:** prüfen, ob diese
Tests (a) auf einem anderen Branch (`entity-cross-option-availability`, `temp-dev`, `USI`) liegen,
(b) mod_booking-seitig committet wurden, oder (c) nie committet wurden. Vorhandene Tests übernehmen statt
duplizieren. (`git log --all --diff-filter=A -- '*occupanc*' '*equipment*test*' '*relation*test*'`)

---

## 3. Prioritäter Plan

### P1 — `entitiesrelation_handler` (größte Lücke) · neues `tests/entitiesrelation_handler_test.php`
Testbare öffentliche API (DB-gestützt, `resetAfterTest`):
- `save_equipment_relations(instanceid, equipment[])` → `get_equipment_relations(instanceid)`:
  - speichert mehrere Equipment-Relationen **mit Mengen**; Re-Save mit geänderter Menge/Set
    aktualisiert (Multi-Relation-Save + Re-Verifikation) statt zu duplizieren.
  - leere Liste entfernt alle Relationen.
- `save_entity_relation(instanceid, entityid)` + `get_entityid_by_instanceid` + `er_record_exists`:
  Idempotenz (kein Doppel-Record), Update vs. Insert (`save_to_db`/`update_db`).
- `delete_relation(instanceid)`: entfernt Relation(en) der Instanz.
- `purge_dates_cache(instanceid)`: invalidiert den dates-Cache (vorher/nachher prüfen).
- `option_has_dates_with_entity_outliers(optionid)`: true/false an einem konstruierten Datensatz.
- `instance_form_validation(data, errors)`: Pflicht-/Konflikt-Regeln (z. B. Menge > 0, gültige entityid).
- `get_instance_data` / `get_entities_by_name` / `_by_shortname` / `_by_id`: Lookup-Korrektheit.

**Fixtures/Generator:** `tests/generator/lib.php` hat nur `create_entities()`. → **erweitern** um
`create_equipment_entity()` / Helper, der eine Entity als Equipment mit Kapazität anlegt, plus einen
Helper, der eine Relation für eine (component, area, instanceid) setzt. Ohne diese Erweiterung sind die
P1-Tests umständlich.

### P2 — `osm_geocoder` Regression (schnell, deterministisch) · `tests/osm_geocoder_test.php`
Sichert die 3 jüngsten Bugfixes ab — **ohne Netzwerk** (pure Methode + MUC-Cache vorbefüllen):
- **`build_query()`** (pure): Leerzeichen werden **`%20`-RFC3986-kodiert, nicht `+`** (Bugfix 76ae03c);
  leeres Adress-Array → `''` (vorhanden); Felder werden korrekt zusammengesetzt.
- **`get_coordinates()` Cache-Verhalten** (Cache `local_entities/geocode` vorbefüllen):
  - Cache-Hit mit `stdClass` → liefert genau dieses Objekt.
  - Cache enthält `NOT_FOUND`-Sentinel → liefert `null` (negativer Treffer, kein Re-Fetch).
  - Leerer Cache + kein Netz im Test → `null` und **kein** negatives Caching eines *transienten*
    Fehlers (Bugfix 4d83a53: definitiver No-Match wird gecacht, transient **nicht**).
- **Non-Array-Guard** (Bugfix 2f97c1b): wenn die Parse-/Decode-Stufe eine Nicht-Array-Antwort erhält,
  kein Fatal → `null`. (Falls die Parse-Logik privat ist: über `get_coordinates` mit einem injizierbaren
  Seam oder eine kleine `@covers`-fähige Hilfsmethode testen; ggf. minimaler Refactor für Testbarkeit.)

### P3 — External-Services · ergänzen in `external_test.php` (oder neue Dateien)
- `search_entities`: Suche nach Name/Shortname liefert erwartete Entities; Capability-Gate; leere Suche.
- `get_entity_calendardata`: Kalenderdaten einer Entity (Belegung/Termine) in erwarteter Struktur;
  Capability-Gate; leere/ungültige entityid.

### P4 — Basis-CRUD & Kalender (niedriger) · optional
- `entity.php`: create/update/delete direkt (nicht nur via External-update).
- `calendar/fullcalendar_helper`, `calendar/reoccuringevent`: Event-Aufbereitung / wiederkehrende Events.

---

## 4. Infrastruktur
- **Generator erweitern** (`tests/generator/lib.php`): Equipment-Entity + Relation-Helper (Voraussetzung für P1/P3).
- Wiederverwendbare Fixture für „Entity mit Kapazität + Equipment-Relation".

---

## 5. Phasen (jede grün + committet)
1. **Schritt 0** (Recherche vorhandener Tests) — verhindert Doppelarbeit.
2. **P2 Geocoder** (schnell, isoliert, sichert 3 Bugfixes) — kleiner erster Gewinn.
3. **Generator-Erweiterung** + **P1 entitiesrelation_handler** (Kern-Lücke).
4. **P3 External** (search/calendar).
5. **P4** optional.

## 6. Nicht-Ziele
- Allocation-Modi/Conflict-Detection-**Logik** wird **mod_booking-seitig** getestet (eigene Suite), nicht hier.
- Kein Behat-Ausbau in diesem Plan (PHPUnit-Lücken zuerst; Behat deckt die UI bereits breit ab).

## 7. Risiken
- `osm_geocoder`-Non-Array-/Transient-Pfade brauchen evtl. einen **Test-Seam** (kleiner Refactor), da
  sie heute über eine private Fetch-/Parse-Stufe laufen — minimal halten.
- P1 hängt an der **Generator-Erweiterung**; ohne sie sind die Tests brüchig.
- Cross-Plugin: sicherstellen, dass Occupancy/Conflict nicht **doppelt** (hier *und* mod_booking) getestet wird.
