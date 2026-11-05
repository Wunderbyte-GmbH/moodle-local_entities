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

use core_user\hook\extend_user_menu;

/**
 * Callbacks of the plugin on hooks of Moodle, registered in db/hooks.php.
 *
 * Each callback only decides whether to act and hands the work on, so this class stays small.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Adds the link to the unavailability page to the user menu, for those who can use the page.
     *
     * @param extend_user_menu $hook
     * @return void
     */
    public static function extend_user_menu(extend_user_menu $hook): void {
        if (!unavailability_access::can_open_page()) {
            return;
        }

        $url = new \moodle_url('/local/entities/unavailability.php');
        $title = get_string('unavailability', 'local_entities');

        // Since Moodle 5.3 (MDL-88938) the items of the user menu are objects of their own classes, and
        // add_navitem() with a plain object is deprecated: it shows a debugging notice on every page.
        // We ask the hook what it offers instead of comparing version numbers.
        if (method_exists($hook, 'add_menu_item')) {
            $hook->add_menu_item(new \core_user\output\user_action_menu\link(
                $url,
                $title,
                null,
                new \pix_icon('i/calendar', '')
            ));
            return;
        }

        // Moodle 4.5 to 5.2.
        $item = new \stdClass();
        $item->itemtype = 'link';
        $item->url = $url;
        $item->title = $title;
        $item->titleidentifier = 'unavailability,local_entities';
        $item->pix = 'i/calendar';
        $hook->add_navitem($item);
    }
}
