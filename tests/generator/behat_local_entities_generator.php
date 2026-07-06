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
 * Behat data generator for local_entities.
 *
 * @package   local_entities
 * @category  test
 * @copyright 2024 Wunderbyte GmbH <info@wunderbyte.at>
 * @author Andrii Semenets
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_local_entities_generator extends behat_generator_base {
    /**
     * Get a list of the entities that Behat can create using the generator step.
     *
     * @return array
     */
    protected function get_creatable_entities(): array {
        return [
            'entities' => [
                'datagenerator' => 'entities',
                'required' => ['name'],
                'switchids' => ['parent' => 'parentid'],
            ],
        ];
    }

    /**
     * Resolve an entity name to its id (for the 'parent' reference column).
     *
     * @param string $name entity name
     * @return int entity id
     */
    protected function get_parent_id(string $name): int {
        global $DB;
        // A top-level entity has no parent: the framework still calls this resolver for the (empty)
        // 'parent' cell, so treat an empty reference as "no parent" (parentid 0) instead of looking
        // up an entity named '' and failing with a missing-record exception.
        if (trim($name) === '') {
            return 0;
        }
        return (int)$DB->get_field('local_entities', 'id', ['name' => $name], MUST_EXIST);
    }
}
