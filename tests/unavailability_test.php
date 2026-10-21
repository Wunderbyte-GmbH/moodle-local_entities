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

use local_entities_generator;

/**
 * Tests for persons and the unavailability of entities (time ranges with an optional scope).
 *
 * All times are written as hours relative to a fixed base time, so a block "8-10" is the range
 * from base + 8 hours to base + 10 hours.
 *
 * @package    local_entities
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_entities\persons
 * @covers     \local_entities\unavailability
 */
final class unavailability_test extends \advanced_testcase {
    /** @var int Fixed base time of all test ranges. */
    private const BASE = 1700000000;

    /**
     * Timestamp of an hour offset from the base time.
     *
     * @param int $hours
     * @return int
     */
    private function t(int $hours): int {
        return self::BASE + $hours * HOURSECS;
    }

    /**
     * Inserts a block directly, so tests do not depend on the method they test.
     *
     * @param int $entityid
     * @param int $from hour offset
     * @param int $until hour offset
     * @param array $scope component, area, instanceid; empty for everywhere
     * @param string $reason
     * @return int id of the block
     */
    private function block(int $entityid, int $from, int $until, array $scope = [], string $reason = ''): int {
        global $DB;
        return (int)$DB->insert_record('local_entities_unavailability', (object)[
            'entityid' => $entityid,
            'starttime' => $this->t($from),
            'endtime' => $this->t($until),
            'component' => $scope['component'] ?? '',
            'area' => $scope['area'] ?? '',
            'instanceid' => $scope['instanceid'] ?? 0,
            'reason' => $reason,
            'timecreated' => 1,
            'timemodified' => 1,
            'usermodified' => 0,
        ]);
    }

    /**
     * Describes the stored blocks of an entity as short strings, ordered by start time.
     *
     * "8-10" is a block everywhere, "8-10@mod_booking/option/7" a scoped one, "#a" adds the reason.
     *
     * @param int $entityid
     * @return string[]
     */
    private function ranges(int $entityid): array {
        global $DB;
        $records = $DB->get_records('local_entities_unavailability', ['entityid' => $entityid], 'starttime, id');
        $result = [];
        foreach ($records as $record) {
            $text = (($record->starttime - self::BASE) / HOURSECS) . '-' . (($record->endtime - self::BASE) / HOURSECS);
            if (!empty($record->component)) {
                $text .= '@' . $record->component . '/' . $record->area . '/' . $record->instanceid;
            }
            if ((string)$record->reason !== '') {
                $text .= '#' . $record->reason;
            }
            $result[] = $text;
        }
        return $result;
    }

    /**
     * Block data for replace_in_window().
     *
     * @param int $from hour offset
     * @param int $until hour offset
     * @param string $reason
     * @return array
     */
    private function newblock(int $from, int $until, string $reason = ''): array {
        return ['starttime' => $this->t($from), 'endtime' => $this->t($until), 'reason' => $reason];
    }

    /**
     * Scope of an option of the booking plugin.
     *
     * @param int $optionid
     * @return array
     */
    private function optionscope(int $optionid): array {
        return ['component' => 'mod_booking', 'area' => 'option', 'instanceid' => $optionid];
    }

    /**
     * A person is created once per user and then reused; the entity has the expected defaults.
     *
     * @return void
     */
    public function test_person_is_created_once_per_user(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $user = $this->getDataGenerator()->create_user();
        $this->assertNull(persons::get_entityid_for_user($user->id));

        $id = persons::get_or_create_for_user($user->id);
        $this->assertSame($id, persons::get_or_create_for_user($user->id));
        $this->assertSame($id, persons::get_entityid_for_user($user->id));
        $this->assertSame(1, $DB->count_records('local_entities', ['userid' => $user->id]));

        $record = $DB->get_record('local_entities', ['id' => $id], '*', MUST_EXIST);
        $this->assertSame('person', $record->entitytype);
        $this->assertSame('exclusive', $record->allocationmode);
        $this->assertEquals(1, $record->maxconcurrent);
    }

    /**
     * A person that was already created in the edit form is reused, not duplicated.
     *
     * @return void
     */
    public function test_person_from_the_form_is_reused(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $user = $this->getDataGenerator()->create_user();
        /** @var local_entities_generator $gen */
        $gen = $this->getDataGenerator()->get_plugin_generator('local_entities');
        $formid = $gen->create_entities(['entitytype' => 'person', 'userid' => $user->id]);

        $this->assertSame($formid, persons::get_or_create_for_user($user->id));
        $this->assertSame(1, $DB->count_records('local_entities', ['userid' => $user->id]));
    }

    /**
     * Only existing, not deleted users can become a person.
     *
     * @return void
     */
    public function test_person_needs_an_existing_user(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $deleted = $this->getDataGenerator()->create_user();
        $DB->set_field('user', 'deleted', 1, ['id' => $deleted->id]);

        foreach ([999999, (int)$deleted->id] as $userid) {
            try {
                persons::get_or_create_for_user($userid);
                $this->fail('A user that does not exist must not become a person.');
            } catch (\dml_missing_record_exception $e) {
                $this->assertSame(0, $DB->count_records('local_entities', ['userid' => $userid]));
            }
        }
    }

    /**
     * Blocks overlap a range when they start before its end and end after its start, so ranges that
     * only touch do not count. The scope list decides which scoped blocks apply.
     *
     * @return void
     */
    public function test_get_blocks_by_overlap_and_scope(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $user = $this->getDataGenerator()->create_user();
        $entity = persons::get_or_create_for_user($user->id);
        $this->block($entity, 10, 12);
        $this->block($entity, 10, 12, $this->optionscope(7));
        $this->block($entity, 10, 12, $this->optionscope(8));
        $this->block($entity, 20, 22);

        $this->assertCount(3, unavailability::get_blocks($entity, $this->t(11), $this->t(13), null));
        $this->assertCount(1, unavailability::get_blocks($entity, $this->t(11), $this->t(13), []));
        $this->assertCount(2, unavailability::get_blocks($entity, $this->t(11), $this->t(13), [$this->optionscope(7)]));
        $this->assertCount(
            3,
            unavailability::get_blocks($entity, $this->t(11), $this->t(13), [$this->optionscope(7), $this->optionscope(8)])
        );

        // Ranges that only touch the block do not overlap it.
        $this->assertCount(0, unavailability::get_blocks($entity, $this->t(12), $this->t(14), null));
        $this->assertCount(0, unavailability::get_blocks($entity, $this->t(8), $this->t(10), null));
        // An empty or reversed range finds nothing.
        $this->assertCount(0, unavailability::get_blocks($entity, $this->t(12), $this->t(12), null));
    }

    /**
     * One query tells which users of a pool are blocked; users who are no person are never blocked.
     *
     * @return void
     */
    public function test_get_unavailable_userids_for_a_pool(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $first = $this->getDataGenerator()->create_user();
        $second = $this->getDataGenerator()->create_user();
        $third = $this->getDataGenerator()->create_user();
        $pool = [(int)$first->id, (int)$second->id, (int)$third->id];

        $this->block(persons::get_or_create_for_user($first->id), 10, 12);
        $this->block(persons::get_or_create_for_user($second->id), 10, 12, $this->optionscope(7));
        // The third user is no person at all.

        $result = unavailability::get_unavailable_userids($pool, $this->t(11), $this->t(13), []);
        $this->assertSame([(int)$first->id], $result, 'A scoped block does not apply without its scope.');

        $result = unavailability::get_unavailable_userids($pool, $this->t(11), $this->t(13), [$this->optionscope(7)]);
        sort($result);
        $this->assertSame([(int)$first->id, (int)$second->id], $result);

        $this->assertSame([], unavailability::get_unavailable_userids($pool, $this->t(12), $this->t(14), null));
        $this->assertSame([], unavailability::get_unavailable_userids([], $this->t(11), $this->t(13), null));
    }

    /**
     * One query tells which entities of a list are blocked, for every type, with the same scope rules.
     *
     * @return void
     */
    public function test_get_unavailable_entityids_for_any_type(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        /** @var local_entities_generator $gen */
        $gen = $this->getDataGenerator()->get_plugin_generator('local_entities');

        $person = persons::get_or_create_for_user((int)$this->getDataGenerator()->create_user()->id);
        $car = (int)$gen->create_entities(['name' => 'Car 1', 'shortname' => 'c1', 'entitytype' => 'equipment']);
        $scoped = (int)$gen->create_entities(['name' => 'Car 2', 'shortname' => 'c2', 'entitytype' => 'equipment']);
        $free = (int)$gen->create_entities(['name' => 'Car 3', 'shortname' => 'c3', 'entitytype' => 'equipment']);
        $all = [$person, $car, $scoped, $free];

        $this->block($person, 10, 12);
        $this->block($car, 10, 12);
        $this->block($scoped, 10, 12, $this->optionscope(7));

        $result = unavailability::get_unavailable_entityids($all, $this->t(11), $this->t(13), []);
        sort($result);
        $expected = [$person, $car];
        sort($expected);
        $this->assertSame($expected, $result, 'A scoped block does not apply without its scope.');

        $result = unavailability::get_unavailable_entityids($all, $this->t(11), $this->t(13), [$this->optionscope(7)]);
        $expected = [$person, $car, $scoped];
        sort($expected);
        sort($result);
        $this->assertSame($expected, $result);

        $this->assertSame([], unavailability::get_unavailable_entityids($all, $this->t(12), $this->t(14), null));
        $this->assertSame([], unavailability::get_unavailable_entityids($all, $this->t(11), $this->t(11), null));
        $this->assertSame([], unavailability::get_unavailable_entityids([], $this->t(11), $this->t(13), null));
    }

    /**
     * Regression for the data loss when saving: blocks outside of the saved window stay.
     *
     * @return void
     */
    public function test_replace_keeps_blocks_outside_the_window(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $entity = persons::get_or_create_for_user($this->getDataGenerator()->create_user()->id);
        $this->block($entity, 1, 2);
        $this->block($entity, 100, 102);

        unavailability::replace_in_window($entity, $this->t(0), $this->t(24), [], [$this->newblock(5, 7)]);

        $this->assertSame(['5-7', '100-102'], $this->ranges($entity));
    }

    /**
     * A block that reaches over the window edge keeps its part outside of the window.
     *
     * @return void
     */
    public function test_replace_keeps_the_part_of_a_block_outside_the_window(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $entity = persons::get_or_create_for_user($this->getDataGenerator()->create_user()->id);
        $this->block($entity, -10, 5);
        $this->block($entity, 20, 40);

        unavailability::replace_in_window($entity, $this->t(0), $this->t(24), [], [$this->newblock(8, 9)]);

        $this->assertSame(['-10-0', '8-9', '24-40'], $this->ranges($entity));
    }

    /**
     * Overlapping and adjacent blocks with the same reason are merged, different reasons are not,
     * and a stored block that touches the window edge is merged too.
     *
     * @return void
     */
    public function test_replace_merges_overlapping_and_adjacent_blocks(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $entity = persons::get_or_create_for_user($this->getDataGenerator()->create_user()->id);

        unavailability::replace_in_window(
            $entity,
            $this->t(0),
            $this->t(24),
            [],
            [$this->newblock(8, 10), $this->newblock(10, 12), $this->newblock(11, 14)]
        );
        $this->assertSame(['8-14'], $this->ranges($entity));

        unavailability::replace_in_window(
            $entity,
            $this->t(0),
            $this->t(24),
            [],
            [$this->newblock(8, 10, 'a'), $this->newblock(10, 12, 'b')]
        );
        $this->assertSame(['8-10#a', '10-12#b'], $this->ranges($entity));

        // A stored block that ends exactly where the window starts joins the new block.
        unavailability::replace_in_window($entity, $this->t(0), $this->t(24), [], []);
        $this->block($entity, -2, 0);
        unavailability::replace_in_window($entity, $this->t(0), $this->t(24), [], [$this->newblock(0, 3)]);
        $this->assertSame(['-2-3'], $this->ranges($entity));
    }

    /**
     * Replacing one scope leaves the blocks of every other scope alone.
     *
     * @return void
     */
    public function test_replace_only_touches_its_own_scope(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $entity = persons::get_or_create_for_user($this->getDataGenerator()->create_user()->id);
        $this->block($entity, 8, 10);
        $scope = $this->optionscope(7);

        unavailability::replace_in_window($entity, $this->t(0), $this->t(24), $scope, [$this->newblock(12, 14)]);
        $this->assertSame(['8-10', '12-14@mod_booking/option/7'], $this->ranges($entity));

        unavailability::replace_in_window($entity, $this->t(0), $this->t(24), $scope, []);
        $this->assertSame(['8-10'], $this->ranges($entity));
    }

    /**
     * Blocks that do not change keep their row, nothing is deleted and inserted again.
     *
     * @return void
     */
    public function test_replace_keeps_unchanged_rows(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $entity = persons::get_or_create_for_user($this->getDataGenerator()->create_user()->id);
        $id = $this->block($entity, 8, 10);

        unavailability::replace_in_window($entity, $this->t(0), $this->t(24), [], [$this->newblock(8, 10)]);

        $this->assertTrue($DB->record_exists('local_entities_unavailability', ['id' => $id]));
        $this->assertSame(1, $DB->count_records('local_entities_unavailability', ['entityid' => $entity]));
    }

    /**
     * Blocks given outside of the window are cut to it and never written outside.
     *
     * @return void
     */
    public function test_replace_cuts_new_blocks_to_the_window(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $entity = persons::get_or_create_for_user($this->getDataGenerator()->create_user()->id);

        unavailability::replace_in_window(
            $entity,
            $this->t(0),
            $this->t(24),
            [],
            [$this->newblock(-5, 3), $this->newblock(30, 40)]
        );

        $this->assertSame(['0-3'], $this->ranges($entity));
    }

    /**
     * A window must end after it starts.
     *
     * @return void
     */
    public function test_replace_rejects_an_invalid_window(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $entity = persons::get_or_create_for_user($this->getDataGenerator()->create_user()->id);

        $this->expectException(\coding_exception::class);
        unavailability::replace_in_window($entity, $this->t(5), $this->t(5), [], []);
    }

    /**
     * Deleting a person deletes its blocks and only those.
     *
     * @return void
     */
    public function test_blocks_are_deleted_with_the_person(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $gone = persons::get_or_create_for_user($this->getDataGenerator()->create_user()->id);
        $stays = persons::get_or_create_for_user($this->getDataGenerator()->create_user()->id);
        $this->block($gone, 8, 10);
        $this->block($gone, 12, 14, $this->optionscope(7));
        $this->block($stays, 8, 10);

        (new settings_manager($gone))->delete();

        $this->assertSame(0, $DB->count_records('local_entities_unavailability', ['entityid' => $gone]));
        $this->assertSame(1, $DB->count_records('local_entities_unavailability', ['entityid' => $stays]));
        $this->assertTrue($DB->record_exists('local_entities', ['id' => $stays]));
    }

    /**
     * The list of persons for a selection has only users that are a person and not deleted, ordered by
     * last and first name, and gives the full name.
     *
     * @return void
     */
    public function test_list_users_returns_the_persons_ordered_by_name(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $zora = $this->getDataGenerator()->create_user(['firstname' => 'Zora', 'lastname' => 'Zeller']);
        $anna = $this->getDataGenerator()->create_user(['firstname' => 'Anna', 'lastname' => 'Adams']);
        $gone = $this->getDataGenerator()->create_user(['firstname' => 'Gone', 'lastname' => 'Away']);
        $this->getDataGenerator()->create_user(['firstname' => 'Not', 'lastname' => 'Aperson']);
        persons::get_or_create_for_user($zora->id);
        persons::get_or_create_for_user($anna->id);
        persons::get_or_create_for_user($gone->id);
        $DB->set_field('user', 'deleted', 1, ['id' => $gone->id]);

        $list = persons::list_users();

        $this->assertSame([(int)$anna->id, (int)$zora->id], array_keys($list));
        $this->assertSame([fullname($anna), fullname($zora)], array_values($list));
    }
}
