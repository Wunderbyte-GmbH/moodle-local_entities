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

use local_entities\settings_manager;

/**
 * Class local_entities_generator for generation of dummy data
 *
 * @package local_entities
 * @category test
 * @copyright 2024 Wunderbyte GmbH <info@wunderbyte.at>
 * @author Andrii Semenets
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_entities_generator extends testing_module_generator {
    /**
     * Create entities.
     *
     * @param array $data
     * @return int
     */
    public function create_entities(array $data): int {

        $data = (object)(array) $data;
        $sm = new settings_manager();
        $id = $sm->update_or_createentity($data);
        return $id;
    }
}
