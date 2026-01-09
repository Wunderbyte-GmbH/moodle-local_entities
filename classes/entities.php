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
 * local entities
 *
 * @package local_entities
 * @author Thomas Winkler
 * @copyright 2021 Wunderbyte GmbH
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_entities;

use cache_helper;
use DateTime;
use moodle_url;
use stdClass;
use local_entities\calendar\reoccuringevent;
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
        $items = $DB->get_records('local_entities', null, 'name');
        $childs = [];
        foreach ($items as $item) {
            $item->allowedit = $allowedit;

            // Generate delete url for each item.
            $delurl = new moodle_url('/local/entities/entities.php', ['del' => $item->id]);
            $item->delurl = $delurl->out(false);

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
        return $DB->get_records_sql(
            "SELECT * FROM {local_entities}
            WHERE parentid = '0'
            ORDER BY sortorder, name ASC"
        );
    }


    /**
     *
     * This is to return all parent entities from the database
     *
     * @return array Object
     */
    public static function list_all_parent_entities_select(): array {
        global $DB;
        $sql = "SELECT id, name FROM {local_entities} WHERE parentid = '0'
            ORDER BY sortorder, name ASC";
        return $DB->get_records_sql_menu($sql);
    }

    /**
     *
     * This is to update values in the database
     *
     * @param string $table name
     * @param object $change new data
     * @return true in case of success, false otherwise.
     * @throws \invalid_parameter_exception in case parameters were invalid.
     */
    public static function update_entity(string $table, object $change): bool {
        global $DB;
        cache_helper::purge_by_event('purgecachedentities');
        return $DB->update_record($table, $change);
    }

    /**
     *
     * This is to return all children from parententity the database
     * @param int $parentid
     * @return array - returns array of Objects
     */
    public static function list_all_subentities(int $parentid): array {
        global $DB;
        $stmt = "SELECT * FROM {local_entities} WHERE " . "parentid=? ORDER BY sortorder, name ASC";
        return $DB->get_records_sql($stmt, [
            $parentid,
        ]);
    }

    /**
     *
     * This is to return all children from parententity as select from the database
     * @param int $parentid
     * @return array - returns array of Objects
     */
    public static function list_all_subentities_select(int $parentid): array {
        global $DB;
        $stmt = "SELECT id, name FROM {local_entities} WHERE " . "parentid=? ORDER BY sortorder, name ASC";
        return $DB->get_records_sql_menu($stmt, [
            $parentid,
        ]);
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

    /**
     * Function to use callback in connected modules to retrieve all dates...
     * ... connected to this entity.
     *
     * @param int $entityid
     * @return array
     */
    public static function get_all_dates_for_entity(int $entityid): array {

        global $DB;

        // First we retrieve all the ids and components connected to this entitiy.

        $records = $DB->get_records('local_entities_relations', ['entityid' => $entityid], 'component, area ASC');

        // Create an array for the calls we'll execute afterwards.
        $calls = [];
        foreach ($records as $record) {
            // We want to have one call per component.
            if (!isset($calls[$record->component])) {
                $calls[$record->component][$record->area] = [$record->instanceid];
            } else if (isset($calls[$record->component][$record->area])) {
                $calls[$record->component][$record->area][] = $record->instanceid;
            } else {
                $calls[$record->component][$record->area] = [$record->instanceid];
            }
        }

        $datearray = [];
        foreach ($calls as $component => $areas) {
            // Finally, we assemble the array and return it.
            $providerclass = static::get_service_provider_classname($component);

            $newdates = component_class_callback($providerclass, 'return_array_of_entity_dates', [$areas]);

            $datearray = array_merge($datearray, $newdates);
        }

        return $datearray;
    }

    /**
     * Prepares given datearray for fullcalendar js.
     *
     * @param array $datearray
     * @param ?string $bgcolor Background color for calendar
     * @return array
     */
    public static function prepare_datearray_for_calendar(array $datearray, ?string $bgcolor = null): array {
        $bgcolor = $bgcolor ?? get_config('local_entities', 'calendarcolor');
        $calendarevents = [];
        foreach ($datearray as $event) {
            $calendarevent = $event;
            $calendarevent->allDay = false;
            $calendarevent->extendedProps['department'] = 'test';
            if ($event->starttime) {
                $calendarevent->title = $event->name;
                $start = new DateTime();
                $start->setTimestamp($event->starttime);
                $calendarevent->start = $start->format('Y-m-d') . 'T' . $start->format('H:i:s');
            }
            if ($event->endtime) {
                $calendarevent->title = $event->name;
                $end = new DateTime();
                $end->setTimestamp($event->endtime);
                $calendarevent->end = $end->format('Y-m-d') . 'T' . $end->format('H:i:s');
            }

            $calendarevent->url = !empty($event->link) ? $event->link->out(false) : '';
            $calendarevent->backgroundColor = !empty($event->bgcolor) ? $event->bgcolor : $bgcolor;
            $calendarevents[] = $calendarevent;
        }
        return $calendarevents;
    }

    /**
     * Function to check if the function entitiy is available.
     * @param int $entityid
     * @param array $datestobook
     * @param int $noconflictid
     * @param string $noconflictarea
     * @return array
     */
    public static function return_conflicts(
        int $entityid,
        array $datestobook = [],
        $noconflictid = 0,
        $noconflictarea = ''
    ) {

        // First, if there is nothing to compare, we have no conflict.
        if (count($datestobook) < 1) {
            return [];
        }

        // Second we retrieve all the times already booked on this option.
        $bookeddates = self::get_all_dates_for_entity($entityid);

        // Third, if there is nothing to compare, we have no conflict.
        if (count($bookeddates) < 1) {
            return [];
        }

        // Now we check every date one by one, if there is an overlapping with the existing timestamps.
        // We might have a function to do just that, so we don't write it now.

        $conflicts = [];
        $tempconflicts = [];
        $conflicts['openinghours'] = false;
        foreach ($datestobook as $datetobook) {
            $entity = entity::load($entityid);
            $openinghours = $entity->__get('openinghours');
            if (!empty($openinghours)) {
                $openinghoursevents = reoccuringevent::json_to_events($openinghours);
                if (!(reoccuringevent::date_within_openinghours($openinghoursevents, $datetobook))) {
                    $conflicts['openinghours'] = true;
                }
            }
            $maxallocations = $entity->__get('maxallocation');
            if ($maxallocations > 0) {
                foreach ($bookeddates as $bookeddate) {
                    if ($datetobook->link->out() === $bookeddate->link->out()) {
                        continue;
                    }

                    // Avoid conflicts with itself.
                    if ($noconflictarea == $datetobook->area && $noconflictid == $datetobook->itemid) {
                        continue;
                    }

                    if (
                        ($datetobook->starttime >= $bookeddate->starttime && $datetobook->starttime < $bookeddate->endtime)
                        || ($datetobook->endtime > $bookeddate->starttime && $datetobook->endtime < $bookeddate->endtime)
                        || ($datetobook->starttime <= $bookeddate->starttime && $datetobook->endtime >= $bookeddate->endtime)
                    ) {
                            $tempconflicts[] = $datetobook;
                    }
                }
            }
            if (count($tempconflicts) > $maxallocations) {
                $conflicts['conflicts'] = $tempconflicts;
            }
        }
        return $conflicts;
    }

    /**
     * Get the name of the service provider class
     *
     * @param string $component The component
     * @return string
     * @throws \coding_exception
     */
    private static function get_service_provider_classname(string $component) {
        $providerclass = "$component\\entities\\service_provider";

        if (class_exists($providerclass)) {
            $rc = new \ReflectionClass($providerclass);
            if ($rc->implementsInterface(local\callback\service_provider::class)) {
                return $providerclass;
            }
        }
        throw new \coding_exception("$component does not have an eligible implementation of entities service_provider.");
    }

    /**
     * Helper function to clean up entities table.
     */
    public static function clean_up_entities_db() {
        global $DB;

        $records = $DB->get_records_sql(
            "SELECT DISTINCT id
            FROM {local_entities}
            WHERE (name IS NULL OR name = '')
            OR (parentid <> 0 AND parentid NOT IN (SELECT id FROM {local_entities}))
            UNION
            SELECT DISTINCT entityid
            FROM {local_entities_relations}
            WHERE entityid NOT IN (SELECT id FROM {local_entities})
            UNION
            SELECT DISTINCT entityidto
            FROM {local_entities_contacts}
            WHERE entityidto NOT IN (SELECT id FROM {local_entities})
            UNION
            SELECT DISTINCT entityidto
            FROM {local_entities_address}
            WHERE entityidto NOT IN (SELECT id FROM {local_entities})"
        );

        // We also delete orphaned custom fields and images (plugin files).
        if (!empty($records)) {
            foreach ($records as $record) {
                $entity = entity::load($record->id);
                $cfitemid = $entity->__get('cfitemid') ?? 0;
                $entitysettingsmanager = new settings_manager($record->id);
                $entitysettingsmanager->delete_cfhandlers($cfitemid);
                $entitysettingsmanager->delete_pluginfiles($record->id);
            }
        }

        // Delete all entities with missing names.
        $DB->delete_records_select('local_entities', "name IS NULL OR name = ''");

        // This has to happen BEFORE we delete associated addresses, contacts and relations!
        $DB->delete_records_select(
            'local_entities',
            'parentid <> 0 AND parentid NOT IN (SELECT id FROM (SELECT id FROM {local_entities}) t)'
        ); // Extra subquery to avoid "You can't specify target table for update in FROM clause" error under MySQL.

        // Now we can delete relations, contacts and addresses of already deleted entities.
        $DB->delete_records_select(
            'local_entities_relations',
            'entityid NOT IN (SELECT id FROM {local_entities})'
        );
        $DB->delete_records_select(
            'local_entities_contacts',
            'entityidto NOT IN (SELECT id FROM {local_entities})'
        );
        $DB->delete_records_select(
            'local_entities_address',
            'entityidto NOT IN (SELECT id FROM {local_entities})'
        );
    }
}
