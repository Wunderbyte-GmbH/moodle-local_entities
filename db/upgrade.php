<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Function to correctly upgrade local_entities.
 *
 * @package    local_entities
 * @copyright  2022 Wunderbyte GmbH <info@wunderbyte.at>
 * @author     Thomas Winkler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /**
  * Upgrade function
  *
  * @param int $oldversion
  * @return void
  */
function xmldb_local_entities_upgrade($oldversion) {
    global $DB, $CFG;

    require_once($CFG->dirroot . '/local/entities/db/upgradelib.php');

    $dbman = $DB->get_manager();

    if ($oldversion < 2022071800) {
        $table = new xmldb_table('local_entities');

        // End of fix #190.
        $pricefactor = new xmldb_field('pricefactor', XMLDB_TYPE_NUMBER, '10, 2', null, null, null, '1', null);

        // Conditionally launch add field semesterid.
        if (!$dbman->field_exists($table, $pricefactor)) {
            $dbman->add_field($table, $pricefactor);
        }

        // Booking savepoint reached.
        upgrade_plugin_savepoint(true, 2022071800, 'local', 'entities');
    }

    if ($oldversion < 2022072200) {
        $localentitiesaddress = new xmldb_table('local_entities_address');

        $country = new xmldb_field('country', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'entityidto');
        $city = new xmldb_field('city', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'country');
        $postcode = new xmldb_field('postcode', XMLDB_TYPE_CHAR, '30', null, null, null, null, 'city');
        $streetname = new xmldb_field('streetname', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'postcode');

        $dbman->change_field_precision($localentitiesaddress, $country);
        $dbman->change_field_precision($localentitiesaddress, $city);
        $dbman->change_field_type($localentitiesaddress, $postcode);
        $dbman->change_field_precision($localentitiesaddress, $postcode);
        $dbman->change_field_precision($localentitiesaddress, $streetname);

        $localentitiescontacts = new xmldb_table('local_entities_contacts');

        $givenname = new xmldb_field('givenname', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'entityidto');
        $surname = new xmldb_field('surname', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'givenname');
        $mail = new xmldb_field('mail', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'surname');

        $dbman->change_field_precision($localentitiescontacts, $givenname);
        $dbman->change_field_precision($localentitiescontacts, $surname);
        $dbman->change_field_precision($localentitiescontacts, $mail);

        // Entities savepoint reached.
        upgrade_plugin_savepoint(true, 2022072200, 'local', 'entities');
    }

    if ($oldversion < 2022072800) {
        // Changing type of field name on table local_entities to char.
        $table = new xmldb_table('local_entities');
        $field = new xmldb_field('name', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'id');

        // Launch change of type for field name.
        $dbman->change_field_type($table, $field);
        // Launch change of precision for field name.
        $dbman->change_field_precision($table, $field);

        // Entities savepoint reached.
        upgrade_plugin_savepoint(true, 2022072800, 'local', 'entities');
    }

    if ($oldversion < 2022100700) {
        // Changing type of field name on table local_entities to char.
        $table = new xmldb_table('local_entities');
        $field = new xmldb_field('cfitemid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'sortorder');

        // Conditionally launch add field cfitiemid (Customfielditemid).
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Entities savepoint reached.
        upgrade_plugin_savepoint(true, 2022100700, 'local', 'entities');
    }

    if ($oldversion < 2022101100) {
        $table = new xmldb_table('local_entities_address');

        // Define field maplink to be added to local_entities_address.
        $maplinkfield = new xmldb_field('maplink', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'streetnumber');

        // Define field mapembed to be added to local_entities_address.
        $mapembedfield = new xmldb_field('mapembed', XMLDB_TYPE_TEXT, null, null, null, null, null, 'maplink');

        // Conditionally launch add field maplink.
        if (!$dbman->field_exists($table, $maplinkfield)) {
            $dbman->add_field($table, $maplinkfield);
        }

        // Conditionally launch add field mapembed.
        if (!$dbman->field_exists($table, $mapembedfield)) {
            $dbman->add_field($table, $mapembedfield);
        }

        // Entities savepoint reached.
        upgrade_plugin_savepoint(true, 2022101100, 'local', 'entities');
    }

    if ($oldversion < 2022101300) {
        $table = new xmldb_table('local_entities');

        // Define field shortname to be added to local_entities.
        $field = new xmldb_field('shortname', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'name');

        // Conditionally launch add field shortname.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Entities savepoint reached.
        upgrade_plugin_savepoint(true, 2022101300, 'local', 'entities');
    }

    if ($oldversion < 2022101400) {
        // Define field component to be added to local_entities_relations.
        $table = new xmldb_table('local_entities_relations');
        $field = new xmldb_field('component', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'entityid');

        // Conditionally launch add field component.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Write "mod_booking" into all newly created fields for existing rows.
        fix_entities_relations_2022101400();

        // Entities savepoint reached.
        upgrade_plugin_savepoint(true, 2022101400, 'local', 'entities');
    }

    if ($oldversion < 2022101401) {
        // Rename field modulename on table local_entities_relations to area.
        $table = new xmldb_table('local_entities_relations');
        $field = new xmldb_field('modulename', XMLDB_TYPE_CHAR, '30', null, null, null, null, 'component');

        // Launch rename field modulename.
        $dbman->rename_field($table, $field, 'area');

        // Change value "bookingoption" to just "option" for values of column "area".
        fix_entities_relations_2022101401();

        // Entities savepoint reached.
        upgrade_plugin_savepoint(true, 2022101401, 'local', 'entities');
    }

    if ($oldversion < 2022101403) {
        // Define index modinstance (not unique) to be added to local_entities_relations.
        $table = new xmldb_table('local_entities_relations');
        $index = new xmldb_index('idx_componentareainstanceid', XMLDB_INDEX_NOTUNIQUE, ['component', 'area', 'instanceid']);

        // Conditionally launch add index modinstance.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index modinstance (not unique) to be dropped from local_entities_relations.
        $indextodelete = new xmldb_index('modinstance', XMLDB_INDEX_NOTUNIQUE, ['modulename', 'instanceid']);
        // Conditionally launch drop index modinstance.
        if ($dbman->index_exists($table, $indextodelete)) {
            $dbman->drop_index($table, $indextodelete);
        }

        // Entities savepoint reached.
        upgrade_plugin_savepoint(true, 2022101403, 'local', 'entities');
    }

    if ($oldversion < 2022103001) {
        // Define field openinghours to be added to local_entities.
        $table = new xmldb_table('local_entities');

        // First, drop field pictue.
        $field = new xmldb_field('picture');

        // Conditionally launch drop field picture.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Second, drop field type.
        $field = new xmldb_field('type');

        // Conditionally launch drop field type.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Third, rename openentitiy to status.
        $field = new xmldb_field('openentity', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', 'timemodified');

        // Launch rename field openentity.
        $dbman->rename_field($table, $field, 'status');

        // Add field openinghours.
        $field = new xmldb_field('openinghours', XMLDB_TYPE_TEXT, null, null, null, null, null, 'cfitemid');

        // Conditionally launch add field openinghours.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Finally, add field max allocation.
        $field = new xmldb_field('maxallocation', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', 'openinghours');

        // Conditionally launch add field maxallocation.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Entities savepoint reached.
        upgrade_plugin_savepoint(true, 2022103001, 'local', 'entities');
    }

    if ($oldversion < 2022120600) {
        $table = new xmldb_table('local_entities');
        $index = new xmldb_index('parentid', XMLDB_INDEX_NOTUNIQUE, ['parentid']);
        // Conditionally launch add index.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $table = new xmldb_table('local_entities');
        $index = new xmldb_index('cfitemid', XMLDB_INDEX_NOTUNIQUE, ['cfitemid']);
        // Conditionally launch add index.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Entities savepoint reached.
        upgrade_plugin_savepoint(true, 2022120600, 'local', 'entities');
    }

    if ($oldversion < 2024062501) {
        // Define field floor to be added to local_entities_address.
        $table = new xmldb_table('local_entities_address');
        $field = new xmldb_field('floor', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'mapembed');

        // Conditionally launch add field floor.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('entrance', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'floor');

        // Conditionally launch add field entrance.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Entities savepoint reached.
        upgrade_plugin_savepoint(true, 2024062501, 'local', 'entities');
    }

    if ($oldversion < 2026070301) {
        $table = new xmldb_table('local_entities');

        // Booking/allocation mode for the entity. Default 'none' preserves the previous behaviour
        // (no overlap checking) for existing entities.
        $field = new xmldb_field('allocationmode', XMLDB_TYPE_CHAR, '20', null, null, null, 'none', 'maxallocation');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Max concurrent reservations used by the exclusive mode.
        $field = new xmldb_field('maxconcurrent', XMLDB_TYPE_INTEGER, '10', null, null, null, '1', 'allocationmode');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Capacity mode: how the consumed amount per booking is determined.
        $field = new xmldb_field('capacitysource', XMLDB_TYPE_CHAR, '20', null, null, null, 'maxanswers', 'maxconcurrent');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Kind of entity: 'location' (default) or 'equipment'.
        $field = new xmldb_field('entitytype', XMLDB_TYPE_CHAR, '20', null, null, null, 'location', 'capacitysource');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Equipment: whether sub-locations may also use the item.
        $field = new xmldb_field('availableinsublocations', XMLDB_TYPE_INTEGER, '1', null, null, null, '1', 'entitytype');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Capacity mode: consumed amount of the entity by a single relation (participants or units).
        $table = new xmldb_table('local_entities_relations');
        $field = new xmldb_field('quantity', XMLDB_TYPE_INTEGER, '10', null, null, null, '1', 'timecreated');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Seed the default, ready-to-use field templates (location & equipment) on existing sites.
        // Idempotent and non-destructive: it only adds two new custom field categories and never
        // touches existing entities.
        \local_entities\local\templates\template_seeder::seed_default_templates();

        // Migrate the two legacy display checkboxes into the global active-view-template setting so
        // existing sites keep their exact look (showpicture → image; show_calendar → calendar; else
        // classic). Runs only while the new setting is unset.
        \local_entities\output\entity_view::migrate_legacy_view_settings();

        // Entities savepoint reached.
        upgrade_plugin_savepoint(true, 2026070301, 'local', 'entities');
    }

    if ($oldversion < 2026101300) {
        // Person entities: the Moodle user an entity of type "person" represents.
        $table = new xmldb_table('local_entities');
        $field = new xmldb_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'availableinsublocations');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $index = new xmldb_index('userid', XMLDB_INDEX_NOTUNIQUE, ['userid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Entities savepoint reached.
        upgrade_plugin_savepoint(true, 2026101300, 'local', 'entities');
    }

    if ($oldversion < 2026101301) {
        // Unavailability of entities (e.g. persons): time ranges with an optional scope.
        $table = new xmldb_table('local_entities_unavailability');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('entityid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('starttime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('endtime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('component', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('area', XMLDB_TYPE_CHAR, '30', null, null, null, null);
        $table->add_field('instanceid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('reason', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('entityid', XMLDB_KEY_FOREIGN, ['entityid'], 'local_entities', ['id']);

        $table->add_index('entity_time', XMLDB_INDEX_NOTUNIQUE, ['entityid', 'starttime', 'endtime']);
        $table->add_index('scope', XMLDB_INDEX_NOTUNIQUE, ['component', 'area', 'instanceid']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Entities savepoint reached.
        upgrade_plugin_savepoint(true, 2026101301, 'local', 'entities');
    }

    if ($oldversion < 2026101302) {
        // Series of unavailability blocks: the recurrence rule and the reference from each block.
        $table = new xmldb_table('local_entities_unavail_series');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('entityid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('component', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('area', XMLDB_TYPE_CHAR, '30', null, null, null, null);
            $table->add_field('instanceid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('weekdays', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
            $table->add_field('allday', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('daystart', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('dayend', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('validfrom', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('validuntil', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timezone', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
            $table->add_field('reason', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
            $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('entityid', XMLDB_KEY_FOREIGN, ['entityid'], 'local_entities', ['id']);

            $dbman->create_table($table);
        }

        // Reference from a block to its series.
        $table = new xmldb_table('local_entities_unavailability');
        $field = new xmldb_field('seriesid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'usermodified');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $index = new xmldb_index('seriesid', XMLDB_INDEX_NOTUNIQUE, ['seriesid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Entities savepoint reached.
        upgrade_plugin_savepoint(true, 2026101302, 'local', 'entities');
    }

    if ($oldversion < 2026101901) {
        // Pools of persons.
        $table = new xmldb_table('local_entities_pool');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
            $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('name', XMLDB_INDEX_UNIQUE, ['name']);

            $dbman->create_table($table);
        }

        // Members of a pool.
        $table = new xmldb_table('local_entities_pool_member');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('poolid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('entityid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
            $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('poolid', XMLDB_KEY_FOREIGN, ['poolid'], 'local_entities_pool', ['id']);
            $table->add_key('entityid', XMLDB_KEY_FOREIGN, ['entityid'], 'local_entities', ['id']);
            $table->add_index('pool_entity', XMLDB_INDEX_UNIQUE, ['poolid', 'entityid']);

            $dbman->create_table($table);
        }

        // Entities savepoint reached.
        upgrade_plugin_savepoint(true, 2026101901, 'local', 'entities');
    }

    if ($oldversion < 2026102002) {
        // Pools became general: a pool has a type, the entity type of its members. The tables created
        // with the first version of the pools lose "people" in their name and keep their data.
        $renames = [
            'local_entities_people_pool' => 'local_entities_pool',
            'local_entities_people_pool_member' => 'local_entities_pool_member',
        ];
        foreach ($renames as $oldname => $newname) {
            if ($dbman->table_exists($oldname) && !$dbman->table_exists($newname)) {
                $dbman->rename_table(new xmldb_table($oldname), $newname);
            }
        }

        $table = new xmldb_table('local_entities_pool');
        $field = new xmldb_field('pooltype', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'person', 'name');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Entities savepoint reached.
        upgrade_plugin_savepoint(true, 2026102002, 'local', 'entities');
    }

    return true;
}
