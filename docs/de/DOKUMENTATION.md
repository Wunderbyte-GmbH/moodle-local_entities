# local_entities – Einrichtung und Nutzung

**Plugin:** `local_entities` (Entity-Manager)  
**Entwickler:** Wunderbyte GmbH  
**Kompatibel mit:** Moodle 4.x / 5.x, Bootstrap 4 & 5

---

## Inhaltsverzeichnis

1. [Was ist das Entities-Plugin?](#1-was-ist-das-entities-plugin)
2. [Installation](#2-installation)
3. [Grundkonfiguration](#3-grundkonfiguration)
4. [Berechtigungen](#4-berechtigungen)
5. [Entities verwalten](#5-entities-verwalten)
   - [Entity erstellen](#51-entity-erstellen)
   - [Sub-Entities (Unterräume)](#52-sub-entities-unterräume)
   - [Öffnungszeiten](#53-öffnungszeiten)
   - [Entities importieren (CSV)](#54-entities-importieren-csv)
6. [Kalender](#6-kalender)
7. [Integration mit mod_booking](#7-integration-mit-mod_booking)
   - [Funktionsweise](#71-funktionsweise)
   - [Entity einer Buchungsoption zuweisen](#72-entity-einer-buchungsoption-zuweisen)
   - [Entity pro Kurstermin zuweisen](#73-entity-pro-kurstermin-zuweisen)
   - [Konfliktprüfung](#74-konfliktprüfung)
   - [Preisfaktor](#75-preisfaktor)
8. [Webservices](#8-webservices)
9. [Häufige Fragen](#9-häufige-fragen)

---

## 1. Was ist das Entities-Plugin?

Das Plugin `local_entities` (Entity-Manager) ermöglicht es, **Orte, Räume oder andere buchbare Ressourcen** (genannt „Entities") zentral in Moodle zu verwalten. Jede Entity kann folgende Informationen enthalten:

- Name und Kurzname
- Beschreibung und Bild
- Adresse (Straße, Stadt, Land, Stockwerk, Stiege) inkl. Kartenlink/-einbettung
- Kontaktpersonen
- Öffnungszeiten (tagesgenau, pro Wochentag)
- Maximale Buchungskapazität
- Relativer Preisfaktor (für die Preisberechnung in mod_booking)
- Benutzerdefinierte Felder (Custom Fields)

Entities sind hierarchisch aufgebaut: Es gibt **Parent-Entities** (z. B. ein Gebäude) und **Sub-Entities** (z. B. einzelne Räume im Gebäude).

---

## 2. Installation

### Option A – ZIP-Datei hochladen

1. Als Admin anmelden und zu *Website-Administration → Plugins → Plugins installieren* navigieren.
2. ZIP-Datei des Plugins hochladen.
3. Validierungsbericht prüfen und Installation abschließen.

### Option B – Manuell

1. Plugin-Verzeichnis nach `{moodle-root}/local/entities` kopieren.
2. Als Admin anmelden und *Website-Administration → Benachrichtigungen* aufrufen, um die Installation abzuschließen.
3. Alternativ über die Kommandozeile:
   ```bash
   php admin/cli/upgrade.php
   ```

### Webservice-Token (Pflicht)

Das Plugin nutzt Ajax-Webservices. Nach der Installation muss ein Token erstellt werden:

1. Webservices aktivieren:  
   *Website-Administration → Erweiterte Funktionen → Webservices aktivieren*
2. Token erstellen für den Admin-Nutzer:  
   `{moodle-root}/admin/webservice/tokens.php`

---

## 3. Grundkonfiguration

Die Einstellungen sind unter *Website-Administration → Lokale Plugins → Entity-Manager* zu finden.

| Einstellung | Beschreibung | Standard |
|---|---|---|
| **Entity-Kategorien** | Auswahl der Custom-Field-Kategorien, die in allen Entity-Bearbeitungsmasken sichtbar sind | – |
| **Bild der übergeordneten Entity als Fallback** | Zeigt das Bild der Parent-Entity, wenn die Sub-Entity kein eigenes Bild hat | ✓ |
| **Adresse der übergeordneten Entity als Fallback** | Übernimmt die Adresse der Parent-Entity, wenn keine eigene Adresse angegeben ist | ✓ |
| **Kontakte der übergeordneten Entity als Fallback** | Übernimmt die Kontakte der Parent-Entity, wenn keine eigenen Kontakte angegeben sind | ✓ |
| **Kalender auf Detailseite anzeigen** | Zeigt den Belegungskalender direkt auf der Entity-Detailseite | ✗ |
| **Bild anstatt Kalender anzeigen** | Zeigt bei allen Entities das Bild in Großdarstellung statt des Kalenders | ✗ |
| **Filter: Namen von Sub-Entities verwenden** | Filter nutzen standardmäßig den Namen der Parent-Entity; mit dieser Option wird jede Sub-Entity einzeln im Filter angezeigt | ✗ |

---

## 4. Berechtigungen

Das Plugin definiert drei Capabilities auf Systemebene:

| Capability | Beschreibung | Standard |
|---|---|---|
| `local/entities:view` | Entity-Detailseiten ansehen | Alle Nutzer:innen |
| `local/entities:edit` | Entities erstellen und bearbeiten | Manager:innen |
| `local/entities:delete` | Entities löschen | Manager:innen |

Die Rechte können unter *Website-Administration → Nutzer → Rollen definieren* angepasst werden.

---

## 5. Entities verwalten

Der Entity-Manager ist erreichbar unter:  
`{moodle-root}/local/entities/entities.php`

### 5.1 Entity erstellen

1. Auf *Neue Entity* klicken.
2. Pflichtfelder ausfüllen:
   - **Entity-Name** – vollständiger Name (z. B. „Seminarraum 1")
   - **Kurzname** – interner Bezeichner
3. Optionale Felder:
   - **Beschreibung** – Rich-Text mit Bild möglich
   - **Entity-Parent** – übergeordnete Entity (z. B. Gebäude)
   - **Sortier-Reihenfolge** – numerische Reihenfolge in der Liste
   - **Maximale Buchungsanzahl** – `0` = kein Limit, `-1` = nicht buchbar
   - **Relativer Preisfaktor** – Multiplikator für automatische Preisberechnung
   - **Adresse** – Straße, Hausnummer, PLZ, Stadt, Land, Stockwerk, Stiege
   - **Kartenlink / Karte einbetten** – Google Maps- oder OpenStreetMap-Link
   - **Kontakte** – Vorname, Nachname, E-Mail (mehrere möglich)
   - **Öffnungszeiten** – Wochentage mit Start- und Endzeit
4. Auf *Speichern* klicken.

### 5.2 Sub-Entities (Unterräume)

Um eine Sub-Entity anzulegen, wählt man im Feld **Entity-Parent** eine bereits vorhandene Entity aus. Auf der Übersichtsseite werden Sub-Entities eingerückt unter ihrer Parent-Entity angezeigt. Auf der Detailseite einer Parent-Entity erscheinen alle zugehörigen Sub-Entities unter dem Abschnitt *Zugehörige Orte*.

### 5.3 Öffnungszeiten

Für jede Entity können pro Wochentag ein oder mehrere Zeitfenster hinterlegt werden (Stunden und Minuten separat). Diese Öffnungszeiten werden bei der Konfliktprüfung in mod_booking berücksichtigt: Buchungen außerhalb der Öffnungszeiten werden mit einem Validierungsfehler abgelehnt.

### 5.4 Entities importieren (CSV)

Entities können per CSV-Datei massenimportiert werden:  
`{moodle-root}/local/entities/import.php`

Erforderliche Berechtigung: `mod/booking:updatebooking`

**Format der CSV-Datei** (Trennzeichen: `;`):

```
name;shortname;description
"Seminarraum 1";seminarraum1;"Großer Seminarraum im EG"
"Seminarraum 2";seminarraum2;"Kleiner Seminarraum im 1. OG"
```

---

## 6. Kalender

Jede Entity verfügt über einen Belegungskalender, der alle Buchungen anzeigt, die dieser Entity zugeordnet sind (z. B. aus mod_booking). Abgesagte Veranstaltungen werden mit dem Zusatz `[ABGESAGT]` dargestellt.

Der Kalender ist erreichbar unter:  
`{moodle-root}/local/entities/calendar.php?entityid={ID}`

Je nach Einstellung wird der Kalender auf der Detailseite der Entity direkt eingebettet oder als Link angeboten.

---

## 7. Integration mit mod_booking

### 7.1 Funktionsweise

Die Verknüpfung zwischen mod_booking und local_entities erfolgt über den `entitiesrelation_handler`. Dieser speichert Beziehungen in der Tabelle `local_entities_relations` in den Feldern:

- `entityid` – ID der gewählten Entity
- `component` – z. B. `mod_booking`
- `area` – z. B. `option` oder `optiondate`
- `instanceid` – ID der Buchungsoption oder des Kurstermins

### 7.2 Entity einer Buchungsoption zuweisen

Beim Bearbeiten einer Buchungsoption in mod_booking erscheint der Abschnitt **Entity** (mit Gebäude-Symbol). Dort kann per Autocomplete-Suche eine Entity ausgewählt werden. Es wird automatisch nach Entity-Namen gesucht (Groß-/Kleinschreibung wird ignoriert).

1. Buchungsoption öffnen und bearbeiten.
2. Abschnitt **Entity** aufklappen.
3. Entity im Suchfeld suchen und auswählen.
4. Option speichern.

Die gewählte Entity gilt dann für die gesamte Buchungsoption (alle Kurstermine), sofern keine abweichende Entity pro Termin gesetzt wurde.

### 7.3 Entity pro Kurstermin zuweisen

Bei Buchungsoptionen mit mehreren Terminen kann für jeden einzelnen Kurstermin eine eigene Entity gesetzt werden. Dies ermöglicht z. B. rotierende Raumnutzung. Die Zuweisung erfolgt im Bearbeitungsformular des jeweiligen Kurstermins.

Wenn einige Termine eine abweichende Entity haben (*Outlier*), zeigt mod_booking eine Bestätigungsabfrage an, bevor eine übergeordnete Entity-Änderung alle Termine überschreibt.

### 7.4 Konfliktprüfung

Das Plugin prüft beim Speichern automatisch, ob die gewählte Entity im gewünschten Zeitraum bereits belegt ist:

- **Doppelbuchungen** werden erkannt und als Validierungsfehler mit Links zu den konfliktierenden Buchungen angezeigt.
- **Öffnungszeiten** werden geprüft: Liegt ein Termin außerhalb der definierten Öffnungszeiten der Entity, erscheint der Fehler „Außerhalb der Öffnungszeiten".

### 7.5 Preisfaktor

Jede Entity kann einen **relativen Preisfaktor** tragen (Standard: `1.0`). mod_booking kann diesen Faktor bei der automatischen Preisberechnung berücksichtigen. Ein Faktor von `1.5` würde den Grundpreis einer Buchungsoption um 50 % erhöhen, wenn diese Entity verwendet wird.

---

## 8. Webservices

Das Plugin stellt folgende Ajax-Webservices bereit (Service-Kurzname: `local_entities_external`):

| Funktion | Beschreibung | Berechtigung |
|---|---|---|
| `local_entities_list_all_parent_entities` | Alle Top-Level-Entities abrufen | `local/entities:edit` |
| `local_entities_list_all_subentities` | Sub-Entities einer Parent-Entity abrufen | – |
| `local_entities_update_entity` | Entity-Datensatz aktualisieren | `local/entities:edit` |
| `local_entities_delete_entity` | Entity löschen | `local/entities:delete` |
| `local_entities_get_entity_calendardata` | Kalenderdaten einer Entity abrufen | – (kein Login erforderlich) |
| `local_entities_search_entities` | Entities anhand eines Suchbegriffs suchen | Angemeldet |

---

## 9. Häufige Fragen

**Warum erscheint der Entity-Abschnitt in mod_booking nicht?**  
Stellen Sie sicher, dass `local_entities` installiert und mod_booking aktuell ist. Die Integration muss in den mod_booking-Einstellungen aktiviert sein.

**Kann ich Entities ohne mod_booking nutzen?**  
Ja. Entities sind eigenständig verwaltbar und über ihre Detailseite (`/local/entities/view.php?id={ID}`) für alle Nutzer:innen sichtbar.

**Wie verhindere ich, dass eine Entity gebucht werden kann?**  
Setzen Sie die *Maximale Buchungsanzahl* auf `-1`.

**Was passiert, wenn ich eine Parent-Entity lösche?**  
Das Plugin enthält eine DB-Bereinigungsfunktion. Stellen Sie dennoch sicher, dass alle Sub-Entities und Relationen vorher aufgelöst sind, um Inkonsistenzen zu vermeiden.

**Die Autocomplete-Suche findet meine Entity nicht.**  
Die Suche ist Groß-/Kleinschreibung-unabhängig. Prüfen Sie, ob die Entity aktiv ist und ob die Webservices und der Token korrekt konfiguriert sind.
