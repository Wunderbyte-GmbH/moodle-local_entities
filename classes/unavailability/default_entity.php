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

namespace local_entities\unavailability;

use local_entities\persons;

/**
 * Decides which entity the unavailability page opens when no entity is asked for.
 *
 * - Someone who is a person gets the own person.
 * - Someone who is no person but may manage other entities gets the first entity of the selection.
 * - Everybody else gets nothing. Nobody becomes a person by visiting the page.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class default_entity {
    /**
     * Returns the id of the entity to open.
     *
     * @param int|null $userid null for the current user
     * @return int|null entity id, or null if there is nothing the user may open
     */
    public function resolve(?int $userid = null): ?int {
        global $USER;

        $userid = $userid ?? (int)$USER->id;

        $own = persons::get_entityid_for_user($userid);
        if ($own !== null) {
            return $own;
        }

        $groups = (new manageable_entities())->find($userid);
        $first = reset($groups);
        return $first ? (int)array_key_first($first) : null;
    }
}
