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

namespace local_entities\pool;

use local_entities\persons;

/**
 * The types a pool can have.
 *
 * Persons and equipment are enabled. To enable another type, add the entity type to {@see self::ENABLED}
 * and the texts pooltype_<type> and pool_add_<type> to the language files.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class pool_types {
    /** @var string[] The enabled types. */
    private const ENABLED = [persons::TYPE, 'equipment'];

    /** @var string[] Icon (Font Awesome class) of each type. */
    private const ICONS = [persons::TYPE => 'fa-users', 'equipment' => 'fa-cube'];

    /**
     * Returns the enabled types with their names.
     *
     * @return string[] names, keyed by type, in the order of {@see self::ENABLED}
     */
    public static function all(): array {
        $types = [];
        foreach (self::ENABLED as $type) {
            $types[$type] = get_string('pooltype_' . $type, 'local_entities');
        }
        return $types;
    }

    /**
     * Is this an enabled type?
     *
     * @param string $type
     * @return bool
     */
    public static function exists(string $type): bool {
        return in_array($type, self::ENABLED, true);
    }

    /**
     * Returns the icon of a type.
     *
     * @param string $type
     * @return string Font Awesome class
     */
    public static function icon(string $type): string {
        return self::ICONS[$type] ?? 'fa-folder-o';
    }

    /**
     * Returns the SQL condition that selects the entities of a type.
     *
     * Older locations may have no type at all, they count as locations.
     *
     * @param string $type
     * @param string $alias alias of the table local_entities in the query
     * @param string $param name of the parameter that has to be bound to the type
     * @return string
     */
    public static function entity_condition(string $type, string $alias = 'e', string $param = 'pooltype'): string {
        $condition = "{$alias}.entitytype = :{$param}";
        if ($type === 'location') {
            $condition = "({$condition} OR {$alias}.entitytype IS NULL)";
        }
        return $condition;
    }

    /**
     * Returns the entities that can be members of a pool of a type, for a selection.
     *
     * @param string $type
     * @return string[] names, keyed by entity id, ordered by name; empty for a type that is not enabled
     */
    public static function member_options(string $type): array {
        global $DB;

        if (!self::exists($type)) {
            return [];
        }

        if ($type === persons::TYPE) {
            $namefields = \core_user\fields::for_name()->get_sql('u', false, '', '', false)->selects;
            $records = $DB->get_records_sql(
                "SELECT e.id, $namefields
                   FROM {local_entities} e
                   JOIN {user} u ON u.id = e.userid
                  WHERE e.entitytype = :pooltype
                    AND u.deleted = 0
               ORDER BY u.lastname, u.firstname, e.id",
                ['pooltype' => $type]
            );
            return array_map(fn($record) => fullname($record), $records);
        }

        $records = $DB->get_records_sql(
            "SELECT e.id, e.name
               FROM {local_entities} e
              WHERE " . self::entity_condition($type) . "
                AND e.name <> ''
           ORDER BY e.name, e.id",
            ['pooltype' => $type]
        );
        return array_map(fn($record) => $record->name, $records);
    }
}
