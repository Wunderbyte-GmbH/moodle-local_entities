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
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\tests\provider_testcase;
use local_entities\people_pool\people_pool_repository;
use local_entities\persons;

/**
 * Tests for the privacy provider of local_entities.
 *
 * @package    local_entities
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_entities\privacy\provider
 * @covers     \local_entities\privacy\user_data
 */
final class provider_test extends provider_testcase {
    /**
     * Creates the person entity of a user, with one block and one series of unavailability that the
     * given user changed last.
     *
     * @param \stdClass $user
     * @param int $changedby id of the user who changed the unavailability
     * @return int id of the person entity
     */
    private function create_person_with_unavailability(\stdClass $user, int $changedby): int {
        global $DB;
        $this->setAdminUser();
        $entityid = persons::get_or_create_for_user((int)$user->id);
        $now = time();
        $DB->insert_record('local_entities_unavailability', (object)[
            'entityid' => $entityid, 'starttime' => $now + DAYSECS, 'endtime' => $now + 2 * DAYSECS,
            'component' => '', 'area' => '', 'instanceid' => 0, 'reason' => 'Holiday', 'seriesid' => 0,
            'timecreated' => $now, 'timemodified' => $now, 'usermodified' => $changedby,
        ]);
        $DB->insert_record('local_entities_unavail_series', (object)[
            'entityid' => $entityid, 'component' => '', 'area' => '', 'instanceid' => 0, 'weekdays' => '1,3',
            'allday' => 0, 'daystart' => 8 * HOURSECS, 'dayend' => 12 * HOURSECS, 'validfrom' => $now,
            'validuntil' => $now + 30 * DAYSECS, 'timezone' => 'Europe/Vienna', 'reason' => '',
            'timecreated' => $now, 'timemodified' => $now, 'usermodified' => $changedby,
        ]);
        return $entityid;
    }

    /**
     * The metadata describes the tables that hold personal data.
     *
     * @return void
     */
    public function test_metadata_describes_the_tables(): void {
        $collection = provider::get_metadata(new collection('local_entities'));
        $names = array_map(fn($item) => $item->get_name(), $collection->get_collection());

        $tables = [
            'local_entities',
            'local_entities_unavailability',
            'local_entities_unavail_series',
            'local_entities_people_pool',
            'local_entities_people_pool_member',
            'local_entities_contacts',
        ];
        foreach ($tables as $table) {
            $this->assertContains($table, $names);
        }
    }

    /**
     * Only users with data have a context, and the users of a context are the ones with data.
     *
     * @return void
     */
    public function test_contexts_and_users(): void {
        $this->resetAfterTest();
        $examiner = $this->getDataGenerator()->create_user();
        $nobody = $this->getDataGenerator()->create_user();
        $this->create_person_with_unavailability($examiner, (int)$examiner->id);
        $context = \context_system::instance();

        $contextids = provider::get_contexts_for_userid((int)$examiner->id)->get_contextids();
        $this->assertSame([$context->id], array_map('intval', $contextids));
        $this->assertEmpty(provider::get_contexts_for_userid((int)$nobody->id)->get_contextids());

        $userlist = new userlist($context, 'local_entities');
        provider::get_users_in_context($userlist);
        $this->assertContains((int)$examiner->id, array_map('intval', $userlist->get_userids()));
        $this->assertNotContains((int)$nobody->id, array_map('intval', $userlist->get_userids()));
    }

    /**
     * A user who only changed the unavailability of someone else has data as well.
     *
     * @return void
     */
    public function test_a_secretary_who_changed_unavailability_has_data(): void {
        $this->resetAfterTest();
        $examiner = $this->getDataGenerator()->create_user();
        $secretary = $this->getDataGenerator()->create_user();
        $this->create_person_with_unavailability($examiner, (int)$secretary->id);

        $this->assertNotEmpty(provider::get_contexts_for_userid((int)$secretary->id)->get_contextids());
    }

    /**
     * The export has the unavailability of the person with readable dates.
     *
     * @return void
     */
    public function test_export_has_the_unavailability_of_the_person(): void {
        $this->resetAfterTest();
        $examiner = $this->getDataGenerator()->create_user();
        $this->create_person_with_unavailability($examiner, (int)$examiner->id);
        $context = \context_system::instance();

        $this->export_context_data_for_user((int)$examiner->id, $context, 'local_entities');
        $writer = writer::with_context($context);
        $this->assertTrue($writer->has_any_data());

        $base = get_string('pluginname', 'local_entities');
        $blocks = $writer->get_data([$base, get_string('privacyunavailability', 'local_entities')]);
        $this->assertCount(1, $blocks->entries);
        $this->assertSame('Holiday', $blocks->entries[0]->reason);
        $this->assertIsString($blocks->entries[0]->starttime, 'Points in time are exported as readable dates.');

        $series = $writer->get_data([$base, get_string('privacyunavailabilityseries', 'local_entities')]);
        $this->assertCount(1, $series->entries);
        $this->assertSame('1,3', $series->entries[0]->weekdays);
    }

    /**
     * Erasing a user deletes the person with its unavailability and series, and only that.
     *
     * @return void
     */
    public function test_deleting_a_user_deletes_the_person_and_nothing_else(): void {
        global $DB;
        $this->resetAfterTest();
        $examiner = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $entityid = $this->create_person_with_unavailability($examiner, (int)$examiner->id);
        $otherid = $this->create_person_with_unavailability($other, (int)$other->id);
        $context = \context_system::instance();

        provider::delete_data_for_user(new approved_contextlist($examiner, 'local_entities', [$context->id]));

        $this->assertFalse($DB->record_exists('local_entities', ['id' => $entityid]));
        $this->assertFalse($DB->record_exists('local_entities_unavailability', ['entityid' => $entityid]));
        $this->assertFalse($DB->record_exists('local_entities_unavail_series', ['entityid' => $entityid]));
        $this->assertTrue($DB->record_exists('local_entities', ['id' => $otherid]));
        $this->assertTrue($DB->record_exists('local_entities_unavailability', ['entityid' => $otherid]));
        $this->assertEmpty(provider::get_contexts_for_userid((int)$examiner->id)->get_contextids());
    }

    /**
     * What a user only changed or created stays, but loses the id of the user.
     *
     * @return void
     */
    public function test_what_a_user_changed_stays_without_the_user(): void {
        global $DB;
        $this->resetAfterTest();
        $examiner = $this->getDataGenerator()->create_user();
        $secretary = $this->getDataGenerator()->create_user();
        $entityid = $this->create_person_with_unavailability($examiner, (int)$secretary->id);
        $DB->set_field('local_entities', 'createdby', $secretary->id, ['id' => $entityid]);
        $context = \context_system::instance();

        provider::delete_data_for_user(new approved_contextlist($secretary, 'local_entities', [$context->id]));

        $this->assertTrue($DB->record_exists('local_entities', ['id' => $entityid]));
        $this->assertSame(0, (int)$DB->get_field('local_entities', 'createdby', ['id' => $entityid]));
        $this->assertTrue($DB->record_exists('local_entities_unavailability', ['entityid' => $entityid, 'usermodified' => 0]));
        $this->assertTrue($DB->record_exists('local_entities_unavail_series', ['entityid' => $entityid, 'usermodified' => 0]));
    }

    /**
     * Erasing the users of a context, and all users of the context.
     *
     * @return void
     */
    public function test_deleting_users_in_a_context(): void {
        global $DB;
        $this->resetAfterTest();
        $first = $this->getDataGenerator()->create_user();
        $second = $this->getDataGenerator()->create_user();
        $third = $this->getDataGenerator()->create_user();
        $firstid = $this->create_person_with_unavailability($first, (int)$first->id);
        $secondid = $this->create_person_with_unavailability($second, (int)$second->id);
        $thirdid = $this->create_person_with_unavailability($third, (int)$third->id);
        $context = \context_system::instance();

        provider::delete_data_for_users(new approved_userlist($context, 'local_entities', [(int)$first->id, (int)$second->id]));
        $this->assertFalse($DB->record_exists('local_entities', ['id' => $firstid]));
        $this->assertFalse($DB->record_exists('local_entities', ['id' => $secondid]));
        $this->assertTrue($DB->record_exists('local_entities', ['id' => $thirdid]));

        provider::delete_data_for_all_users_in_context($context);
        $this->assertFalse($DB->record_exists('local_entities', ['id' => $thirdid]));
        $this->assertSame(0, $DB->count_records('local_entities_unavailability'));
        $this->assertSame(0, $DB->count_records('local_entities_unavail_series'));
    }

    /**
     * A user who only created a pool or put someone into a pool has data as well.
     *
     * @return void
     */
    public function test_a_user_who_changed_pools_has_data(): void {
        $this->resetAfterTest();
        $examiner = $this->getDataGenerator()->create_user();
        $creator = $this->getDataGenerator()->create_user();
        $adder = $this->getDataGenerator()->create_user();
        $entityid = $this->create_person_with_unavailability($examiner, (int)$examiner->id);
        $repo = new people_pool_repository();
        $poolid = $repo->create('Greek', '', (int)$creator->id);
        $repo->add_members($poolid, [$entityid], (int)$adder->id);

        $this->assertNotEmpty(provider::get_contexts_for_userid((int)$creator->id)->get_contextids());
        $this->assertNotEmpty(provider::get_contexts_for_userid((int)$adder->id)->get_contextids());
        $userlist = new userlist(\context_system::instance(), 'local_entities');
        provider::get_users_in_context($userlist);
        $this->assertContains((int)$creator->id, array_map('intval', $userlist->get_userids()));
        $this->assertContains((int)$adder->id, array_map('intval', $userlist->get_userids()));
    }

    /**
     * The export names the pools of the person and the pools the user created or changed.
     *
     * @return void
     */
    public function test_export_has_the_pools(): void {
        $this->resetAfterTest();
        $examiner = $this->getDataGenerator()->create_user();
        $entityid = $this->create_person_with_unavailability($examiner, (int)$examiner->id);
        $repo = new people_pool_repository();
        $poolid = $repo->create('Greek', '', (int)$examiner->id);
        $repo->create('Latin', '', 2);
        $repo->add_members($poolid, [$entityid], 2);
        $context = \context_system::instance();

        $this->export_context_data_for_user((int)$examiner->id, $context, 'local_entities');
        $writer = writer::with_context($context);
        $base = get_string('pluginname', 'local_entities');

        $memberships = $writer->get_data([$base, get_string('privacypoolmemberships', 'local_entities')]);
        $this->assertCount(1, $memberships->entries);
        $this->assertSame('Greek', $memberships->entries[0]->name);

        $changed = $writer->get_data([$base, get_string('privacychangedpools', 'local_entities')]);
        $this->assertCount(1, $changed->entries);
        $this->assertSame('Greek', $changed->entries[0]->name);
    }

    /**
     * Erasing a user takes the person out of all pools and, for the others, removes only the id of the user.
     *
     * @return void
     */
    public function test_erasing_a_user_cleans_the_pools(): void {
        global $DB;
        $this->resetAfterTest();
        $examiner = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $secretary = $this->getDataGenerator()->create_user();
        $examinerid = $this->create_person_with_unavailability($examiner, (int)$examiner->id);
        $otherid = $this->create_person_with_unavailability($other, (int)$other->id);
        $repo = new people_pool_repository();
        $poolid = $repo->create('Greek', '', (int)$secretary->id);
        $repo->add_members($poolid, [$examinerid, $otherid], (int)$secretary->id);
        $context = \context_system::instance();

        provider::delete_data_for_user(new approved_contextlist($examiner, 'local_entities', [$context->id]));
        $this->assertSame([$otherid], $repo->get_member_entityids($poolid));

        provider::delete_data_for_user(new approved_contextlist($secretary, 'local_entities', [$context->id]));
        $this->assertSame(0, (int)$DB->get_field('local_entities_people_pool', 'usermodified', ['id' => $poolid]));
        $this->assertSame([$otherid], $repo->get_member_entityids($poolid), 'The membership of another person stays.');
        $this->assertEquals(0, $DB->count_records_select('local_entities_people_pool_member', 'usermodified <> 0'));
    }
}
