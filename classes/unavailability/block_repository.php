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

namespace local_entities\unavailability;

use local_entities\persons;

/**
 * Storage of the single blocks of an unavailability (table local_entities_unavailability).
 *
 * It only reads and writes blocks. Rules, series and the question whether a block collides with a
 * booking are handled elsewhere.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_repository {
    /** @var string Table of the blocks. */
    private const TABLE = 'local_entities_unavailability';

    /**
     * Returns the blocks of an entity that overlap a time range.
     *
     * @param int $entityid
     * @param int $from start of the range (unix timestamp)
     * @param int $until end of the range (unix timestamp)
     * @param scope_filter $filter which scopes are selected
     * @return \stdClass[] blocks ordered by start time, keyed by id
     */
    public function find(int $entityid, int $from, int $until, scope_filter $filter): array {
        global $DB;

        if ($entityid <= 0 || $until <= $from) {
            return [];
        }

        [$scopesql, $scopeparams] = $filter->to_sql('u', 'sc');
        $params = ['entityid' => $entityid, 'rangefrom' => $from, 'rangeuntil' => $until] + $scopeparams;

        return $DB->get_records_sql(
            "SELECT u.*
               FROM {" . self::TABLE . "} u
              WHERE u.entityid = :entityid
                AND u.starttime < :rangeuntil
                AND u.endtime > :rangefrom
                AND $scopesql
           ORDER BY u.starttime, u.id",
            $params
        );
    }

    /**
     * Returns the users, out of a list, that are blocked somewhere in a time range.
     *
     * Users who are no person yet can not have blocks and are never returned. One query for any
     * number of users.
     *
     * @param int[] $userids users to check
     * @param int $from start of the range (unix timestamp)
     * @param int $until end of the range (unix timestamp)
     * @param scope_filter $filter which scopes are selected
     * @return int[] ids of the blocked users
     */
    public function find_blocked_userids(array $userids, int $from, int $until, scope_filter $filter): array {
        global $DB;

        $userids = array_values(array_unique(array_filter(array_map('intval', $userids))));
        if (empty($userids) || $until <= $from) {
            return [];
        }

        [$usersql, $userparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'uid');
        [$scopesql, $scopeparams] = $filter->to_sql('u', 'sc');
        $params = [
            'persontype' => persons::TYPE,
            'rangefrom' => $from,
            'rangeuntil' => $until,
        ] + $userparams + $scopeparams;

        $blocked = $DB->get_fieldset_sql(
            "SELECT DISTINCT e.userid
               FROM {" . self::TABLE . "} u
               JOIN {local_entities} e ON e.id = u.entityid
              WHERE e.entitytype = :persontype
                AND e.userid $usersql
                AND u.starttime < :rangeuntil
                AND u.endtime > :rangefrom
                AND $scopesql",
            $params
        );

        return array_map('intval', $blocked);
    }

    /**
     * Replaces the blocks of one scope inside a time window and leaves everything outside untouched.
     *
     * Inside the window the stored blocks of this scope become exactly the given ranges. A stored
     * block that reaches over the window edge keeps its part outside the window. Ranges of the same
     * reason and series that overlap or touch are merged. A range may only refer to a series of the
     * same entity, otherwise it is stored as a single block.
     *
     * @param int $entityid
     * @param int $from start of the window (unix timestamp)
     * @param int $until end of the window (unix timestamp)
     * @param scope $scope
     * @param time_range[] $ranges
     * @return void
     */
    public function replace_in_window(int $entityid, int $from, int $until, scope $scope, array $ranges): void {
        global $DB;

        if ($entityid <= 0) {
            throw new \coding_exception('An entity id is required.');
        }
        if ($until <= $from) {
            throw new \coding_exception('The end of the window must be after its start.');
        }

        // Existing blocks of exactly this scope that overlap or touch the window.
        [$scopesql, $scopeparams] = scope_filter::exactly($scope)->to_sql('', 'sc');
        $params = ['entityid' => $entityid, 'rangefrom' => $from, 'rangeuntil' => $until] + $scopeparams;
        $existing = $DB->get_records_select(
            self::TABLE,
            "entityid = :entityid AND starttime <= :rangeuntil AND endtime >= :rangefrom AND $scopesql",
            $params,
            'starttime, id'
        );

        $knownseries = $DB->get_fieldset_select(
            'local_entities_unavail_series',
            'id',
            'entityid = :entityid',
            ['entityid' => $entityid]
        );
        $knownseries = array_flip(array_map('intval', $knownseries));

        // What stays: the parts of the existing blocks outside of the window.
        $candidates = [];
        foreach ($existing as $block) {
            $reason = (string)($block->reason ?? '');
            $seriesid = (int)($block->seriesid ?? 0);
            if ($block->starttime < $from) {
                $candidates[] = new time_range((int)$block->starttime, min((int)$block->endtime, $from), $reason, $seriesid);
            }
            if ($block->endtime > $until) {
                $candidates[] = new time_range(max((int)$block->starttime, $until), (int)$block->endtime, $reason, $seriesid);
            }
        }

        // What is new: the given ranges, cut to the window.
        foreach ($ranges as $range) {
            $start = max($range->starttime, $from);
            $end = min($range->endtime, $until);
            if ($end <= $start) {
                continue;
            }
            $seriesid = isset($knownseries[$range->seriesid]) ? $range->seriesid : 0;
            $candidates[] = new time_range($start, $end, $range->reason, $seriesid);
        }

        $final = range_merger::merge($candidates);

        // Only write what changed: identical blocks keep their row.
        $remaining = [];
        foreach ($existing as $block) {
            $key = (new time_range(
                (int)$block->starttime,
                (int)$block->endtime,
                (string)($block->reason ?? ''),
                (int)($block->seriesid ?? 0)
            ))->identity();
            $remaining[$key][] = $block;
        }

        $toadd = [];
        foreach ($final as $range) {
            $key = $range->identity();
            if (!empty($remaining[$key])) {
                array_shift($remaining[$key]);
                continue;
            }
            $toadd[] = $range;
        }

        $transaction = $DB->start_delegated_transaction();
        $this->add($entityid, $scope, $toadd);
        foreach ($remaining as $blocks) {
            foreach ($blocks as $block) {
                $DB->delete_records(self::TABLE, ['id' => $block->id]);
            }
        }
        $transaction->allow_commit();
    }

    /**
     * Stores ranges as blocks of an entity, without looking at what is stored already.
     *
     * @param int $entityid
     * @param scope $scope
     * @param time_range[] $ranges
     * @return void
     */
    public function add(int $entityid, scope $scope, array $ranges): void {
        global $DB, $USER;

        $now = time();
        foreach ($ranges as $range) {
            $DB->insert_record(self::TABLE, (object)[
                'entityid' => $entityid,
                'starttime' => $range->starttime,
                'endtime' => $range->endtime,
                'component' => $scope->component,
                'area' => $scope->area,
                'instanceid' => $scope->instanceid,
                'reason' => $range->reason,
                'seriesid' => $range->seriesid ?: null,
                'timecreated' => $now,
                'timemodified' => $now,
                'usermodified' => (int)($USER->id ?? 0),
            ]);
        }
    }

    /**
     * Removes blocks of an entity by their ids.
     *
     * Ids of blocks that belong to another entity, or that do not exist, are ignored.
     *
     * @param int $entityid
     * @param int[] $ids
     * @return int number of blocks removed
     */
    public function remove_by_ids(int $entityid, array $ids): int {
        global $DB;

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($entityid <= 0 || empty($ids)) {
            return 0;
        }

        [$idsql, $idparams] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'bid');
        $where = "entityid = :entityid AND id $idsql";
        $params = ['entityid' => $entityid] + $idparams;

        $count = $DB->count_records_select(self::TABLE, $where, $params);
        $DB->delete_records_select(self::TABLE, $where, $params);

        return $count;
    }

    /**
     * Stores a single block and merges it with the single blocks next to it.
     *
     * Single blocks of the same scope and reason that overlap or touch the new one are replaced by one
     * merged block. Blocks that belong to a series, blocks of another reason and blocks of another
     * scope are neither merged nor touched.
     *
     * @param int $entityid
     * @param scope $scope
     * @param time_range $range the new block, without series
     * @return void
     */
    public function add_merged(int $entityid, scope $scope, time_range $range): void {
        global $DB;

        [$scopesql, $scopeparams] = scope_filter::exactly($scope)->to_sql('', 'sc');
        $neighbours = $DB->get_records_select(
            self::TABLE,
            "entityid = :entityid AND seriesid IS NULL AND COALESCE(reason, '') = :reason
                AND starttime <= :rangeend AND endtime >= :rangestart AND $scopesql",
            [
                'entityid' => $entityid,
                'reason' => $range->reason,
                'rangestart' => $range->starttime,
                'rangeend' => $range->endtime,
            ] + $scopeparams
        );

        $ranges = [$range];
        foreach ($neighbours as $block) {
            $ranges[] = new time_range((int)$block->starttime, (int)$block->endtime, $range->reason);
        }

        $transaction = $DB->start_delegated_transaction();
        foreach ($neighbours as $block) {
            $DB->delete_records(self::TABLE, ['id' => $block->id]);
        }
        $this->add($entityid, $scope, range_merger::merge($ranges));
        $transaction->allow_commit();
    }

    /**
     * Removes the blocks of a series from a point in time on.
     *
     * A block that reaches over this point in time is cut there, so its part before stays.
     *
     * @param int $seriesid
     * @param int $notbefore start of the first day that is removed (unix timestamp)
     * @return void
     */
    public function remove_series_from(int $seriesid, int $notbefore): void {
        global $DB;

        $DB->delete_records_select(
            self::TABLE,
            'seriesid = :seriesid AND starttime >= :notbefore',
            ['seriesid' => $seriesid, 'notbefore' => $notbefore]
        );
        $DB->set_field_select(
            self::TABLE,
            'endtime',
            $notbefore,
            'seriesid = :seriesid AND starttime < :startbefore AND endtime > :endafter',
            ['seriesid' => $seriesid, 'startbefore' => $notbefore, 'endafter' => $notbefore]
        );
    }

    /**
     * Does a series have blocks left?
     *
     * @param int $seriesid
     * @return bool
     */
    public function series_has_blocks(int $seriesid): bool {
        global $DB;
        return $DB->record_exists(self::TABLE, ['seriesid' => $seriesid]);
    }
}
