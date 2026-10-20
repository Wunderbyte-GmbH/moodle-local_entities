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

namespace local_entities\privacy;

use local_entities\persons;
use local_entities\settings_manager;

/**
 * The personal data of a Moodle user in local_entities.
 *
 * There are three kinds: the person entity that stands for the user together with its unavailability,
 * the entities the user created, and the unavailability the user changed last. The privacy provider
 * and the observer for deleted users both use this class, so the data is found, exported and erased in
 * one place.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class user_data {
    /**
     * The ids of the person entities of a user. Normally there is one.
     *
     * @param int $userid
     * @return int[]
     */
    public static function person_entityids(int $userid): array {
        global $DB;

        $ids = $DB->get_fieldset_select('local_entities', 'id', 'entitytype = :type AND userid = :userid', [
            'type' => persons::TYPE,
            'userid' => $userid,
        ]);
        return array_map('intval', $ids);
    }

    /**
     * The ids of all users that have data.
     *
     * @return int[]
     */
    public static function userids(): array {
        global $DB;

        $sql = "SELECT userid FROM {local_entities} WHERE entitytype = :type AND userid > 0
                 UNION
                SELECT createdby FROM {local_entities} WHERE createdby > 0
                 UNION
                SELECT usermodified FROM {local_entities_unavailability} WHERE usermodified > 0
                 UNION
                SELECT usermodified FROM {local_entities_unavail_series} WHERE usermodified > 0
                 UNION
                SELECT usermodified FROM {local_entities_pool} WHERE usermodified > 0
                 UNION
                SELECT usermodified FROM {local_entities_pool_member} WHERE usermodified > 0";
        return array_map('intval', $DB->get_fieldset_sql($sql, ['type' => persons::TYPE]));
    }

    /**
     * Has the user any data?
     *
     * @param int $userid
     * @return bool
     */
    public static function has_data(int $userid): bool {
        global $DB;

        return !empty(self::person_entityids($userid))
            || $DB->record_exists('local_entities', ['createdby' => $userid])
            || $DB->record_exists('local_entities_unavailability', ['usermodified' => $userid])
            || $DB->record_exists('local_entities_unavail_series', ['usermodified' => $userid])
            || $DB->record_exists('local_entities_pool', ['usermodified' => $userid])
            || $DB->record_exists('local_entities_pool_member', ['usermodified' => $userid]);
    }

    /**
     * The data of the user, as it is exported.
     *
     * @param int $userid
     * @return array unavailability and series of the person of the user, memberships (pools the person is in),
     *               created (entities the user created), changed and changedseries (unavailability the user
     *               changed on other entities), changedpools (pools the user created or changed)
     */
    public static function export(int $userid): array {
        global $DB;

        $ids = self::person_entityids($userid) ?: [0];
        [$insql, $inparams] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'own');
        [$notinsql, $notinparams] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'notown', false);

        return [
            'unavailability' => array_values($DB->get_records_select(
                'local_entities_unavailability',
                "entityid {$insql}",
                $inparams,
                'starttime',
                'id, starttime, endtime, reason, timecreated, timemodified'
            )),
            'series' => array_values($DB->get_records_select(
                'local_entities_unavail_series',
                "entityid {$insql}",
                $inparams,
                'validfrom',
                'id, weekdays, allday, daystart, dayend, validfrom, validuntil, timezone, reason, timecreated, timemodified'
            )),
            'created' => array_values($DB->get_records_select(
                'local_entities',
                "createdby = :userid AND id {$notinsql}",
                ['userid' => $userid] + $notinparams,
                'id',
                'id, name, timecreated'
            )),
            'changed' => array_values($DB->get_records_select(
                'local_entities_unavailability',
                "usermodified = :userid AND entityid {$notinsql}",
                ['userid' => $userid] + $notinparams,
                'starttime',
                'id, entityid, starttime, endtime, timemodified'
            )),
            'changedseries' => array_values($DB->get_records_select(
                'local_entities_unavail_series',
                "usermodified = :userid AND entityid {$notinsql}",
                ['userid' => $userid] + $notinparams,
                'validfrom',
                'id, entityid, validfrom, validuntil, timemodified'
            )),
            'memberships' => array_values($DB->get_records_sql(
                "SELECT m.id, p.name, m.timecreated
                   FROM {local_entities_pool_member} m
                   JOIN {local_entities_pool} p ON p.id = m.poolid
                  WHERE m.entityid {$insql}
               ORDER BY p.name",
                $inparams
            )),
            'changedpools' => array_values($DB->get_records_select(
                'local_entities_pool',
                'usermodified = :userid',
                ['userid' => $userid],
                'name',
                'id, name, timemodified'
            )),
        ];
    }

    /**
     * Erases the data of the user.
     *
     * The person entity of the user is deleted with its unavailability. What the user only created or
     * changed does not belong to the user: it stays, without the id of the user.
     *
     * @param int $userid
     * @return void
     */
    public static function erase(int $userid): void {
        global $DB;

        foreach (self::person_entityids($userid) as $entityid) {
            $DB->delete_records('local_entities_unavailability', ['entityid' => $entityid]);
            $DB->delete_records('local_entities_unavail_series', ['entityid' => $entityid]);
            (new settings_manager($entityid))->delete();
        }

        $DB->set_field('local_entities', 'createdby', 0, ['createdby' => $userid]);
        $DB->set_field('local_entities_unavailability', 'usermodified', 0, ['usermodified' => $userid]);
        $DB->set_field('local_entities_unavail_series', 'usermodified', 0, ['usermodified' => $userid]);
        $DB->set_field('local_entities_pool', 'usermodified', 0, ['usermodified' => $userid]);
        $DB->set_field('local_entities_pool_member', 'usermodified', 0, ['usermodified' => $userid]);
    }
}
