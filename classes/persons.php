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

use core\lock\lock_config;

/**
 * Persons: Moodle users represented as entities of type "person" (e.g. examiners).
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class persons {
    /** @var string Entity type of a person. */
    public const TYPE = 'person';

    /**
     * Returns the id of the person entity of a user, without creating it.
     *
     * @param int $userid
     * @return int|null entity id, or null if the user is no person (yet)
     */
    public static function get_entityid_for_user(int $userid): ?int {
        global $DB;

        $id = $DB->get_field(
            'local_entities',
            'id',
            ['entitytype' => self::TYPE, 'userid' => $userid],
            IGNORE_MULTIPLE
        );
        return $id ? (int)$id : null;
    }

    /**
     * Returns the id of the person entity of a user and creates it if it does not exist yet.
     *
     * Other plugins use this to refer to a user as a resource (e.g. an examiner) without having to
     * create the entity by hand. The user must exist and must not be deleted.
     *
     * @param int $userid
     * @return int entity id
     * @throws \dml_missing_record_exception if the user does not exist or is deleted
     */
    public static function get_or_create_for_user(int $userid): int {
        global $DB, $USER;

        $existing = self::get_entityid_for_user($userid);
        if ($existing !== null) {
            return $existing;
        }

        $DB->get_record('user', ['id' => $userid, 'deleted' => 0], 'id', MUST_EXIST);

        // The uniqueness of a person per user is not enforced by an index, so two requests asking at
        // the same time must not both create one.
        $lock = lock_config::get_lock_factory('local_entities_persons')->get_lock('user_' . $userid, 10);
        if (!$lock) {
            throw new \moodle_exception('locktimeout');
        }

        try {
            $existing = self::get_entityid_for_user($userid);
            if ($existing !== null) {
                return $existing;
            }

            $now = time();
            $record = (object)[
                'name' => '',
                'shortname' => '',
                'description' => '',
                'entitytype' => self::TYPE,
                'userid' => $userid,
                'parentid' => 0,
                'sortorder' => 0,
                'status' => 0,
                'pricefactor' => 1,
                'allocationmode' => 'exclusive',
                'maxconcurrent' => 1,
                'createdby' => (int)($USER->id ?? 0),
                'timecreated' => $now,
                'timemodified' => $now,
            ];
            return (new settings_manager())->create_entity($record);
        } finally {
            $lock->release();
        }
    }

    /**
     * Returns the users that are a person, for a selection.
     *
     * Deleted users are left out. Only persons are listed, not all users of the site, so a selection
     * does not need the right to search users.
     *
     * @return string[] full names, keyed by user id, ordered by last and first name
     */
    public static function list_users(): array {
        global $DB;

        $namefields = \core_user\fields::for_name()->get_sql('u', false, '', '', false)->selects;
        $records = $DB->get_records_sql(
            "SELECT DISTINCT u.id, $namefields
               FROM {local_entities} e
               JOIN {user} u ON u.id = e.userid
              WHERE e.entitytype = :persontype
                AND u.deleted = 0
           ORDER BY u.lastname, u.firstname, u.id",
            ['persontype' => self::TYPE]
        );

        $list = [];
        foreach ($records as $record) {
            $list[(int)$record->id] = fullname($record);
        }
        return $list;
    }
}
