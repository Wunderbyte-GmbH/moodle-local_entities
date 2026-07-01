# Umbauplan: Mehrstufige Entity-Hierarchien + hierarchischer Filter

Status: **festgeschrieben, noch nicht umgesetzt** · Datum 2026-06-29 · Betrifft `local_entities`, `local_wunderbyte_table`, `mod_booking`, `local_musi`.

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
  filters/types/treefilter (NEU, erbt hierarchicalfilter):
     - add_to_categoryobject(): REKURSIV (heute 2-stufig) → N Ebenen
     - apply_filter(): emittiert  "<col> IN (:ids)"  statt LIKE
     - Baum + Expansion via PROVIDER-Callback (kein Entity-Wissen im Plugin)
  templates: component_filter.mustache / filterview.mustache → rekursives Partial

mod_booking:
  booking::get_options_filter_sql()          → entityid als Spalte joinen (local_entities_relations)
  output/view.php:1280                        → standardfilter('location') ⇒ treefilter('entityid', provider)
  col_location (bookingoptions_wbtable)        → vollen namepath statt parent(child)

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
- **D — `mod_booking`:** `entityid` in `get_options_filter_sql` joinen; `view.php` Filter auf `treefilter` umstellen (Provider = local_entities); `col_location` voller Pfad.
- **E — `local_musi`:** `col_location` voller Pfad (reuse); Katalog-Filterpanel verifizieren.
- **F — Setting-Migration:** `usesubentitynamesforfilter` (binär) → `entityfilterlevel`/`treefilter`-Default; `upgrade.php`-Mapping; alter Wert deprecaten. (Kein Datenmigrations-Task nötig, da nicht materialisiert — nur Filter-Verhalten.)
- **G — NACHHER: vollständige Testmatrix** — siehe §7.

## 7. Testabdeckung (heavy, VORHER + NACHHER)
**VORHER (Characterization, lockt Ist + dokumentiert die Lücke):**
- `local_entities`: `get_name_for_filter`/`get_instance_data` bei 1/2/3 Ebenen (3-Ebenen liefert nur unmittelbaren Parent — als Ist festgeschrieben); `resolve_lineage`/`namepath` bei 3 Ebenen korrekt (lock); Zyklen-Guard.
- `mod_booking`: Filter-Registrierung = `standardfilter('location')`; `get_options_filter_sql` ohne entityid (lock Baseline-SQL).
- `local_musi`: `col_location` 1/2/3 Ebenen; Distinct-Filterwerte einer 3-Ebenen-Menge (kollabiert — Ist).
- `local_wunderbyte_table`: `hierarchicalfilter` Render = 2 Ebenen (lock).

**NACHHER:**
- `local_entities`: `get_descendant_ids` (0/1/3/5 Ebenen, mehrere Branches, Zyklus, fehlender Parent, Batch==Einzeln); `get_ancestor_path` (Root/2/3/5); `get_filter_tree` (nur belegte Knoten, Counts). Cross-DB (MariaDB+PG).
- `local_wunderbyte_table`: `treefilter::add_to_categoryobject` rekursiv → N Ebenen (Mustache-Kontext-Assert); `apply_filter` erzeugt korrektes `IN(...)`; Provider-Vertrag; Count-Aggregation über Teilbaum.
- `mod_booking`: SQL mit entityid-Join (Query-Count/N+1-Assert, Muster `perf_get_reads`); Filterauswahl „Gebäude A" trifft alle Räume darunter (3+ Ebenen); Cross-DB.
- `local_musi` + Behat: 3-Ebenen-Baum im Katalog, Filterpanel zeigt Baum, Auswahl filtert Nachfahren; `col_location`-Breadcrumb.
- Reparent-Live-Test: Option unter Raum; Raum umhängen; **ohne** Neuspeichern der Option zeigt der Filter sofort den neuen Pfad/Teilbaum.

## 8. Risiken / gemeldete Probleme
- **R1 — Booking-SQL-Eingriff:** `entityid` in `get_options_filter_sql` (`booking.php:~1164-1371`, sehr zentral/komplex, GROUP BY) zu joinen ist die heikelste Stelle. LEFT JOIN + ggf. Aggregation; Cross-DB-Tests Pflicht. *Fallback ohne SQL-Change:* `callback`-Filter (PHP-Post-Filter, `filter_by_callback`) — aber bricht saubere Pagination/Counts → nicht empfohlen.
- **R2 — Keine Entity-Events:** kein `db/events.php`/`classes/event/` in local_entities, keine Observer auf reparent/rename. Für Live-Ableitung **kein Problem** (wir materialisieren nicht). Aber: `cachedentities` (per instanceid) kann nach reparent stale `parentname` liefern → `col_location` live aus Map lesen oder Cache bei reparent purgen. (Materialisierungs-Variante wäre wegen fehlender Events **nicht** empfehlenswert.)
- **R3 — `resolve_lineage`/`get_entity_map` sind `protected`:** Extraktion erfordert Refactor von `entities_table` → Regressionsrisiko an der Entities-Admin-Tabelle; durch Characterization-Test (A) abgesichert.
- **R4 — wunderbyte_table ist shared/breit genutzt:** der neue rekursive Render + Templates müssen die bestehenden 2-Ebenen-Nutzungen 100% bewahren (Default-Pfad unverändert). Heavy Tests + Default-Verhalten unangetastet.
- **R5 — Perf bei sehr großen Bäumen:** `IN(<viele ids>)` / Live-Teilbaum. Für typische Standortzahlen (Dutzende) unkritisch. *Escape-Hatch (nur bei Bedarf):* materialisierter `pathids` auf der **Entity** (nicht Option), gepflegt beim Entity-Save/Reparent + Rebuild-Task → `LIKE '/…/X/%'`. Reparent-sicher (Pflege an einer Stelle), aber erst nach Messung.
- **R6 — Mehrere Entities pro Option:** heute praktisch 1 (Location); Relations-Tabelle erlaubt mehrere. `IN`-Ansatz deckt mehrere Relationen natürlich ab (`EXISTS`/Join). Equipment ist separat (`get_equipment_relations`) und NICHT Teil des Location-Filters.

## 9. Offene Produkt-/Technik-Entscheidungen
- Filter-UX-Detail: Single-Select-Knoten vs. Multi-Select über Ebenen.
- Setting-Granularität: pro Instanz wählbare Default-Ebene zusätzlich zum Tree-Filter?
- `treefilter` als eigener Typ vs. `hierarchicalfilter` rekursiv erweitern (Default-BC zwingend).
</content>
