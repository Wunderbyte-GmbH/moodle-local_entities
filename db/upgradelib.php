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
 * @author     Bernhard Fischer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Function to upgrade local_entities_relations
 * for savepoint 2022101400.
 * Write "mod_booking" into empty "component" rows.
 * @return void
 */
function fix_entities_relations_2022101400(): void {
    global $DB;
    if ($recordstoupdate = $DB->get_records('local_entities_relations', ['component' => null])) {
        foreach ($recordstoupdate as $record) {
            $record->component = 'mod_booking';
            $DB->update_record('local_entities_relations', $record);
        }
    }
}

/**
 * Function to upgrade local_entities_relations
 * for savepoint 2022101401.
 * Change value "bookingoption" to just "option" for values of column "area".
 * @return void
 */
function fix_entities_relations_2022101401(): void {
    global $DB;
    if ($recordstoupdate = $DB->get_records('local_entities_relations', ['area' => 'bookingoption'])) {
        foreach ($recordstoupdate as $record) {
            $record->area = 'option';
            $DB->update_record('local_entities_relations', $record);
        }
    }
}
