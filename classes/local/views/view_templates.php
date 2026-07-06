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
 * Registry of the available entity detail-view templates.
 *
 * Each template is a key + a display name + an icon and maps to a mustache file
 * `local_entities/view/<key>`. Adding a template = add an entry here + the mustache file.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class view_templates {
    /** @var string The baseline template, byte-identical to the historical layout. */
    const DEFAULT_TEMPLATE = 'classic';

    /**
     * Ordered list of template keys → FontAwesome icon. Names come from language strings
     * `viewtemplate_<key>` so the registry stays the single source of truth.
     *
     * @var array<string,string>
     */
    const TEMPLATES = [
        'classic' => 'fa-table-columns',
        'image' => 'fa-image',
        'calendar' => 'fa-calendar',
        'compact' => 'fa-id-card',
        'map' => 'fa-map-marker',
    ];

    /**
     * Returns all templates as display records (key, name, icon).
     *
     * @return array<int,\stdClass>
     */
    public static function get_all(): array {
        $list = [];
        foreach (self::TEMPLATES as $key => $icon) {
            $list[] = (object)[
                'key' => $key,
                'name' => get_string('viewtemplate_' . $key, 'local_entities'),
                'icon' => $icon,
            ];
        }
        return $list;
    }

    /**
     * Returns the template keys → names map (e.g. for an admin select).
     *
     * @return array<string,string>
     */
    public static function menu(): array {
        $menu = [];
        foreach (array_keys(self::TEMPLATES) as $key) {
            $menu[$key] = get_string('viewtemplate_' . $key, 'local_entities');
        }
        return $menu;
    }

    /**
     * Whether a template key is known.
     *
     * @param string $key
     * @return bool
     */
    public static function exists(string $key): bool {
        return $key !== '' && isset(self::TEMPLATES[$key]);
    }
}
