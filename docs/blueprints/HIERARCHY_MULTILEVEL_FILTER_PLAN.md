# Umbauplan: Mehrstufige Entity-Hierarchien + hierarchischer Filter

Status: **umgesetzt (Phasen B–G), Opt-in Default OFF** · Datum 2026-06-29 · BC-Review am Code verifiziert 2026-07-01 (§10) · umgesetzt 2026-07-01 (§11) · Betrifft `local_entities`, `local_wunderbyte_table`, `mod_booking`, `local_musi`.

> **Follow-up:** UX-Fixes zur Hierarchie-Anzeige (col_location-Hover, edit.php-Parent-Select, treefilter-Kaskade) sind separat geplant in [UX_FIXES_HIERARCHY_DISPLAY_PLAN.md](UX_FIXES_HIERARCHY_DISPLAY_PLAN.md) (P0 „fehlender booking-Provider" dort erledigt 2026-07-02, Entscheidungen E1–E3 geschlossen).

> **Rückwärtskompatibilität ist Leitplanke Nr. 1.** Die verbindlichen BC-Regeln stehen in **§10** und sind gegen den realen Code (Versionen: local_wunderbyte_table 3.2.3, local_entities 0.4.9, mod_booking 9.4.0, local_musi 1.0.5) geprüft. Kern: **`treefilter` ist Opt-in mit Default = heutiges Verhalten**; nichts Bestehendes ändert sich, bis explizit aktiviert.

## 1. Ziel
Beliebig tiefe Entity-Hierarchien (`Standort → Gebäude → Stock → Raum`) im Standort-Filter und in der Anzeige korrekt abbilden. Heute hart **2-stufig** (Entity + unmittelbarer Parent). Auswahl eines Hierarchie-Knotens soll **alle Nachfahren** einschließen.

## 2. Leitprinzipien
- **Hierarchie NIE auf der Option materialisieren.** Stabile Wahrheiten = Relation `Option→entityid` (`local_entities_relations`) + `parentid`-Baum. Pfad/Tiefe/Namen werden **live abgeleitet**. (Begründung: kein Event-System auf reparent/rename vorhanden → jede Materialisierung wäre fragil; siehe Risiko R2.)
- **Filtern über Mengen, nicht über Pfad-Strings:** Knoten X gewählt ⇒ `entityid IN (live-Nachfahren von X)`. Kein concat/JSON pro Zeile nötig.
- **wunderbyte_table bleibt generisch:** neuer Filtertyp bekommt einen **Provider** (Baum + Nachfahren-Expansion); Entity-Wissen bleibt in `local_entities`.
- **Keine Doppellösungen** — vorhandene Funktionen extrahieren/wiederverwenden (siehe Reuse-Map §4).

## 3. Architektur (Ziel)
```
local_entities (Provider, alles LIVE):
  entities::get_entity_map()                 [vorhanden, extrahieren]   id→{name,parentid,sortorder}
  entities::get_ancestor_path(id)            [aus resolve_lineage extrahieren]  Root→…→id
  entities::get_descendant_ids(id)           [NEU, auf get_entity_map aufbauend] Teilbaum-IDs inkl. self
  entities::get_filter_tree(array $entityids)[NEU] Baumstruktur für das Panel (nur belegte Knoten)

local_wunderbyte_table (generisch):
  filters/types/treefilter (NEU, EIGENER Typ — erbt hierarchicalfilter NUR als Render-Gerüst):
     - add_to_categoryobject(): REKURSIV (heute 2-stufig) → N Ebenen
     - apply_filter(): emittiert EXISTS/IN über vom Provider gelieferte IDs (statt LIKE)
     - Baum + Expansion via PROVIDER-Callback (kein Entity-Wissen im Plugin)
     - hierarchicalfilter bleibt 100% unangetastet (extern genutzt: local_urise, mod_booking-Customfields) → BC-1
  templates: NEUE rekursive Partials (additiv); bestehende component_filter.mustache / filterview.mustache
             (2-Ebenen, {{#hierarchy}}/{{#values}}) bleiben unveränderter Default-Pfad → BC-1

mod_booking:
  booking::get_options_filter_sql()          → entityid ADDITIV via EXISTS-Subquery (KEIN LEFT JOIN; GROUP BY/Subquery `) s1` unangetastet) → BC-4
  view.php:1323 (+ shortcodes.php:773, :1688)  → standardfilter('location') ⇒ treefilter('entityid', provider), OPT-IN + Text-Fallback → BC-2/BC-3
  col_location (bookingoptions_wbtable:781)    → voller namepath NUR ≥3 Ebenen; ≤2 Ebenen byte-identisch zu heute → BC-6

local_musi:
  musi_table::col_location                     → vollen namepath (reuse mod_booking-Render)
  (Filter wird von mod_booking registriert; musi erbt)
```

## 4. Reuse-Map (vorhanden → wie wiederverwendet; NICHT neu bauen)
| Bedarf | Vorhandene Funktion | Datei:Zeile | Plan |
|---|---|---|---|
| Ahnenkette/Pfad | `entities_table::resolve_lineage()` (protected) | `entities_table.php:144` | nach `entities.php` als `public static get_ancestor_path()` **extrahieren**; Table konsumiert die extrahierte Methode |
| Tiefe + namepath | `entities_table::arrange_as_tree()` | `:100` | für Anzeige/Sort wiederverwenden; baut auf extrahiertem Pfad auf |
| Entity-Map (1 Query) | `entities_table::get_entity_map()` (protected) | `:129` | nach `entities.php` **extrahieren** (Cache-fähig); Basis für alle Live-Ableitungen |
| Voller Baum | `entities::build_whole_entitytree()` | `entities.php:66` | Basis für `get_descendant_ids` / `get_filter_tree` |
| Eine Kind-Ebene | `entities::list_all_subentities()` | `:158` | unverändert (Spezialfall) |
| Option→entityid | `entitiesrelation_handler::get_entityid_by_instanceid()` | `erh.php:486` | im SQL-Join / Provider nutzen |
| Entity-Daten/Parent | `entitiesrelation_handler::get_instance_data()` | `:430` | für `col_location` (erweitern auf vollen Pfad, nicht duplizieren) |
| Filter-WHERE | `base::apply_filter()` (LIKE-OR) | `wbt/.../base.php:470` | `treefilter` überschreibt nur den WHERE-Teil (`IN`) |
| 2-Ebenen-Filter | `filters/types/hierarchicalfilter` | `hierarchicalfilter.php` | `treefilter` **erbt** davon (Render-Gerüst), macht es rekursiv |
| Cache | `cachedentities` + Event `purgecachedentities` | `entities.php:148` | bestehende Invalidierung nutzen; KEIN neues Cache-System |

**Echte Neuteile (minimal):** `get_descendant_ids`, `get_filter_tree`, der `treefilter`-Typ + rekursive Templates, der `entityid`-Join im Booking-SQL. Alles andere = Extraktion/Erweiterung.

## 5. Datenfluss „Knoten X gewählt"
1. UI sendet Filterwert = entityid X (Knoten im Baum).
2. `treefilter::apply_filter` ruft Provider `expand(X)` → `entities::get_descendant_ids(X)` (live aus Map).
3. WHERE: `... AND boe.entityid IN (:ids)` (DB-portabel, MariaDB+PG; Param-Liste via `$DB->get_in_or_equal`).
4. Counts/Panel: `treefilter::add_to_categoryobject` baut rekursiv den Baum aus `get_filter_tree(<entityids im Resultset>)`, Count je Knoten = Optionen im Teilbaum.
- **Umhängen einer Entity wirkt sofort** (Teilbaum live) — keine Stale-Daten.

## 6. Phasen
- **A — VORHER: Characterization-Tests** (Ist einfrieren, je Plugin) — siehe §7.
- **B — Foundation `local_entities`:** `get_entity_map`/`get_ancestor_path` extrahieren (Table darauf umstellen), `get_descendant_ids` + `get_filter_tree` neu; Unit-Tests.
- **C — `local_wunderbyte_table` `treefilter`:** rekursives `add_to_categoryobject` + `IN`-`apply_filter` + Provider-Schnittstelle; rekursive Mustache-Partials; Filter-Render-Tests.
- **D — `mod_booking`:** `entityid` **additiv als `EXISTS`-Subquery** in `get_options_filter_sql` (nur bei aktivem treefilter, Signatur unverändert, BC-4); `treefilter` an **allen** Location-Registrierungsstellen (view.php:1323, shortcodes.php:773 & :1688) mit **Text-Fallback + Opt-in** (BC-2/BC-3); Provider = local_entities (`class_exists`-guarded, keine neue harte Dependency); `col_location` voller Pfad nur ≥3 Ebenen (BC-6). Relation-Area (option vs. optiondate) vorher klären (BC-4a).
- **E — `local_musi`:** `col_location` voller Pfad (reuse mod_booking-Render, keine Duplikat-Logik, BC-6); eigene Location-Registrierung (`shortcodes.php:773`) mitziehen; Katalog-Filterpanel verifizieren.
- **F — Setting-Migration:** neues **Opt-in-Setting (treefilter aktiv, Default OFF)**; `usesubentitynamesforfilter` (binär, genau 1 Lesestelle) → `upgrade.php`-Mapping das heutiges Verhalten erhält; alter Wert **deprecaten, nicht entfernen** (Upgrade-Pfad). (Kein Datenmigrations-Task nötig, da nicht materialisiert — nur Filter-Verhalten.)
- **G — NACHHER: vollständige Testmatrix** — siehe §7.

## 7. Testabdeckung (heavy, VORHER + NACHHER)
**VORHER (Characterization, lockt Ist + dokumentiert die Lücke):**
- `local_entities`: `get_name_for_filter`/`get_instance_data` bei 1/2/3 Ebenen (3-Ebenen liefert nur unmittelbaren Parent — als Ist festgeschrieben); `resolve_lineage`/`namepath` bei 3 Ebenen korrekt (lock); Zyklen-Guard.
- `mod_booking`: Filter-Registrierung = `standardfilter('location')` an **allen 3 Stellen** (view.php:1323, shortcodes.php:773/:1688); `get_options_filter_sql` ohne entityid (lock Baseline-SQL, Signatur + GROUP BY). **Opt-in-AUS = exakt heutiges Verhalten** (lock). **Text-Location ohne Entity** filterbar (lock Soft-Dep-Fall). Fixieren: Optiondate erbt Option-Entity als **eigene Zeile** beim Anlegen (Snapshot); diese Fallback-Logik bleibt unangetastet und beeinflusst den option-only Filter nicht (BC-4a).
- `local_musi`: `col_location` 1/2/3 Ebenen; Distinct-Filterwerte einer 3-Ebenen-Menge (kollabiert — Ist).
- `local_wunderbyte_table`: `hierarchicalfilter` Render = 2 Ebenen (lock).

**NACHHER:**
- `local_entities`: `get_descendant_ids` (0/1/3/5 Ebenen, mehrere Branches, Zyklus, fehlender Parent, Batch==Einzeln); `get_ancestor_path` (Root/2/3/5); `get_filter_tree` (nur belegte Knoten, Counts). Cross-DB (MariaDB+PG).
- `local_wunderbyte_table`: `treefilter::add_to_categoryobject` rekursiv → N Ebenen (Mustache-Kontext-Assert); `apply_filter` erzeugt korrektes `IN(...)`; Provider-Vertrag; Count-Aggregation über Teilbaum.
- `mod_booking`: SQL mit Option-Entity-`EXISTS` (Query-Count/N+1-Assert, Muster `perf_get_reads`); Filterauswahl „Gebäude A" trifft alle Räume darunter (3+ Ebenen); Cross-DB. **Fälle (BC-4a):** (a) Option-Entity im Teilbaum → getroffen; (b) Option-Entity außerhalb, aber Optiondate-Override im Teilbaum → **nicht** getroffen (erwartet: Filter ist option-only); (c) Option ohne eigene Entity, nur Optiondate gesetzt → **nicht** getroffen (erwarteter Randfall); (d) keine Zeilen-Duplikate/Count-Verfälschung durch das `EXISTS`.
- `local_musi` + Behat: 3-Ebenen-Baum im Katalog, Filterpanel zeigt Baum, Auswahl filtert Nachfahren; `col_location`-Breadcrumb.
- Reparent-Live-Test: Option unter Raum; Raum umhängen; **ohne** Neuspeichern der Option zeigt der Filter sofort den neuen Pfad/Teilbaum.

## 8. Risiken / gemeldete Probleme
- **R1 — Booking-SQL-Eingriff:** `entityid`-Filter in `get_options_filter_sql` (`booking.php:1163-1371`, sehr zentral, **40+ Aufrufer** inkl. local_musi/local_urise, GROUP BY Z.1331-1332 + äußere Subquery `) s1`). **Lösung: additive `EXISTS`-Subquery statt LEFT JOIN** (Detail BC-4) — nur bei aktivem treefilter emittiert, IDs in PHP via Provider vorberechnet ⇒ **GROUP BY/Counts/Signatur bleiben unverändert**, keine Zeilen-Duplikation. **Nur Option-Entity** (`area='option'`, ein `EXISTS`, kein Join auf `booking_optiondates`) — exakte SQL + Begründung in BC-4a. Cross-DB (MariaDB+PG) Pflicht. *Nicht empfohlen:* `callback`/PHP-Post-Filter (bricht Pagination/Counts).
- **R2 — Keine Entity-Events:** kein `db/events.php`/`classes/event/` in local_entities, keine Observer auf reparent/rename. Für Live-Ableitung **kein Problem** (wir materialisieren nicht). Aber: `cachedentities` (per instanceid) kann nach reparent stale `parentname` liefern → `col_location` live aus Map lesen oder Cache bei reparent purgen. (Materialisierungs-Variante wäre wegen fehlender Events **nicht** empfehlenswert.) *Fix 2026-07-02:* Entity-Writes (create/update/delete/Webservice) purgen jetzt zusätzlich `changesinwunderbytetable` (wunderbyte_table-rawdata-Cache), und die Admin-Liste `entities.php` setzt `bypasscache` — vorher blieben neue/gelöschte Entities in der gecachten Liste unsichtbar; nachgewiesen durch `settings_manager_test::test_entity_writes_purge_wunderbyte_rawdata_cache`.
- **R3 — `resolve_lineage`/`get_entity_map` sind `protected`:** Extraktion erfordert Refactor von `entities_table` → Regressionsrisiko an der Entities-Admin-Tabelle; durch Characterization-Test (A) abgesichert.
- **R4 — wunderbyte_table ist shared/breit genutzt:** der neue rekursive Render + Templates müssen die bestehenden 2-Ebenen-Nutzungen 100% bewahren (Default-Pfad unverändert). Heavy Tests + Default-Verhalten unangetastet.
- **R5 — Perf bei sehr großen Bäumen:** `IN(<viele ids>)` / Live-Teilbaum. Für typische Standortzahlen (Dutzende) unkritisch. *Escape-Hatch (nur bei Bedarf):* materialisierter `pathids` auf der **Entity** (nicht Option), gepflegt beim Entity-Save/Reparent + Rebuild-Task → `LIKE '/…/X/%'`. Reparent-sicher (Pflege an einer Stelle), aber erst nach Messung.
- **R6 — Mehrere Entities pro Option (Option + N Optiondate-Zeilen) existieren datenseitig** (Optiondate-Zeilen sind beim Anlegen Snapshots der Option-Entity, danach überschreibbar), **betreffen den Location-Filter aber NICHT:** der Filter keyt bewusst nur auf die Option-Entity (BC-4a), die Optiondate-Multiplizität bleibt ihm gegenüber transparent. Equipment ist separat (`get_equipment_relations`) und NICHT Teil des Location-Filters.
- **R9 — Filter/Anzeige-Konsistenz: GELÖST (Entscheidung 2026-07-01).** Filter und `col_location` operieren beide ausschließlich auf der Option-Entity → **keine Divergenz**. Preis: der reine Optiondate-Standort-Sonderfall ist weder im Filter noch in der Anzeige sichtbar (BC-4a, akzeptiert). Kein offener Punkt mehr.
- **R7 — Location ist heute Text, nicht `entityid`; mod_booking hat nur Soft-Dep auf entities.** `standardfilter('location')` filtert `booking_options.location` (varchar) an 3 Stellen (view.php:1323, shortcodes.php:773/:1688) + musi. Optionen ohne Entity haben nur die Text-Location. ⇒ treefilter **Opt-in mit Text-Fallback**, Provider `class_exists`-guarded (keine neue harte Dependency), alle Stellen gemeinsam migrieren (BC-2/BC-3/BC-7). *Achtung:* `local_musi/db/upgrade.php:132-157` materialisiert bereits Entity-Namen in `booking_options.location` — dieser Text ist der Fallback, nicht entfernen (BC-10).
- **R8 — Persistierte Filter-States:** alte `location`-Text-Auswahl ist mit `entityid`-Auswahl nicht wertkompatibel. Durch **Opt-in + neuen Filterschlüssel `entityid`** kollidieren alte/neue States nicht; alte `location`-States bleiben gültig, solange der Opt-in aus ist (BC-2, §10 Schluss).

## 9. Entscheidungen (durch BC-Review 2026-07-01 geschlossen) + Rest-Offenes
**Geschlossen (Begründung in §10):**
- **`treefilter` = eigener NEUER Typ** (nicht `hierarchicalfilter` in-place rekursiv). Grund: hierarchicalfilter extern genutzt + FQCN wird persistiert → BC-1.
- **Location-treefilter ist Opt-in, Default = heutiges `standardfilter`-Verhalten.** Grund: heute Text-Filter, mod_booking soft-dep → BC-2/BC-3.
- **SQL via `EXISTS`-Subquery statt LEFT JOIN.** Grund: GROUP BY/Counts/Signatur unantastbar → BC-4.

**Weiterhin Produkt-offen (keine BC-Wirkung):**
- Filter-UX-Detail: Single-Select-Knoten vs. Multi-Select über Ebenen.
- Setting-Granularität: pro Instanz wählbare Default-Ebene zusätzlich zum Tree-Filter?
- ~~Zu klären vor Phase D: Relation-Area option vs. optiondate~~ → **geklärt (BC-4a, 2026-07-01):** Filter matcht NUR die Option-Entity (ein `EXISTS`), Fallback-Logik unangetastet; konsistent mit `col_location` (R9 gelöst). Reiner Optiondate-Standort = akzeptierter, nicht abgedeckter Randfall.

## 10. Rückwärtskompatibilität — verbindliche Regeln (am Code verifiziert, 2026-07-01)
Verifizierte Stände: `local_wunderbyte_table` 3.2.3 (2026062200), `local_entities` 0.4.9 (2026062604), `mod_booking` 9.4.0 (2026062302), `local_musi` 1.0.5 (2026062500). Die im Plan genannten Datei:Zeile-Referenzen wurden bestätigt (einzige Drift: `view.php` Location-Registrierung liegt bei **:1323**, nicht :1280, plus zwei weitere Stellen in `shortcodes.php`).

**BC-1 — `hierarchicalfilter` bleibt unangetastet.** Extern genutzt von `local_urise` (Felder *kompetenzen*, *organisation*) und `mod_booking/classes/shortcodes.php` (dynamische Customfields, Schleife), beide 2-stufig via `set_sql_for_fieldid`. Der Filtertyp wird als **FQCN-String in `wbfilterclass` persistiert** → Klassenname, Signaturen (`add_to_categoryobject`, `apply_filter`) und die 2-Ebenen-Mustache-Struktur (`{{#hierarchy}}`/`{{#values}}` in `component_filter.mustache` **und** `filterview.mustache`) dürfen sich nicht ändern. ⇒ `treefilter` ist ein **eigener neuer Typ** (erbt hierarchicalfilter nur als Render-Gerüst, überschreibt `add_to_categoryobject` rekursiv + `apply_filter`). Neue rekursive Partials **additiv**; 2-Ebenen-Templates bleiben Default.

**BC-2 — Der Location-Filter operiert heute auf `booking_options.location` (varchar), nicht auf `entityid`.** `standardfilter('location')` ist ein LIKE-Filter auf Text. Registriert an **drei** Stellen (`mod_booking/output/view.php:1323`, `mod_booking/shortcodes.php:773` und `:1688`) plus `local_musi/shortcodes.php:773`. Umstellung auf `entityid` ändert die gefilterte Größe. ⇒ **Opt-in, Default = altes Verhalten.** Neues Setting default OFF; bestehende Installationen filtern unverändert per standardfilter, bis explizit aktiviert. Alle vier Registrierungsstellen gemeinsam migrieren.

**BC-3 — `mod_booking` hat KEINE harte Abhängigkeit auf `local_entities`** (nur `class_exists`-Soft-Dep; version.php listet nur `local_wunderbyte_table`). Optionen können reine Text-Locations ohne Entity haben. ⇒ `treefilter` braucht **Fallback**: kein local_entities / keine Entity-Relation ⇒ Verhalten wie standardfilter (Text). Provider-Registrierung in mod_booking `class_exists`-guarded — keine neue harte Dependency. (`local_musi` hat die harte Dep und darf sie nutzen.)

**BC-4 — `get_options_filter_sql`-Signatur einfrieren.** 13 Parameter, 40+ Aufrufer (mod_booking view/shortcodes/settings/mobile/bulkops/tests, **local_musi**, **local_urise**). Keine Signaturänderung. Entity-Filter **additiv, nur bei aktivem treefilter**, als **ein `EXISTS`-Subquery** (nicht LEFT JOIN), damit GROUP BY (Z.1331-1332) und die Subquery `) s1` unberührt bleiben. **Entscheidung 2026-07-01:** der Filter matcht ausschließlich auf die **Option-Relation** (`area='option'`, `instanceid=bo.id`) — kein Optiondate-Zweig, kein Join auf `booking_optiondates` (Detail + Begründung in BC-4a). IDs in PHP via Provider `expand(X)` = `entities::get_descendant_ids(X)` (`get_in_or_equal`). Cross-DB (MariaDB+PG) Pflicht.

**BC-4a — GEKLÄRT (2026-07-01): Filter matcht NUR auf die Option-Entity; Fallback-Logik bleibt unangetastet.** mod_booking nutzt zwei Relations-Paare: `('mod_booking','option')` (instanceid=optionid) und `('mod_booking','optiondate')` (instanceid=optiondateid) — verifiziert an `option/fields/entities.php`, `option/optiondate.php`, `dates.php`, `booking_option_settings.php`, `booking_option.php`. Bestehende Fachlogik (am Code bestätigt, **wird NICHT angefasst**): Default-Entity eines Optiondate = Option-Entity, per Optiondate überschreibbar; der Default ist **materialisiert** — beim Anlegen eines Optiondate ohne explizite Entity wird die Option-Entity als **eigene `optiondate`-Zeile gespeichert** (`optiondate.php:236-245`, `save_entity_relation`). ⇒ **Der Filter operiert ausschließlich auf der Option-Relation** (konsistent mit `col_location`, das ebenfalls nur die Option-Entity zeigt → keine Divergenz, R9 gelöst):
```sql
AND EXISTS (
  SELECT 1 FROM {local_entities_relations} ler
  WHERE ler.component = 'mod_booking' AND ler.area = 'option'
    AND ler.instanceid = bo.id
    AND ler.entityid {IN (:ids)}
)
```
`{IN (:ids)}` = `get_in_or_equal(entities::get_descendant_ids(X))`. Ein `EXISTS`, kein Join auf `booking_optiondates` → GROUP BY/Counts/Signatur unberührt, minimaler R1-Eingriff.
**Bewusst NICHT abgedeckt (akzeptierter Randfall, Entscheidung des Product Owners):** eine Option ohne eigene Entity, deren Standort nur an einem Optiondate hängt, erscheint nicht im Filter (der Fallback geht Optiondate→Option, nicht umgekehrt). Als erwartetes Verhalten im Characterization-Test festhalten.

**BC-5 — `entitiesrelation_handler::get_instance_data()` Rückgabe-Schema einfrieren.** Festes stdClass-Feldset (`id, relationid, component, area, instanceid, name, shortname, description, timecreated, parentid, maplink, mapembed, parentname`), genutzt von mod_booking (viele Stellen) + `local_shopping_cart`. Für den vollen Pfad **neue Felder additiv ergänzen** (`namepath`, `pathids`); `parentname` & Co. unverändert. `booking_option_settings::load_entity()` befüllt neue Array-Keys additiv.

**BC-6 — `col_location`-Ausgabe für 1–2 Ebenen identisch lassen.** Heute in `bookingoptions_wbtable.php:781` **und** identisch in `local_musi/musi_table.php:573` (nur `is_downloading()`-Zweig unterscheidet sich): `parentname (name)` bzw. `name`. Regel: bei ≤2 Ebenen **byte-identische Ausgabe**; Breadcrumb-Pfad nur bei ≥3 Ebenen. Render **einmal** in mod_booking, musi ruft ihn auf (keine Duplikat-Logik). Download-Export bei ≤2 Ebenen unverändert. **Anzeige ist Option-Level only:** `$settings->entity` wird ausschließlich aus der `('mod_booking','option')`-Relation geladen (`booking_option_settings.php:1208-1225`, `load_entity`); Optiondate-Overrides fließen NICHT in die Anzeige. Die Pfad-Erweiterung betrifft daher nur die Option-Entity; Per-Optiondate-Location-Anzeige ist **out of scope** (separates UX-Thema, siehe R9).

**BC-7 — `list_all_subentities()` ist ein Webservice** (`local_entities_list_all_subentities`, db/services.php). Signatur/Semantik (nur direkte Kinder) unantastbar. Nachfahren-Logik kommt in die **neue** `get_descendant_ids()`, nicht durch Umbau dieser Methode.

**BC-8 — Extraktion `resolve_lineage`/`get_entity_map` (protected).** Nur intern von `arrange_as_tree()` genutzt (getestet in `entities_table_test.php`). Extraktion nach `entities.php` als public static; `entities_table` **delegiert** an die extrahierte Methode → bestehende Tests bleiben grün. `arrange_as_tree()` bleibt public. Characterization-Test (Phase A) vorher.

**BC-9 — `usesubentitynamesforfilter` hat genau eine Lesestelle** (`entitiesrelation_handler.php:895` in `get_name_for_filter()`). Migration: alter Binärwert → neues Setting, Default-Mapping erhält heutiges Verhalten; alten Key **deprecaten, nicht hart entfernen**. Kein Datenmigrations-Task (nicht materialisiert).

**BC-10 — musi materialisiert die Location bereits.** `local_musi/db/upgrade.php:132-157` schreibt Entity-Parent/Child-Namen in `booking_options.location`. Diese Text-Location ist der **Fallback** für BC-2/BC-3 — nicht entfernen. treefilter liest `entityid` live; der Text bleibt für Nicht-Entity-Optionen und den Default-Pfad.

**Verbleibendes Einzelrisiko:** gespeicherte Filter-States der alten `location`-Textauswahl sind mit `entityid`-Auswahl nicht wertkompatibel. Da treefilter Opt-in (BC-2) und ein **neuer Filterschlüssel** (`entityid`) ist, kollidieren alte/neue States nicht — alte `location`-States bleiben gültig, solange der Opt-in aus ist.

## 11. Umsetzungsstand (2026-07-01)
Alle Phasen umgesetzt und mit PHPUnit (PostgreSQL) grün. Opt-in `booking/entitytreefilter` **Default OFF** — bestehende Installationen unverändert.

**Neu/geändert:**
- **B — local_entities:** `entities::get_entity_map()`/`get_ancestor_path()` (extrahiert, request-cached, keine Cross-Request-Cache wegen fehlender Reparent-Events), `get_descendant_ids()`, `get_filter_tree()` neu; `entities_table` delegiert an die extrahierten Methoden (protected-Signaturen erhalten). Tests: `entities_test.php` (7).
- **C — local_wunderbyte_table:** neuer Typ `filters/types/treefilter` (erbt hierarchicalfilter nur als Gerüst, rekursiv), Interface `tree_provider` (Provider liefert Präsenz-Counts, Baum, WHERE-Prädikat), additives Template `filter_treenode.mustache` + `treehierarchy`-Block in `component_filter.mustache`, Lang `treefilter`. hierarchicalfilter **unangetastet** (18 Tests grün). Tests: `treefilter_test.php` (6).
- **D — mod_booking:** `mod_booking\local\entities_tree_provider` (option-only `EXISTS` auf `s1.id`, Präsenz-Count-Query spiegelt `get_db_filter_column`, live Subtree-Expansion, `render_location_name`); Registrierung an view.php + shortcodes.php (Opt-in via `entities_tree_provider::get_location_filter()`, sonst standardfilter); `col_location` voller Pfad ab 3 Ebenen. Setting `entitytreefilter` (Default 0). Tests: `entities_tree_provider_test.php` (5: present-counts, subtree-match **inkl. BC-4a-Negativfall**, reparent-live, BC-6-Rendering, Opt-in).
- **E — local_musi:** `col_location` nutzt den geteilten Renderer; Location-Registrierung über dieselbe Opt-in-Helper.
- **F — Settings/Version:** Opt-in Default OFF; `usesubentitynamesforfilter` **bewusst nicht migriert** (steuert weiter den Flat-Fallback, BC-9); Version-Bumps wunderbyte_table (3.2.4) + mod_booking (9.4.1) für Template/Lang/Setting-Purge.

**Bewusst offen / Follow-up (kein Blocker, außerhalb dieser Umsetzung):**
- **Cross-DB:** nur auf PostgreSQL getestet; MariaDB-Lauf noch ausstehend (SQL ist portabel: `EXISTS`/`JOIN`/`IN` mit named params, `COUNT(DISTINCT)`).
- **Behat/UX:** Katalog-Filterpanel-Behat + Collapse-UX für sehr tiefe Bäume (Server-Filter + Rendering sind unit/integration-getestet).
- **Optiondate-Union:** bewusst nicht umgesetzt (Filter = option-only, BC-4a); nachrüstbar über den Provider (siehe frühere Aufwandsschätzung).
</content>
