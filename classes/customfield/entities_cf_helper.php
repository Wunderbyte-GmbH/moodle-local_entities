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
 * Course handler for custom fields
 *
 * @package   core_course
 * @copyright 2018 David Matamoros <davidmc@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_entities\customfield;

defined('MOODLE_INTERNAL') || die;

/**
 * Course handler for custom fields
 *
 * @package   local_entities
 * @copyright 2021 Wunderbyte
 * @author    Thomas Winkler
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class entities_cf_helper {

    const CFAREA = 'entities';

    const CFCOMPONENT = 'local_entities';

    // TODO function descriptons
    /**
     * Run reset code after unit tests to reset the singleton usage.
     */
    public static function get_all_cf_categories(): array {
        global $DB;
        $sql = 'SELECT itemid, name FROM {customfield_category} WHERE area = ? AND component = ? AND sortorder = 0 GROUP BY itemid';
        return $DB->get_records_sql_menu($sql, [self::CFAREA, self::CFCOMPONENT]);
    }

    public static function get_standard_cf_category(): array {
        global $DB;
        $itemids = get_config('local_entities', 'categories');
        $sql = 'SELECT itemid, name FROM {customfield_category} WHERE area = ? AND component = ? AND sortorder = 0 GROUP BY itemid';
        $records = $DB->get_records_sql_menu($sql, [self::CFAREA, self::CFCOMPONENT]);
        return $records;
    }

    public static function get_alternative_cf_categories(): array {
        global $DB;
        $sql = 'SELECT itemid, name FROM {customfield_category} WHERE area = ? AND component = ? AND sortorder = 0 GROUP BY itemid';
        return $DB->get_records_sql_menu($sql, [self::CFAREA, self::CFCOMPONENT]);
    }

    public static function get_next_itemid(): int {
        global $DB;
        $sql = 'SELECT max(itemid) as count FROM {customfield_category}';
        $record = $DB->get_record_sql($sql);
        if ($record) {
            return $record->count + 1;
        }
        return 0;
    }


}
