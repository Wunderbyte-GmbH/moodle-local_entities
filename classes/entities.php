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
     * Returns the equipment available at a location, resolved over the location hierarchy.
     *
     * Equipment is an entity (entitytype='equipment') hung under its home location via parentid.
     * A location offers its own equipment plus the equipment of its ancestor locations, the latter
     * only when that equipment is flagged 'availableinsublocations'.
     *
     * @param int $locationid the location entity id
     * @return array equipment entity records keyed by entity id
     */
    public static function get_equipment_for_location(int $locationid): array {
        global $DB;

        if ($locationid <= 0) {
            return [];
        }

        // Build the location chain upwards: the location itself, then its ancestors.
        $chain = [];
        $current = $locationid;
        $guard = 0;
        while ($current > 0 && $guard++ < 50) {
            if (array_key_exists($current, $chain)) {
                break; // Cycle guard.
            }
            $location = $DB->get_record('local_entities', ['id' => $current], 'id, parentid');
            if (empty($location)) {
                break;
            }
            $chain[$current] = ($current === $locationid);
            $current = (int)($location->parentid ?? 0);
        }

        $equipment = [];
        foreach ($chain as $locid => $isself) {
            foreach (self::list_all_subentities($locid) as $child) {
                if (($child->entitytype ?? 'location') !== 'equipment') {
                    continue;
                }
                // Own equipment is always available; ancestor equipment only if usable in sub-locations.
                if (!$isself && empty($child->availableinsublocations)) {
                    continue;
                }
                $equipment[(int)$child->id] = $child;
            }
        }

        return $equipment;
    }


    /**
     *
     * This is to return all categories and fields from the database
     *
     * @return stdClass
     */
    public function get_categories() {
        $categories = new stdClass();
        return $categories;
    }

    /**
     *
     * This is to set categories and fields from the database
     *
     * @return stdClass
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
     * @param bool $uselive if true, bypass the read cache and query live (for authoritative
     *                      write-time checks such as the actual booking commit)
     * @return array
     */
    public static function get_all_dates_for_entity(int $entityid, bool $uselive = false): array {

        global $DB;

        $cache = \cache::make('local_entities', 'entitydates');

        // Read accelerator for availability checks. Bypassed when an authoritative live result is
        // required (booking commit), so a stale entry never causes a wrong booking.
        if (!$uselive) {
            $cached = $cache->get($entityid);
            if ($cached !== false) {
                return $cached;
            }
        }

        // First we retrieve all the ids and components connected to this entitiy.

        $records = $DB->get_records('local_entities_relations', ['entityid' => $entityid], 'component, area ASC');

        // Create an array for the calls we'll execute afterwards, and remember the booked quantity
        // per relation so we can attach it to the returned dates (capacity mode). The option-level
        // relation (saved by the handler with full form data) is the source of truth for an option's
        // consumed amount; its optiondate-level dates inherit it.
        $calls = [];
        $quantitymap = [];
        $optionlevelquantity = [];
        foreach ($records as $record) {
            $quantitymap["$record->component-$record->area-$record->instanceid"] = (int)($record->quantity ?? 1);
            if ($record->area === 'option') {
                $optionlevelquantity[(int)$record->instanceid] = (int)($record->quantity ?? 1);
            }

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

        // Attach the consumed quantity to each date for capacity-based checks.
        // Resolution mirrors the entity-override/fallback rule: an optiondate that links this entity
        // directly (override) uses its own relation quantity; an optiondate that inherits the option
        // entity (no own entity) uses the owning option's option-level relation quantity. The
        // option-level relation is the source of truth, since the handler saved it with full form data.
        foreach ($datearray as $date) {
            $directkey = "{$date->component}-{$date->area}-{$date->itemid}";
            $ownerid = self::extract_owner_id_from_link($date->link ?? null);

            if ($date->area !== 'option' && $ownerid > 0 && isset($optionlevelquantity[$ownerid])) {
                // Optiondate inheriting the option entity → use the option's consumed amount.
                $date->quantity = $optionlevelquantity[$ownerid];
            } else if (isset($quantitymap[$directkey])) {
                // Option-level date, or an optiondate that overrides with its own entity relation.
                $date->quantity = $quantitymap[$directkey];
            }
        }

        $cache->set($entityid, $datearray);

        return $datearray;
    }

    /** @var array<int, string> Per-request cache of entity allocation modes, keyed by entityid. */
    private static $allocationmodecache = [];

    /** @var array<int, string> Per-request cache of entity capacity sources, keyed by entityid. */
    private static $capacitysourcecache = [];

    /**
     * Reset static request caches (call from tests teardown).
     *
     * @return void
     */
    public static function reset_caches(): void {
        self::$allocationmodecache = [];
        self::$capacitysourcecache = [];
    }

    /**
     * Purge the cached occupancy dates of a single entity.
     *
     * Low-level, entityid-keyed purge. The targeted, item-aware entry point used by consumers is
     * entitiesrelation_handler::purge_dates_cache(), which resolves an item to its entity first.
     *
     * @param int $entityid
     * @return void
     */
    public static function purge_dates_cache(int $entityid): void {
        if ($entityid <= 0) {
            return;
        }
        \cache::make('local_entities', 'entitydates')->delete($entityid);
        unset(self::$allocationmodecache[$entityid]);
        unset(self::$capacitysourcecache[$entityid]);
    }

    /**
     * Returns the allocation (overlap-checking) mode of an entity.
     *
     * 'none' (default) means no overlap checking at all — the legacy behaviour. 'exclusive' counts
     * concurrent reservations, 'capacity' counts participants. Result is request-cached so callers
     * (e.g. per-slot availability checks) do not hit the DB repeatedly.
     *
     * @param int $entityid
     * @return string one of 'none', 'exclusive', 'capacity'
     */
    public static function get_allocation_mode(int $entityid): string {
        if ($entityid <= 0) {
            return 'none';
        }
        if (!array_key_exists($entityid, self::$allocationmodecache)) {
            $entity = entity::load($entityid);
            $mode = $entity->__get('allocationmode');
            self::$allocationmodecache[$entityid] = !empty($mode) ? (string)$mode : 'none';
        }
        return self::$allocationmodecache[$entityid];
    }

    /**
     * Returns the capacity source of an entity: how the consumed amount per booking is determined.
     *
     * 'maxanswers' (default) = participant count of the option; 'manual' = an explicitly entered
     * quantity (e.g. equipment units). Only relevant when allocationmode is 'capacity'. Request-cached.
     *
     * @param int $entityid
     * @return string one of 'maxanswers', 'manual'
     */
    public static function get_capacity_source(int $entityid): string {
        if ($entityid <= 0) {
            return 'maxanswers';
        }
        if (!array_key_exists($entityid, self::$capacitysourcecache)) {
            $entity = entity::load($entityid);
            $source = $entity->__get('capacitysource');
            self::$capacitysourcecache[$entityid] = !empty($source) ? (string)$source : 'maxanswers';
        }
        return self::$capacitysourcecache[$entityid];
    }

    /**
     * Resolves the amount a booking consumes of an entity (capacity mode), from submitted form data.
     *
     * - 'maxanswers': the option's participant cap. An unlimited option (maxanswers <= 0) consumes the
     *   entity's whole capacity, so it blocks any overlapping booking.
     * - 'manual': the explicitly entered quantity field for this entity index.
     *
     * @param int $entityid
     * @param array $formdata submitted booking option form data
     * @param int $index entity field index (0 = option level)
     * @return int consumed amount (>= 0)
     */
    public static function resolve_consumed_quantity(int $entityid, array $formdata, int $index = 0): int {
        if (self::get_capacity_source($entityid) === 'manual') {
            return max(0, (int)($formdata[LOCAL_ENTITIES_FORM_QUANTITY . $index] ?? 1));
        }

        // 'maxanswers' source.
        $maxanswers = (int)($formdata['maxanswers'] ?? 0);
        if ($maxanswers > 0) {
            return $maxanswers;
        }
        // Unlimited option: consume the whole capacity so it blocks any overlap.
        $entity = entity::load($entityid);
        return max(1, (int)($entity->__get('maxallocation') ?? 0));
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
            if (!empty($event->status) && $event->status == 1) {
                $calendarevent->classNames = ['entities-cancelled'];
                $calendarevent->title = '[' . get_string('cancelled', 'local_entities') . '] ' . $calendarevent->title;
            }
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

        // If overlap checking is disabled for this entity ('none', the default), there is never a
        // conflict. Short-circuit before the expensive occupancy query so the legacy/default
        // behaviour stays cheap.
        $allocationmode = self::get_allocation_mode($entityid);
        if ($allocationmode === 'none') {
            return [];
        }

        // Retrieve all the times already booked on this entity (across all options and components).
        // This is an authoritative write-time check, so we read live (bypass cache).
        $bookeddates = self::get_all_dates_for_entity($entityid, true);

        // If there is nothing to compare, we have no conflict.
        if (count($bookeddates) < 1) {
            return [];
        }

        $entity = entity::load($entityid);
        $openinghours = $entity->__get('openinghours');

        // The allocation mode decides how concurrent bookings on the entity are counted:
        // - 'exclusive': the entity is a resource that is occupied per reservation, independent of
        //   the number of participants (e.g. a tennis court). Up to 'maxconcurrent' reservations
        //   may overlap (default 1 = strictly exclusive).
        // - 'capacity': the consumed amounts (quantity per relation; participants or units) of the
        //   overlapping bookings are summed against 'maxallocation' (total capacity / stock).
        $maxconcurrent = (int) ($entity->__get('maxconcurrent') ?? 1);
        if ($maxconcurrent < 1) {
            $maxconcurrent = 1;
        }
        $maxallocation = (int) ($entity->__get('maxallocation') ?? 0);

        $conflicts = [];
        $conflicts['openinghours'] = false;
        $tempconflicts = [];

        foreach ($datestobook as $datetobook) {

            // Opening hours are checked regardless of the allocation mode.
            if (!empty($openinghours)) {
                $openinghoursevents = reoccuringevent::json_to_events($openinghours);
                if (!(reoccuringevent::date_within_openinghours($openinghoursevents, $datetobook))) {
                    $conflicts['openinghours'] = true;
                }
            }

            // The owning item (e.g. booking option) of the candidate date, taken from the shared
            // link reference. An item must NEVER conflict with its own already-stored dates.
            $candidateowner = self::extract_owner_id_from_link($datetobook->link ?? null);

            // Count overlapping reservations (exclusive) and sum their consumed amounts (capacity).
            $overlapping = 0;
            $bookedquantity = 0;
            foreach ($bookeddates as $bookeddate) {
                // Same owning item (identified via the shared link reference): never a self-conflict.
                $bookedowner = self::extract_owner_id_from_link($bookeddate->link ?? null);
                if ($candidateowner > 0 && $candidateowner === $bookedowner) {
                    continue;
                }

                // Generic fallback: skip the booked date of the very item being edited, when the
                // caller identifies it explicitly via component area + item id.
                if (
                    !empty($noconflictarea)
                    && $noconflictarea == $bookeddate->area
                    && (int)$noconflictid === (int)$bookeddate->itemid
                ) {
                    continue;
                }

                if (self::entitydates_overlap($datetobook, $bookeddate)) {
                    $overlapping++;
                    $bookedquantity += max(0, (int)($bookeddate->quantity ?? 1));
                }
            }

            if ($allocationmode === 'capacity') {
                // Capacity mode: a candidate consumes its quantity; the entity is overbooked once the
                // sum of overlapping consumed amounts plus the candidate exceeds the total capacity.
                // maxallocation <= 0 means "no capacity limit" (unlimited).
                $candidate = max(0, (int)($datetobook->quantity ?? 1));
                $isconflict = ($maxallocation > 0) && (($bookedquantity + $candidate) > $maxallocation);
            } else {
                // Exclusive mode: the candidate itself counts as one reservation, so the entity is
                // overbooked as soon as existing overlaps + 1 exceed the allowed concurrency.
                $isconflict = ($overlapping + 1) > $maxconcurrent;
            }

            if ($isconflict) {
                $tempconflicts[] = $datetobook;
            }
        }

        if (!empty($tempconflicts)) {
            $conflicts['conflicts'] = $tempconflicts;
        }

        return $conflicts;
    }

    /**
     * Check whether two entitydate-like objects overlap in time.
     *
     * @param object $a object exposing starttime and endtime
     * @param object $b object exposing starttime and endtime
     * @return bool true if the two time ranges overlap
     */
    private static function entitydates_overlap($a, $b): bool {
        return ($a->starttime >= $b->starttime && $a->starttime < $b->endtime)
            || ($a->endtime > $b->starttime && $a->endtime < $b->endtime)
            || ($a->starttime <= $b->starttime && $a->endtime >= $b->endtime);
    }

    /**
     * Extracts the owning item id (e.g. a booking option id) shared by an entitydate's link.
     *
     * Both the candidate dates and the already-booked dates of mod_booking carry an
     * "optionid" parameter in their link, which uniquely identifies the owning booking option.
     * This lets us reliably exclude an item's own dates from conflict detection even when the
     * surrounding link parameters (userid, returnurl, ...) differ between the two sources.
     *
     * @param mixed $link a moodle_url (or null)
     * @return int the owning option id, or 0 if it cannot be determined
     */
    private static function extract_owner_id_from_link($link): int {
        if (empty($link) || !($link instanceof moodle_url)) {
            return 0;
        }

        $params = $link->params();
        return (int)($params['optionid'] ?? 0);
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
