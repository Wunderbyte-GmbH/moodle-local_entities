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

/**
 * A person confirms to be available the whole planning period.
 *
 * Without any unavailability in the period one can not tell whether the person is always free or never looked at the
 * calendar. The confirmation tells it. It belongs to the planning period it was given in: when the period changes, it
 * no longer counts.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class always_free {
    /** @var string Table of the confirmations. */
    public const TABLE = 'local_entities_alwaysfree';

    /**
     * Has the entity confirmed for the current planning period?
     *
     * @param int $entityid
     * @return bool false also when there is no planning period
     */
    public static function is_confirmed(int $entityid): bool {
        return in_array($entityid, self::confirmed([$entityid]), true);
    }

    /**
     * Which of the entities have confirmed for the current planning period, with one query.
     *
     * @param int[] $entityids
     * @return int[] the entity ids that confirmed
     */
    public static function confirmed(array $entityids): array {
        global $DB;

        $period = planning_period::get();
        $ids = array_values(array_unique(array_filter(array_map('intval', $entityids))));
        if ($period === null || !$ids) {
            return [];
        }
        [$insql, $params] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
        $params += ['periodstart' => $period->starttime, 'periodend' => $period->endtime];
        return array_map('intval', $DB->get_fieldset_select(
            self::TABLE,
            'entityid',
            "entityid $insql AND periodstart = :periodstart AND periodend = :periodend",
            $params
        ));
    }

    /**
     * Gives or takes back the confirmation for the current planning period.
     *
     * @param int $entityid
     * @param bool $confirmed
     * @param int $userid who does it
     * @return void
     * @throws \moodle_exception if there is no planning period
     */
    public static function set(int $entityid, bool $confirmed, int $userid): void {
        global $DB;

        $period = planning_period::get();
        if ($period === null) {
            throw new \moodle_exception('alwaysfree_noperiod', 'local_entities');
        }
        $conditions = ['entityid' => $entityid, 'periodstart' => $period->starttime, 'periodend' => $period->endtime];
        $exists = $DB->record_exists(self::TABLE, $conditions);
        if ($confirmed && !$exists) {
            $DB->insert_record(self::TABLE, (object)($conditions + ['usermodified' => $userid, 'timecreated' => time()]));
        } else if (!$confirmed && $exists) {
            $DB->delete_records(self::TABLE, $conditions);
        }
    }
}
