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

namespace local_entities\local\views;

/**
 * The available entity types (location / equipment).
 *
 * Central, small list so settings and the per-type view-template logic stay in sync and a future
 * type only needs to be added here (plus its entitytype_<type> language string).
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class entity_types {

    /** @var string[] Known entity type keys (match the entitytype DB column values). */
    const TYPES = ['location', 'equipment'];

    /**
     * Returns the type keys → display names map.
     *
     * @return array<string,string>
     */
    public static function all(): array {
        $list = [];
        foreach (self::TYPES as $type) {
            $list[$type] = get_string('entitytype_' . $type, 'local_entities');
        }
        return $list;
    }

    /**
     * Whether a type key is known.
     *
     * @param string $type
     * @return bool
     */
    public static function exists(string $type): bool {
        return in_array($type, self::TYPES, true);
    }

    /**
     * Display name for a type key (falls back to the raw key).
     *
     * @param string $type
     * @return string
     */
    public static function name(string $type): string {
        return self::exists($type) ? get_string('entitytype_' . $type, 'local_entities') : $type;
    }
}
