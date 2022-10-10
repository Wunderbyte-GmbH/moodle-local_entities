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
 * local pages
 *
 * @package local_entities
 * @author Thomas Winkler
 * @copyright 2021 Wunderbyte GmbH
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_entities;

use stdClass;
defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once("$CFG->libdir/externallib.php");

/**
 * Class entity
 *
 * @author Thomas Winkler
 * @copyright 2021 Wunderbyte GmbH
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class entities {

    /**
     * entities constructor.
     */
    public function __construct() {
        // Empty?
    }


     /**
      * Get all Objects
      *
      * @return array Object
      */
    public function prepare_for_select(): array {
        return [];
    }

    /**
     * Either returns one tree or treearray for every parentnode
     *
     * @param int $fulltree
     * @param boolean $allowedit
     * @return array
     */
    public static function build_whole_entitytree(int $fulltree = 0, $allowedit = true): array {
        global $DB;
        $items = $DB->get_records('local_entities');
        $childs = array();
        foreach ($items as $item) {
                $item->allowedit = $allowedit;
                $childs[$item->parentid][] = $item;
        }
        foreach ($items as $item) {
            if (isset($childs[$item->id])) {
                    $item->childs = $childs[$item->id];
            } else {
                $item->childs = false;
                $item->leaf = true;
            }
        }
        $itemtree = $fulltree ? $childs : $childs[0];
        return $itemtree;
    }

    /**
     * Get all Objects
     *
     * @return array Object
     */
    public static function list_all_entities(): array {
        global $DB;
        $sql = "SELECT id,
            CASE
                WHEN parentid = '0' THEN name
                ELSE concat('-', name)
            END newname
            FROM {local_entities}
            order by coalesce(parentid, id), parentid <> '0', id";
        return $DB->get_records_sql($sql);
    }

    /**
     *
     * This is to return all parent entities from the database
     *
     * @return array Object
     */
    public static function list_all_parent_entities(): array {
        global $DB;
        $stmt = "SELECT * FROM {local_entities} WHERE parentid = '0' ORDER BY sortorder, timecreated";
        return $DB->get_records_sql($stmt);
    }


    /**
     *
     * This is to return all parent entities from the database
     *
     * @return array Object
     */
    public static function list_all_parent_entities_select(): array {
        global $DB;
        $sql = "SELECT id, name FROM {local_entities} WHERE parentid = '0' ORDER BY sortorder, timecreated";
        return $DB->get_records_sql_menu($sql);
    }

    /**
     *
     * This is to update values in the database
     *
     * @param array of objects with information on what to update:
     *      field name, id of the record, newvalue
     * @return true in case of success, false otherwise.
     * @throws \invalid_parameter_exception in case parameters were invalid.
     */
    public static function update_entity(string $table, object $change): bool {
        global $DB;
        return $DB->update_record($table, $change);
    }

    /**
     *
     * This is to return all children from parententity the database
     *
     * @return array - returns array of Objects
     */
    public static function list_all_subentities(int $parentid): array {
        global $DB;
        $stmt = "SELECT * FROM {local_entities} WHERE " . "parentid=? ORDER BY sortorder";
        return $DB->get_records_sql($stmt, array(
            $parentid
        ));
    }

    /**
     *
     * This is to return all children from parententity the database
     *
     * @return array - returns array of Objects
     */
    public static function list_all_subentities_select(int $parentid): array {
        global $DB;
        $sql = "SELECT id, name FROM {local_entities} WHERE " . "parentid=? ORDER BY sortorder";
        return $DB->get_records_sql_menu($sql, array(
            $parentid
        ));
    }

    /**
     *
     * This is to return all categories and fields from the database
     *
     * @return Object
     */
    public function get_categories() {
        $categories = new stdClass();
        return $categories;
    }

    /**
     *
     * This is to set categories and fields from the database
     *
     * @return Object
     */
    public function set_categories() {
        $categories = new stdClass();
        return $categories;
    }
}
