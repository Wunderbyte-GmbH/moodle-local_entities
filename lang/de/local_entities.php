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

$string['pluginname'] = 'Entity-Manager';
$string['none'] = 'Keine Entity';
$string['edit_details'] = 'Details bearbeiten';
$string['entity_name'] = 'Entity-Name';
$string['edit_image'] = 'Bild auswählen';
$string['entity_parent'] = 'Entity-Parent';
$string['entity_order'] = 'Sortier-Reihenfolge';
$string['entity_category'] = 'Entity-Kategorie';
$string['entity_description'] = 'Entity-Beschreibung';
$string['address'] = 'Adresse';
$string['address_city'] = 'Stadt';
$string['address_country'] = 'Land';
$string['address_postcode'] = 'Postleitzahl';
$string['address_streetname'] = 'Straßenname';
$string['address_streetnumber'] = 'Haus-Nr.';
$string['address_map_link'] = 'Karten-Link';
$string['address_map_embed'] = 'Karte einbetten (HTML)';
$string['affiliated'] = 'Zugehörige Orte';
$string['belongs_to'] = 'Gehört zu';
$string['contacts'] = 'Kontakte';
$string['contacts_givenname'] = 'Vorname';
$string['contacts_surname'] = 'Nachname';
$string['contacts_mail'] = 'E-Mail';
$string['addentity'] = 'Entity hinzufügen';
$string['entitysetup_heading'] = 'Entity erstellen oder bearbeiten';
$string['entity_title'] = 'Entity';
$string['backtolist'] = 'Zurück zum Entity-Manager';
$string['new_entity'] = 'Neue Entity';
$string['edit_entity'] = 'Entity bearbeiten';
$string['view'] = 'Ansehen';
$string['edit'] = 'Bearbeiten';
$string['delete'] = 'Löschen';
$string['deleteentity'] = 'Entity löschen!';
$string['deleteentityconfirm'] = 'Wollen Sie die Entity wirklich löschen?';
$string['entities:copymodule'] = 'Entities: Modul kopieren';
$string['categories'] = 'Entity Kategorien';
$string['map'] = 'Karte';
$string['entity_openinghours'] = "Öffnungszeiten";

// Kalender.
$string['openinghours'] = 'Öffnungszeiten';
$string['daysofweek'] = 'Wochentage';
$string['starthours'] = 'Start hh';
$string['startminutes'] = 'Start mm';
$string['endhours'] = 'Ende hh';
$string['endminutes'] = 'Ende mm';
$string['deleteopeninghours'] = 'Öffnungszeiten löschen';
$string['addopeninghours'] = 'Öffnungszeiten hinzufügen';
$string['notwithinopeninghours'] = 'Außerhalb der Öffnungszeiten';
$string['stdcategories'] = 'Standard-Kategorien';
$string['stdcategories:description'] = 'Standard-Kategorien aus der Liste der Customfield-Kategorien auswählen, die auf allen Bearbeitungsseiten sichtbar sein sollen.';
$string['entitieslist'] = 'Entity Liste';
$string['er_entitiesname'] = 'Ausgewählte Entity';
$string['er_saverelationsforoptiondates'] = 'Entity auch für jeden Termin speichern';
$string['addcategory'] = 'Kategorie hinzufügen';
$string['er_placeholder'] = '... suche';

$string['pricefactor'] = 'Relativer Preisfaktor';
$string['pricefactor_help'] = 'Relativer Preisfaktor: Kann für automatische Preisberechnung (z.B. mit dem Booking-Plugin) verwendet werden';

$string['errorwiththefollowingdates'] = 'Es gibt einen Konflikt mit den folgenden Buchungen:';

$string['maxallocation'] = 'Maximale Anzahl möglicher Buchungen.';
$string['maxallocation_help'] = '0 für kein Limit, -1 bedeutet nicht buchbar';

// Settings.
$string['fallback_image_parent'] = 'Bild der übergeordneten Entity als Fallback verwenden';
$string['fallback_image_parent:description'] = 'Wenn die Option gesetzt ist, wird, wenn keine Bild angegeben ist, das Bild der übergeordneten Entity verwendet.';
$string['fallback_contacts_parent'] = 'Kontakte der übergeordneten Entity als Fallback verwenden';
$string['fallback_contacts_parent:description'] = 'Wenn die Option gesetzt ist, werden, wenn keine Kontaktdaten angegeben sind, die Kontaktdaten der übergeordneten Entity verwendet.';
$string['fallback_address_parent'] = 'Adresse der übergeordneten Entity als Fallback verwenden';
$string['fallback_address_parent:description'] = 'Wenn die Option gesetzt ist, werden, wenn keine Adressdaten angegeben sind, die Adressdaten der übergeordneten Entity verwendet.';
$string['show_calendar_on_details_page'] = 'Kalender auf der Detailseite anzeigen';
$string['show_calendar_on_details_page:description'] = 'Wenn die Option gesetzt ist, wird der Kalender direkt auf der Detailseite angezeigt, ansonsten wird ein Link zur Kalenderseite angezeigt';
$string['event_category_course'] = 'ID des Kurses der Eventkategorien';
$string['event_category_course:description'] = 'Wenn der Wert gesetzt ist kann aus einer Page-Activity das Custom-Field "bgcolor" für die Kalendereintragfarbe verwendet werden';
$string['event_status_course'] = 'ID des Kurses des Eventstatus';
$string['event_status_course:description'] = 'Wenn der Wert gesetzt ist kann aus einer Page-Activity das Custom-Field "bgcolor" für die Kalendereintragfarbe verwendet werden, wenn bgcolor gesetzt ist überschreibt er den Wert der Kategorie';

// Access.php.
$string['entities:edit'] = 'Nutzer*in darf Entities editieren.';
$string['entities:view'] = 'Nutzer*in darf Entities sehen.';

$string['calendar'] = 'Kalender';
$string['opencalendar'] = 'Kalender öffnen';
$string['opencalendarfullsize'] = 'Kalender in Vollansicht öffnen';

// Entities handler.
$string['opentimetable'] = "Öffne Stundenplan";
$string['timetablemodaltitle'] = "Entity Timetable";
$string['timetablemodalbutton'] = "OK";

// Import.
$string['import'] = "Entities importieren";
$string['conflictingshortnames'] = 'Es gibt einen Konflikt zwischen den verwendenten Kurznamen der benutzerdefinierten Felder und der Tabellennamen. Bitte ändern Sie diesen Kurznamen: {$a}';
$string['successfullimport'] = "Die Datei wurde erfolgreich importiert";
$string['failedimport'] = "Es gab ein Problem beim Import.";
