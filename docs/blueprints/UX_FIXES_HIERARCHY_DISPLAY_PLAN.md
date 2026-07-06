# UX-Fixes Hierarchie-Anzeige — Plan (col_location, edit.php-Parent-Select, treefilter-UI)

Status: **UMGESETZT 2026-07-02** · Ist-Stand am Code verifiziert (3 parallele Explorationen + manuelle Nachprüfung) · Basis: [HIERARCHY_MULTILEVEL_FILTER_PLAN.md](HIERARCHY_MULTILEVEL_FILTER_PLAN.md) (dessen BC-Regeln §10 gelten unverändert weiter).

**Umsetzungs-Commits (Branch entities-tree-filter / entity-tree-filter):** Fix 3 → wunderbyte_table `43dc50e`; Fix 1 → booking `0db0ddf43` (get_config-Bugfix) + `c35c5058c` (Hover-Card/Setting/Template), local_entities `f018829` (get_image_url), musi `a73d403`, urise `21c076f` (urise brauchte dieselben Änderungen wie musi: Filter-Registrierung + col_location, jeweils guarded); Fix 2 → local_entities `a9aca3a`. Alle PHPUnit-Tests der berührten Bereiche grün.

Betrifft: `mod_booking`, `local_musi`, `local_wunderbyte_table`, `local_entities`.

---

## 0. P0 — ✅ ERLEDIGT (verifiziert 2026-07-02)

**mod_booking ist wieder am richtigen Stand:** Commit `80cb5b5e8` „New Feature: Treefilter for entities #2011" liefert `classes/local/entities_tree_provider.php` vollständig (`is_active`, `get_location_filter`, `get_present_counts`, `build_tree`, `filter_sql`, `render_location_name` + Param-Helper). Verifiziert: Filter-Registrierung läuft über den Provider (view.php:1327, shortcodes.php:1689), **beide** col_location (booking :798, musi :581) nutzen den shared Renderer, `render_location_name` liefert ≤2 Ebenen byte-identisch und ≥3 Ebenen aktuell einen **Inline-Breadcrumb** `A / B / C` (→ Baseline für Fix 1). Der musi-Fatal ist damit behoben.

*Offen bleibt (niedrige Prio, im Zuge von Fix 1 miterledigen):* defensiver `class_exists`-Guard in musi (musi_table:581, shortcodes:775) gegen Versions-Skew + musi-`$plugin->dependencies` auf die booking-Version mit Provider anheben.

**Ursprünglicher Befund (historisch, zur Nachvollziehbarkeit):** Die Treefilter-Implementierung war über die Repos verteilt committed — der mod_booking-Teil fehlte:

| Repo | Commit | Stand |
|---|---|---|
| local_wunderbyte_table | f363189 „New multilevel treefilter #2011" | ✅ `treefilter.php`, `tree_provider`-Interface, rekursives Partial, 6 Tests |
| local_musi | 0e4f3c2 „Treefilter for entities #2011" | ✅ committed — referenziert aber `\mod_booking\local\entities_tree_provider` |
| local_entities | bab57ad, 30374b9, a631f28 | ✅ Helper-Suite (`get_entity_map`, `get_ancestor_path`, `get_descendant_ids`, `get_tree_sortkey`, `get_filter_tree`) |
| **mod_booking** | — | ❌ **`classes/local/entities_tree_provider.php` existiert nirgends** (find über gesamtes /var/www/moodle leer; kein Treefilter-Commit in booking) |

**Konsequenz:** `local_musi/classes/table/musi_table.php:581` (`render_location_name`) und `local_musi/classes/shortcodes.php:775` (`get_location_filter`) rufen die fehlende Klasse **ohne `class_exists`-Guard** auf → **Fatal Error** im musi-Katalog, sobald eine Option mit Entity gerendert wird bzw. das Filterpanel aufgebaut wird.

**P0-Arbeitspaket (vor allen drei Fixes):**
1. `mod_booking\local\entities_tree_provider` (neu) implementiert `local_wunderbyte_table\filters\types\tree_provider`:
   - `get_present_counts($table, $col)`: Entity-Zählung je Knoten über `{local_entities_relations}` (beide Areas gem. **BC-4a**: `area='option'` direkt + `area='optiondate'` via Join `{booking_optiondates}.optionid`), eingeschränkt auf das aktuelle Tabellen-SQL.
   - `build_tree($counts)`: delegiert an `entities::get_filter_tree($entityids)` (existiert, local_entities bab57ad).
   - `filter_sql($table, $col, $ids)`: Zwei-Zweig-`EXISTS` gem. Hauptplan **BC-4a** (Union option+optiondate), Expansion via `entities::get_descendant_ids()`, Params über `$table->set_params()`, `'1=1'` bei leerer Auswahl.
   - `get_location_filter(string $label)`: Factory — liefert konfigurierten `treefilter('entityid', …)` mit gesetztem Provider (das ruft musi shortcodes:775 auf).
   - `render_location_name(array $entity)`: shared Renderer für col_location (Signatur so, wie musi:581 ihn bereits aufruft; Verhalten: siehe Fix 1 — bis Fix 1 umgesetzt ist, liefert er exakt das heutige `parentname (name)`-Verhalten für alle Tiefen ≥2 → kein Verhaltenssprung durch P0 allein).
   - Alle local_entities-Zugriffe `class_exists`-guarded (**BC-3**, keine harte Dependency).
2. `local_musi` defensiv nachziehen: `class_exists('\mod_booking\local\entities_tree_provider')`-Guard an beiden Stellen mit Fallback auf heutiges Verhalten (standardfilter bzw. `parentname (name)`) — schützt gegen Versions-Skew zwischen den Plugins (musi neu + booking alt).
3. Tests: PHPUnit Provider (Counts/Union/Expansion, Fälle a–d aus Hauptplan §7 NACHHER), musi-Fallback-Test.

---

## 1. Fix 1 — col_location: Ebene ≥3 mit Hover-Ahnenkette (+ optional Bilder)

### Ziel (Nutzeranforderung)
- **Ebene 1**: unverändert `name`. **Ebene 2**: unverändert `parentname (name)` — **byte-identisch** zu heute (BC-6, inkl. Download).
- **Ebene ≥3**: sichtbar nur die **gewählte Entity**; die übergeordneten Ebenen erscheinen per **Hover** (und Fokus!); **screenreader-optimiert**; wenn möglich hinterlegte **Entity-Bilder klein** in der Hover-Anzeige.

### Ist (verifiziert, nach P0-Wiederherstellung aktualisiert 2026-07-02)
- **Beide** col_location delegieren an `entities_tree_provider::render_location_name()` (booking `bookingoptions_wbtable.php:798`, musi `musi_table.php:581`); der Rückgabewert wird vom Aufrufer in ein `<a>` gewrappt.
- `render_location_name()` (entities_tree_provider ~Z.198-215): ≤2 Ebenen byte-identisch (`parentname (name)` / `name`); **≥3 Ebenen: Inline-Breadcrumb `A / B / C`** — genau das ersetzt Fix 1 durch „nur gewählte Entity + Hover-Card".
- booking hat `is_downloading()`-Plaintext-Zweig **vor** dem Renderer-Aufruf; musi **keinen**.
- `$settings->entity` (booking_option_settings:1208-1225) enthält nur `id, name, shortname, parentname, description, maplink, mapembed` — **kein** namepath/pathids. `get_instance_data()`-Schema unangetastet (BC-5 ✅).
- Entity-Bilder: Filearea `local_entities/image`, itemid=entityid, URL via `moodle_url::make_file_url()` (entity_view.php:186-207); Setting `fallback_image_parent` (1 Ebene Fallback, entity_view.php:257-280).
- BS4/BS5-Tooltip-Muster in booking: **Dual-Attribute** `data-toggle="tooltip"` + `data-bs-toggle="tooltip"` (67 Stellen, z.B. button_notifyme.mustache:47-56).
- Zellen-Template-Muster existiert: `col_teacher` (Datenklasse `output/col_teacher.php` + Renderer + `is_downloading()`-Plaintext-Zweig, bookingoptions_wbtable.php:188-219).

### Design
**Datenfluss (live, reparent-sicher):** Der Renderer berechnet den Pfad **live** aus `entities::get_ancestor_path($entity['id'])` (Request-statische Map; R2 des Hauptplans: kein Stale-`parentname`) — `class_exists`-guarded; ohne local_entities → heutiges Verhalten. **Kein** Schema-Change an `get_instance_data`/`load_entity` nötig.

**Rendering nach col_teacher-Muster:**
- Neue Datenklasse `mod_booking\output\col_location` + Template `mod_booking/templates/col_location.mustache`; `render_location_name()` bleibt als dünner Einstiegspunkt erhalten, bekommt aber die volle Verantwortung: **Link, Download-Zweig und Hover-Markup wandern hinein** (neue Signatur-Variante bzw. Zusatzmethode `render_location_cell($entity, bool $isdownloading)`), die beiden col_location reduzieren sich auf reine Delegation — damit erbt musi den heute fehlenden Download-Zweig, und das `<a>`-Wrapping der Aufrufer entfällt (nötig, weil Link + Card zusammen ins Template gehören). musi ruft weiterhin denselben Renderer — keine Duplikatlogik (BC-6).
- **Tiefe ≤2:** Template gibt exakt den heutigen String aus (Snapshot-Test byte-identisch).
- **Tiefe ≥3:** sichtbar `<a href=view.php aria-label="{name}, in {A / B}">{name}</a>` + Hover/Fokus-Card.

**Hover-Mechanik — Empfehlung: reine CSS-Hover-Card** (statt Bootstrap-Tooltip):
- Begründung: Bootstrap-Tooltips brauchen JS-Init und können kein HTML/Bilder ohne `html:true`-Risiko; eine CSS-Card (`.mod-booking-location-path`, sichtbar bei `:hover` **und** `:focus-within`) ist BS4/BS5-identisch, JS-frei und kann Markup (Breadcrumb-Liste + Mini-Bilder) enthalten.
- Zusätzlich natives `title`-Attribut mit Plaintext-Pfad + Dual-`data-toggle`-Attribute (bestehendes Muster) als Degradations-Stufe.
- **A11y:** Ahnenkette zusätzlich als visuell versteckter Text (`sr-only`-Span „in Hauptgebäude / Seminarraum 1") im Link — Screenreader lesen den Kontext ohne Hover; `aria-label` dupliziert; Card selbst `aria-hidden="true"` (Info ist redundant zum sr-Text, verhindert Doppeltvorlesen). Karten-Einblendung ohne Zeitverzögerung, per Tastatur erreichbar (`:focus-within` am Zellcontainer, Link ist fokussierbar).
- Klassen im Template BS-neutral halten (eigene Klassen + wenige geteilte wie `text-muted`); Abstände über eigene CSS-Regeln in mod_booking `styles.css`, nicht über `gap-*`/`fs-*` (bs5-bridge-Lücken).

**Bilder (optional, per Setting abschaltbar):**
- Neuer Helper `entities::get_image_url(int $entityid): ?moodle_url` — kapselt `get_area_files('local_entities','image',$id)` + verallgemeinerten `fallback_image_parent` (über `get_ancestor_path` statt nur 1 Ebene), **request-statisch gecacht** (Filestorage-Lookups sonst N×Ahnen pro Zeile).
- In der Hover-Card: pro Ahnen-Knoten Thumbnail ~24px, `loading="lazy"`, `alt=""` (dekorativ — Name steht daneben; Screenreader-Doppelung vermeiden).
- Neues mod_booking-Setting `showlocationimages` (**E1 entschieden**: Card mit Bildern, Setting default **an**, abschaltbar); ohne Bilder bleibt die Card reine Textliste.

**Download/Export:** Tiefe ≤2 wie heute; Tiefe ≥3 → Plaintext-Vollpfad `A / B / C` (kein HTML). musi bekommt den fehlenden `is_downloading()`-Zweig über den geteilten Renderer gratis.

### BC-Leitplanken
- ≤2 Ebenen byte-identisch inkl. Download (Snapshot-Tests VORHER-Phase).
- `$settings->entity`-Struktur unverändert; `get_instance_data` unverändert (BC-5).
- Ohne local_entities (`class_exists`) → exakt heutiges Verhalten (BC-3).

### Tests
- PHPUnit: Renderer mit 1/2/3/5 Ebenen (≤2 byte-identisch zu Alt-Implementierung; ≥3: Name sichtbar, Pfad im sr-Text/aria-label/title); Download-Zweig; `get_image_url` (eigenes Bild / Parent-Fallback über 2 Ebenen / keins).
- Behat: Hover-Card erscheint bei Hover und Tastaturfokus; aria-label vorhanden; musi-Katalog rendert 3-Ebenen-Entity ohne Fehler.

---

## 2. Fix 2 — edit.php: Parent-Select hierarchisch korrekt (+ Zyklen-Schutz)

### Ist (verifiziert)
- `local_entities/classes/form/edit_dynamic_form.php:81-88, 241-245`: Autocomplete `parentid`, Optionen aus `entities::list_all_entities()` — SQL mit `CASE WHEN parentid='0' THEN name ELSE concat('-', name) END`: **ein** Dash für *jede* Nicht-Root-Ebene, Sortierung 2-Ebenen-artig → Ebene ≥3 erscheint falsch einsortiert und nicht tiefer eingerückt (identisches Symptom wie beim Options-Formular-Select, dort bereits gefixt).
- **Kein Zyklen-Schutz:** `validation()` (Z.529-532) leer; nur `id != $entityid` wird ausgeschlossen — die **eigenen Nachfahren sind wählbar** → Reparent auf Enkel erzeugt Zyklus (die Helper haben zwar Guards gegen Endlosschleifen, aber der Teilbaum hängt dann unsichtbar in der Luft).
- `settings_manager::update_or_createentity()` validiert parentid nicht (Z.135-137).

### Design
1. **Options-Aufbau im Form ersetzen** (Methode `list_all_entities()` selbst **unangetastet** lassen — public API): Optionen aus `entities::get_entity_map()`, Reihenfolge via `get_tree_sortkey()`, Label mit Einrückungspräfix je Tiefe (`str_repeat('— ', $depth) . $name`). Präfix-Zeichen statt CSS-Einrückung, damit Screenreader und das Autocomplete-Suchfeld die Struktur mitbekommen; zusätzlich voller Pfad als `title`/Suchtext (**Entscheidung E3**).
2. **Ausschlussmenge:** beim Bearbeiten von Entity X werden `{X} ∪ get_descendant_ids(X)` aus den Optionen entfernt (Zyklus im UI unmöglich).
3. **Server-Validierung** (Defense in depth, Formular kann via Webservice/dynamic_form umgangen werden):
   - `edit_dynamic_form::validation()`: Fehler, wenn `parentid ∈ {X} ∪ descendants(X)` oder parentid nicht existiert.
   - `settings_manager::update_or_createentity()`: gleiche Prüfung, bei Verstoß Exception (kein Silent-Fix) — schützt auch CSV-Import/externe Aufrufer.
4. Equipment-Entities: heutige Menge beibehalten (alles anzeigen wie bisher — keine stille Verhaltensänderung; nur Reihenfolge/Einrückung/Ausschluss ändern sich).

### BC
- Elementname `parentid`, Elementtyp autocomplete, `0 = kein Parent` unverändert (Formular-State, Behat, dynamic_form-Consumer).
- `list_all_entities()` bleibt wie sie ist (falls externe Nutzung existiert).
- Bestehende Bäume mit Alt-Zyklen (falls durch die Lücke bereits entstanden): `clean_up_entities_db()`-Erweiterung prüfen? → nur **Diagnose** einbauen (Debug-Log), kein Auto-Repair (Datenverlustrisiko).

### Tests
- PHPUnit: Options-Builder (tiefensortiert, Präfixtiefe korrekt, self+descendants fehlen, Root „0" vorhanden); `validation()` mit Zyklus-Versuch; `update_or_createentity()` wirft bei descendant-parentid.
- Behat: 3-Ebenen-Baum, Editieren von Ebene-1-Entity zeigt Enkel nicht als Parent-Option; Select zeigt Einrückung.

---

## 3. Fix 3 — treefilter: Ebenen sichtbar versetzt + Eltern/Kind-Kaskade

### Ist (verifiziert)
- Rekursives Partial existiert: `local_wunderbyte_table/templates/filter_treenode.mustache` (Z.47-59), Verschachtelung über `<ul class="wbt-treechildren ps-3">`.
- **Warum es flach aussieht:** `ps-3` ist eine **BS5-Klasse** — auf Moodle 4.x/BS4 (euer System) existiert sie nicht, die bs5-bridge deckt `p s-*`-Padding nicht ab → **keine Einrückung auf BS4**. Genau die Dual-Kompat-Falle aus der Projekt-Erfahrung (fw-*/gap-*/fs-*-Klasse der bridge-Lücken).
- **Keine Kaskade:** JS behandelt nur hierarchicalfilter (`handleHierarchyCategoryCheckbox`, filter.js:835 — Parent hakt Kinder, 2 Ebenen); treefilter-Checkboxen (`wbt-treenode-checkbox`) sind generische `filterelement`s ohne Eltern/Kind-Logik.
- Submit-Format: `getChecked()` (filter.js:632) sammelt **alle** gehakten ids pro `name`; `filter_sql()` expandiert jede id zum Subtree — Mehrfachauswahl (Parent + Kinder gleichzeitig) ist **idempotent** (Union), Kaskade verfälscht das Ergebnis also nicht.

### Design
**a) Einrückung BS4+BS5-fest:**
- Eigene CSS-Regel in wunderbyte_table `styles.css`: `.wbt-treechildren { padding-inline-start: 1rem; list-style: none; }` — unabhängig von der Bootstrap-Version (`padding-inline-start` = RTL-sicher). `ps-3` im Template entfernen oder als `pl-3 ps-3` dual belassen; **Empfehlung: eigene CSS-Klasse**, kein Bootstrap-Utility (eine Quelle, keine bridge-Abhängigkeit).
- Optional (nicht gefordert, **nur notieren**): Collapse-Chevron je Knoten wie beim hierarchicalfilter — separates Ticket, nicht Teil dieses Fixes.

**b) Kaskade (nur `.wbt-treenode-checkbox` — hierarchicalfilter-JS unangetastet, BC-1). Entscheidung E2: Logik wie beim hierarchicalfilter — binär, kein Tristate/indeterminate:**
- Neuer Handler in `amd/src/filter.js`, bewusst als rekursive Verallgemeinerung von `handleHierarchyCategoryCheckbox` (filter.js:835 — dort: Parent-Klick kopiert `checked` auf alle `.form-check-input` im umschließenden `<ul>`):
  1. **Abwärts (= hierarchicalfilter-Verhalten, rekursiv):** Klick auf Knoten K kopiert `checked` auf **alle** Nachfahren-Checkboxen von K (`closest('li.wbt-treenode')` → alle `input.wbt-treenode-checkbox` darunter).
  2. **Aufwärts (binäre Ergänzung, „und umgekehrt" aus der Anforderung):** nach jeder Änderung die Eltern-Kette neu berechnen: **alle** Kinder gehakt ⇒ Parent `checked=true`; mindestens eins nicht ⇒ Parent `checked=false`. Kein `indeterminate` — exakt zwei Zustände wie beim hierarchicalfilter.
  3. **Ein** `getChecked()`+`triggerReload()` pro Nutzer-Interaktion (nach der Kaskade), wie beim hierarchicalfilter-Muster — kein Reload-Sturm.
  4. **Re-Init nach Re-Render:** das Filterpanel wird nach jedem Reload aus JSON neu gerendert (`renderFilter`, filter.js:742); der Aufwärts-Zustand der Eltern ergibt sich dann bereits aus den persistierten checked-Werten (Server liefert `{{checked}}` pro Knoten) — ein bottom-up-Konsistenzlauf in `initializeCheckboxes` gleicht Restfälle an (z.B. Parent war gehakt persistiert, ein Kind nicht).
- **Submit-Semantik:** übertragen werden alle physisch gehakten Knoten-ids; Server-Expansion (Subtree-Union) macht „Parent gehakt" und „alle Kinder gehakt" ergebnisgleich. Folge des binären Aufwärts-Verhaltens: hakt man ein Kind ab, wird der Parent mit abgehakt — gefiltert wird dann die Union der verbleibenden gehakten Teilbäume (erwartetes Verhalten).
- **A11y:** binäre native Checkbox-Zustände, keine Zusatz-ARIA nötig; Labels existieren; verschachtelte `<ul>` geben Baumkontext. Keyboard: Checkboxen nativ fokussierbar; `keyup`-Pfad existiert bereits (filter.js:117).

### BC
- hierarchicalfilter: Templates, Klassen (`hierarchycategory-checkbox`/`hierarchychild-checkbox`) und dessen JS **unverändert** (BC-1; extern von urise/booking-Customfields genutzt).
- Neues JS greift ausschließlich auf `wbt-treenode-*`-Klassen; Submit-Format (`{spalte: [ids]}`) unverändert; `filter_sql`-Vertrag unverändert.
- wunderbyte_table ist shared: CSS-Regel nur auf `.wbt-treechildren` gescoped.
- **Grunt-Build erforderlich** (amd/src → amd/build) — im gleichen Commit.

### Tests
- PHPUnit (bestehende 6 treefilter-Tests bleiben grün; Template-Kontext unverändert).
- Behat (wunderbyte_table, 3-Ebenen-Fixture): Mutter anhaken ⇒ alle Kind-Checkboxen gehakt, Tabelle zeigt Subtree; einzelnes Kind abhaken ⇒ Mutter wird abgehakt, Tabelle zeigt Union der verbleibenden Teilbäume; alle Kinder anhaken ⇒ Mutter gehakt; Einrückung vorhanden (CSS-Klasse im DOM).
- Manuell auf BS4 (Moodle 4.5) **und** BS5 (Moodle 5.x): Einrückung sichtbar.

---

## 4. Reihenfolge & Abhängigkeiten

1. ~~**P0**~~ — ✅ erledigt (booking 80cb5b5e8); Rest-Punkt „musi-Guards + Dependency-Bump" wandert in Fix 1.
2. **Fix 3** — treefilter-CSS + Kaskaden-JS (rein in wunderbyte_table, unabhängig von 1/2; inkl. Grunt).
3. **Fix 1** — col_location-Renderer/Template/Hover/Bilder (ersetzt den Inline-Breadcrumb ≥3; inkl. musi-Guards + Dependency-Bump).
4. **Fix 2** — edit.php-Select + Zyklen-Validierung (rein local_entities, jederzeit einschiebbar).

Jede Stufe mit eigener VORHER-Verifikation (Characterization) und NACHHER-Tests; Versionsbumps je Repo.

## 5. Entscheidungen — ✅ geschlossen (PO-Feedback 2026-07-02)
- **E1 (Fix 1): ENTSCHIEDEN — Hover-Card mit Mini-Bildern, per Setting abschaltbar** (`showlocationimages` in mod_booking; Bilder request-gecacht + lazy).
- **E2 (Fix 3): ENTSCHIEDEN — Logik wie beim hierarchicalfilter**: binär, kein indeterminate; abwärts Zustand kopieren (rekursive Verallgemeinerung von `handleHierarchyCategoryCheckbox`), aufwärts binär (alle Kinder gehakt ⇒ Parent gehakt, sonst nicht). Details in §3b.
- **E3 (Fix 2): ENTSCHIEDEN — Einrückungspräfix** (`— `×Tiefe vor dem Namen), voller Pfad zusätzlich als title/Suchtext.
