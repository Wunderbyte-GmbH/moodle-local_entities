# Blueprint: Vorinstallierte Entity-Vorlagen (Ort & Equipment)

Status: **Vorschlag / Konzept** — noch keine Implementierung.
Autor: Georg + Claude · Datum: 2026-06-25 · Plugin: `local_entities`

---

## 1. Ziel

Beim Installieren (und beim Upgrade bestehender Sites) bringt `local_entities` **zwei
fertige, sofort brauchbare Custom-Field-Vorlagen** mit:

- **Ort / Location** — beschreibende Felder für Räume/Standorte
- **Equipment** — beschreibende Felder für Geräte/Inventar

Damit bekommt ein Admin, der eine neue Entity anlegt, **sofort ein sinnvoll
ausgefülltes Formular** statt einer leeren Custom-Field-Leinwand. Die volle
Flexibilität bleibt: die Vorlagen sind ganz normale Custom-Field-Kategorien, die
nachträglich editier-, erweiter- und löschbar sind.

**Kernidee zusätzlich zum reinen Seeding:** die Vorlage wird automatisch an die
bereits existierende echte Spalte `entitytype` (`location` | `equipment`) gekoppelt.
Wer im Edit-Formular „Equipment" wählt, bekommt automatisch die Equipment-Felder —
ohne das heute verwirrende, separate „Categories"-Dropdown verstehen zu müssen.

---

## 2. Ausgangslage (Ist-Mechanik, kompakt)

- Custom-Field-Kategorien liegen in der Core-Tabelle `customfield_category` mit
  `component='local_entities'`, `area='entities'` und einer **`itemid`**, die als
  Kategorie-Identifier dient (`entities_cf_helper::CFAREA/CFCOMPONENT`).
- Eine Entity speichert in der echten Spalte **`local_entities.cfitemid`**, welche
  *eine* „alternative" Kategorie für sie gilt.
- **Zwei Sorten Kategorien:**
  - *Standard* — comma-separierte itemids im Config `local_entities/categories`,
    gelten für **alle** Entities (`entities_cf_helper::create_std_handlers()`).
  - *Alternativ* — alle übrigen; **eine pro Entity** via `cfitemid`-Dropdown im
    Edit-Formular (`edit_dynamic_form.php:254-264`, AJAX-Reload).
- Kategorie + Felder werden über die **Core-Customfield-API** verwaltet:
  - `entities_handler::create($itemid)` → Handler für eine itemid
  - `$handler->create_category($name)` → legt `customfield_category`-Zeile an
    (`customfield/classes/handler.php:202`)
  - `$handler->save_field_configuration($field, $data)` → legt/ändert ein Feld
    (`customfield/classes/handler.php:817`, Datenform siehe §6)
- `get_next_itemid()` liefert `max(itemid)+1` über **alle** customfield_categories
  (`entities_cf_helper.php:117`) — global, nicht plugin-lokal.
- Verfügbare Feldtypen: `text`, `textarea`, `number`, `select`, `checkbox`, `date`
  (Verzeichnis `customfield/field/`).

> Die Vorlagen werden als **alternative** Kategorien angelegt (NICHT als Standard),
> damit Ort-Felder nicht auf Equipment auftauchen und umgekehrt.

---

## 3. Designentscheidungen

| # | Entscheidung | Begründung |
|---|--------------|-----------|
| D1 | Vorlagen = **alternative** Kategorien (nicht Standard) | Typ-spezifisch; sonst erscheinen alle Felder auf allen Entities |
| D2 | Seeding **idempotent**, einmalig, guarded per Config-Flag | Re-Upgrade darf admin-editierte Vorlagen nicht überschreiben/duplizieren |
| D3 | Resultierende itemids in Config persistieren (`template_location_itemid`, `template_equipment_itemid`) | Für Auto-Bindung & Idempotenz-Check stabil referenzierbar; itemid ist global, daher nicht hartkodierbar |
| D4 | Feldnamen via **echte Moodle-Lang-Strings** (`get_string` zur Seed-Zeit) | UMGESETZT (Georg): Namen werden in die Plattform-Sprache aufgelöst und so gespeichert (DE-Plattform → deutsch, sonst englisch). Kein `{mlang}`, keine Filter-Abhängigkeit. (Frühere {mlang}-Idee in den Code-Skizzen unten ist überholt.) |
| D5 | **Auto-Default** von `cfitemid` aus `entitytype` im Edit-Formular (Phase 2) | Macht Vorlagen „unsichtbar nützlich"; manueller Override bleibt |
| D6 | Bestehende Entities werden **nicht** angefasst | Non-destruktiv; nur neue Entities profitieren vom Auto-Default |
| D7 | Gemeinsame Seeder-Klasse, aufgerufen aus `install.php` **und** `upgrade.php` | DRY; Fresh-Install + Bestandssite identisch behandelt |

---

## 4. Die zwei Vorlagen (vorgeschlagene Default-Felder)

Alle Felder sind nach der Installation frei editierbar. Vorschlag bewusst schlank
gehalten; nichts, was die echten DB-Spalten dupliziert (Name, Kurzname,
Beschreibung, Bild, Adresse, Kontakte, Öffnungszeiten, allocationmode/Kapazität,
entitytype, parent liegen bereits als echte Felder vor).

### 4.1 Ort / Location (`shortname`-Präfix `loc_`)

| Feld (DE / EN) | shortname | Typ | Konfiguration |
|----------------|-----------|-----|---------------|
| Gebäude / Building | `loc_building` | text | – |
| Raumnummer / Room number | `loc_roomnumber` | text | – |
| Fläche (m²) / Area (m²) | `loc_area` | number | – |
| Sitzplätze / Seats | `loc_seats` | number | rein deskriptiv (≠ allocation) |
| Ausstattung / Equipment | `loc_amenities` | textarea | freie Liste (Beamer, Whiteboard, WLAN …) |
| Barrierefrei / Wheelchair accessible | `loc_accessible` | checkbox | – |
| Hinweise / Notes | `loc_notes` | textarea | – |

### 4.2 Equipment (`shortname`-Präfix `eq_`)

| Feld (DE / EN) | shortname | Typ | Konfiguration |
|----------------|-----------|-----|---------------|
| Inventarnummer / Inventory number | `eq_inventoryno` | text | uniquevalues=1 |
| Hersteller / Manufacturer | `eq_manufacturer` | text | – |
| Modell / Model | `eq_model` | text | – |
| Seriennummer / Serial number | `eq_serial` | text | – |
| Anschaffungsdatum / Purchase date | `eq_purchasedate` | date | – |
| Zustand / Condition | `eq_condition` | select | Optionen: neu, gut, gebraucht, defekt |
| Verantwortlich / Responsible | `eq_responsible` | text | – |
| Hinweise / Notes | `eq_notes` | textarea | – |

> `shortname`-Präfixe (`loc_`/`eq_`) dienen auch dem **Idempotenz-/Erkennungs-Check**
> und vermeiden Kollisionen mit admin-eigenen Feldern.

---

## 5. Seeding-Architektur

```
db/install.php            (NEU)  → ruft template_seeder::seed_default_templates()
db/upgrade.php            (+1 Step, neuer Savepoint) → idem
classes/local/templates/template_seeder.php  (NEU)  → die eigentliche Logik
lang/{en,de}/local_entities.php  (+ Strings für Doku/Settings, NICHT für Feldnamen)
version.php               (Bump für den Upgrade-Step)
```

### 5.1 `template_seeder` (Skizze, illustrativ — nicht final)

```php
namespace local_entities\local\templates;

use local_entities\customfield\entities_handler;
use local_entities\customfield\entities_cf_helper;
use core_customfield\category_controller;
use core_customfield\field_controller;

class template_seeder {

    /** Config-Flag, damit nur einmal geseedet wird. */
    const SEEDED_FLAG = 'defaulttemplatesseeded';

    public static function seed_default_templates(): void {
        // D2: einmalig. Re-Lauf no-op.
        if (get_config('local_entities', self::SEEDED_FLAG)) {
            return;
        }

        $locitemid = self::create_template(
            self::location_definition()
        );
        $equipitemid = self::create_template(
            self::equipment_definition()
        );

        // D3: itemids für spätere Auto-Bindung merken.
        set_config('template_location_itemid', $locitemid, 'local_entities');
        set_config('template_equipment_itemid', $equipitemid, 'local_entities');
        set_config(self::SEEDED_FLAG, 1, 'local_entities');
    }

    /** Legt Kategorie + Felder an, gibt itemid zurück. */
    private static function create_template(array $def): int {
        $itemid  = entities_cf_helper::get_next_itemid();   // global max+1
        $handler = entities_handler::create($itemid);

        $categoryid = $handler->create_category($def['name']); // {mlang}-Name
        $category   = category_controller::create($categoryid);

        foreach ($def['fields'] as $f) {
            $field = field_controller::create(0, (object)['type' => $f['type']], $category);
            $handler->save_field_configuration($field, (object)[
                'name'           => $f['name'],        // {mlang}…{mlang}
                'shortname'      => $f['shortname'],   // loc_/eq_…
                'type'           => $f['type'],
                'description'    => '',
                'descriptionformat' => FORMAT_HTML,
                'configdata'     => $f['configdata'] ?? [],
            ]);
        }
        return $itemid;
    }

    private static function location_definition(): array {
        return [
            'name' => '{mlang de}Ort{mlang}{mlang other}Location{mlang}',
            'fields' => [
                ['shortname' => 'loc_building',  'type' => 'text',
                 'name' => '{mlang de}Gebäude{mlang}{mlang other}Building{mlang}'],
                ['shortname' => 'loc_area',      'type' => 'number',
                 'name' => '{mlang de}Fläche (m²){mlang}{mlang other}Area (m²){mlang}'],
                ['shortname' => 'loc_accessible','type' => 'checkbox',
                 'name' => '{mlang de}Barrierefrei{mlang}{mlang other}Wheelchair accessible{mlang}',
                 'configdata' => ['checkbydefault' => 0]],
                // … restliche Felder aus §4.1
            ],
        ];
    }

    private static function equipment_definition(): array {
        return [
            'name' => '{mlang de}Equipment{mlang}{mlang other}Equipment{mlang}',
            'fields' => [
                ['shortname' => 'eq_inventoryno', 'type' => 'text',
                 'name' => '{mlang de}Inventarnummer{mlang}{mlang other}Inventory number{mlang}',
                 'configdata' => ['uniquevalues' => 1]],
                ['shortname' => 'eq_condition',   'type' => 'select',
                 'name' => '{mlang de}Zustand{mlang}{mlang other}Condition{mlang}',
                 'configdata' => ['options' => "neu\ngut\ngebraucht\ndefekt"]],
                // … restliche Felder aus §4.2
            ],
        ];
    }
}
```

### 5.2 `db/install.php` (NEU)

```php
function xmldb_local_entities_install() {
    \local_entities\local\templates\template_seeder::seed_default_templates();
}
```

### 5.3 `db/upgrade.php` (neuer Step)

```php
if ($oldversion < 2026062500) {
    \local_entities\local\templates\template_seeder::seed_default_templates();
    upgrade_plugin_savepoint(true, 2026062500, 'local', 'entities');
}
```

`version.php`: `$plugin->version = 2026062500;`

> **Wichtig:** Sowohl Install als auch Upgrade rufen denselben Seeder. Der
> `SEEDED_FLAG`-Guard (D2) verhindert Doppel-Seeding, falls Install-Hook und
> Upgrade-Hook in einem frischen Setup beide feuern.

### 5.4 configdata-Felddetails

Die `configdata`-Schlüssel entsprechen den Core-Field-Formularen
(`api::save_field_configuration` json-kodiert das Array, `handler.php:817`):

- gemeinsam: `required` (0/1), `uniquevalues` (0/1), `locked` (0/1),
  `visibility` (0=nicht sichtbar,1=Lehrende,2=alle — `entities_handler` Konstanten)
- `text`: `displaysize`, `maxlength`, `defaultvalue`
- `number`: `defaultvalue`, ggf. `decimalplaces`
- `select`: `options` (newline-separierte Liste), `defaultvalue`
- `checkbox`: `checkbydefault`
- `textarea`: `defaultvalue`, `defaultvalueformat`

Empfehlung: `visibility = VISIBLETOALL (2)`, damit die Felder auch auf der
View-Seite im Metadaten-Block erscheinen.

---

## 6. Auto-Bindung an `entitytype` (Phase 2 — der eigentliche UX-Gewinn)

Heute muss der Nutzer im Edit-Formular die Vorlage über das separate, mit
„Categories" beschriftete Dropdown (`cfitemid`) selbst wählen. Vorschlag:

**`edit_dynamic_form.php` (Definition):** wenn für die Entity noch keine `cfitemid`
gesetzt ist, den Default aus `entitytype` ableiten:

```php
// Pseudocode in definition(), VOR Auswahl des customhandlers:
$autocfitemid = null;
if (empty($data->cfitemid)) {
    $autocfitemid = ($data->entitytype ?? 'location') === 'equipment'
        ? get_config('local_entities', 'template_equipment_itemid')
        : get_config('local_entities', 'template_location_itemid');
}
$cfitemid = $this->_ajaxformdata['cfitemid'] ?? $autocfitemid ?? <bisheriger fallback>;
```

Zusätzlich: `entitytype`-Select als **No-Submit-Trigger** registrieren (wie der
Equipment-Refresh-Button), sodass beim Wechsel location↔equipment die passende
Vorlage live nachgeladen wird. Manueller Override über das Kategorie-Dropdown
bleibt erhalten (für Sonderfälle / admin-eigene Kategorien).

**Begleitend (Klarheit):** das Dropdown-Label von „Categories" auf z. B.
„Feldvorlage / Field template" umbenennen, weil es heute mit dem gleichnamigen
Admin-Setting („Standard categories") kollidiert.

> Phase 2 ist optional separat ausrollbar. Phase 1 (Seeding) liefert bereits Wert:
> die Vorlagen sind dann im Dropdown sofort auswählbar.

---

## 7. Non-Destruktion, Migration, Edge Cases

- **Bestehende Sites:** Upgrade legt die zwei Vorlagen an, ändert aber **keine**
  bestehenden Entities (D6). Bestehende admin-eigene Kategorien bleiben unberührt.
- **Admin löscht/ändert Vorlage:** erlaubt. `SEEDED_FLAG` verhindert Wieder-Anlegen
  beim nächsten Upgrade. (Bewusst: kein „self-healing", um Admin-Willen zu respektieren.)
- **itemid-Kollision:** ausgeschlossen, da `get_next_itemid()` global `max+1` nimmt
  und die Ergebnisse in Config gespeichert werden.
- **Mehrsprachigkeit:** `{mlang}`-Tags greifen nur, wenn der Multilang-Filter
  aktiv ist; sonst zeigt Moodle den Rohtext inkl. Tags. → In Doku erwähnen oder
  Fallback: bei inaktivem Filter Namen in Site-Default-Sprache via `get_string`
  seeden (Alternative zu D4, falls Multilang-Filter site-weit nicht garantiert ist).
- **Deinstallation:** Core-Customfield räumt seine Kategorien/Felder/Daten über die
  `area/component`-Bindung auf; Config-Flags via `uninstall` mitnehmen.
- **`cfitemid` zeigt auf gelöschte Kategorie:** bereits heute möglich; Auto-Default
  (Phase 2) greift nur, wenn `cfitemid` leer ist → keine Verschlechterung.

---

## 8. Testplan

**PHPUnit** (`tests/template_seeder_test.php`, neu):
1. `seed_default_templates()` legt genau 2 Kategorien an (`loc_*`, `eq_*` vorhanden).
2. Idempotenz: zweiter Aufruf legt nichts Neues an (Kategorie-/Feldzahl stabil).
3. Config-Flags + `template_*_itemid` gesetzt und zeigen auf existierende Kategorien.
4. select-Feld `eq_condition` hat die 4 Optionen; `eq_inventoryno` uniquevalues=1.
5. (Phase 2) Neue location-Entity ohne cfitemid → Form-Default = location-itemid;
   equipment analog. Override via cfitemid wird respektiert.

**Behat** (`tests/behat/entity_templates.feature`, neu, `@javascript`):
1. Frische Site: „New entity" → entitytype „Equipment" → Equipment-Felder
   (z. B. „Inventarnummer") erscheinen ohne manuelle Kategoriewahl.
2. entitytype „Ort" → Location-Felder erscheinen; Wechsel lädt korrekt um.
3. Werte speichern → View-Seite zeigt sie im Metadaten-Block.

> Hinweis: Seeding-Tests müssen den `SEEDED_FLAG` in `setUp`/`tearDown`
> zurücksetzen bzw. `resetAfterTest` nutzen, da Config zwischen Tests persistiert.

---

## 9. Commit-Strategie & Risiko-Isolation

**Vorgabe (Georg):** Die einzige verhaltensändernde Änderung (Phase 2 / Auto-Bindung)
liegt in **eigenen, obenauf liegenden Commits**. Reverten wir sie, bleiben alle
unkritischen Verbesserungen (Seeding, Schutz, Labels, View-Politur) voll funktionsfähig
erhalten. Reihenfolge so, dass **kein** unkritischer Commit auf den kritischen aufbaut.

| Commit | Inhalt | Kritisch? | Bei Revert von C4 noch nutzbar? |
|--------|--------|-----------|-------------------------------|
| **C1 — Seeding** | `template_seeder`, `db/install.php`, Upgrade-Step, version bump, Lang-Strings, PHPUnit (§8.1–4) | **nein** (rein additiv: 2 neue Kategorien, keine Bestandsdaten berührt) | ✅ Vorlagen existieren, im Dropdown manuell wählbar |
| **C2 — Form-Schutz** | „Kein-Template"-Default (cfitemid 0) für Entities **ohne** gesetzte Vorlage, statt erzwungenem „erste Kategorie". Macht C1 nachweisbar altverhalten-neutral. | **nein** (defensiv; entspricht Nutzerwillen „nichts gewählt = nichts angehängt") | ✅ unabhängig sinnvoll |
| **C3 — Labels & View-Politur** | Dropdown-Relabel „Categories" → „Feldvorlage"; Metadaten auf View-Seite nach Kategorie gruppieren (behebt „nur letzter Kategoriename") | **nein** (rein kosmetisch) | ✅ |
| **C4 — Auto-Bindung (Phase 2)** | `entitytype` → Default-`cfitemid` (nur wenn leer) + `entitytype` als No-Submit-Trigger in `edit_dynamic_form` | **JA** (ändert Formular-Default-Logik) | n/a — das ist der revertierbare Teil |

**Abhängigkeiten:** C4 referenziert die in C1 gesetzten `template_*_itemid`-Configs →
C1 muss vor C4. C2/C3 sind unabhängig von C4. `git revert <C4>` lässt C1–C3 intakt:
Vorlagen bleiben, werden nur wieder **manuell** per Dropdown gewählt statt automatisch
per Typ.

> Jeweils **getrennte Repos** beachten: `local/entities` ist ein eigenes Git-Repo
> (alle vier Commits dort). Kein Cross-Repo-Commit.

### 9.1 Der Form-Schutz (C2) im Detail

Heute defaultet `edit_dynamic_form` bei leerem `cfitemid` auf `reset($arraykeys)`
(= erste alternative Kategorie). Sobald C1 zwei Vorlagen anlegt, würde dadurch beim
nächsten Speichern einer **alten** Entity (cfitemid 0) ungewollt eine Vorlage gesetzt.

C2 ändert den Default auf **„— keine Vorlage —" (cfitemid 0)**, solange der Nutzer
nicht aktiv eine wählt. Damit gilt: „nichts gewählt ⇒ nichts angehängt". Das ist
zugleich eine kleine Korrektur des bisherigen (überraschenden) Verhaltens und
**keine** Funktionsänderung an Buchung/Verfügbarkeit/Relations.

---

## 9b. Umsetzungsreihenfolge

1. **C1 + C2 + C3** zusammen ausrollen (alles unkritisch, sofort nützlich, altverhalten-neutral).
2. **C4** separat obenauf (kritisch, einzeln revertierbar). PHPUnit (§8.5) + Behat (§8).
3. Feldlisten §4 vorab final abstimmen (O1).

---

## 10. Offene Fragen (vor Implementierung klären)

- **O1 — Feldumfang:** Sind die Feldlisten in §4 so gewünscht, oder
  reduzieren/ergänzen (z. B. bei Ort: „Etage" existiert schon in Adresse → drin lassen?).
- **O2 — Multilang vs. Site-Sprache:** ENTSCHIEDEN (Georg): einfach Moodle-Lang-Strings,
  Seeding in der Plattform-Sprache (DE → deutsch, sonst englisch). Kein {mlang}.
- **O3 — Phase 2 jetzt oder später:** Auto-Bindung an `entitytype` direkt mitliefern
  (empfohlen, weil sie den Nutzen erst „magisch" macht) oder erst Seeding ausrollen?
- **O4 — Bestandssites:** Sollen die Vorlagen auch auf bestehenden Sites per Upgrade
  erscheinen (empfohlen, D7), oder nur bei Neuinstallationen?
- **O5 — Sichtbarkeit:** Felder default `VISIBLETOALL` (auf View-Seite sichtbar) — ok?
