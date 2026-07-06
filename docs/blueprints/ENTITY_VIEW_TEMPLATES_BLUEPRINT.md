# Blueprint: Umschaltbare Darstellungs-Templates für die Entity-Detailseite

Status: **Plan / Konzept** — NICHT umgesetzt (auf Wunsch von Georg).
Autor: Georg + Claude · Datum: 2026-06-26 · Plugin: `local_entities`

---

## 1. Ziel

Die Entity-Detailseite (`view.php`) soll **zwischen mehreren Darstellungs-Templates
umschaltbar** sein. Vorgaben (Georg):

1. **Bestehende Darstellungen bleiben erhalten** — Bild und Kalender müssen weiter
   funktionieren, optisch unverändert.
2. **Weitere/bessere Darstellungen** sollen möglich werden.
3. **Manager können direkt auf der Seite** zwischen den Templates wechseln.

### Geltungsbereich (WICHTIG, Georg-Vorgabe)
> Die Template-Wahl gilt **global für ALLE Entities**, NICHT pro Entity. Es gibt also
> **keine** pro-Entity gespeicherte Ansicht und **keine** Schema-Spalte. Es existiert genau
> **eine** aktive Darstellung site-weit.
>
> Auf der Seite kann ein Berechtigter Templates **live als Vorschau** durchschalten (nur für
> sich, nur dieser Seitenaufruf). Damit sich die Ansicht **nicht sofort für alle ändert**, wird
> die Vorschau erst durch **explizites Speichern** zur neuen globalen Ansicht (für alle Entities,
> alle Besucher). Das Umschalten/Speichern ist durch ein **eigenes Recht** geschützt.

---

## 2. Ausgangslage (Ist)

- `view.php` (Z. 70–228) lädt die Entity, baut **ein** `$entity`-Kontextobjekt
  (Bild/`picture`/`haspdf`, `metagroups`/`metadata`, `affiliation`, `parent`,
  `addresscleaned`, `contactscleaned`, `showcalendar`, `calendarurl`,
  `showpictureinsteadofcalendar`, `canedit`, …) und rendert **ein** Template
  `local_entities/view` (`view.mustache`).
- „Varianten" gibt es nur über **globale** Settings (`settings.php`):
  `showpictureinsteadofcalendar` (Z. 97-104), `show_calendar_on_details_page`
  (Z. 79-86) + Fallbacks (image/address/contacts vom Parent).
- Capabilities (`db/access.php`): `local/entities:edit` & `:delete` (Archetype
  `manager`), `:view` (Archetype `user`). „Manager" = `has_capability('local/entities:edit')`.
- Keine Per-Entity-Auswahl, kein On-Page-Umschalten, nur ein Mustache.

→ Die „bisherigen Versuche, flexibler zu werden" sind genau diese zwei globalen
Checkboxen. Wir heben das auf eine echte, erweiterbare Template-Ebene.

---

## 3. Zielarchitektur

### 3.1 Template-Registry (erweiterbar)
Eine zentrale Liste verfügbarer View-Templates, jedes mit:
`key` · Anzeigename (Lang-String) · Mustache-Datei · Icon · optional Verfügbarkeits-/
Capability-Bedingung.

Vorschlag Startset:
| key | Name | Beschreibung | deckt ab |
|-----|------|--------------|----------|
| `classic` | Standard | heutiges Layout (Header + Sidebars + Kalender/Bild-Mitte) | **bestehend, 1:1** |
| `image` | Bild | großes Hero-Bild im Mittelpunkt, Infos darunter | heutiges `showpictureinsteadofcalendar` |
| `calendar` | Kalender | Verfügbarkeits-Kalender prominent/breit | heutige Kalender-Anzeige |
| `compact` | Kompakt | info-dichte Karte: Metadaten/Kontakte/Adresse, kleines Bild | NEU |
| `map` *(optional)* | Karte | Adresse/Map prominent (nutzt vorhandenes `mapembed`) | NEU |

Umsetzung als kleine Klasse `local_entities\local\views\view_templates`
(`get_all(): array` von DTOs, `exists($key)`, `default_key()`), damit „neues Template
hinzufügen = Eintrag + Mustache-Datei".

### 3.2 Welches Template wird gerendert? (Auflösungs-Reihenfolge)
Bewusst **flach** (global, nicht pro Entity):
1. **URL-Param** `template` — nur **Vorschau** für *diesen* Render, nur für Berechtigte; wird
   **nicht** gespeichert und ändert nichts für andere.
2. **Globale aktive Ansicht** — Setting `local_entities/activeviewtemplate` (gilt für ALLE Entities).
3. **Fallback** `classic`.

→ KEINE Per-Entity-Ebene. KEINE Spalte `local_entities.viewtemplate`.

### 3.3 Geteilter Kontext-Builder (Refactor)
Die Kontext-Erstellung aus `view.php` in einen wiederverwendbaren Builder ziehen,
z. B. `local_entities\output\entity_view::build_context(int $id): array`. Liefert den
vollen, einheitlichen Kontext (Bild, Kalenderdaten, metagroups, Kontakte, Adresse,
Affiliation, Parent, Capabilities, URLs). **Jedes** Template bekommt denselben
Kontext und zeigt nur, was es braucht. `view.php` wird dünn:
```
$ctx = entity_view::build_context($id);
// Preview-Param nur akzeptieren, wenn der/die Nutzer:in das Wechsel-Recht hat; sonst globale Ansicht.
$key = entity_view::resolve_active_template(optional_param('template', '', PARAM_ALPHANUMEXT));
echo $OUTPUT->render_from_template("local_entities/view/$key", $ctx + $switcherdata);
```

### 3.4 Templates als eigene Mustaches
- `templates/view/classic.mustache` ← **Inhalt des heutigen `view.mustache`** (unverändert,
  garantiert gleiche Optik). Heutiges `view.mustache` bleibt als Weiche/Alias oder wird ersetzt.
- `templates/view/image.mustache`, `view/calendar.mustache`, `view/compact.mustache`, …
- Alle nutzen denselben `build_context`.

### 3.5 On-Page-Umschalter (Vorschau + explizites Speichern)
- Kleiner Umschalter (Segmented-Control/Dropdown mit Icons) **oberhalb** des Inhalts,
  nur sichtbar, wenn der/die Nutzer:in das **neue Recht** `local/entities:changeviewtemplate` hat.
- Ablauf (zwei klar getrennte Schritte):
  1. **Vorschau (live, lokal):** Template wählen → `view.php?id=X&template=key` rendert die
     gewählte Ansicht. Das betrifft **nur diesen Seitenaufruf des Berechtigten** — nichts wird
     gespeichert, für alle anderen bleibt die bisherige globale Ansicht aktiv.
  2. **Speichern (explizit, global):** Erst ein expliziter **„Als Ansicht speichern"**-Button
     schreibt die Wahl in `local_entities/activeviewtemplate` → ab dann sehen **alle** Entities
     und **alle** Besucher diese Ansicht. Umsetzung via External-Function
     `set_active_view_template(key)` mit `require_capability('local/entities:changeviewtemplate')`
     + sesskey. Solange nicht gespeichert wird, ändert sich für andere nichts.
- Ein „Verwerfen/Zurück"-Link führt zur aktuell gespeicherten globalen Ansicht zurück.
- Ohne das Recht: kein Umschalter; man sieht die globale aktive Ansicht.

### 3.6 Rückwärtskompatibilität & Migration (harte Vorgabe: KEINE ungewollte Änderung)

Die heutige Optik wird allein durch die zwei Checkboxen bestimmt (sie steuern nur den
**Mittel-Block**; Header + beide Sidebars sind immer gleich). Daraus folgt das exakte Mapping:

| `showpictureinsteadofcalendar` | `show_calendar_on_details_page` | heutige Mitte | → `activeviewtemplate` |
|:---:|:---:|---|:---:|
| 1 | (egal) | großes Bild statt Kalender | **`image`** |
| 0 | 1 | **inline** eingebetteter Kalender | **`calendar`** |
| 0 | 0 *(Default)* | „Kalender öffnen"-Link/Button (kein Inline-Kalender) | **`classic`** |

Damit das byte-genau passt, werden die drei Templates **genau so** gebaut:
- `image` = heutiges Layout mit Bild im Mittel-Block (kleines Header-Thumbnail entfällt, wie heute).
- `calendar` = heutiges Layout mit inline gerendertem Kalender im Mittel-Block.
- `classic` = heutiges Layout mit „Kalender öffnen"-Link im Mittel-Block (= heutiger Default).

**Migration (einmalig, `upgrade.php`):** aus den zwei vorhandenen Config-Werten gemäß Tabelle
`activeviewtemplate` setzen (nur, wenn noch nicht gesetzt). Ergebnis: **jede Bestandssite rendert
exakt wie vorher.** Frische Installation: Default `classic` (= heutiger Default beider Checkboxen = 0/0).

Nach der Migration sind die Templates **selbsttragend** (lesen die alten Settings nicht mehr) — die
zwei Checkboxen werden damit **obsolet** (siehe §4: aus der Settings-UI entfernen; der Wert dient nur
noch als einmalige Migrationsquelle).

---

## 4. Daten-/Settings-Änderungen
- **KEINE DB-Schema-Änderung** (kein `viewtemplate`-Feld, da global statt pro Entity).
- **Settings:** **ein** Wert `local_entities/activeviewtemplate` (Select der Registry) — die
  global aktive Ansicht. Wird sowohl im Admin-Settingsformular als auch über den On-Page-„Speichern"-
  Button (External-Function) gesetzt. Die zwei alten Checkboxen `showpictureinsteadofcalendar` und
  `show_calendar_on_details_page` werden **per Upgrade einmalig in `activeviewtemplate` migriert**
  (Mapping-Tabelle §3.6) und danach **aus `settings.php` entfernt** (Ziel: keine ungewollte Änderung,
  keine doppelte Wahrheit).
- **KEINE Edit-Form-Änderung** (keine Per-Entity-Auswahl).
- **Capability (NEU):** `local/entities:changeviewtemplate` (Archetype `manager`), eigenes,
  leichtes Recht nur fürs Umschalten+Speichern — getrennt von `:edit`/`:delete`. In `db/access.php`
  ergänzen.
- **External-Function:** `local_entities_set_active_view_template(key)` (in `db/services.php`),
  prüft Recht + sesskey, validiert `key` gegen die Registry, `set_config('activeviewtemplate', …)`.
- **Lang:** Template-Namen/-Beschreibungen, Umschalter-Labels, Capability-String.

---

## 5. Testplan
- **Behat:** Berechtigte:r sieht Umschalter → Vorschau-Wechsel rendert gewähltes Template;
  **ohne** Speichern sieht ein zweiter Nutzer weiterhin die alte globale Ansicht; nach
  **Speichern** sehen alle (auch Besucher, auch andere Entities) die neue Ansicht; Nutzer ohne
  `:changeviewtemplate` sieht keinen Umschalter; Back-Compat (Default = heutige Optik); Bild- und
  Kalender-Darstellung funktionieren weiter.
- **PHPUnit:** `build_context()` liefert erwartete Felder; `resolve_active_template()`-Schichtung
  (Preview-Param nur mit Recht > global > classic); Registry-Validierung (ungültiger key → Fallback);
  Capability-Gate + key-Validierung von `set_active_view_template`.

---

## 6. Phasen & Commit-Isolation
- **A — Refactor (kein Verhaltenswechsel):** `build_context` extrahieren, heutiges
  `view.mustache` → `view/classic.mustache`, Resolver wählt immer `classic` → identische
  Ausgabe. *(unkritisch)*
- **B — Registry + neue Templates** (`image`, `calendar`, `compact`). *(additiv)*
- **C — Globales Setting `activeviewtemplate` + Back-Compat-Mapping (Upgrade) + Admin-Select.**
  Kein Schema, kein Edit-Form. *(verhaltensnah, eigener Commit)*
- **D — On-Page-Umschalter (Vorschau) + „Speichern"-Button + External-Function + neues
  Capability `:changeviewtemplate` + AMD.** *(die eigentliche Interaktions-/Schreib-Änderung,
  isoliert & einzeln revertierbar)*

Wie beim Templates-Feature: die schreibenden/verhaltensändernden Teile (C/D) bleiben von
der additiven Basis (A/B) getrennt.

---

## 7. Entscheidungen & offene Fragen

**Entschieden (Georg, 2026-06-26):**
- **O1 — Persistenz:** **Explizites Speichern** nötig (Vorschau ändert nichts für andere). ✓
- **O2 — Capability:** **Neues Recht** `local/entities:changeviewtemplate`. ✓
- **O3 — Startset:** **classic + image + calendar + compact**. ✓
- **O4 — Reichweite der Wahl:** **Global für ALLE Entities** (eine site-weite aktive Ansicht),
  **nicht** pro Entity. Kein Schema-Feld, keine Edit-Form-Auswahl, keine Parent-Vererbung. ✓

- **O5 — Alt-Settings:** **ENTSCHIEDEN (Georg):** per Upgrade in `activeviewtemplate` **migrieren**
  (Mapping §3.6) mit dem Ziel **KEINE ungewollte Änderung**, danach die zwei Checkboxen entfernen. ✓

**Noch offen:**
- **O6 — Reichweite (Seitentyp):** Nur Detailseite, oder dasselbe Konzept später auch für
  Listen-/Kalender-Ansicht?
