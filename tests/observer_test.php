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
 * Tests for the observer of deleted users.
 *
 * @package    local_entities
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_entities\observer
 */
final class observer_test extends \advanced_testcase {
    /**
     * Deleting a user in Moodle deletes the person that stands for the user, with its unavailability,
     * and leaves the persons of other users alone.
     *
     * @return void
     */
    public function test_deleting_a_user_deletes_the_person(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $examiner = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $entityid = persons::get_or_create_for_user((int)$examiner->id);
        $otherid = persons::get_or_create_for_user((int)$other->id);
        $now = time();
        foreach ([$entityid, $otherid] as $id) {
            $DB->insert_record('local_entities_unavailability', (object)[
                'entityid' => $id, 'starttime' => $now + DAYSECS, 'endtime' => $now + 2 * DAYSECS,
                'component' => '', 'area' => '', 'instanceid' => 0, 'reason' => '', 'seriesid' => 0,
                'timecreated' => $now, 'timemodified' => $now, 'usermodified' => (int)$examiner->id,
            ]);
        }

        delete_user($examiner);

        $this->assertFalse($DB->record_exists('local_entities', ['id' => $entityid]));
        $this->assertFalse($DB->record_exists('local_entities_unavailability', ['entityid' => $entityid]));
        $this->assertTrue($DB->record_exists('local_entities', ['id' => $otherid]));
        $this->assertTrue(
            $DB->record_exists('local_entities_unavailability', ['entityid' => $otherid, 'usermodified' => 0]),
            'What the deleted user changed stays, without the id of the user.'
        );
    }

    /**
     * A user without any data can be deleted without trouble.
     *
     * @return void
     */
    public function test_deleting_a_user_without_data_changes_nothing(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $otherid = persons::get_or_create_for_user((int)$other->id);

        delete_user($user);

        $this->assertTrue($DB->record_exists('local_entities', ['id' => $otherid]));
    }

    /**
     * The case of the manual check: a person with a recurring unavailability and single blocks, made
     * through the same API as the page, disappears with the user. Nothing is left of it, in no table.
     *
     * @return void
     */
    public function test_deleting_a_user_leaves_nothing_of_the_unavailability(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $examiner = $this->getDataGenerator()->create_user();
        $entityid = persons::get_or_create_for_user((int)$examiner->id);
        $now = time();
        $seriesid = unavailability::create_series($entityid, [
            'weekdays' => [1, 2, 3, 4, 5, 6, 7], 'allday' => true, 'daystart' => 0, 'dayend' => 0,
            'validfrom' => $now, 'validuntil' => $now + 6 * DAYSECS, 'timezone' => 'Europe/Vienna', 'reason' => '',
        ]);
        unavailability::replace_in_window($entityid, $now + 10 * DAYSECS, $now + 12 * DAYSECS, [], [
            ['starttime' => $now + 10 * DAYSECS, 'endtime' => $now + 10 * DAYSECS + HOURSECS],
        ]);
        $this->assertTrue($DB->record_exists('local_entities_unavail_series', ['id' => $seriesid]));
        $this->assertGreaterThan(1, $DB->count_records('local_entities_unavailability', ['entityid' => $entityid]));

        delete_user($examiner);

        $this->assertFalse($DB->record_exists('local_entities', ['id' => $entityid]));
        $this->assertSame(0, $DB->count_records('local_entities_unavailability', ['entityid' => $entityid]));
        $this->assertSame(0, $DB->count_records('local_entities_unavail_series', ['entityid' => $entityid]));
        $this->assertNull(persons::get_entityid_for_user((int)$examiner->id));
    }

    /**
     * A user who only created a place does not take the place with them: it stays, without the id of
     * the user.
     *
     * @return void
     */
    public function test_a_place_created_by_a_deleted_user_stays(): void {
        global $DB;
        $this->resetAfterTest();
        $creator = $this->getDataGenerator()->create_user();
        $this->setAdminUser();
        /** @var \local_entities_generator $gen */
        $gen = $this->getDataGenerator()->get_plugin_generator('local_entities');
        $roomid = $gen->create_entities(['name' => 'Room D', 'shortname' => 'rd', 'entitytype' => 'location']);
        $DB->set_field('local_entities', 'createdby', $creator->id, ['id' => $roomid]);

        delete_user($creator);

        $this->assertTrue($DB->record_exists('local_entities', ['id' => $roomid]));
        $this->assertSame(0, (int)$DB->get_field('local_entities', 'createdby', ['id' => $roomid]));
    }
}
