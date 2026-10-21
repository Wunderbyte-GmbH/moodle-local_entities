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

$string['activeviewtemplate'] = 'Detail view template';
$string['activeviewtemplate:description'] = 'The layout used for every entity detail page. Managers can also switch and save it directly on a detail page.';
$string['activeviewtemplatefortype'] = 'Detail view template – {$a}';
$string['activeviewtemplatefortype:description'] = 'Template used for "{$a}" entities. Leave on the global default to inherit.';
$string['addcategory'] = 'Add category';
$string['addentity'] = 'Add entity';
$string['addopeninghours'] = 'Add opening hours';
$string['addperson'] = 'Add person';
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
$string['allocationmode'] = 'Booking mode';
$string['allocationmode_capacity'] = 'Capacity (sum of participants)';
$string['allocationmode_exclusive'] = 'Exclusive resource (occupied per reservation)';
$string['allocationmode_help'] = 'Exclusive resource: the entity is occupied per reservation, regardless of the number of participants (e.g. a room or court that can only be used once at a time). The number of allowed concurrent reservations is set in "Max concurrent reservations".

Capacity: overlapping bookings are allowed as long as the sum of their participants does not exceed "Max number of bookings on this entity".

None (default): overlapping bookings on this entity are not checked at all.';
$string['allocationmode_none'] = 'No overlap check';
$string['availableinsublocations'] = 'Available in sub-locations';
$string['availableinsublocations_help'] = 'Equipment only. If enabled, this item is also offered for bookings of sub-locations (descendants of its home location), not just its home location itself.';
$string['backtolist'] = 'Back to entity manager';
$string['belongs_to'] = 'Belongs to';
$string['cachedef_cachedentities'] = 'Cache to store entities';
$string['cachedef_entitydates'] = 'Cache to store entity dates';
$string['cachedef_geocode'] = 'Cache to store geocoding results';
$string['calendar'] = 'Calendar';
$string['calendarfirstday'] = 'Calendar: first day of the week';
$string['calendarfirstday:description'] = 'Weekday the entity calendar starts the week with (full-size calendar and detail-page calendar).';
$string['calendartimeformat'] = 'Calendar: time format';
$string['calendartimeformat12'] = '12-hour (am/pm)';
$string['calendartimeformat24'] = '24-hour';
$string['calendartimeformat:description'] = 'Time format used for events and time axis in the entity calendar.';
$string['cancelled'] = 'CANCELLED';
$string['capacitysource'] = 'Capacity counted by';
$string['capacitysource_help'] = 'Capacity mode only. Determines how much each booking consumes of this entity:

Participants (max answers): the option\'s participant limit is counted against the capacity (e.g. people in a room, one bungee rope per person).

Manual quantity: an explicitly entered number is counted (e.g. beamers, tandems) — independent of the number of participants.';
$string['capacitysource_manual'] = 'Manual quantity';
$string['capacitysource_maxanswers'] = 'Participants (max answers)';
$string['categories'] = 'Categories';
$string['categories:description'] = 'Set the default category from the list of the customfieldcategories visible on the edit page';
$string['conflictcapacity'] = 'Not enough free capacity for "{$a->name}" at the requested times: you requested {$a->requested}, but the total capacity is {$a->capacity}. It is already used by these overlapping bookings:';
$string['conflictexclusive'] = 'The resource "{$a}" is already booked at the requested times and cannot be booked twice. Conflicting bookings:';
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
$string['entities:changeviewtemplate'] = 'Change the entity detail view template';
$string['entities:copymodule'] = 'Entities: Copy module';
$string['entities:delete'] = 'User is allowed to delete entities';
$string['entities:edit'] = 'User is allowed to edit entities';
$string['entities:manageunavailability'] = 'User is allowed to manage the unavailability of all entities (persons, places, equipment)';
$string['entities:view'] = 'User is allowed to see entities';
$string['entitieslist'] = 'List entities';
$string['entity_category'] = 'Entity category';
$string['entity_description'] = 'Entity description';
$string['entity_name'] = 'Entity name';
$string['entity_openinghours'] = 'Opening hours';
$string['entity_order'] = 'Sort order';
$string['entity_parent'] = 'Entity parent';
$string['entity_title'] = 'Entity';
$string['entitylist'] = 'Entity List';
$string['entitysetup_heading'] = 'Edit or create entity';
$string['entitytype'] = 'Entity type';
$string['entitytype_equipment'] = 'Equipment / resource';
$string['entitytype_help'] = 'Location: rooms and places used in the location hierarchy and offered in room pickers. Equipment: bookable resources (e.g. beamers, balls) hung under a location via its parent; they are offered in addition to the room when a location is chosen. Person: a Moodle user (e.g. an examiner) whose availability is managed here; persons are never offered in room pickers.';
$string['entitytype_location'] = 'Location';
$string['entitytype_person'] = 'Person';
$string['equipmentquantity'] = 'Quantity: {$a}';
$string['er_entitiesname'] = 'Entity';
$string['er_placeholder'] = '... search';
$string['error:entitydoesnotexist'] = 'ERROR: Entity does not exist!';
$string['error:invalidparent'] = 'The chosen parent entity does not exist.';
$string['error:parentcycle'] = 'An entity cannot be moved below itself: the chosen parent is the entity itself or one of its sub-entities.';
$string['error:personalreadyexists'] = 'This user is already registered as a person.';
$string['error:personuserrequired'] = 'Please select an existing Moodle user.';
$string['error:poolmemberwrongtype'] = 'Only entities of the type of the pool can be members.';
$string['error:poolnameexists'] = 'A pool with this name already exists.';
$string['error:poolnameinvalid'] = 'Please enter a name for the pool (up to 255 characters).';
$string['error:poolnotfound'] = 'The pool does not exist.';
$string['error:pooltypeinvalid'] = 'This type of pool is not available.';
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
$string['failedimport'] = 'Your import failed.';
$string['fallback_address_parent'] = 'Use address of parent entity as fallback';
$string['fallback_address_parent:description'] = 'If enabled and there is not address, the address of the parent entity is used';
$string['fallback_contacts_parent'] = 'Use contacts of parent entity as fallback';
$string['fallback_contacts_parent:description'] = 'If enabled and there are not contact information, the contact information of the parent entity is used';
$string['fallback_image_parent'] = 'Use image of parent entity as fallback';
$string['fallback_image_parent:description'] = 'If enabled and there is no image, the image of the parent entity is used.';
$string['fieldtemplate'] = 'Field template';
$string['fieldtemplate_help'] = 'A field template adds a set of extra fields to this entity (e.g. the Equipment template adds inventory number, condition …). Choose "None" to add no extra fields. Templates can be managed under Categories.';
$string['import'] = 'Import entities';
$string['inheritglobal'] = 'Use global default';
$string['map'] = 'Map';
$string['maxallocation'] = 'Max number of bookings on this entity';
$string['maxallocation_help'] = '0 for no limit, -1 for not bookable.';
$string['maxconcurrent'] = 'Max concurrent reservations';
$string['maxconcurrent_help'] = 'Exclusive mode only: how many reservations may occupy this entity at the same time. 1 means the entity can only be booked once for any given time range.';
$string['new_entity'] = 'New entity';
$string['new_person'] = 'New person';
$string['nomapavailable'] = 'No location available for this entity.';
$string['none'] = 'None';
$string['notwithinopeninghours'] = 'Outside business hours';
$string['opencalendar'] = 'Open calendar';
$string['opencalendarfullsize'] = 'Open calendar in full size';
$string['openinghours'] = 'Opening hours';
$string['openmap'] = 'Open map';
$string['opentimetable'] = 'Open timetable';
$string['person_user'] = 'Moodle user';
$string['pluginname'] = 'Entity Manager';
$string['pool_actions'] = 'Actions';
$string['pool_add_equipment'] = 'Add equipment pool';
$string['pool_add_person'] = 'Add person pool';
$string['pool_deleteconfirm'] = 'Delete the pool "{$a}"? The members stay, only the group is removed.';
$string['pool_deleted'] = 'The pool was deleted.';
$string['pool_description'] = 'Description';
$string['pool_edit'] = 'Edit pool';
$string['pool_members'] = 'Members';
$string['pool_members_placeholder'] = 'Search for a member';
$string['pool_name'] = 'Name';
$string['pool_none'] = 'There are no pools yet.';
$string['pools'] = 'Pools';
$string['pools_intro'] = 'A pool groups entities of one type, for example the examiners of one subject or the cars of a driving school. A pool can be chosen in a booking option.';
$string['pooltype'] = 'Type';
$string['pooltype_equipment'] = 'Equipment pool';
$string['pooltype_person'] = 'Person pool';
$string['pricefactor'] = 'Relative price factor';
$string['pricefactor_help'] = 'Relative price factor: Can be used for automatic price calculations, e.g. in booking plugin';
$string['privacy:metadata'] = 'The Entity Manager stores which Moodle users are managed as persons, for example examiners, when they are not available, and who created or last changed entities and unavailability.';
$string['privacy:metadata:local_entities'] = 'Entities. A person is an entity that stands for a Moodle user.';
$string['privacy:metadata:local_entities:createdby'] = 'The user who created the entity.';
$string['privacy:metadata:local_entities:userid'] = 'The Moodle user a person entity stands for.';
$string['privacy:metadata:local_entities_contacts'] = 'Contacts of an entity, for example the person in charge of a room. They are not linked to Moodle users.';
$string['privacy:metadata:local_entities_contacts:givenname'] = 'The first name of the contact.';
$string['privacy:metadata:local_entities_contacts:mail'] = 'The email address of the contact.';
$string['privacy:metadata:local_entities_contacts:surname'] = 'The last name of the contact.';
$string['privacy:metadata:local_entities_pool'] = 'Named groups of persons, for example the examiners of one subject.';
$string['privacy:metadata:local_entities_pool:name'] = 'The name of the pool.';
$string['privacy:metadata:local_entities_pool:usermodified'] = 'The user who created or last changed the pool.';
$string['privacy:metadata:local_entities_pool_member'] = 'Which persons belong to which pool.';
$string['privacy:metadata:local_entities_pool_member:entityid'] = 'The person, and with it the Moodle user, who belongs to the pool.';
$string['privacy:metadata:local_entities_pool_member:usermodified'] = 'The user who added the person to the pool.';
$string['privacy:metadata:local_entities_unavail_series'] = 'Recurring unavailability of an entity, for example every Monday.';
$string['privacy:metadata:local_entities_unavail_series:dayend'] = 'The time of day at which the unavailability ends.';
$string['privacy:metadata:local_entities_unavail_series:daystart'] = 'The time of day at which the unavailability starts.';
$string['privacy:metadata:local_entities_unavail_series:entityid'] = 'The entity that is not available.';
$string['privacy:metadata:local_entities_unavail_series:reason'] = 'The reason for the unavailability.';
$string['privacy:metadata:local_entities_unavail_series:usermodified'] = 'The user who last changed the unavailability.';
$string['privacy:metadata:local_entities_unavail_series:validfrom'] = 'The first day of the recurring unavailability.';
$string['privacy:metadata:local_entities_unavail_series:validuntil'] = 'The last day of the recurring unavailability.';
$string['privacy:metadata:local_entities_unavail_series:weekdays'] = 'The weekdays of the recurring unavailability.';
$string['privacy:metadata:local_entities_unavailability'] = 'The times at which an entity, for a person the Moodle user, is not available.';
$string['privacy:metadata:local_entities_unavailability:endtime'] = 'The time at which the unavailability ends.';
$string['privacy:metadata:local_entities_unavailability:entityid'] = 'The entity that is not available.';
$string['privacy:metadata:local_entities_unavailability:reason'] = 'The reason for the unavailability.';
$string['privacy:metadata:local_entities_unavailability:starttime'] = 'The time at which the unavailability starts.';
$string['privacy:metadata:local_entities_unavailability:usermodified'] = 'The user who last changed the unavailability.';
$string['privacychangedpools'] = 'Pools created or changed by the user';
$string['privacychangedunavailability'] = 'Unavailability changed by the user';
$string['privacycreatedentities'] = 'Entities created by the user';
$string['privacyentities'] = 'Entities';
$string['privacypoolmemberships'] = 'Pools the person belongs to';
$string['privacyunavailability'] = 'Unavailability';
$string['privacyunavailabilityseries'] = 'Recurring unavailability';
$string['refreshequipment'] = 'Show equipment for the selected location';
$string['saveview'] = 'Save as view';
$string['saveviewfortype'] = 'Save as the view for all "{$a}" entities';
$string['show_calendar_on_details_page'] = 'Show calendar on detail page';
$string['show_calendar_on_details_page:description'] = 'If enabled the calendar will be shown on the detail page, otherwise there will be a link to the calendar page';
$string['showpictureinsteadofcalendar'] = 'Show Picture instead of calendar';
$string['showpictureinsteadofcalendar:description'] = 'For all entities, in detail view, you\'ll see the picture instead of the calendar';
$string['starthours'] = 'Start hh';
$string['startminutes'] = 'Start mm';
$string['stdcategories'] = 'Default categories';
$string['successfullimport'] = 'Import was successfull.';
$string['template_equipment'] = 'Equipment';
$string['template_location'] = 'Location';
$string['timetablemodalbutton'] = 'OK';
$string['timetablemodaltitle'] = 'Entity Timetable';
$string['tplcond_defective'] = 'Defective';
$string['tplcond_good'] = 'Good';
$string['tplcond_new'] = 'New';
$string['tplcond_used'] = 'Used';
$string['tplfield_accessible'] = 'Wheelchair accessible';
$string['tplfield_amenities'] = 'Amenities';
$string['tplfield_area'] = 'Area (m²)';
$string['tplfield_building'] = 'Building';
$string['tplfield_condition'] = 'Condition';
$string['tplfield_inventoryno'] = 'Inventory number';
$string['tplfield_manufacturer'] = 'Manufacturer';
$string['tplfield_model'] = 'Model';
$string['tplfield_notes'] = 'Notes';
$string['tplfield_purchasedate'] = 'Purchase date';
$string['tplfield_responsible'] = 'Responsible';
$string['tplfield_roomnumber'] = 'Room number';
$string['tplfield_seats'] = 'Seats';
$string['tplfield_serial'] = 'Serial number';
$string['unavailability'] = 'Unavailability';
$string['unavailability_absent'] = 'Unavailable';
$string['unavailability_addseries'] = 'Add recurring unavailability';
$string['unavailability_allday'] = 'All day';
$string['unavailability_apply'] = 'Apply';
$string['unavailability_conflicts'] = 'Not saved. These assignments overlap with your changes:';
$string['unavailability_day'] = 'Day';
$string['unavailability_delete'] = 'Delete';
$string['unavailability_deleteconfirm'] = 'Do you really want to delete this unavailability? It is removed when you press "Save".';
$string['unavailability_discard'] = 'Discard changes';
$string['unavailability_edit'] = 'Edit unavailability';
$string['unavailability_entity'] = 'Entity';
$string['unavailability_from'] = 'From';
$string['unavailability_help'] = 'Drag over a time range to mark it as unavailable. Click a day in the top row or in the month view to mark the whole day. Click the number of a day to open that day, the number of a week to open the week, and the title to zoom out. Purple entries are assignments, for example booked slots. They cannot be changed here, and no time can be marked over them. Click one to open it. Use "Add recurring unavailability" for regular times, e.g. every Monday. Click a saved entry to change or delete it. Time that is over cannot be changed. Nothing is saved until you press "Save".';
$string['unavailability_invalidrange'] = 'The end must be after the start, and the start must not be in the past.';
$string['unavailability_locked'] = 'Changing the unavailability of this kind of entity is locked. You can look at it, but only managers can change it.';
$string['unavailability_more'] = 'more';
$string['unavailability_notsaved'] = 'Not saved';
$string['unavailability_save'] = 'Save';
$string['unavailability_saved'] = 'The changes were saved.';
$string['unavailability_series'] = 'Recurring unavailability';
$string['unavailability_series_badperiod'] = 'The first day must not be in the past, the last day must not be before the first day, and the period can last at most 366 days.';
$string['unavailability_series_badtime'] = 'The end time must be after the start time.';
$string['unavailability_series_belongs'] = 'This day belongs to a recurring unavailability.';
$string['unavailability_series_delete'] = 'Delete the series from today on';
$string['unavailability_series_deleteconfirm'] = 'Do you really want to delete this recurring unavailability from today on? Days that are over stay as they are. It is removed when you press "Save".';
$string['unavailability_series_edit'] = 'Edit the series';
$string['unavailability_series_end'] = 'End time';
$string['unavailability_series_firstday'] = 'First day';
$string['unavailability_series_lastday'] = 'Last day';
$string['unavailability_series_noweekday'] = 'Choose at least one weekday.';
$string['unavailability_series_start'] = 'Start time';
$string['unavailability_series_thisday'] = 'Delete this day only';
$string['unavailability_series_weekdays'] = 'Weekdays';
$string['unavailability_showmonth'] = 'Show the month';
$string['unavailability_showweek'] = 'Show the week';
$string['unavailability_unsaved'] = 'You have unsaved changes.';
$string['unavailability_until'] = 'Until';
$string['unavailabilitylock_equipment'] = 'Lock self-editing of unavailability: equipment';
$string['unavailabilitylock_equipment_desc'] = 'When this is on, nobody but managers (right "manage the unavailability of all entities") can change the unavailability of equipment. Everyone else can only look at it.';
$string['unavailabilitylock_heading'] = 'Locking the unavailability';
$string['unavailabilitylock_heading_desc'] = 'Decide per kind of entity whether people may still change the unavailability themselves.';
$string['unavailabilitylock_location'] = 'Lock self-editing of unavailability: places';
$string['unavailabilitylock_location_desc'] = 'When this is on, nobody but managers (right "manage the unavailability of all entities") can change the unavailability of places. Everyone else can only look at it.';
$string['unavailabilitylock_person'] = 'Lock self-editing of unavailability: persons';
$string['unavailabilitylock_person_desc'] = 'When this is on, nobody but managers (right "manage the unavailability of all entities") can change the unavailability of persons. Everyone else can only look at it.';
$string['unavailabilityof'] = 'Unavailability of {$a}';
$string['usecount'] = 'Used by';
$string['usesubentitynamesforfilter'] = 'For filters, use name of the actual sub-entity';
$string['usesubentitynamesforfilter:description'] = 'Filters use the parent entity by default.
If you activate this checkbox, then every single entity (sub-entity) will be shown in filter.';
$string['usetreelist'] = 'Use the new hierarchical entity list';
$string['usetreelist:description'] = 'Show the new entity overview (hierarchical tree filter, full-text search, pagination) instead of the classic list.';
$string['view'] = 'View';
$string['viewtemplate'] = 'View';
$string['viewtemplate_calendar'] = 'Calendar';
$string['viewtemplate_classic'] = 'Classic';
$string['viewtemplate_compact'] = 'Compact';
$string['viewtemplate_image'] = 'Image';
$string['viewtemplate_map'] = 'Map';
$string['viewtemplatepreview'] = 'Preview – not saved';
