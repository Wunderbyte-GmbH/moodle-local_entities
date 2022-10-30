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

function xmldb_local_entities_upgrade($oldversion) {
    global $DB, $CFG;

    require_once($CFG->dirroot . '/local/entities/db/upgradelib.php');

    $dbman = $DB->get_manager();

    if ($oldversion < 2022071800) {
        $table = new xmldb_table('local_entities');

        // End of fix #190.
        $pricefactor = new xmldb_field('pricefactor',  XMLDB_TYPE_NUMBER, '10, 2', null, null, null, '1', null);

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


    return true;
}
