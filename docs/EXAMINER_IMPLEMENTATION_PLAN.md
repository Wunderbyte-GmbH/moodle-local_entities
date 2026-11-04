# Examiner-Verteilung — Umsetzungsplan (local_entities + mod_booking)

> **Achtung, 2026-10-28:** Neue Kundenanforderungen IQSH. Die Schritte 12b, 13–15 und 18 werden ersetzt durch
> `IQSH_EXAM_PLANNING_PLAN.md`. Neu ist: Prüfer = Trainer, eine Zuweisungsseite, eine Vorab-Verteilung und eine eigene
> `bookingextension`. Die abgenommenen Schritte 1–12c bleiben die Grundlage.

> **Stand:** 2026-10-20. Dieses Dokument ersetzt den Plan `EXAMINER_ENTITIES_IMPLEMENTATION_PLAN_2026-10-13.md`,
> der als nicht eingechecktes Dokument in `mod/booking/docs/blueprints` beim Wechsel des Booking-Branches verloren
> ging. Es ist aus dem Arbeitsverlauf neu aufgebaut: Der Fortschritt und die Entscheidungen sind vollständig, die
> ausführlichen Einzelprotokolle der Schritte 1–5 sind verkürzt.
> **Zielarchitektur:** `EXAMINER_DISTRIBUTION_ARCHITECTURE.md` (Verteilung, Rollen, Pools) und
> `EXAMINER_ENTITIES_ARCHITECTURE_2026-10-13.md` (Personen, Abwesenheiten, Rechte, Datenschutz), beide in diesem
> Ordner.
> **Ticket:** IQSH Slot-Buchung mit Prüfer/innen, Abschnitte A–F (Bezug #1318, #1390, #2320, #2231).

---

## 1. Arbeitsweise

Kein Schritt beginnt vor Abnahme des vorherigen:

1. **Besprechen:** Claude beschreibt Ziel, Ort und Warum. Offene Fragen kommen als Entscheidungsliste.
2. **Einigen:** Georg bestätigt oder ändert den Umfang.
3. **Umsetzen:** Produktivcode in Entities und Booking zeigt Claude als Text, genau eine Änderung pro Nachricht mit
   exaktem Ort und Warum, Georg baut ein und Claude prüft die Datei danach. JavaScript, CSS, Templates, Sprachstrings
   und PHPUnit-Tests schreibt Claude direkt. Rein mechanische Umbenennungen und Lint-Korrekturen macht Claude
   selbst, wenn Georg zustimmt, und sagt es ausdrücklich.
4. **Sichtprüfung:** Georg prüft das Ergebnis auf seiner lokalen Instanz (Klickpfad mit Benutzername und Passwort).
   Behat wird im Umbau nicht eingesetzt.
5. **Abnahme:** Georg bestätigt, Anpassungen werden vor dem nächsten Schritt gemacht.
6. **Commits und Pushes** macht nur Georg. Nicht eingecheckte Dateien gehen beim Wechsel des Branches verloren
   (so geschehen am 2026-10-20), deshalb liegen Dokumente jetzt im Plugin Entities.

Qualitätsregeln: Klassen normal bis 900 Zeilen (Warnung ab 600), keine God-Klassen, Design Patterns gezielt.
`local_entities\entities` (~1057 Zeilen) und `slot_availability` (~1600 Zeilen) sind Legacy und bekommen keine neue
Logik. Prüfung vor jeder Abnahme: phpcs (Moodle-Standard), ESLint, Stylelint, `php -l`, Zeilenlänge ≤ 132, PHPUnit.

## 2. Fortschritt

| Schritt | Titel | Plugin | Sichtbar | Status |
|---|---|---|---|---|
| 1 | Person als Entity anlegen | Entities | ja | abgenommen 2026-10-13 |
| 2 | Personen- und Abwesenheits-API | Entities | nein | abgenommen 2026-10-13 |
| 3 | Abwesenheiten-Seite (Kalender, Serien, Konfliktschutz, 3a–3g) | Entities | ja | abgenommen 2026-10-19 |
| 4 | Sicherheit und Datenschutz (Sichtbarkeit von Personen, Provider, Löschen von Benutzern) | Entities | ja | abgenommen 2026-10-19 |
| 5 | Ist-Stand-Tests Booking (`slot_examiner_baseline_test`, 10 Tests) | Booking | nein | abgenommen 2026-10-19 |
| 6 | Migration von `booking_teacher_unavailability` | Booking | — | **entfällt** (2026-10-20): Entwicklung, es gibt keine Abwesenheiten |
| 7 | Booking auf Entities umstellen, Gate, Link statt Seite | Booking | ja | abgenommen 2026-10-20 |
| 8 | **Pools** in Entities (Tabellen, Repository, Resolver, Zugriff, Fassade, Datenschutz) | Entities | nein | fertig 2026-10-20 |
| 9 | **Pool-Oberfläche** (Tab „Pools", Dialog, Filter, Pool-Marken in der Personenliste, Feld „Pools" an der Person) | Entities | ja | fertig 2026-10-20 |
| 10 | `booking_slot_staff` mit Rolle, Herkunft, Lauf; `staff_availability` | Booking | teilweise | offen, **nächster Schritt** |
| 11 | `staff_reservation` (Sperren, Neuprüfung), Umstellung Buchungsdialog und `slot_mover` | Booking | nein | abgenommen 2026-10-20 (Buchen, Option-übergreifende Belegung, Verschieben auf freien Termin, Ablehnung bei Abwesenheit per Sichtprüfung belegt) |
| 11b | **Prüferwahl beim Umbuchen**: Umbuchen-Fenster zeigt je Zieltermin die freien Prüfer des Pools, schlägt den bisherigen vor, meldet nicht verfügbare namentlich, Fehler direkt im Fenster | Booking | ja | abgenommen 2026-10-20 |
| 12 | **Generische Ressourcen-Rollen** (Entscheidung 2026-10-21, siehe Verteilungs-Architektur 5a): 12.1 Entities-API, 12.2 Zuweisung auf Entity-IDs, 12.3 Tabellen + Resolver + Upgrade, 12.4 Rollen-Editor im Optionsformular, 12.5 Verfügbarkeit mit Rollen (fertig; die Buchung nutzt bis 12b nur die erste Rolle der Option) | beide | ja | 12.1 bis 12.5 fertig, getestet mit einem und zwei Prüfern |
| 12b | Buchungsdialog und Umbuchen je Rolle (Icon, Bezeichnung, je Rolle eigene Auswahl); **Auswahl mehrerer Personen verständlicher** (Georg 2026-10-21: die Mehrfachauswahl-Liste ist nicht intuitiv; Vorschlag: Hinweis „1 von 2 gewählt" und Häkchenliste statt Listenfeld) | Booking | ja | 12b.1 bis 12b.5 fertig, Buchungsdialog von Georg abgenommen (2026-10-21); 12b.6 Umbuchen und Sammelbuchung **nächster Schritt**; Feinschliff der Optik als Punkt 19 |
| 12c | **Abwesenheitsansicht mit Belegungen** (Georg 2026-10-21, vor der Verteilung): 12c.1 Tagesansicht, 12c.2 Belegungen schreibgeschützt im Kalender (Kurs/Option, Zeit, Link), 12c.3 Booking meldet Zuweisungen (`booking_slot_staff`) und Termine als Belegung, 12c.4 Konfliktschutz: Belegung ist nicht blockierbar (Oberfläche und Server), 12c.5 **Globale Sperre je Entity-Typ** (Georg 2026-10-21): Einstellung in Entities, je Typ ein Schalter „Selbstbearbeitung sperren"; ist er an, können nur Inhaber von `manageunavailability` Abwesenheiten dieses Typs ändern, alle anderen sehen den Kalender nur lesend mit Hinweis „gesperrt"; 12c.6 Tests und Abnahme. **Entschieden (Georg 2026-10-21):** eine Belegung zeigt Zeit, Kurs, Option und Rolle, nie die Teilnehmer; der Link führt zur Seite der Buchungsoption (ohne Recht dafür kein Link); trifft eine Abwesenheitsserie auf eine Belegung, wird die ganze Serie abgelehnt und die Meldung nennt die belegten Tage; Belegungen sieht jeder, der den Kalender der Entity sieht | beide | ja | abgenommen 2026-10-21 (12c.1–12c.7). Ganztags-Klick über einer Belegung wird nicht in der Oberfläche blockiert, der Server lehnt beim Speichern ab (Georg: so lassen) |
| 13 | Verteilung: `term_provider`, `staff_load`, Strategie, `distribution_run`, `distribution_result` | Booking | nein | offen, **nächster Schritt** |
| 14 | Lauf speichern, Bericht, Knopf „Prüfungsteilnehmer automatisch zuweisen", Rücknahme | Booking | ja | offen |
| 15 | Ticket-Punkte A, B, D, E (einzeln) | Booking | ja | offen |
| 16 | Aufräumen, Doku, Testprotokoll, Altspalten und -tabellen im Folgerelease entfernen | beide | — | offen |
| 17 | (aufgegangen in 12c, Entscheidung Georg 2026-10-21: vor der Verteilung) | — | — | siehe 12c |
| 18 | **Vorbelegte Rollen im Kurs** (Georg 2026-10-21, ganz hinten): Admin legt im Kurs Zuordnungen vorab fest, z. B. Prüfer X für Teilnehmer Y, ohne die übrigen Rollen (Protokolant) zu füllen; die Verteilung (13) und der Dialog (12b) füllen nur die freien Rollen auf. Umfang und Entscheidungen werden erst besprochen, wenn wir dort ankommen | Booking | ja | zurückgestellt, nach 16 |
| 19 | **Rollenauswahl im Buchungsdialog optisch verbessern** (Georg 2026-10-21, ganz hinten): die Häkchenliste je Rolle ist funktional und hinnehmbar, aber noch nicht schön; Gestaltung wird besprochen, wenn wir dort ankommen | Booking | ja | zurückgestellt, nach 18 |
| — | Zurückgestellt: wiederkehrende Zeiten, Grund-Feld in der Oberfläche | Entities | — | zurückgestellt |

Abhängigkeiten: 1 → 2 → 3; 2 → 4; 5 vor 7; 8 → 9; 8 → 12; 10 → 11 → 13; 12 und 13 → 14. Die Verteilung (Schritt 13)
braucht die Antwort auf F8 (feste Zuweisungen) aus der Verteilungs-Architektur.

## 3. Schritt 7 — Booking auf Entities umstellen

**Ziel:** Booking liest die Abwesenheiten der Prüfer aus Entities. Die alte Abwesenheiten-Seite von Booking entfällt
und wird durch einen Link auf die Seite in Entities ersetzt.

**Orte (Stand Branch `iqsh-2602`, 2026-10-20):**
- `classes/local/slotbooking/slot_availability.php`, `get_available_teacher_ids` (~Zeile 1585): statt der SQL-Abfrage auf
  `booking_teacher_unavailability` die Abwesenheiten über `local_entities\unavailability::get_unavailable_userids`
  (eine Abfrage für alle Prüfer) holen. Bereich: überall plus `mod_booking/booking/<Instanz>` plus
  `mod_booking/option/<Option>`. Die Entities-Oberfläche pflegt heute nur „überall" (D21).
- `report.php` (~Zeile 1119) und `classes/local/bookingstracker/report2_header_links.php`: Knopf „Examiner unavailability"
  verweist auf `/local/entities/unavailability.php` statt auf `teacherunavailability.php`.
- Entfernen: `teacherunavailability.php`, `classes/form/teacherunavailability_form.php`,
  `amd/src/teacherUnavailability.js` (und Build), zugehörige Zeilen in `tests/capabilities/option_editing_capabilities_test.php`
  und `tests/bookingstracker/report2_header_links_test.php`.
- `classes/privacy/provider.php`: Verweise auf `booking_teacher_unavailability` bleiben **bis Schritt 16** (Entscheidung
  2026-10-20): Solange die Tabelle besteht, kann sie auf anderen Installationen Daten halten, und die müssen bei
  Auskunft und Löschung weiter erfasst werden. Provider, Lang-Strings und Provider-Test werden zusammen mit der
  Tabelle entfernt.
- **Gate** (neue Klasse `entities_compat`): Ist `local_entities` mit Personen-API vorhanden? Nur dann gibt es die
  Prüfer-Funktion („Prüfer zu Slots hinzufügen"). Slot-Buchung ohne Prüfer läuft ohne Entities. `mod_booking`
  bekommt `local_entities` **nicht** in `dependencies`.
- Die Tests aus Schritt 5 sind die Absicherung. Wo sie rot werden, weil die Abwesenheit jetzt aus Entities kommt,
  stellen wir die Test-Vorbereitung um (Abwesenheit über `local_entities\unavailability` anlegen), nicht die
  Erwartung.

**Sichtprüfung:** Als Prüfer eine Abwesenheit in Entities setzen → als Studierende/r im Slot-Dialog ist der Prüfer nicht
wählbar. Der Knopf im Bericht führt auf die Entities-Seite. Ohne Entities: Schalter fehlt, Slot-Buchung ohne Prüfer
geht.

**PHPUnit:** Baseline-Tests (umgestellt), Gate-Test (mit und ohne Entities), Header-Links-Test, Provider-Test.

## 4. Offene Fragen (Verteilung)

Siehe Abschnitt 12 der Verteilungs-Architektur. F1–F4, F6 (keine Pufferzeiten für Personen) und F7 sind entschieden. Offen und rechtzeitig vor dem
jeweiligen Schritt zu klären: F5 (Gruppenprüfungen), F8 (feste Zuweisungen
bevorzugt?), F9 (Benachrichtigung).
Weitere offene Punkte: O8 (Serie über vorhandenen Einzelblöcken erzeugt Überlappungen), O9 (FullCalendar-Sprachdateien),
O10 (nur `edit` kann eine Person anlegen, sieht sie dann nicht), die leere Rechteprüfung in
`edit_dynamic_form::check_access_for_dynamic_submission` (Entities, bestehend), Booking-Optionen die ein Aufrufer
ohne Recht nicht sieht, werden vom Konflikttest übersehen (Schritt 10).

## 5. Fortschritts-Log

| Datum | Schritt | Ergebnis |
|---|---|---|
| 2026-10-13 | 1, 2 | Person als Entity-Typ (`userid`), `persons`, `local_entities_unavailability`, API `get_unavailable_userids`, `get_blocks`, `replace_in_window`. Abgenommen. |
| 2026-10-14 | 3 | Umbau der Abwesenheiten in kleine Klassen (`scope`, `scope_filter`, `time_range`, `range_merger`, `series_rule`, `block_repository`, `series_repository`, `conflict_detector`, Fassade `unavailability`); Rechtemodell D19, Nutzermenü D20. |
| 2026-10-16 | 3 | Kalender-Oberfläche (Ziehen, Klicken, Serien, Speichern als Änderungsliste, Konfliktmeldung), durchsuchbare Entity-Auswahl, ISO-Wochen. Entscheidungen D21–D24. |
| 2026-10-19 | 3, 4 | Schritt 3 und 4 abgenommen. Sichtbarkeit von Personen (`person_visibility`), Datenschutz (`privacy\user_data`, `provider`, `observer`), Entscheidungen D25–D27. JS-Logik hat Node-Tests mit Loader in `tests/js`. |
| 2026-10-19 | 5 | `slot_examiner_baseline_test` (10 Tests): Abwesenheit nimmt den Prüfer aus dem Angebot, belegt ist, wer in einer nicht stornierten Buchung mit überlappender Zeit steht (auch andere Option), **alle** Zugewiesenen müssen frei sein (heutige Antwort auf O1), ohne Auswahl braucht der Slot `teachers_required` freie Prüfer. |
| 2026-10-19 | Konzept | IQSH-Konzept „Automatisierte Zuweisung von Prüfern und Protokollanten" → Verteilungs-Architektur. F1–F4 entschieden. |
| 2026-10-20 | 8, 9 | Pools (Entscheidungen: eigene Pools in Entities, Recht `manageunavailability`, Pool-Typ = Entity-Typ der Mitglieder, heute `person`, Tab „Pools" statt Eintrag in der Entities-Liste). Version 2026102002. Lint sauber, alle Entities-Tests grün. |
| 2026-10-20 | 6 | Entfällt, weil in der Entwicklung keine Abwesenheiten vorhanden sind. |
| 2026-10-20 | 5 | Nach Verlust der nicht eingecheckten Dateien beim Branch-Wechsel aus dem Arbeitsverlauf wiederhergestellt; auf `iqsh-2602` weiter grün (10 Tests, 43 Prüfungen). |
| 2026-10-20 | 7 | `entities_compat::has_person_support`, `slotbooking\teacher_unavailability` (`find_unavailable_userids`, `get_page_url`), `slot_availability` liest Abwesenheiten aus Entities, Link in `report.php` und Tracker auf `/local/entities/unavailability.php`, alte Seite samt Formular und JS entfernt. Baseline-Tests (Vorbereitung auf Entities umgestellt, Gegenprobe rot), neuer `teacher_unavailability_test`, Header-Links- und Capability-Test angepasst. Provider bleibt bis Schritt 16. |
| 2026-10-20 | 10 | Namen rollenneutral: Tabelle `booking_slot_staff` (Felder `userid` = Buchende, `staffid` = zugewiesene Person, `role` als freier Schlüssel), Klasse `slotbooking\staff_unavailability` (vorher `teacher_unavailability`). Alte Namen (`booking_slot_student_teacher`, `teacher_pool`, `teachers_required`) bleiben bis Schritt 12. |
| 2026-10-20 | 10 | Tabelle `booking_slot_staff` (Upgrade 2026102001, Rückfüllung 2026102002). `staff_assignment_store::sync_answer` gleicht Zeilen mit dem JSON ab (Rolle, Herkunft, Lauf bleiben beim Verschieben erhalten), Aufrufe in `booking_option::after_successful_booking_routine` und `slot_mover` (Freigeben, Verschieben). `staff_availability` (`free_among`, `is_free`, `find_busy_staff`) ersetzt `get_busy_teacher_ids`: eine Abfrage statt JSON-Suche, jede Rolle zählt als belegt, keine Pufferzeiten (F6). Alle Slot-Tests grün, Gegenprobe rot. Fixtures in Baseline- und Selection-Test füllen die Tabelle wie der Buchungsweg. |
| 2026-10-20 | 10 | Korrektur: Abgleich der Tabelle jetzt in `booking_option::write_user_answer_to_db` (jede Antwort, auch Warenkorb-Reservierung), nicht mehr in der Nachbereitung, die bei Reservierungen früh zurückkehrt. Test: Reservierte und Wartelisten-Buchungen halten ihre Personen. |
| 2026-10-20 | 11 | `slotbooking\staff_reservation`: sperrt Personen je ID aufsteigend (Datei-/DB-Sperre der Website, injizierbar für Tests), prüft unter der Sperre erneut (`assert_free` über `staff_availability`), gibt im Destruktor frei. Angeschlossen in `booking_option::write_user_answer_to_db` (gilt für Buchung, Reservierung, Update) und `slot_mover::move_validated`. **Verhaltensänderung:** Beim Verschieben wird abgelehnt, wenn die mitgenommene Person am neuen Termin belegt oder abwesend ist (bisher stillschweigend doppelt gebucht). Tests: `staff_reservation_test` (8), `staff_booking_flow_test` (6, echter Buchungsweg, Gegenproben rot). Echte Parallelität nicht per PHPUnit prüfbar. |
| 2026-10-20 | 11 | Sichtprüfung mit Testdaten (Kurs „abschluss", Instanz 129, Optionen 5166/5167, `e2e_*`-Benutzer, Abwesenheit Prüfer Eins Do 22.10. 11:00): Prüfer wird in der zweiten Option nicht angeboten, Verschieben auf freien Termin geht, auf den Abwesenheitstermin wird abgelehnt. Rückmeldung Georg: Fehler muss für Nutzer anders abgefangen werden. |
| 2026-10-20 | 11b | `slot_availability::get_available_teachers_for_slot` (+`$excludeanswerid`), `slot_mover` (Zielslots mit freien Prüfern, `currentteachers`, `teachersrequired`, Auswahl in `move`/`move_self`/`move_validated`/`commit_pending_move`), neue Klasse `staff_selection` (bereinigen, Anzahl und Pool/Frei-Prüfung), `slot_update_service::plan/apply` (+`$teacherselection`, `staffproblems`; Warenkorb-Weg speichert die Auswahl im JSON von `newslots`), `slotupdate_form`, `slotUpdate.js` (+Build), String `slot_update_staff_unavailable` (en, de). Tests: `staff_booking_flow_test` 10. Bekannte Grenze: je Zielslot eine Abfrage der freien Prüfer (wie im Buchungsdialog), bei sehr vielen Slots später mit `preload` bündeln. |
| 2026-10-21 | 12 | Vorgabe Georg: vollständig generische, erweiterbare Rollen/Ressourcen (Entity-Typen frei je Option, Bezeichnung und Icon, Quellen direkt oder über Pools, Entities pluginübergreifend). Entschieden: Zuweisung auf Entity-IDs; Typen person + equipment; Konfiguration je Option; Schritt 12 in zwei Teilen (12 Modell/Formular/Entity-IDs, 12b Dialog). Entwurf in Verteilungs-Architektur Abschnitt 5a. 11b von Georg abgenommen (2026-10-20). |
| 2026-10-21 | 12c | Abwesenheitsansicht mit Belegungen: Tages-, Wochen- und Monatsansicht (Klick auf Tag oder Titel wechselt die Ansicht). Booking meldet Zuweisungen über den Pull-Provider `return_array_of_resource_dates(int $entityid)` an `mod_booking\local\slotbooking\staff_occupation` (aus `booking_slot_staff` und aktiven `booking_answers`; Name „Rolle · Option · Kurs", Link zur Optionsseite, ohne Recht „Rolle · verborgen" und kein Link; unbekannte Altrolle fällt auf die erste Rolle der Option zurück). `entities::get_all_dates_for_entity` mischt die Belegungen live dazu (nicht im Cache). `get_unavailability` liefert `occupations`; `conflict_detector` und `unavailability_saver` lehnen Abwesenheiten, auch Serien, über Belegungen ab und nennen die Tage. Sperre der Selbstbearbeitung je Typ (`unavailabilitylock_person/equipment/location`, `unavailability_access::can_view_entity`/`can_manage_entity`), Seite dann schreibgeschützt mit Hinweis. Belegungen violett. Tests: `staff_occupation_test` (7), `unavailability_access_test` (12), Reader, Saver und Page erweitert. Abgenommen. |
