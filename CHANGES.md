## Version 0.4.6 (2026010800)
* Improvement: Make entities ready for Bootstrap 5 (introduced in Moodle 5).

## Version 0.4.5 (2026010700)
* Improvement: Add entities cache.

## Version 0.4.4 (2024111100)
* Improvement: Show picture instead of calendar.
* Improvement: Changed entities view for pictures.

## Version 0.4.1 (2024091700)
* Bugfix: Temporary github handling to avoid failing actions

## Version 0.4.0 (2024080900)
* Improvement: Meet requirements for strings in Moodle 4.4
* Improvement: Create generator class to enable test integration within other plugins
* Bugfix: Only display entrance and floor if given

## Version 0.3.9 (2024072200)
* Bugfix: Also fetch entities with no addresses given.

## Version 0.3.8 (2024071200)
* Improvement: Add entity description to handler for better integration.

## Version 0.3.5 (2024031400)
* Improvement: Get rid of old expert / simple mode (formmode).

## Version 0.3.4 (2024030700)
* Bugfix: Fix capability checks.

## Version 0.3.3 (2024030600)
* Improvement: Better string in autocomplete if no entity is set.

## Version 0.3.2 (2024022300)
* Improvement: New setting to set if we want to get parent name or subentity name for filters.

## Version 0.3.1 (2024011000)
* Improvement: Re-write entitiesrelation_handler.
* Improvement: Remove string only relevant for mod_booking.
* Improvement: Better strings.
* Improvement: Checkbox to save entity for each session no longer needed. Layout improvements.
* Improvement: Collapse entity section by default and show a "Choose..." string when entity is missing (instead of "0").
* Improvement: Linting: Phpdocs for function entitiesrelation_handler::instance_form_save has incomplete parameters list.
* Bugfix: Do not require login on view.php (web service still required it).
* Bugfix: Fix alignment of entity list.
* Bugfix: QuickForm Error: nonexistent html element: Element 'entitiesrelation' does not exist in HTML_QuickForm::getElement().
* Bugfix: Check for empty is important. Otherwise we overwrite form values when any nosubmit button is pressed.

## Version 0.3.0 (2023120700)
* Improvement: Harden against errors.
* Bugfix: Don't fail js when element is not found.

## Version 0.2.9 (2023120500)
* Improvement: Rename return_array_of_dates to return_array_of_entity_dates.
* Improvement: Layout improvements.
* Improvement: Lots of fixes and improvements with sorting, cancel buttons, deleting etc.
* Improvement: Better handling of deleting entities and add a DB cleanup function.
* Improvement: Autocomplete for parent entities, modals for every delete button, better navigation.
* Improvement: Show an error if trying to view or edit a non-existing entity.
* Bugfix: Fix and improve broken CSV importer.

## Version 0.2.8 (2023112700)
* Improvement: Added support for Moodle 4.3 and PHP 8.2.

## Version 0.2.7 (2023112000)
* Improvement: view.php layout change: base instead of standard.
* Improvement: Only load secondary navigation if user is logged in.

## Version 0.2.6 (2023101300)
* Improvement: We even show entities when logged out.

## Version 0.2.5 (2023100900)
* Bugfix: Add FontAwesome 6 compatibility for Moodle 4.2.
* Bugfix: Fix github actions.

## Version 0.2.4 (2023091900)
* Bugfix: Fix namespaces for Calendar webservices.

## Version 0.2.3 (2023091400)
**Improvements:**
* Improvement: Return parent name directly via sql on many occassions.

## Version 0.2.2 (2023081100)
**Bugfixes:**
* Bugfix: Return values of webservice should be PARAM_RAW - so they do not crash with HTML tags.

## Version 0.2.1 (2023072100)
**Improvements:**
* Improvement: Renamed get_entity_by_id to get_entities_by_id
    (there can be more than one because of join with address table).
* Improvement: Do not show shortname on view.php.

## Version 0.2.0 (2023072000)
**Improvements:**
* Improvement: Entity import via CSV is now case-insensitive.

**Bugfixes:**
* Bugfix: Entity autocomplete search was case sensitive - made it case insensitive.

## Version 0.1.9 (2023062200)
**Bugfixes:**
* Bugfix: Fix a Bug with PHP 8.

## Version 0.1.8 (2023060900)
**Improvements:**
* Set calendar color.
* Set URL for clickable events.
* Remove Settings only used for testing.
* Layout Bugfix and Language String added.

**Bugfixes:**
* Bugfix: Fix parent image.
* Bugfix: Fix address and contacts count.
* Bugfix: Check contact id for update or insert.

## Version 0.1.7 (2023042400)
**Improvements:**
* Contact and address as fallback from parent.
* Layout improvements.
* Minor fixes and code quality.

## Version 0.1.6 (2023031600)
**Improvements:**
* Improvement: Added list of affiliated entities to detail page.

## Version 0.1.5 (2023022000)
**Improvements:**
* Improvement: New set data function to set data to stdclass, not mform (for nested handlers).
* Improvement: Corrected and improved some strings.

**Bugfixes:**
* Bugfix: Hide opentimetable button correctly in simple mode (if entities deactivated).

## Version 0.1.4 (2023011200)
**Bugfixes:**
* Bugfix: Added missing indices (closes issue #8).

## Version 0.1.3 (2022120500)
**Bugfixes:**
* Bugfix: Wrong capability name.
