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
 * Tells other plugins what an entity is: its type, its name and its icon.
 *
 * Plugins that assign entities to something (a booking, a lesson, a shift) must not know how a name
 * is made. A person is shown with the name of its Moodle user, other types with their own name. This
 * class is the one place that knows, so a new type only needs to be added here.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class entity_directory {
    /** @var string[] Icon (Font Awesome class) of each type. */
    private const ICONS = ['person' => 'fa-user', 'equipment' => 'fa-cube', 'location' => 'fa-map-marker'];

    /**
     * Describes entities, one query for any number of them.
     *
     * Entities that do not exist, and persons whose user was deleted, are missing in the result, so the
     * caller can tell.
     *
     * @param int[] $entityids
     * @return array[] keyed by entity id, each ['id' => int, 'type' => string, 'name' => string,
     *                 'icon' => string, 'userid' => int]; userid is 0 for everything but persons
     */
    public static function describe(array $entityids): array {
        global $DB;

        $entityids = array_values(array_unique(array_filter(array_map('intval', $entityids))));
        if (empty($entityids)) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal($entityids, SQL_PARAMS_NAMED, 'ent');
        $namefields = \core_user\fields::for_name()->get_sql('u', false, 'u_', '', false)->selects;
        $records = $DB->get_records_sql(
            "SELECT e.id, e.entitytype, e.name, e.userid, u.deleted AS u_deleted, $namefields
               FROM {local_entities} e
          LEFT JOIN {user} u ON u.id = e.userid
              WHERE e.id $insql",
            $params
        );

        $result = [];
        foreach ($records as $record) {
            $type = (string)($record->entitytype ?? '');
            $type = $type === '' ? 'location' : $type;
            $name = (string)$record->name;
            $userid = 0;
            if ($type === persons::TYPE) {
                if (empty($record->userid) || !empty($record->u_deleted)) {
                    continue;
                }
                $userid = (int)$record->userid;
                $name = fullname(self::user_of($record));
            }
            $result[(int)$record->id] = [
                'id' => (int)$record->id,
                'type' => $type,
                'name' => $name,
                'icon' => self::icon($type),
                'userid' => $userid,
            ];
        }
        return $result;
    }

    /**
     * Returns the icon of an entity type.
     *
     * @param string $type
     * @return string Font Awesome class
     */
    public static function icon(string $type): string {
        return self::ICONS[$type] ?? 'fa-map-marker';
    }

    /**
     * Builds a user object with the name fields from a row that has them with the prefix u_.
     *
     * @param \stdClass $record row of {@see self::describe()}
     * @return \stdClass
     */
    private static function user_of(\stdClass $record): \stdClass {
        $user = new \stdClass();
        foreach (\core_user\fields::get_name_fields() as $field) {
            $user->$field = $record->{'u_' . $field} ?? '';
        }
        return $user;
    }
}
