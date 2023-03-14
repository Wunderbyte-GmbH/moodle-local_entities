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

$string['pluginname'] = 'Entity Manager';
$string['none'] = 'None';
$string['edit_details'] = 'Edit details';
$string['entity_name'] = 'Entity name';
$string['edit_image'] = 'Edit image';
$string['entity_parent'] = 'Entity parent';
$string['entity_order'] = 'Sort order';
$string['entity_category'] = 'Entity category';
$string['entity_description'] = 'Entity description';
$string['address'] = 'Address';
$string['address_city'] = 'City';
$string['address_country'] = 'Country';
$string['address_postcode'] = 'Postcode';
$string['address_streetname'] = 'Street name';
$string['address_streetnumber'] = 'Street number';
$string['address_map_link'] = 'Maps link';
$string['address_map_embed'] = 'Embed map (HTML)';
$string['affiliated'] = 'Affiliated locations';
$string['contacts'] = 'Contacts';
$string['contacts_givenname'] = 'Given name';
$string['contacts_surname'] = 'Surname';
$string['contacts_mail'] = 'E-Mail';
$string['addentity'] = 'Add entity';
$string['entitysetup_heading'] = 'Edit or create entity';
$string['entity_title'] = 'Entity';
$string['backtolist'] = 'Back to entity manager';
$string['new_entity'] = 'New entity';
$string['edit_entity'] = 'Edit entity';
$string['view'] = 'View';
$string['edit'] = 'Edit';
$string['delete'] = 'Delete';
$string['entities:copymodule'] = 'Entities: Copy module';
$string['categories'] = 'Categories';
$string['map'] = 'Map';
$string['entity_openinghours'] = "Opening hours";

// Kalender.
$string['openinghours'] = 'Opening hours';
$string['daysofweek'] = 'Weekdays';
$string['starthours'] = 'Start hh';
$string['startminutes'] = 'Start mm';
$string['endhours'] = 'End hh';
$string['endminutes'] = 'End mm';
$string['deleteopeninghours'] = 'Delete opening hours';
$string['addopeninghours'] = 'Add opening hours';
$string['notwithinopeninghours'] = 'Outside business hours';

$string['stdcategories'] = 'Default categories';
$string['categories:description'] = 'Set the default category from the list of the customfieldcategories visible on the edit page';
$string['er_entitiesname'] = 'Selected entitiy';
$string['er_saverelationsforoptiondates'] = 'Save entity for each date too';
$string['er_placeholder'] = '... search';
$string['addcategory'] = 'Add category';
$string['entitieslist'] = 'List entities';

$string['pricefactor'] = 'Relative price factor';
$string['pricefactor_help'] = 'Relative price factor: Can be used for automatic price calculations, e.g. in booking plugin';

$string['errorwiththefollowingdates'] = 'There is a conflict with the following bookings:';

$string['maxallocation'] = 'Max number of bookings on this entity';
$string['maxallocation_help'] = '0 for no limit, -1 for not bookable.';

// Access.php.
$string['entities:edit'] = 'User is allowed to edit entities';
$string['entities:view'] = 'User is allowed to see entities';

$string['calendar'] = 'Calendar';

// Entities handler.
$string['opentimetable'] = "Open timetable";
$string['timetablemodaltitle'] = "Entity Timetable";
$string['timetablemodalbutton'] = "OK";

// Import.
$string['import'] = "Import";
$string['conflictingshortnames'] = 'There is a conflict between the column names in the table and the used customfield shortanmes. Please change this shortname: {$a}';
$string['successfullimport'] = "Import was successfull.";
$string['failedimport'] = "Your import failed.";
