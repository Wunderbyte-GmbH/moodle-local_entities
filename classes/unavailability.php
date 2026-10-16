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

use local_entities\unavailability\block_repository;
use local_entities\unavailability\conflict_detector;
use local_entities\unavailability\entities_occupation_source;
use local_entities\unavailability\scope;
use local_entities\unavailability\scope_filter;
use local_entities\unavailability\series_repository;
use local_entities\unavailability\series_rule;
use local_entities\unavailability\time_range;

/**
 * Unavailability of entities, in particular of persons (e.g. examiners).
 *
 * A block is a time range in which an entity can not be used. It applies everywhere (empty
 * component) or only inside a scope given as component, area and instance id, for example
 * mod_booking / option / 12 or mod_booking / booking / 3.
 *
 * Scopes are always passed as a list of ['component' => ..., 'area' => ..., 'instanceid' => ...].
 * Null means "ignore the scope" (all blocks), a list means "blocks that apply everywhere plus blocks
 * of exactly these scopes". An empty list therefore returns only the blocks that apply everywhere.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unavailability {
    /**
     * Returns the blocks of an entity that overlap a time range.
     *
     * @param int $entityid
     * @param int $from start of the range (unix timestamp)
     * @param int $until end of the range (unix timestamp)
     * @param array|null $scopes null for all blocks, otherwise the scopes that apply (see class doc)
     * @return \stdClass[] blocks ordered by start time, keyed by id
     */
    public static function get_blocks(int $entityid, int $from, int $until, ?array $scopes = null): array {
        return (new block_repository())->find($entityid, $from, $until, scope_filter::from_nullable($scopes));
    }

    /**
     * Returns the users, out of a list, that are blocked somewhere in a time range.
     *
     * Users who are no person yet can not have blocks and are never returned. One query for any
     * number of users, so it is cheap to call for a whole examiner pool.
     *
     * @param int[] $userids users to check
     * @param int $from start of the range (unix timestamp)
     * @param int $until end of the range (unix timestamp)
     * @param array|null $scopes null for all blocks, otherwise the scopes that apply (see class doc)
     * @return int[] ids of the blocked users
     */
    public static function get_unavailable_userids(array $userids, int $from, int $until, ?array $scopes = null): array {
        return (new block_repository())->find_blocked_userids($userids, $from, $until, scope_filter::from_nullable($scopes));
    }

    /**
     * Replaces the blocks of one scope inside a time window and leaves everything outside untouched.
     *
     * Inside the window the stored blocks of this scope become exactly the given blocks. A stored
     * block that reaches over the window edge keeps its part outside the window. Overlapping or
     * directly adjacent blocks with the same scope, the same reason and the same series are merged
     * into one. Blocks of different series, and blocks of a series and single blocks, are never merged.
     *
     * @param int $entityid
     * @param int $from start of the window (unix timestamp)
     * @param int $until end of the window (unix timestamp)
     * @param array $scope ['component' => ..., 'area' => ..., 'instanceid' => ...], empty component = everywhere
     * @param array $blocks list of ['starttime' => ..., 'endtime' => ..., 'reason' => ..., 'seriesid' => ...]
     * @return void
     */
    public static function replace_in_window(int $entityid, int $from, int $until, array $scope, array $blocks): void {
        $ranges = [];
        foreach ($blocks as $block) {
            $ranges[] = new time_range(
                (int)($block['starttime'] ?? 0),
                (int)($block['endtime'] ?? 0),
                (string)($block['reason'] ?? ''),
                (int)($block['seriesid'] ?? 0)
            );
        }

        (new block_repository())->replace_in_window($entityid, $from, $until, scope::from_array($scope), $ranges);
    }

    /**
     * Creates a series: stores its rule and the single blocks the rule describes.
     *
     * The blocks are normal blocks that carry the id of the series, so each of them can be removed on
     * its own. The weekdays and times of the rule are meant in the timezone of the rule.
     *
     * @param int $entityid
     * @param array $rule weekdays (1 = Monday to 7 = Sunday), allday, daystart and dayend (seconds since
     *                    midnight, only if not allday), validfrom and validuntil (any time on the first and
     *                    the last day), timezone, reason
     * @param array $scope ['component' => ..., 'area' => ..., 'instanceid' => ...], empty = everywhere
     * @return int id of the series
     */
    public static function create_series(int $entityid, array $rule, array $scope = []): int {
        return (new series_repository())->create($entityid, series_rule::from_array($rule), scope::from_array($scope));
    }

    /**
     * Changes the rule of a series.
     *
     * From the start of today on (in the timezone of the rule) the blocks of the series are replaced by
     * the blocks of the new rule. What lies in the past stays as it was. The entity and the scope of a
     * series can not be changed.
     *
     * @param int $seriesid
     * @param array $rule see create_series()
     * @return void
     */
    public static function update_series(int $seriesid, array $rule): void {
        (new series_repository())->update($seriesid, series_rule::from_array($rule));
    }

    /**
     * Deletes a series from today on.
     *
     * The blocks from the start of today on (in the timezone of the series) are removed, what lies in
     * the past stays. If no block stays, the series is deleted completely, otherwise it ends yesterday.
     *
     * @param int $seriesid
     * @return void
     */
    public static function delete_series(int $seriesid): void {
        (new series_repository())->delete($seriesid);
    }

    /**
     * Returns the series of an entity.
     *
     * @param int $entityid
     * @param array|null $scopes null for all series, otherwise the scopes that apply (see class doc)
     * @param int|null $activefrom if set, only series whose last day is not before this time are returned
     * @return \stdClass[] series ordered by first day, keyed by id
     */
    public static function get_series(int $entityid, ?array $scopes = null, ?int $activefrom = null): array {
        return (new series_repository())->find($entityid, scope_filter::from_nullable($scopes), $activefrom);
    }

    /**
     * Returns the occupations of an entity that overlap time ranges.
     *
     * Used to refuse an unavailability while the person is already assigned to something. The
     * occupations are read live, so a stale cache can not hide an assignment.
     *
     * @param int $entityid
     * @param array $ranges list of ['starttime' => ..., 'endtime' => ...]
     * @return array see conflict_detector::find()
     */
    public static function get_conflicts(int $entityid, array $ranges): array {
        return (new conflict_detector(new entities_occupation_source()))->find($entityid, $ranges);
    }
}
