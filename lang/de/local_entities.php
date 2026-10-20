<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     local_entities
 * @category    string
 * @copyright   2021 Wunderbyte GmbH <info@wunderbyte.at>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['activeviewtemplate'] = 'Detailansicht-Template';
$string['activeviewtemplate:description'] = 'Das Layout für jede Entity-Detailseite. Manager können es auch direkt auf einer Detailseite umschalten und speichern.';
$string['activeviewtemplatefortype'] = 'Detailansicht-Template – {$a}';
$string['activeviewtemplatefortype:description'] = 'Template für „{$a}"-Entities. Auf globalem Standard belassen, um zu erben.';
$string['addcategory'] = 'Kategorie hinzufügen';
$string['addentity'] = 'Entity hinzufügen';
$string['addopeninghours'] = 'Öffnungszeiten hinzufügen';
$string['addperson'] = 'Person hinzufügen';
$string['address'] = 'Adresse';
$string['address_city'] = 'Stadt';
$string['address_country'] = 'Land';
$string['address_entrance'] = 'Stiege';
$string['address_floor'] = 'Stockwerk';
$string['address_map_embed'] = 'Karte einbetten (HTML)';
$string['address_map_link'] = 'Karten-Link';
$string['address_postcode'] = 'Postleitzahl';
$string['address_streetname'] = 'Straßenname';
$string['address_streetnumber'] = 'Haus-Nr.';
$string['affiliated'] = 'Zugehörige Orte';
$string['allocationmode'] = 'Buchungsmodus';
$string['allocationmode_capacity'] = 'Kapazität (Summe der Teilnehmer)';
$string['allocationmode_exclusive'] = 'Exklusive Ressource (pro Reservierung belegt)';
$string['allocationmode_help'] = 'Exklusive Ressource: Die Entity ist pro Reservierung belegt, unabhängig von der Teilnehmerzahl (z. B. ein Raum oder Platz, der zu einem Zeitpunkt nur einmal genutzt werden kann). Die erlaubte Anzahl gleichzeitiger Reservierungen wird unter „Maximale gleichzeitige Reservierungen" festgelegt.

Kapazität: Überlappende Buchungen sind erlaubt, solange die Summe der Teilnehmer „Maximale Anzahl möglicher Buchungen" nicht übersteigt.

Keine (Standard): Überschneidungen auf dieser Entity werden gar nicht geprüft.';
$string['allocationmode_none'] = 'Keine Überschneidungsprüfung';
$string['availableinsublocations'] = 'In Unterorten verfügbar';
$string['availableinsublocations_help'] = 'Nur für Equipment. Wenn aktiviert, wird dieser Gegenstand auch für Buchungen von Unterorten (Nachfahren seines Heimat-Orts) angeboten, nicht nur am Heimat-Ort selbst.';
$string['backtolist'] = 'Zurück zum Entity-Manager';
$string['belongs_to'] = 'Gehört zu';
$string['cachedef_cachedentities'] = 'Cache um Entities zu speichern';
$string['cachedef_entitydates'] = 'Cache um Entity-Daten zu speichern';
$string['cachedef_geocode'] = 'Cache um Geocoding-Daten zu speichern';
$string['calendar'] = 'Kalender';
$string['calendarfirstday'] = 'Kalender: Erster Tag der Woche';
$string['calendarfirstday:description'] = 'Wochentag, mit dem der Entity-Kalender die Woche beginnt (Vollansicht und Kalender auf der Detailseite).';
$string['calendartimeformat'] = 'Kalender: Zeitformat';
$string['calendartimeformat12'] = '12-Stunden (am/pm)';
$string['calendartimeformat24'] = '24-Stunden';
$string['calendartimeformat:description'] = 'Zeitformat für Termine und Zeitachse im Entity-Kalender.';
$string['cancelled'] = 'ABGESAGT';
$string['capacitysource'] = 'Belegung gezählt nach';
$string['capacitysource_help'] = 'Nur im Kapazitäts-Modus. Legt fest, wie viel eine Buchung von dieser Entity verbraucht:

Teilnehmer (max. Buchungen): Die Teilnehmer-Obergrenze der Option wird gegen die Kapazität gezählt (z. B. Personen im Raum, ein Bungee-Seil pro Person).

Manuelle Stückzahl: Eine explizit eingegebene Zahl wird gezählt (z. B. Beamer, Tandem) — unabhängig von der Teilnehmerzahl.';
$string['capacitysource_manual'] = 'Manuelle Stückzahl';
$string['capacitysource_maxanswers'] = 'Teilnehmer (max. Buchungen)';
$string['categories'] = 'Entity Kategorien';
$string['conflictcapacity'] = 'Nicht genügend freie Kapazität für „{$a->name}" zu den gewünschten Zeiten: angefragt wurden {$a->requested}, die Gesamtkapazität beträgt aber {$a->capacity}. Bereits belegt durch diese überschneidenden Buchungen:';
$string['conflictexclusive'] = 'Die Ressource „{$a}" ist zu den gewünschten Zeiten bereits gebucht und kann nicht doppelt gebucht werden. Konfliktbuchungen:';
$string['conflictingshortnames'] = 'Es gibt einen Konflikt zwischen den verwendenten Kurznamen der benutzerdefinierten Felder und der Tabellennamen. Bitte ändern Sie diesen Kurznamen: {$a}';
$string['contacts'] = 'Kontakte';
$string['contacts_givenname'] = 'Vorname';
$string['contacts_mail'] = 'E-Mail';
$string['contacts_surname'] = 'Nachname';
$string['daysofweek'] = 'Wochentage';
$string['delete'] = 'Löschen';
$string['deleteentity'] = 'Entity löschen!';
$string['deleteentityconfirm'] = 'Wollen Sie die Entity wirklich löschen?';
$string['deleteopeninghours'] = 'Öffnungszeiten löschen';
$string['edit'] = 'Bearbeiten';
$string['edit_details'] = 'Details bearbeiten';
$string['edit_entity'] = 'Entity bearbeiten';
$string['edit_image'] = 'Bild auswählen';
$string['endhours'] = 'Ende hh';
$string['endminutes'] = 'Ende mm';
$string['entities:changeviewtemplate'] = 'Template der Entity-Detailansicht ändern';
$string['entities:copymodule'] = 'Entities: Modul kopieren';
$string['entities:delete'] = 'Nutzer:in darf Entities löschen';
$string['entities:edit'] = 'Nutzer:in darf Entities editieren';
$string['entities:manageunavailability'] = 'Nutzer:in darf die Abwesenheiten aller Entities verwalten (Personen, Orte, Ausstattung)';
$string['entities:view'] = 'Nutzer:in darf Entities sehen';
$string['entitieslist'] = 'Entity Liste';
$string['entity_category'] = 'Entity-Kategorie';
$string['entity_description'] = 'Entity-Beschreibung';
$string['entity_name'] = 'Entity-Name';
$string['entity_openinghours'] = 'Öffnungszeiten';
$string['entity_order'] = 'Sortier-Reihenfolge';
$string['entity_parent'] = 'Entity-Parent';
$string['entity_title'] = 'Entity';
$string['entitylist'] = 'Entity Liste';
$string['entitysetup_heading'] = 'Entity erstellen oder bearbeiten';
$string['entitytype'] = 'Entity-Typ';
$string['entitytype_equipment'] = 'Equipment / Ressource';
$string['entitytype_help'] = 'Ort: Räume und Plätze in der Standort-Hierarchie, die in Raum-Auswahlen angeboten werden. Equipment: buchbare Ressourcen (z. B. Beamer, Bälle), die über parentid unter einem Ort hängen; sie werden zusätzlich zum Raum angeboten, wenn ein Ort gewählt wird. Person: ein Moodle-Benutzer (z. B. Prüfer/in), dessen Verfügbarkeit hier verwaltet wird; Personen werden nie in Raum-Auswahlen angeboten.';
$string['entitytype_location'] = 'Ort';
$string['entitytype_person'] = 'Person';
$string['equipmentquantity'] = 'Anzahl: {$a}';
$string['er_entitiesname'] = 'Entity';
$string['er_placeholder'] = '... suche';
$string['error:entitydoesnotexist'] = 'FEHLER: Die Entity existiert nicht!';
$string['error:invalidparent'] = 'Die gewählte übergeordnete Entity existiert nicht.';
$string['error:parentcycle'] = 'Eine Entity kann nicht unter sich selbst verschoben werden: Die gewählte übergeordnete Entity ist die Entity selbst oder eine ihrer Unter-Entities.';
$string['error:personalreadyexists'] = 'Dieser Benutzer ist bereits als Person angelegt.';
$string['error:personuserrequired'] = 'Bitte wählen Sie einen vorhandenen Moodle-Benutzer.';
$string['error:poolmemberwrongtype'] = 'Nur Entities vom Typ des Pools können Mitglied sein.';
$string['error:poolnameexists'] = 'Ein Pool mit diesem Namen existiert bereits.';
$string['error:poolnameinvalid'] = 'Bitte geben Sie einen Namen für den Pool an (bis zu 255 Zeichen).';
$string['error:poolnotfound'] = 'Der Pool existiert nicht.';
$string['error:pooltypeinvalid'] = 'Diese Art von Pool ist nicht verfügbar.';
$string['errorwiththefollowingdates'] = 'Es gibt einen Konflikt mit den folgenden Buchungen:';
$string['examplecsv'] = '<div class="alert alert-info">
    <p><b>Beispiel für eine CSV-Datei:</b></p>
    <p class="text-monospace">
        name;shortname;description<br>
        "Testimport 1";testimport1;"Beschreibung 1"<br>
        "Testimport 2";testimport2;"Beschreibung 2"<br>
        "Testimport 3";testimport3;"Beschreibung 3"<br>
    </p>
</div>';
$string['failedimport'] = 'Es gab ein Problem beim Import.';
$string['fallback_address_parent'] = 'Adresse der übergeordneten Entity als Fallback verwenden';
$string['fallback_address_parent:description'] = 'Wenn die Option gesetzt ist, werden, wenn keine Adressdaten angegeben sind, die Adressdaten der übergeordneten Entity verwendet.';
$string['fallback_contacts_parent'] = 'Kontakte der übergeordneten Entity als Fallback verwenden';
$string['fallback_contacts_parent:description'] = 'Wenn die Option gesetzt ist, werden, wenn keine Kontaktdaten angegeben sind, die Kontaktdaten der übergeordneten Entity verwendet.';
$string['fallback_image_parent'] = 'Bild der übergeordneten Entity als Fallback verwenden';
$string['fallback_image_parent:description'] = 'Wenn die Option gesetzt ist, wird, wenn keine Bild angegeben ist, das Bild der übergeordneten Entity verwendet.';
$string['fieldtemplate'] = 'Feldvorlage';
$string['fieldtemplate_help'] = 'Eine Feldvorlage fügt dieser Entity zusätzliche Felder hinzu (z. B. ergänzt die Equipment-Vorlage Inventarnummer, Zustand …). Mit „Keine" werden keine Zusatzfelder hinzugefügt. Vorlagen werden unter Kategorien verwaltet.';
$string['import'] = 'Entities importieren';
$string['inheritglobal'] = 'Globalen Standard verwenden';
$string['map'] = 'Karte';
$string['maxallocation'] = 'Maximale Anzahl möglicher Buchungen.';
$string['maxallocation_help'] = '0 für kein Limit, -1 bedeutet nicht buchbar';
$string['maxconcurrent'] = 'Maximale gleichzeitige Reservierungen';
$string['maxconcurrent_help'] = 'Nur im exklusiven Modus: wie viele Reservierungen die Entity gleichzeitig belegen dürfen. 1 bedeutet, dass die Entity für einen bestimmten Zeitraum nur einmal gebucht werden kann.';
$string['new_entity'] = 'Neue Entity';
$string['new_person'] = 'Neue Person';
$string['nomapavailable'] = 'Für diese Entity ist kein Standort hinterlegt.';
$string['none'] = 'Keine Entity';
$string['notwithinopeninghours'] = 'Außerhalb der Öffnungszeiten';
$string['opencalendar'] = 'Kalender öffnen';
$string['opencalendarfullsize'] = 'Kalender in Vollansicht öffnen';
$string['openinghours'] = 'Öffnungszeiten';
$string['openmap'] = 'Karte öffnen';
$string['opentimetable'] = 'Öffne Stundenplan';
$string['person_user'] = 'Moodle-Benutzer';
$string['pluginname'] = 'Entity-Manager';
$string['pool_actions'] = 'Aktionen';
$string['pool_add_person'] = 'Personen-Pool anlegen';
$string['pool_deleteconfirm'] = 'Den Pool "{$a}" löschen? Die Personen bleiben bestehen, nur die Gruppe wird entfernt.';
$string['pool_deleted'] = 'Der Pool wurde gelöscht.';
$string['pool_description'] = 'Beschreibung';
$string['pool_edit'] = 'Pool bearbeiten';
$string['pool_members'] = 'Mitglieder';
$string['pool_members_placeholder'] = 'Person suchen';
$string['pool_name'] = 'Name';
$string['pool_none'] = 'Es gibt noch keine Pools.';
$string['pools'] = 'Pools';
$string['pools_intro'] = 'Ein Pool fasst Personen zusammen, zum Beispiel die Prüfer eines Fachs. Ein Pool kann in einer Buchungsoption ausgewählt werden.';
$string['pooltype'] = 'Typ';
$string['pooltype_person'] = 'Personen-Pool';
$string['pricefactor'] = 'Relativer Preisfaktor';
$string['pricefactor_help'] = 'Relativer Preisfaktor: Kann für automatische Preisberechnung (z.B. mit dem Booking-Plugin) verwendet werden';
$string['privacy:metadata'] = 'Der Entity Manager speichert, welche Moodle-Benutzer:innen als Personen verwaltet werden, zum Beispiel Prüfer:innen, wann sie nicht verfügbar sind und wer Entities und Abwesenheiten angelegt oder zuletzt geändert hat.';
$string['privacy:metadata:local_entities'] = 'Entities. Eine Person ist eine Entity, die für eine:n Moodle-Benutzer:in steht.';
$string['privacy:metadata:local_entities:createdby'] = 'Der Benutzer, der die Entity angelegt hat.';
$string['privacy:metadata:local_entities:userid'] = 'Der Moodle-Benutzer, für den eine Person-Entity steht.';
$string['privacy:metadata:local_entities_contacts'] = 'Kontakte einer Entity, zum Beispiel die Ansprechperson eines Raums. Sie sind nicht mit Moodle-Benutzer:innen verknüpft.';
$string['privacy:metadata:local_entities_contacts:givenname'] = 'Der Vorname des Kontakts.';
$string['privacy:metadata:local_entities_contacts:mail'] = 'Die E-Mail-Adresse des Kontakts.';
$string['privacy:metadata:local_entities_contacts:surname'] = 'Der Nachname des Kontakts.';
$string['privacy:metadata:local_entities_pool'] = 'Benannte Gruppen von Personen, zum Beispiel die Prüfer eines Fachs.';
$string['privacy:metadata:local_entities_pool:name'] = 'Der Name des Pools.';
$string['privacy:metadata:local_entities_pool:usermodified'] = 'Der Benutzer, der den Pool angelegt oder zuletzt geändert hat.';
$string['privacy:metadata:local_entities_pool_member'] = 'Welche Personen zu welchem Pool gehören.';
$string['privacy:metadata:local_entities_pool_member:entityid'] = 'Die Person, und damit der Moodle-Benutzer, die zum Pool gehört.';
$string['privacy:metadata:local_entities_pool_member:usermodified'] = 'Der Benutzer, der die Person in den Pool aufgenommen hat.';
$string['privacy:metadata:local_entities_unavail_series'] = 'Wiederkehrende Abwesenheit einer Entity, zum Beispiel jeden Montag.';
$string['privacy:metadata:local_entities_unavail_series:dayend'] = 'Die Uhrzeit, zu der die Abwesenheit endet.';
$string['privacy:metadata:local_entities_unavail_series:daystart'] = 'Die Uhrzeit, zu der die Abwesenheit beginnt.';
$string['privacy:metadata:local_entities_unavail_series:entityid'] = 'Die Entity, die nicht verfügbar ist.';
$string['privacy:metadata:local_entities_unavail_series:reason'] = 'Der Grund der Abwesenheit.';
$string['privacy:metadata:local_entities_unavail_series:usermodified'] = 'Der Benutzer, der die Abwesenheit zuletzt geändert hat.';
$string['privacy:metadata:local_entities_unavail_series:validfrom'] = 'Der erste Tag der wiederkehrenden Abwesenheit.';
$string['privacy:metadata:local_entities_unavail_series:validuntil'] = 'Der letzte Tag der wiederkehrenden Abwesenheit.';
$string['privacy:metadata:local_entities_unavail_series:weekdays'] = 'Die Wochentage der wiederkehrenden Abwesenheit.';
$string['privacy:metadata:local_entities_unavailability'] = 'Die Zeiten, zu denen eine Entity, bei einer Person der Moodle-Benutzer, nicht verfügbar ist.';
$string['privacy:metadata:local_entities_unavailability:endtime'] = 'Die Zeit, zu der die Abwesenheit endet.';
$string['privacy:metadata:local_entities_unavailability:entityid'] = 'Die Entity, die nicht verfügbar ist.';
$string['privacy:metadata:local_entities_unavailability:reason'] = 'Der Grund der Abwesenheit.';
$string['privacy:metadata:local_entities_unavailability:starttime'] = 'Die Zeit, zu der die Abwesenheit beginnt.';
$string['privacy:metadata:local_entities_unavailability:usermodified'] = 'Der Benutzer, der die Abwesenheit zuletzt geändert hat.';
$string['privacychangedpools'] = 'Vom Benutzer angelegte oder geänderte Pools';
$string['privacychangedunavailability'] = 'Vom Benutzer geänderte Abwesenheiten';
$string['privacycreatedentities'] = 'Vom Benutzer angelegte Entities';
$string['privacyentities'] = 'Entities';
$string['privacypoolmemberships'] = 'Pools, zu denen die Person gehört';
$string['privacyunavailability'] = 'Abwesenheiten';
$string['privacyunavailabilityseries'] = 'Wiederkehrende Abwesenheiten';
$string['refreshequipment'] = 'Equipment für den gewählten Ort anzeigen';
$string['saveview'] = 'Als Ansicht speichern';
$string['saveviewfortype'] = 'Als Ansicht für alle „{$a}"-Entities speichern';
$string['show_calendar_on_details_page'] = 'Kalender auf der Detailseite anzeigen';
$string['show_calendar_on_details_page:description'] = 'Wenn die Option gesetzt ist, wird der Kalender direkt auf der Detailseite angezeigt, ansonsten wird ein Link zur Kalenderseite angezeigt';
$string['showpictureinsteadofcalendar'] = 'Zeige Bild anstatt Kalender';
$string['showpictureinsteadofcalendar:description'] = 'Bei allen Entities wird statt des Kalendars das große Bild angezeigt';
$string['starthours'] = 'Start hh';
$string['startminutes'] = 'Start mm';
$string['stdcategories'] = 'Standard-Kategorien';
$string['stdcategories:description'] = 'Standard-Kategorien aus der Liste der Customfield-Kategorien auswählen, die auf allen Bearbeitungsseiten sichtbar sein sollen.';
$string['successfullimport'] = 'Die Datei wurde erfolgreich importiert';
$string['template_equipment'] = 'Equipment';
$string['template_location'] = 'Ort';
$string['timetablemodalbutton'] = 'OK';
$string['timetablemodaltitle'] = 'Entity Timetable';
$string['tplcond_defective'] = 'Defekt';
$string['tplcond_good'] = 'Gut';
$string['tplcond_new'] = 'Neu';
$string['tplcond_used'] = 'Gebraucht';
$string['tplfield_accessible'] = 'Barrierefrei';
$string['tplfield_amenities'] = 'Ausstattung';
$string['tplfield_area'] = 'Fläche (m²)';
$string['tplfield_building'] = 'Gebäude';
$string['tplfield_condition'] = 'Zustand';
$string['tplfield_inventoryno'] = 'Inventarnummer';
$string['tplfield_manufacturer'] = 'Hersteller';
$string['tplfield_model'] = 'Modell';
$string['tplfield_notes'] = 'Hinweise';
$string['tplfield_purchasedate'] = 'Anschaffungsdatum';
$string['tplfield_responsible'] = 'Verantwortlich';
$string['tplfield_roomnumber'] = 'Raumnummer';
$string['tplfield_seats'] = 'Sitzplätze';
$string['tplfield_serial'] = 'Seriennummer';
$string['usecount'] = 'Verwendet von';
$string['unavailability'] = 'Abwesenheiten';
$string['unavailability_absent'] = 'Nicht verfügbar';
$string['unavailability_addseries'] = 'Wiederkehrende Abwesenheit hinzufügen';
$string['unavailability_allday'] = 'Ganztägig';
$string['unavailability_apply'] = 'Übernehmen';
$string['unavailability_conflicts'] = 'Nicht gespeichert. Diese Termine überschneiden sich mit Ihren Änderungen:';
$string['unavailability_delete'] = 'Löschen';
$string['unavailability_deleteconfirm'] = 'Möchten Sie diese Abwesenheit wirklich löschen? Sie wird entfernt, sobald Sie „Speichern“ drücken.';
$string['unavailability_discard'] = 'Änderungen verwerfen';
$string['unavailability_edit'] = 'Abwesenheit bearbeiten';
$string['unavailability_entity'] = 'Entity';
$string['unavailability_from'] = 'Von';
$string['unavailability_help'] = 'Ziehen Sie über einen Zeitraum, um ihn als nicht verfügbar zu markieren. Klicken Sie in der oberen Zeile oder in der Monatsansicht auf einen Tag, um den ganzen Tag zu markieren. Mit einem Klick auf die Zahl eines Tages öffnen Sie dessen Woche. Für regelmäßige Zeiten, z. B. jeden Montag, nutzen Sie „Wiederkehrende Abwesenheit hinzufügen“. Klicken Sie auf einen gespeicherten Eintrag, um ihn zu ändern oder zu löschen. Vergangene Zeit kann nicht geändert werden. Gespeichert wird erst mit „Speichern“.';
$string['unavailability_invalidrange'] = 'Das Ende muss nach dem Beginn liegen, und der Beginn darf nicht in der Vergangenheit liegen.';
$string['unavailability_more'] = 'weitere';
$string['unavailability_notsaved'] = 'Nicht gespeichert';
$string['unavailability_save'] = 'Speichern';
$string['unavailability_saved'] = 'Die Änderungen wurden gespeichert.';
$string['unavailability_series'] = 'Wiederkehrende Abwesenheit';
$string['unavailability_series_badperiod'] = 'Der erste Tag darf nicht in der Vergangenheit liegen, der letzte Tag nicht vor dem ersten, und der Zeitraum darf höchstens 366 Tage dauern.';
$string['unavailability_series_badtime'] = 'Das Ende der Uhrzeit muss nach dem Beginn liegen.';
$string['unavailability_series_belongs'] = 'Dieser Tag gehört zu einer wiederkehrenden Abwesenheit.';
$string['unavailability_series_delete'] = 'Serie ab heute löschen';
$string['unavailability_series_deleteconfirm'] = 'Möchten Sie diese wiederkehrende Abwesenheit wirklich ab heute löschen? Vergangene Tage bleiben bestehen. Sie wird entfernt, sobald Sie „Speichern“ drücken.';
$string['unavailability_series_edit'] = 'Serie bearbeiten';
$string['unavailability_series_end'] = 'Ende (Uhrzeit)';
$string['unavailability_series_firstday'] = 'Erster Tag';
$string['unavailability_series_lastday'] = 'Letzter Tag';
$string['unavailability_series_noweekday'] = 'Wählen Sie mindestens einen Wochentag.';
$string['unavailability_series_start'] = 'Beginn (Uhrzeit)';
$string['unavailability_series_thisday'] = 'Nur diesen Tag löschen';
$string['unavailability_series_weekdays'] = 'Wochentage';
$string['unavailability_unsaved'] = 'Sie haben nicht gespeicherte Änderungen.';
$string['unavailability_until'] = 'Bis';
$string['unavailabilityof'] = 'Abwesenheiten von {$a}';
$string['usesubentitynamesforfilter'] = 'Filter: Namen von Sub-Entities verwenden';
$string['usesubentitynamesforfilter:description'] = 'Filter nutzen standardmäßig den Namen der Parent-Entity.
Wenn Sie dieses Häkchen aktivieren, dann wird jede einzelne Sub-Entity im Filter angezeigt.';
$string['usetreelist'] = 'Neue hierarchische Entities-Liste verwenden';
$string['usetreelist:description'] = 'Zeigt die neue Entities-Übersicht (hierarchischer Baumfilter, Volltextsuche, Pagination) statt der klassischen Liste.';
$string['view'] = 'Ansehen';
$string['viewtemplate'] = 'Ansicht';
$string['viewtemplate_calendar'] = 'Kalender';
$string['viewtemplate_classic'] = 'Klassisch';
$string['viewtemplate_compact'] = 'Kompakt';
$string['viewtemplate_image'] = 'Bild';
$string['viewtemplate_map'] = 'Karte';
$string['viewtemplatepreview'] = 'Vorschau – nicht gespeichert';
