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

namespace local_entities;

/**
 * Who may see a person as an entity: in lists, in a search, in a selection and on the detail page.
 *
 * A person is personal data. Only the person itself and everyone with the right
 * local/entities:manageunavailability may see it. Places and equipment are not affected, they are
 * visible as before. Guests and users that are not logged in never see a person.
 *
 * Every place that shows entities asks this class, so the rule exists only once.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class person_visibility {
    /**
     * May the user see the entity?
     *
     * @param \stdClass $entity record of local_entities with entitytype and userid
     * @param int|null $userid null for the current user
     * @return bool
     */
    public static function can_see(\stdClass $entity, ?int $userid = null): bool {
        if (($entity->entitytype ?? '') !== persons::TYPE) {
            return true;
        }

        $userid = unavailability_access::resolve_userid($userid);
        if ($userid === 0) {
            return false;
        }
        return (int)($entity->userid ?? 0) === $userid || unavailability_access::can_manage_any($userid);
    }

    /**
     * May the user see the entity with this id? An entity that does not exist can not be seen.
     *
     * @param int $entityid
     * @param int|null $userid null for the current user
     * @return bool
     */
    public static function can_see_id(int $entityid, ?int $userid = null): bool {
        global $DB;

        $entity = $DB->get_record('local_entities', ['id' => $entityid], 'id, entitytype, userid');
        return $entity !== false && self::can_see($entity, $userid);
    }

    /**
     * A condition for a query that leaves out the persons the user may not see.
     *
     * @param string $alias alias of the table local_entities in the query
     * @param int|null $userid null for the current user
     * @param string $prefix prefix of the names of the parameters, to use the condition twice in a query
     * @return array the condition as sql, and its parameters
     */
    public static function sql_condition(string $alias = 'e', ?int $userid = null, string $prefix = 'pv'): array {
        $userid = unavailability_access::resolve_userid($userid);
        if ($userid !== 0 && unavailability_access::can_manage_any($userid)) {
            return ['1 = 1', []];
        }

        $sql = "({$alias}.entitytype IS NULL OR {$alias}.entitytype <> :{$prefix}type OR {$alias}.userid = :{$prefix}user)";
        return [$sql, [$prefix . 'type' => persons::TYPE, $prefix . 'user' => $userid]];
    }
}
