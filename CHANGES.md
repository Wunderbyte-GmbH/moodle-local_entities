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
