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

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider of local_entities.
 *
 * The plugin keeps the person entities of Moodle users with their unavailability, and the ids of the
 * users who created entities or changed unavailability. Everything is in the system context. Finding,
 * exporting and erasing the data is done by {@see user_data}.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /** @var string[] Fields of the exported rows that are points in time. */
    private const TIMEFIELDS = ['starttime', 'endtime', 'timecreated', 'timemodified', 'validfrom', 'validuntil'];

    /**
     * Describes the personal data the plugin stores.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_entities', [
            'userid' => 'privacy:metadata:local_entities:userid',
            'createdby' => 'privacy:metadata:local_entities:createdby',
        ], 'privacy:metadata:local_entities');

        $collection->add_database_table('local_entities_unavailability', [
            'entityid' => 'privacy:metadata:local_entities_unavailability:entityid',
            'starttime' => 'privacy:metadata:local_entities_unavailability:starttime',
            'endtime' => 'privacy:metadata:local_entities_unavailability:endtime',
            'reason' => 'privacy:metadata:local_entities_unavailability:reason',
            'usermodified' => 'privacy:metadata:local_entities_unavailability:usermodified',
        ], 'privacy:metadata:local_entities_unavailability');

        $collection->add_database_table('local_entities_unavail_series', [
            'entityid' => 'privacy:metadata:local_entities_unavail_series:entityid',
            'weekdays' => 'privacy:metadata:local_entities_unavail_series:weekdays',
            'daystart' => 'privacy:metadata:local_entities_unavail_series:daystart',
            'dayend' => 'privacy:metadata:local_entities_unavail_series:dayend',
            'validfrom' => 'privacy:metadata:local_entities_unavail_series:validfrom',
            'validuntil' => 'privacy:metadata:local_entities_unavail_series:validuntil',
            'reason' => 'privacy:metadata:local_entities_unavail_series:reason',
            'usermodified' => 'privacy:metadata:local_entities_unavail_series:usermodified',
        ], 'privacy:metadata:local_entities_unavail_series');

        $collection->add_database_table('local_entities_contacts', [
            'givenname' => 'privacy:metadata:local_entities_contacts:givenname',
            'surname' => 'privacy:metadata:local_entities_contacts:surname',
            'mail' => 'privacy:metadata:local_entities_contacts:mail',
        ], 'privacy:metadata:local_entities_contacts');

        return $collection;
    }

    /**
     * The contexts that hold data of the user: the system context, if the user has any data.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        if (user_data::has_data($userid)) {
            $contextlist->add_system_context();
        }
        return $contextlist;
    }

    /**
     * The users that have data in a context.
     *
     * @param userlist $userlist
     * @return void
     */
    public static function get_users_in_context(userlist $userlist): void {
        if ($userlist->get_context() instanceof \context_system) {
            $userlist->add_users(user_data::userids());
        }
    }

    /**
     * Exports the data of a user.
     *
     * @param approved_contextlist $contextlist
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        $context = \context_system::instance();
        if (!in_array($context->id, $contextlist->get_contextids())) {
            return;
        }

        $parts = [
            'unavailability' => 'privacyunavailability',
            'series' => 'privacyunavailabilityseries',
            'created' => 'privacycreatedentities',
            'changed' => 'privacychangedunavailability',
            'changedseries' => 'privacyunavailabilityseries',
        ];
        $writer = writer::with_context($context);
        foreach (user_data::export($contextlist->get_user()->id) as $part => $rows) {
            if (empty($rows)) {
                continue;
            }
            $subcontext = [get_string('pluginname', 'local_entities'), get_string($parts[$part], 'local_entities')];
            if ($part === 'changedseries') {
                $subcontext[] = get_string('privacychangedunavailability', 'local_entities');
            }
            $writer->export_data($subcontext, (object)['entries' => array_map([self::class, 'format_row'], $rows)]);
        }
    }

    /**
     * Deletes the data of all users in a context.
     *
     * @param \context $context
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        if ($context instanceof \context_system) {
            foreach (user_data::userids() as $userid) {
                user_data::erase($userid);
            }
        }
    }

    /**
     * Deletes the data of a user.
     *
     * @param approved_contextlist $contextlist
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        if (in_array(\context_system::instance()->id, $contextlist->get_contextids())) {
            user_data::erase($contextlist->get_user()->id);
        }
    }

    /**
     * Deletes the data of several users in a context.
     *
     * @param approved_userlist $userlist
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        if ($userlist->get_context() instanceof \context_system) {
            foreach ($userlist->get_userids() as $userid) {
                user_data::erase((int)$userid);
            }
        }
    }

    /**
     * A row for the export: points in time are written as dates.
     *
     * @param \stdClass $row
     * @return \stdClass
     */
    private static function format_row(\stdClass $row): \stdClass {
        foreach (self::TIMEFIELDS as $field) {
            if (isset($row->$field)) {
                $row->$field = transform::datetime((int)$row->$field);
            }
        }
        return $row;
    }
}
