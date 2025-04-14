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

$string['addcategory'] = 'Kategorie hinzufügen';
$string['addentity'] = 'Entity hinzufügen';
$string['addopeninghours'] = 'Öffnungszeiten hinzufügen';
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
$string['backtolist'] = 'Zurück zum Entity-Manager';
$string['belongs_to'] = 'Gehört zu';
$string['cachedef_cachedentities'] = 'Cache um Entities zu speichern';
$string['calendar'] = 'Kalender';
$string['categories'] = 'Entity Kategorien';
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
$string['entities:copymodule'] = 'Entities: Modul kopieren';
$string['entities:delete'] = 'Nutzer:in darf Entities löschen';
$string['entities:edit'] = 'Nutzer:in darf Entities editieren';
$string['entities:view'] = 'Nutzer:in darf Entities sehen';
$string['entitieslist'] = 'Entity Liste';
$string['entity_category'] = 'Entity-Kategorie';
$string['entity_description'] = 'Entity-Beschreibung';
$string['entity_name'] = 'Entity-Name';
$string['entity_openinghours'] = "Öffnungszeiten";
$string['entity_order'] = 'Sortier-Reihenfolge';
$string['entity_parent'] = 'Entity-Parent';
$string['entity_title'] = 'Entity';
$string['entitylist'] = 'Entity Liste';
$string['entitysetup_heading'] = 'Entity erstellen oder bearbeiten';
$string['er_entitiesname'] = 'Entity';
$string['er_placeholder'] = '... suche';
$string['error:entitydoesnotexist'] = "FEHLER: Die Entity existiert nicht!";
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
$string['failedimport'] = "Es gab ein Problem beim Import.";
$string['fallback_address_parent'] = 'Adresse der übergeordneten Entity als Fallback verwenden';
$string['fallback_address_parent:description'] = 'Wenn die Option gesetzt ist, werden, wenn keine Adressdaten angegeben sind, die Adressdaten der übergeordneten Entity verwendet.';
$string['fallback_contacts_parent'] = 'Kontakte der übergeordneten Entity als Fallback verwenden';
$string['fallback_contacts_parent:description'] = 'Wenn die Option gesetzt ist, werden, wenn keine Kontaktdaten angegeben sind, die Kontaktdaten der übergeordneten Entity verwendet.';
$string['fallback_image_parent'] = 'Bild der übergeordneten Entity als Fallback verwenden';
$string['fallback_image_parent:description'] = 'Wenn die Option gesetzt ist, wird, wenn keine Bild angegeben ist, das Bild der übergeordneten Entity verwendet.';
$string['import'] = "Entities importieren";
$string['map'] = 'Karte';
$string['maxallocation'] = 'Maximale Anzahl möglicher Buchungen.';
$string['maxallocation_help'] = '0 für kein Limit, -1 bedeutet nicht buchbar';
$string['new_entity'] = 'Neue Entity';
$string['none'] = 'Keine Entity';
$string['notwithinopeninghours'] = 'Außerhalb der Öffnungszeiten';
$string['opencalendar'] = 'Kalender öffnen';
$string['opencalendarfullsize'] = 'Kalender in Vollansicht öffnen';
$string['openinghours'] = 'Öffnungszeiten';
$string['openmap'] = 'Karte öffnen';
$string['opentimetable'] = "Öffne Stundenplan";
$string['pluginname'] = 'Entity-Manager';
$string['pricefactor'] = 'Relativer Preisfaktor';
$string['pricefactor_help'] = 'Relativer Preisfaktor: Kann für automatische Preisberechnung (z.B. mit dem Booking-Plugin) verwendet werden';
$string['show_calendar_on_details_page'] = 'Kalender auf der Detailseite anzeigen';
$string['show_calendar_on_details_page:description'] = 'Wenn die Option gesetzt ist, wird der Kalender direkt auf der Detailseite angezeigt, ansonsten wird ein Link zur Kalenderseite angezeigt';
$string['showpictureinsteadofcalendar'] = "Zeige Bild anstatt Kalender";
$string['showpictureinsteadofcalendar:description'] = "Bei allen Entities wird statt des Kalendars das große Bild angezeigt";
$string['starthours'] = 'Start hh';
$string['startminutes'] = 'Start mm';
$string['stdcategories'] = 'Standard-Kategorien';
$string['stdcategories:description'] = 'Standard-Kategorien aus der Liste der Customfield-Kategorien auswählen, die auf allen Bearbeitungsseiten sichtbar sein sollen.';
$string['successfullimport'] = "Die Datei wurde erfolgreich importiert";
$string['timetablemodalbutton'] = "OK";
$string['timetablemodaltitle'] = "Entity Timetable";
$string['usesubentitynamesforfilter'] = 'Filter: Namen von Sub-Entities verwenden';
$string['usesubentitynamesforfilter:description'] = 'Filter nutzen standardmäßig den Namen der Parent-Entity.
Wenn Sie dieses Häkchen aktivieren, dann wird jede einzelne Sub-Entity im Filter angezeigt.';
$string['view'] = 'Ansehen';
