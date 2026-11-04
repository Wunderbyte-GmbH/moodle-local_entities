# IQSH Prüfungsplanung: Plan nach den neuen Kundenanforderungen

Stand: 2026-10-28, Entwurf zur Besprechung. Es gibt noch keinen Code dazu.

Dieser Plan ersetzt die Schritte 12b (Auswahl im Buchungsdialog), 13–15 und 18 aus
`EXAMINER_IMPLEMENTATION_PLAN.md`. Was dort abgenommen ist (Personen, Abwesenheiten, Pools, Konfliktschutz,
Belegungen im Kalender, `booking_slot_staff`, `staff_availability`, `staff_reservation`), bleibt die Grundlage.

---

## 1. Ziel

Der Trainer einer Slot-Buchungsoption plant die Prüfungen für alle Prüflinge selbst:

1. Er ordnet jedem Prüfling einen Protokollanten zu. Dabei entsteht kein Termin.
2. Das System berechnet daraus einen vorläufigen Plan.
3. Er passt den Plan an: verschieben, tauschen oder neu verteilen lassen.
4. Er bestätigt. Erst dann entstehen echte Buchungen, und alle Beteiligten werden benachrichtigt.

Prüfer ist immer der Trainer der Option. Die Studierenden buchen in diesen Optionen nicht selbst.

## 2. Entscheidungen (Georg, 2026-10-28)

| Nr | Entscheidung |
|---|---|
| IQ1 | Kundenspezifisches gehört in ein eigenes Subplugin vom Typ `bookingextension`. Booking-Core wird so wenig wie möglich verändert. |
| IQ2 | Der Code aus Schritt 12–12b (Rollen, Quellen, Rollen-Seite, Rollen-Editor, Auswahl im Dialog) **zieht in die Extension um**. Im Core bleiben nur Andockstellen und die neutrale Tabelle `booking_slot_staff`. |
| IQ3 | Nur der Trainer plant und bucht. In geplanten Optionen ist die Selbstbuchung der Studierenden aus. |
| IQ4 | Prüfer = der Trainer der Option. Hat die Option nicht **genau einen** Trainer, startet die Verteilung nicht, und die Meldung sagt warum. |
| IQ5 | Hat der Trainer keine Person-Entity, legt Booking (die Extension) sie an. Das ist eine Ausnahme zur Regel „Booking legt nie Entities an". |
| IQ6 | Bestehende Teile bleiben: Pools, die Verknüpfung mit Entities, Abwesenheiten, Konfliktschutz. |
| IQ7 (F-X1, geändert 2026-10-28 nachmittags) | Der Core bleibt **genau `MOODLE_405_DEV`** plus der kleine Haken aus P3. Der WIP-Stand von `iqsh-2602` (Schritte 5–12c) wurde verworfen und als `iqsh-2602-wip-backup` gesichert; er ist die Vorlage für die Extension. Die alte Prüfer-Funktion und die alte Abwesenheitsseite von Booking bleiben unangetastet. **Alles für IQSH liegt in der Extension**, auch die Abwesenheitsprüfung über Entities, die Zuweisungen von Personen zu Slots (bisher `booking_slot_staff`), die Sperren gegen Doppelbelegung und die Belegungen im Abwesenheitskalender. |
| IQ8 (F-E1) | Der Planungszeitraum gilt für Personen. Planer und Admins (`manageunavailability`) sehen und bearbeiten den ganzen Kalender. |
| IQ9 (F-E2) | Das Tageskontingent gilt für jede Person, also auch für den Trainer. Leer bedeutet unbegrenzt. |
| IQ10 (F-X2) | Genau ein Protokollant je Prüfling. |
| IQ11 (F-X3) | Vorab-Pläne blockieren nichts. Erst die Buchung zählt, Konflikte fängt die Prüfung beim Bestätigen ab und nennt sie. |
| IQ12 (F-X4) | Die Verteilung läuft immer als Ad-hoc-Task. Die Seite zeigt „Berechnung läuft" und aktualisiert sich. |
| IQ13 (F-X5) | Geändert wird über ein Menü je Eintrag: „Verschieben nach…", „Tauschen mit…", „Protokollant ändern…". Angeboten werden nur gültige Ziele. Kein Ziehen und Ablegen. |
| IQ14 (F-X6) | Termine, an denen der Trainer in anderen Optionen als Lehrender eingetragen ist (`booking_optiondates_teachers`), zählen als belegt. |
| IQ15 (F-X7) | Ein gebuchter Plan kann nach einer Bestätigung zurückgenommen werden. Dabei werden die Buchungen des Laufs (`runid`) storniert und die Protokollanten benachrichtigt. Danach kann neu verteilt werden. |
| IQ16 (F-X8) | Der Name der Extension ist `bookingextension_examplanning`, eigenes Repository `Wunderbyte-GmbH/moodle-bookingextension_examplanning` (Branch `main`). |
| IQ18 (P7) | Sichtbarkeit erst mit Termin: Der Prüfling sieht Protokollant und Termin erst, wenn ein Termin gebucht ist. Der Protokollant sieht seine Termine im eigenen Abwesenheitskalender (Entities) als Belegung: Prüfling, eigene Rolle bei diesem Termin, Name der Buchungsoption; Klick führt zur Optionsseite. Auch der Trainer (Prüfer, ebenfalls eine Person-Entity) sieht seine Prüfungstermine im eigenen Abwesenheitskalender, mit Rolle Prüfer. Die Zuordnung aller Teilnehmer sieht er nur auf der Zuordnungsseite. |
| IQ19 (P8) | Wer bei einem gebuchten Prüfungstermin Prüfer und Protokollant ist, steht in einer eigenen Tabelle der Extension (`bookingextension_examplanning_staff`). Die WIP-Tabellen `booking_slot_*` werden aus der Datenbank entfernt. |
| IQ20 (P8) | Entities holt Belegungen über einen Moodle-Hook (`local_entities\hook\collect_occupations`). Die Extension antwortet mit ihren Terminen, Entities kennt die Extension nicht. |
| IQ21 (P8) | Im Kalender erscheinen nur gebuchte Prüfungstermine, keine Vorab-Pläne. |
| IQ22 (P8) | Das Tageskontingent zählt nur Prüfungstermine. Lehrtermine des Trainers blockieren nur ihre Uhrzeit. |
| IQ23 (P9) | Kompakt verteilen: jeder Prüfling bekommt den frühesten Slot, in dem Prüfer und Protokollant frei sind. Schwierige Prüflinge (wenigste gemeinsame freie Slots) zuerst. Shuffle mischt nur die Reihenfolge bei Gleichstand. |
| IQ24 (P9) | Pause zwischen zwei Prüfungen: der Puffer der Slot-Konfiguration der Option (Aufwärm-/Abkühlzeit). Ohne Puffer dürfen Prüfungen direkt aneinander anschließen. |
| IQ25 (P10) | Zuordnung und Verteilung liegen auf einer Seite „Prüfungsplanung“ (bisher „Assign clerks“): oben die Protokollanten mit Speichern, darunter „Verteilen“ und der Vorab-Plan. |
| IQ26 (P10) | Ist die Berechnung fertig, bekommt der Trainer eine Moodle-Nachricht (Glocke, E-Mail nach seinen Einstellungen) mit Zahl der geplanten und nicht planbaren Prüflinge und Link. |
| IQ27 (P11) | „Verschieben nach…“ öffnet einen Dialog: erst der Tag (Tage ohne freien Slot ausgegraut mit Grund), dann die Slots des Tages, ungültige ausgegraut mit Grund. |
| IQ28 (P11) | Nicht planbare Prüflinge werden nicht von Hand eingeplant. Man ändert Zuordnung oder Abwesenheiten und verteilt neu. |
| IQ29 (P13) | Bestätigt wird nur, wenn alle Prüflinge geplant sind. Ist einer nicht planbar, wird niemand gebucht; eine Fehlermeldung nennt die Prüflinge, und der Trainer klärt das mit den Protokollanten. |
| IQ30 (P13) | Beim Bestätigen wird alles neu geprüft. Alles oder nichts: Passt ein Eintrag nicht mehr, wird nichts gebucht und der Plan zeigt die Gründe. |
| IQ31 (P13) | Die Nachrichten nach dem Buchen laufen über die Booking-Regeln mit dem vorhandenen Regel-Editor. Die Extension liefert dafür eine Regel-Bedingung „Protokollant“ (`bookingextension_examplanning\rules\conditions\…`), Booking lädt solche Bedingungen bereits aus Extensions. Keine eigenen Nachrichten nach dem Buchen. |
| IQ32 (P14) | Ein gebuchter Plan kann nur zurückgenommen werden, solange noch keine seiner Prüfungen begonnen hat. |
| IQ33 (P14) | „Plan zurücknehmen“ storniert alle Buchungen des Laufs auf einmal. Danach ist der Plan wieder ein Vorab-Plan: ändern, erneut bestätigen oder neu verteilen. |
| IQ34 (P14) | Bei einem Storno bleiben die Zeilen in `_staff` mit Zeitpunkt des Stornos (`timecancelled`) erhalten, damit eine Regel auf „Buchung storniert“ den Protokollanten findet. Kalender, Verfügbarkeit und Optionsseite ignorieren stornierte Zeilen. Das gilt auch für ein einzelnes Storno in Booking. |
| IQ35 | Mails nach dem Buchen über zwei Booking-Regeln auf „Buchungsoption gebucht“: an den Protokollanten (Bedingung „Protokollant der Prüfung“) und an den Prüfling (Bedingung „Nutzer aus dem Ereignis“, betroffener Nutzer). Keine Verzögerung nötig; später ggf. „4 Stunden vor Optionsstart“. |
| IQ36 | Der Protokollant wird beim Buchen als Trainer/in ohne Bearbeitungsrecht in den Kurs der Option eingeschrieben, damit er die Online-Prüfung begleiten kann. Beim Storno wird er wieder ausgeschrieben, wenn die Extension ihn eingeschrieben hat und er dort keine andere gebuchte Prüfung mehr hat. |
| IQ17 | Booking-Branch `iqsh-2602` = `origin/MOODLE_405_DEV` + P3-Haken, Version `2026102800` (über der Datenbank-Version von `sofa-2656`). |

## 3. Wer macht was

```
local_entities (allgemein, für alle Kunden)
  Personen, Pools, Abwesenheiten, Konfliktschutz, Belegungen im Kalender   ← vorhanden
  NEU: Planungszeitraum (zentral), maximale Slots pro Tag je Person

mod_booking (Core = MOODLE_405_DEV + Haken aus P3)
  Slot-Buchung, Trainer der Option, booking_answers, Buchen für andere      ← vorhanden, unverändert
  NEU: bookingrestriction_interface + Bedingung extensionrestriction (121)

bookingextension_examplanning (neu, kundenspezifisch)
  Protokollant: Quellen (Pools/Personen) je Option
  Verfügbarkeit (Abwesenheit, Belegung, Tageskontingent), Zuweisungen, Sperren,
  Belegungen im Abwesenheitskalender                                         ← Vorlage: iqsh-2602-wip-backup
  Zuweisungsseite Prüfling → Protokollant
  Sichtbarkeit der Zuordnung (Studierende, Protokollanten, Trainer)
  Verteilung (Ad-hoc-Task), Vorab-Plan, Verschieben/Tauschen, Shuffle
  Prüfung (Validator), Bestätigen und Buchen, Benachrichtigungen
  Person-Entity für den Trainer anlegen
```

Die Extension nutzt die vorhandenen Andockstellen von `bookingextension_interface`:

- Felder im Optionsformular: `contains_option_fields`, `get_option_fields_info_array`.
- Einstellungen: `load_settings`.
- Anzeige auf der Optionsseite: `set_template_data_for_optionview`.
- Aktionen in der Optionsliste: `add_options_to_col_actions`.
- Regel-Ereignisse: `get_allowedruleeventkeys`.

## 4. Erweiterungen in local_entities

### 4.1 Planungszeitraum (zentral)

- **Einstellung:** Start und Ende auf der Einstellungsseite von Entities, also global. Ist nichts eingetragen, ändert sich nichts.
- **Kalender:** Die Abwesenheitsseite zeigt nur diesen Zeitraum (FullCalendar `validRange`). Davor und danach kann man weder blättern noch markieren.
- **Server:** `unavailability_saver` lehnt Abwesenheiten außerhalb ab, ebenso Serien, die über den Zeitraum hinausgehen. Die Meldung nennt den Zeitraum.
- **Planer** (`manageunavailability`): Sie sehen und bearbeiten den ganzen Kalender (IQ8).
- **API:** `local_entities\planning_period::get(): ?time_range`. Die Extension liest den Zeitraum darüber.

### 4.2 Maximale Slots pro Tag je Person

- **Speicherort:** neue Spalte bzw. Einstellung an der Person, zum Beispiel `local_entities.maxslotsperday`. Leer bedeutet unbegrenzt.
- **Oberfläche:** ein Zahlenfeld auf der Abwesenheitsseite über dem Kalender, mit eigenem Speichern. Es gilt die Sperre je Typ, wie bei den Abwesenheiten.
- **API:** `persons::get_max_slots_per_day(array $entityids): array`, eine Abfrage für viele Personen.
- **Wirkung:** Entities zählt selbst nichts. Die Verteilung und der Validator zählen die Belegungen je Tag.

## 5. Zustände und Datenmodell (Extension)

Die drei Zustände sind getrennt. Nur der letzte schreibt in Booking.

| Zustand | Inhalt | Tabelle |
|---|---|---|
| **Zuordnung** | Prüfling → Protokollant, ohne Termin | `bookingextension_examplanning_assign` (optionid, userid, clerkentityid, usermodified, timemodified), eindeutig je (optionid, userid) |
| **Vorab-Plan** | Lauf mit Slots, Prüfling, Prüfer, Protokollant | `…_plan` (optionid, status: queued, running, ready, failed, confirming, booked; seed; userid des Trainers; Zeiten; Bericht als JSON) und `…_plan_item` (planid, userid, examinerentityid, clerkentityid, slotstart, slotend, Problem-Code) |
| **Gebucht** | echte Buchung | `booking_answers` plus `bookingextension_examplanning_staff` (optionid, answerid, userid des Prüflings, entityid, Rolle examiner/clerk, slotstart, slotend, runid = Plan-ID), IQ19 |

- Pro Option gibt es höchstens einen offenen Plan. Ein neuer Lauf ersetzt den alten, der Bericht bleibt erhalten.
- Ein Plan-Eintrag ohne Slot ist „nicht planbar" und trägt Gründe (Abschnitt 6.3).
- **Sichtbarkeit von Vorab-Plänen:** Vorab-Pläne zählen nicht als Belegung. Ein zweiter Trainer kann also denselben Protokollanten vorab einplanen. Das fängt die Prüfung beim Bestätigen ab (IQ11).

## 6. Verteilung

### 6.1 Eingaben

- **Prüflinge:** in der Option eingeschrieben, mit Zuordnung. Prüflinge ohne Protokollant kommen als „nicht planbar" in den Bericht.
- **Slots:** aus der Slot-Konfiguration der Option (Tage, Öffnungszeiten, Dauer), geschnitten mit dem Planungszeitraum aus Entities.
- **Prüfer:** der Trainer, als Entity. Für Protokollanten: Entity aus der Zuordnung.
- **Belegt:**
  - Abwesenheiten (Entities),
  - Belegungen aus `bookingextension_examplanning_staff` in allen Optionen (IQ19),
  - Termine, an denen der Trainer in anderen Optionen lehrt (`booking_optiondates_teachers`, IQ14),
  - das Tageskontingent je Person, nur Prüfungstermine (IQ22),
  - die Slot-Kapazität,
  - bereits gebuchte Prüflinge der Option, die nicht neu verteilt werden.
- **Vorladen:** Alles wird einmal für den ganzen Zeitraum in den Speicher geladen (`staff_availability::preload`). Pro Entscheidung gibt es keine Abfrage.

### 6.2 Strategie

- **Grundprinzip:** Gierig vorgehen, und zwar zuerst die Prüflinge, deren Personen am wenigsten freie Slots haben. Das verringert Sackgassen. Danach wird der früheste passende Slot gewählt (kompakt, IQ23). Der Puffer der Option gilt als Pause (IQ24).
- **Shuffle:** derselbe Ablauf mit einem Zufalls-Seed. Er mischt die Reihenfolge bei Gleichstand und die Wahl unter den gleich guten Slots. Der Seed wird im Plan gespeichert, ein Lauf lässt sich also nachvollziehen.
- **Kapselung:** Die Strategie steht hinter einer Schnittstelle, damit sie später austauschbar ist.
- **Testbarkeit:** Sie ist reine Logik ohne Datenbank und deshalb mit PHPUnit testbar.

### 6.3 Gründe, wenn etwas nicht planbar ist

Jeder Prüfling, der nicht planbar ist, bekommt Codes mit Person und Zahl, zum Beispiel:

- `noclerk`: kein Protokollant zugeordnet,
- `trainer_count`: die Option hat nicht genau einen Trainer,
- `absent`: die Person ist im ganzen Zeitraum abwesend,
- `dailylimit`: Tageskontingent ausgeschöpft,
- `busy`: zu jedem freien Slot des Protokollanten ist der Trainer belegt,
- `noslots`: keine Slots im Zeitraum.

Der Bericht zeigt das als Satz, zum Beispiel: „Max Muster: Protokollant Björn ist an allen freien Tagen des Trainers abwesend."

### 6.4 Asynchron und Benachrichtigung

1. „Verteilen" legt einen Plan mit Status `queued` an und startet einen Ad-hoc-Task. Die Seite zeigt: „Berechnung gestartet".
2. Der Task rechnet und setzt den Status auf `ready` oder `failed`.
3. Dann schickt er dem Trainer eine Moodle-Nachricht mit Link zum Plan. Den Nachrichtentyp liefert die Extension (`db/messages.php`).
4. Es wird immer im Hintergrund gerechnet. Die Seite zeigt „Berechnung läuft" und fragt den Status regelmäßig ab (IQ12).

## 7. Vorab-Plan: Ansicht, Ändern, Prüfen

### 7.1 Ansicht

Eine Zeile je Slot: Datum, Uhrzeit, Prüfling, Prüfer, Protokollant und Hinweise. Darüber stehen die Knöpfe „Neu verteilen (Shuffle)", „Bestätigen und buchen" und „Verwerfen". Die nicht planbaren Prüflinge stehen in einem eigenen Block mit ihren Gründen.

### 7.2 Ändern

- **Prüfling** auf einen anderen Slot verschieben oder mit einem anderen Prüfling tauschen. Der Protokollant geht mit, solange der Trainer nichts anderes wählt.
- **Protokollant** eines Eintrags ändern oder zwischen zwei Einträgen tauschen. Erlaubt ist nur, wer in den Quellen der Option steht.
- **Prüfer** bleibt fest, also der Trainer (IQ4).
- **Bedienung:** ein Menü je Eintrag: „Verschieben nach…", „Tauschen mit…", „Protokollant ändern…". Angeboten werden nur gültige Ziele, ungültige erscheinen ausgegraut mit Grund (IQ13).

### 7.3 Prüfung

- **Frontend:** Die Seite lädt einmal eine Verfügbarkeitskarte für den Zeitraum: Abwesenheiten, Belegungen, Tageszähler je Person. Damit markiert sie ungültige Ziele sofort und sagt warum.
- **Backend:** eine Klasse `plan_validator`, die jede Änderung und den ganzen Plan prüft:
  - Überschneidung bei Personen,
  - Abwesenheit,
  - Tageskontingent,
  - bestehende Buchungen,
  - Planungszeitraum,
  - erlaubte Zuordnung (Quelle der Option, Trainer als Prüfer),
  - Kapazität des Slots.
- **Gründe:** Der Validator gibt Codes und Sätze zurück, zum Beispiel: „Der Protokollant ist am gewünschten Termin bereits einer anderen Prüfung zugewiesen."
- **Speichern:** Jede Änderung wird sofort über einen Webservice gespeichert, wenn sie gültig ist. Ist sie ungültig, bleibt der alte Stand, und der Grund erscheint.

## 8. Bestätigen und Buchen

1. „Bestätigen" setzt den Status auf `confirming` und startet einen Ad-hoc-Task.
2. Der Task sperrt alle Personen (`staff_reservation`, aufsteigend nach ID) und prüft den **ganzen** Plan erneut mit dem Validator.
3. **Ein Fehler:** Es wird nichts gebucht. Der Plan geht mit den Gründen je Eintrag zurück auf `ready`, und der Trainer bekommt eine Nachricht.
4. **Alles gültig:** Der Task bucht jeden Prüfling über den vorhandenen Weg „für andere buchen". Dabei schreibt er Slot, Trainer und Protokollant (`bookingextension_examplanning_staff`, `runid` = Plan, IQ19) und setzt den Status auf `booked`.
5. Danach gehen die Nachrichten raus:
   - an den Trainer: Bestätigung,
   - an jeden Protokollanten: Liste seiner Termine mit Datum, Zeit, Prüfling und Prüfer.
6. **Alles oder nichts:** Bricht das Buchen mittendrin ab, wird das bereits Gebuchte dieses Laufs zurückgenommen. Rücknahme über `runid` wie im alten Schritt 14.
7. **Rücknahme durch den Trainer (IQ15):** „Plan zurücknehmen" mit Rückfrage storniert alle Buchungen dieses Laufs (`runid`). Die Protokollanten werden benachrichtigt, danach kann neu verteilt werden.

## 9. Andockstellen im Booking-Core

Der Core bekommt nur den Haken aus P3. Alles andere nutzt vorhandene Andockstellen.

| Bedarf | Wie |
|---|---|
| Selbstbuchung in geplanten Optionen sperren | **Erledigt (P3):** `bookingrestriction_interface`, Sammler `bookingworkflow\bookingrestriction`, Bedingung `extensionrestriction` (121). |
| Optionsfeld, Einstellungen, Anzeige auf der Optionsseite, Aktion in der Optionsliste | vorhandene `bookingextension_interface` |
| Trainer-Person anlegen | Ereignis `\mod_booking\event\teacher_added`, Beobachter in der Extension (erledigt, P4) |
| Buchen für andere mit vorgegebenem Slot | zu prüfen in P12: vorhandener Weg „für andere buchen" mit den Slot-Daten der Antwort; Zuweisungen schreibt die Extension in ihre eigene Tabelle |
| Belegungen im Abwesenheitskalender | **Entities** fragt Belegungen bisher nur bei `mod`/`local`-Plugins ab; künftig auch bei Booking-Extensions (kleine Änderung in Entities, P8) |

## 10. Was aus der Sicherung `iqsh-2602-wip-backup` in die Extension kommt

| Aus dem WIP | In der Extension |
|---|---|
| `staff_unavailability`, `entities_compat` | Abwesenheitsprüfung von Prüfer und Protokollant über Entities |
| `booking_slot_staff`, `staff_assignment_store` | eigene Tabelle der Zuweisungen (Prüfling, Slot, Prüfer, Protokollant, Lauf) |
| `staff_availability` | Prüfung „frei?": Abwesenheit, Belegung in allen Optionen, Lehrtermine des Trainers, Tageskontingent, Planungszeitraum |
| `staff_reservation` | Sperren je Person beim Bestätigen |
| `staff_occupation` | Belegungen für den Abwesenheitskalender |
| `candidate_resolver` | Protokollanten aus Pools und Einzelpersonen |
| Rollen, Rollen-Seite, Dialog-Auswahl, `role_assignment` | **entfällt**: feste Rollen Prüfer (Trainer) und Protokollant, kein Selbstbuchen |

## 11. Schritte

| Nr | Schritt | Plugin | Sichtbar | Stand |
|---|---|---|---|---|
| P1 | Planungszeitraum: Einstellung, Kalender beschränkt, Server lehnt ab | Entities | ja | abgenommen 2026-10-28 |
| P2 | Maximale Slots pro Tag je Person, Standard zentral (2) | Entities | ja | abgenommen 2026-10-28 |
| P3 | Extension-Gerüst, Optionsfeld „Vom Trainer geplant", Selbstbuchung gesperrt (Core-Haken) | Extension + Core-Haken | ja | abgenommen 2026-10-28 |
| P4 | Trainer als Person (Beobachter, Nachholen), Prüfung „genau ein Trainer" | Extension | ja | abgenommen 2026-10-28 |
| P5 | Protokollanten-Quellen je Option (Pools, Personen), Kandidaten ohne den Prüfer | Extension | ja | abgenommen 2026-10-28 |
| P6 | Zuweisungsseite Prüfling → Protokollant | Extension | ja | abgenommen 2026-10-28 |
| P7 | Sichtbarkeit (IQ18): aufgeteilt, Kalender-Belegung in P8, Ansicht des Prüflings in P13 | Extension | – | entfällt als eigener Schritt |
| P8 | Verfügbarkeit in der Extension: Abwesenheit, Belegungen, Lehrtermine, Tageskontingent, Zeitraum; Belegungen im Abwesenheitskalender mit Prüfling, Rolle, Option und Link (IQ18, kleine Entities-Änderung) | Extension + Entities | teilweise | abgenommen 2026-11-03 |
| P9 | Verteilungs-Logik (Strategie, Gründe) als reine Klassen mit Tests | Extension | nein | abgenommen 2026-11-03 |
| P10 | Plan-Tabellen, Ad-hoc-Task, Nachricht an den Trainer, Plan-Ansicht | Extension | ja | abgenommen 2026-11-03 |
| P11 | Ändern: Verschieben, Tauschen, Protokollant ändern; `plan_validator`, Webservice, Prüfung im Frontend | Extension | ja | abgenommen 2026-11-03 |
| P12 | Shuffle | Extension | ja | abgenommen 2026-11-03 |
| P13 | Bestätigen: neu prüfen, sperren, buchen, Rücknahme bei Abbruch, Nachrichten; Prüfling sieht danach Termin und Protokollant auf der Optionsseite (IQ18) | Extension | ja | abgenommen 2026-11-03 |
| P14 | Plan zurücknehmen (Storno über Lauf, Nachricht an Protokollanten) | Extension | ja | abgenommen 2026-11-04 |
| P15 | Gesamttest, Doku, Testdaten | beide | ja | **nächster Schritt** |

Jeder Schritt läuft wie bisher: besprechen, einigen, umsetzen, prüfen (Lint, phpcs, PHPUnit), Sichtprüfung durch Georg.
Neue Dateien der Extension schreibt Claude direkt; Änderungen am Booking-Core gibt Claude als Text, Georg baut sie ein.

## 12. Offene Fragen

**Offen für später (2026-11-03):** Die Nachrichten der Extension (Plan fertig, Buchung, Rücknahme) sollen über die Booking-Regeln (booking rules) verschickt werden statt über eigene Moodle-Nachrichten. Wird später geklärt; bis dahin bleibt `db/messages.php` mit `planready`.

Alle Fragen F-E1 bis F-X8 sind am 2026-10-28 entschieden, siehe IQ7–IQ16 in Abschnitt 2.

Noch zu klären bei der Umsetzung:
- P3: Wie genau die Extension die Selbstbuchung sperrt, also über eine vorhandene Andockstelle oder einen kleinen Haken im Core.
- P12: Ob „für andere buchen" Slot und Personen schon annimmt.
