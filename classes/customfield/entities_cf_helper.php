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

namespace local_entities\customfield;
use local_entities\customfield\entities_handler;

/**
 * Course handler for custom fields
 *
 * @package   local_entities
 * @copyright 2021 Wunderbyte
 * @author    Thomas Winkler
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class entities_cf_helper {
     /**
     * Defines the customfieldarea
     */
    const CFAREA = 'entities';

     /**
     * Defines the customfield Component
     */
    const CFCOMPONENT = 'local_entities';

    /**
     * Gets all customfield categories for entities
     *
     * @return array
     */
    public static function get_all_cf_categories(): array {
        global $DB;
        $sql = 'SELECT itemid, name FROM {customfield_category}
        WHERE area = ? AND component = ? AND sortorder = 0
        GROUP BY itemid, name';
        $records = $DB->get_records_sql_menu($sql, [self::CFAREA, self::CFCOMPONENT]);
        return $records;
    }

    /**
     * Gets categoryname for cfitemid
     *
     * @param int $cfitemid
     * @return string
     */
    public static function get_categoryname(int $cfitemid) {
        global $DB;
        $sql = 'SELECT name FROM {customfield_category}
        WHERE area = ? AND component = ? AND itemid = ? AND sortorder = 0';

        return $DB->get_field_sql($sql, [self::CFAREA, self::CFCOMPONENT, $cfitemid]);
    }

    /**
     * Gets all customfield categories + subcategories for entities
     *
     * @return array
     */
    public static function get_all_cf_categories_with_subcategories(): array {
        global $DB;
        $sql = 'SELECT id, itemid, name
        FROM {customfield_category}
        WHERE area = ? AND component = ?
        ORDER BY itemid, sortorder';
        return $DB->get_records_sql($sql, [self::CFAREA, self::CFCOMPONENT]);
    }

    /**
     * Gets all standard categories for this plugin configured in admin settings
     *
     * @return array
     */
    public static function get_standard_cf_category(): array {
        global $DB;
        $itemids = get_config('local_entities', 'categories');
        $sql = 'SELECT itemid, name FROM {customfield_category}
        WHERE area = ? AND component = ? AND sortorder = 0
        GROUP BY itemid, name';
        $records = $DB->get_records_sql_menu($sql, [self::CFAREA, self::CFCOMPONENT]);
        return $records;
    }

    /**
     * Gets all categories not defined as standard in admin settings.
     *
     * @return array
     */
    public static function get_alternative_cf_categories(): array {
        global $DB;
        $sql = 'SELECT itemid, name FROM {customfield_category}
        WHERE area = ? AND component = ? AND sortorder = 0
        GROUP BY itemid, name';
        $stdcategories = array_flip(\local_entities\settings_manager::get_standardcategories());
        $categories = $DB->get_records_sql_menu($sql, [self::CFAREA, self::CFCOMPONENT]);
        $altcategories = array_diff_key($categories , $stdcategories);
        return $altcategories;
    }

    /**
     * Finds last saved category and increments the category identifer itemid.
     *
     * @return integer
     */
    public static function get_next_itemid(): int {
        global $DB;
        $sql = 'SELECT max(itemid) as count FROM {customfield_category}';
        $record = $DB->get_record_sql($sql);
        if ($record) {
            return $record->count + 1;
        }
        return 1;
    }

    /**
     * Get itemid () function
     *
     * @param int $instanceid
     * @return int
     */
    public static function get_categoryid_from_instanceid(int $instanceid): int {
        global $DB;
        $sql = 'SELECT itemid FROM {customfield_category} WHERE ';
        $record = $DB->get_record_sql($sql);
        if ($record) {
            return $record->count + 1;
        }
        return 1;
    }

    /**
     * Creates all the customfieldhandlers from all defined standardcategories
     *
     * @return array
     */
    public static function create_std_handlers() {
        $categories = \local_entities\settings_manager::get_standardcategories();
        $handlers = array();
        foreach ($categories as $category) {
            $handlers[] = entities_handler::create($category);
        }
        if (empty($handlers)) {
            return array();
        }
        return $handlers;
    }

    /**
     * Creates the categoryhandler for the specific id itemid used as categoryid
     *
     * @param int $itemid
     * @return void
     */
    public static function create_categoryhandler(int $itemid) {
        $handler = entities_handler::create($itemid);
        return $handler;
    }
}
