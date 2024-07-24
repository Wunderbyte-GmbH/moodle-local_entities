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

$string['addcategory'] = 'Add category';
$string['addentity'] = 'Add entity';
$string['addopeninghours'] = 'Add opening hours';
$string['address'] = 'Address';
$string['address_city'] = 'City';
$string['address_country'] = 'Country';
$string['address_entrance'] = 'Entrance';
$string['address_floor'] = 'Floor';
$string['address_map_embed'] = 'Embed map (HTML)';
$string['address_map_link'] = 'Maps link';
$string['address_postcode'] = 'Postcode';
$string['address_streetname'] = 'Street name';
$string['address_streetnumber'] = 'Street number';
$string['affiliated'] = 'Affiliated locations';
$string['backtolist'] = 'Back to entity manager';
$string['belongs_to'] = 'Belongs to';
$string['calendar'] = 'Calendar';
$string['categories'] = 'Categories';
$string['categories:description'] = 'Set the default category from the list of the customfieldcategories visible on the edit page';
$string['conflictingshortnames'] = 'There is a conflict between the column names in the table and the used customfield shortanmes. Please change this shortname: {$a}';
$string['contacts'] = 'Contacts';
$string['contacts_givenname'] = 'Given name';
$string['contacts_mail'] = 'E-Mail';
$string['contacts_surname'] = 'Surname';
$string['daysofweek'] = 'Weekdays';
$string['delete'] = 'Delete';
$string['deleteentity'] = 'Delete entity!';
$string['deleteentityconfirm'] = 'Are you sure you want to delete this entity?';
$string['deleteopeninghours'] = 'Delete opening hours';
$string['edit'] = 'Edit';
$string['edit_details'] = 'Edit details';
$string['edit_entity'] = 'Edit entity';
$string['edit_image'] = 'Edit image';
$string['endhours'] = 'End hh';
$string['endminutes'] = 'End mm';
$string['entities:copymodule'] = 'Entities: Copy module';
$string['entities:delete'] = 'User is allowed to delete entities';
$string['entities:edit'] = 'User is allowed to edit entities';
$string['entities:view'] = 'User is allowed to see entities';
$string['entitieslist'] = 'List entities';
$string['entity_category'] = 'Entity category';
$string['entity_description'] = 'Entity description';
$string['entity_name'] = 'Entity name';
$string['entity_openinghours'] = "Opening hours";
$string['entity_order'] = 'Sort order';
$string['entity_parent'] = 'Entity parent';
$string['entity_title'] = 'Entity';
$string['entitylist'] = 'Entity List';
$string['entitysetup_heading'] = 'Edit or create entity';
$string['er_entitiesname'] = 'Entity';
$string['er_placeholder'] = '... search';
$string['error:entitydoesnotexist'] = "ERROR: Entity does not exist!";
$string['errorwiththefollowingdates'] = 'There is a conflict with the following bookings:';
$string['examplecsv'] = '<div class="alert alert-info">
    <p><b>CSV example file:</b></p>
    <p class="text-monospace">
        name;shortname;description<br>
        "Testimport 1";testimport1;"Description 1"<br>
        "Testimport 2";testimport2;"Description 2"<br>
        "Testimport 3";testimport3;"Description 3"<br>
    </p>
</div>';
$string['failedimport'] = "Your import failed.";
$string['fallback_address_parent'] = 'Use address of parent entity as fallback';
$string['fallback_address_parent:description'] = 'If enabled and there is not address, the address of the parent entity is used';
$string['fallback_contacts_parent'] = 'Use contacts of parent entity as fallback';
$string['fallback_contacts_parent:description'] = 'If enabled and there are not contact information, the contact information of the parent entity is used';
$string['fallback_image_parent'] = 'Use image of parent entity as fallback';
$string['fallback_image_parent:description'] = 'If enabled and there is no image, the image of the parent entity is used.';
$string['import'] = "Import entities";
$string['map'] = 'Map';
$string['maxallocation'] = 'Max number of bookings on this entity';
$string['maxallocation_help'] = '0 for no limit, -1 for not bookable.';
$string['new_entity'] = 'New entity';
$string['none'] = 'None';
$string['notwithinopeninghours'] = 'Outside business hours';
$string['opencalendar'] = 'Open calendar';
$string['opencalendarfullsize'] = 'Open calendar in full size';
$string['openinghours'] = 'Opening hours';
$string['openmap'] = 'Open map';
$string['opentimetable'] = "Open timetable";
$string['pluginname'] = 'Entity Manager';
$string['pricefactor'] = 'Relative price factor';
$string['pricefactor_help'] = 'Relative price factor: Can be used for automatic price calculations, e.g. in booking plugin';
$string['show_calendar_on_details_page'] = 'Show calendar on detail page';
$string['show_calendar_on_details_page:description'] = 'If enabled the calendar will be shown on the detail page, otherwise there will be a link to the calendar page';
$string['starthours'] = 'Start hh';
$string['startminutes'] = 'Start mm';
$string['stdcategories'] = 'Default categories';
$string['successfullimport'] = "Import was successfull.";
$string['timetablemodalbutton'] = "OK";
$string['timetablemodaltitle'] = "Entity Timetable";
$string['usesubentitynamesforfilter'] = 'For filters, use name of the actual sub-entity';
$string['usesubentitynamesforfilter:description'] = 'Filters use the parent entity by default.
If you activate this checkbox, then every single entity (sub-entity) will be shown in filter.';
$string['view'] = 'View';
