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
    global $DB;

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
    return true;
}
