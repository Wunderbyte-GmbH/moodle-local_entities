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
 * Tests for series of unavailability (a rule that creates single blocks that refer to the series).
 *
 * The tests run in the timezone Europe/Vienna. Daylight saving time ends there on Sunday
 * 2026-10-25, so the weeks used below contain a day with 25 hours.
 *
 * @package    local_entities
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_entities\unavailability
 */
final class unavailability_series_test extends \advanced_testcase {
    /** @var string Timezone of all rules in this test. */
    private const TZ = 'Europe/Vienna';

    /**
     * Timestamp of a local date and time in the timezone of the tests.
     *
     * @param string $datetime e.g. "2026-10-19 08:00"
     * @return int
     */
    private function ts(string $datetime): int {
        return (new \DateTimeImmutable($datetime, new \DateTimeZone(self::TZ)))->getTimestamp();
    }

    /**
     * A rule for a series, by default Monday and Wednesday from 08:00 to 10:00 in two weeks.
     *
     * @param array $overrides
     * @return array
     */
    private function rule(array $overrides = []): array {
        return $overrides + [
            'weekdays' => [1, 3],
            'allday' => false,
            'daystart' => 8 * HOURSECS,
            'dayend' => 10 * HOURSECS,
            'validfrom' => $this->ts('2026-10-19 12:00'),
            'validuntil' => $this->ts('2026-11-01 12:00'),
            'timezone' => self::TZ,
            'reason' => 'Lecture',
        ];
    }

    /**
     * Describes the blocks of an entity in local time: "2026-10-19 08:00-10:00".
     *
     * @param int $entityid
     * @param int $seriesid 0 for all blocks of the entity
     * @return string[]
     */
    private function described(int $entityid, int $seriesid = 0): array {
        global $DB;
        $conditions = ['entityid' => $entityid];
        if ($seriesid) {
            $conditions['seriesid'] = $seriesid;
        }
        $tz = new \DateTimeZone(self::TZ);
        $result = [];
        foreach ($DB->get_records('local_entities_unavailability', $conditions, 'starttime, id') as $record) {
            $start = (new \DateTimeImmutable('@' . $record->starttime))->setTimezone($tz);
            $end = (new \DateTimeImmutable('@' . $record->endtime))->setTimezone($tz);
            $same = $start->format('Y-m-d') === $end->format('Y-m-d');
            $result[] = $start->format('Y-m-d H:i') . '-' . ($same ? $end->format('H:i') : $end->format('Y-m-d H:i'));
        }
        return $result;
    }

    /**
     * Creates a person and returns its entity id.
     *
     * @return int
     */
    private function person(): int {
        return persons::get_or_create_for_user($this->getDataGenerator()->create_user()->id);
    }

    /**
     * A series stores its rule once and one single block per matching day, each referring to the series.
     *
     * @return void
     */
    public function test_series_creates_one_block_per_matching_day(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $entity = $this->person();
        $seriesid = unavailability::create_series($entity, $this->rule());

        $this->assertGreaterThan(0, $seriesid);
        $series = $DB->get_record('local_entities_unavail_series', ['id' => $seriesid], '*', MUST_EXIST);
        $this->assertEquals($entity, $series->entityid);
        $this->assertSame('1,3', $series->weekdays);
        $this->assertSame(self::TZ, $series->timezone);
        $this->assertSame('Lecture', $series->reason);
        $this->assertEquals($this->ts('2026-10-19 00:00'), $series->validfrom, 'The first day is stored as midnight.');
        $this->assertEquals($this->ts('2026-11-01 00:00'), $series->validuntil, 'The last day is stored as midnight.');

        $this->assertSame([
            '2026-10-19 08:00-10:00',
            '2026-10-21 08:00-10:00',
            '2026-10-26 08:00-10:00',
            '2026-10-28 08:00-10:00',
        ], $this->described($entity, $seriesid));
        $this->assertSame(4, $DB->count_records('local_entities_unavailability', ['seriesid' => $seriesid]));
    }

    /**
     * The local time stays the same when daylight saving time ends in the middle of the series, so the
     * blocks after the change are one hour further from the first ones in UTC.
     *
     * @return void
     */
    public function test_series_keeps_local_time_across_daylight_saving_change(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $entity = $this->person();
        $seriesid = unavailability::create_series($entity, $this->rule(['weekdays' => [1]]));

        $blocks = array_values($DB->get_records('local_entities_unavailability', ['seriesid' => $seriesid], 'starttime'));
        $this->assertCount(2, $blocks);
        $this->assertEquals(7 * DAYSECS + HOURSECS, $blocks[1]->starttime - $blocks[0]->starttime);
        $this->assertEquals(2 * HOURSECS, $blocks[1]->endtime - $blocks[1]->starttime);
    }

    /**
     * An all-day series covers the whole local days, also the day that has 25 hours.
     *
     * @return void
     */
    public function test_allday_series_covers_whole_days(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $entity = $this->person();
        $seriesid = unavailability::create_series($entity, $this->rule([
            'weekdays' => [6, 7],
            'allday' => true,
            'daystart' => 0,
            'dayend' => 0,
        ]));

        $this->assertSame([
            '2026-10-24 00:00-2026-10-25 00:00',
            '2026-10-25 00:00-2026-10-26 00:00',
            '2026-10-31 00:00-2026-11-01 00:00',
            '2026-11-01 00:00-2026-11-02 00:00',
        ], $this->described($entity, $seriesid));

        $sunday = $DB->get_record('local_entities_unavailability', [
            'seriesid' => $seriesid,
            'starttime' => $this->ts('2026-10-25 00:00'),
        ], '*', MUST_EXIST);
        $this->assertEquals(25 * HOURSECS, $sunday->endtime - $sunday->starttime);
    }

    /**
     * A series keeps the scope it was created for, and its blocks are found only for that scope.
     *
     * @return void
     */
    public function test_series_is_bound_to_its_scope(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $entity = $this->person();
        $scope = ['component' => 'mod_booking', 'area' => 'option', 'instanceid' => 7];
        $seriesid = unavailability::create_series($entity, $this->rule(['weekdays' => [1]]), $scope);

        $series = $DB->get_record('local_entities_unavail_series', ['id' => $seriesid], '*', MUST_EXIST);
        $this->assertSame('mod_booking', $series->component);
        $this->assertSame('option', $series->area);
        $this->assertEquals(7, $series->instanceid);

        $from = $this->ts('2026-10-19 00:00');
        $until = $this->ts('2026-11-02 00:00');
        $this->assertCount(2, unavailability::get_blocks($entity, $from, $until, [$scope]));
        $this->assertCount(0, unavailability::get_blocks($entity, $from, $until, []));
        foreach (unavailability::get_blocks($entity, $from, $until, [$scope]) as $block) {
            $this->assertEquals($seriesid, $block->seriesid);
            $this->assertSame('mod_booking', $block->component);
            $this->assertEquals(7, $block->instanceid);
        }
    }

    /**
     * Rules that make no sense are rejected, and then nothing is stored at all.
     *
     * @dataProvider invalid_rules_provider
     * @param array $overrides
     * @return void
     */
    public function test_invalid_rules_are_rejected_and_nothing_is_stored(array $overrides): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $entity = $this->person();
        try {
            unavailability::create_series($entity, $this->rule($overrides));
            $this->fail('The rule should have been rejected.');
        } catch (\invalid_parameter_exception $e) {
            $this->assertSame(0, $DB->count_records('local_entities_unavail_series'));
            $this->assertSame(0, $DB->count_records('local_entities_unavailability'));
        }
    }

    /**
     * Invalid rules.
     *
     * @return array
     */
    public static function invalid_rules_provider(): array {
        return [
            'no weekday' => [['weekdays' => []]],
            'weekday 0' => [['weekdays' => [0]]],
            'weekday 8' => [['weekdays' => [1, 8]]],
            'time ends before it starts' => [['daystart' => 10 * HOURSECS, 'dayend' => 8 * HOURSECS]],
            'time without length' => [['daystart' => 8 * HOURSECS, 'dayend' => 8 * HOURSECS]],
            'time reaches into the next day' => [['daystart' => 8 * HOURSECS, 'dayend' => DAYSECS + HOURSECS]],
            'last day before first day' => [['validfrom' => 1793000000, 'validuntil' => 1792000000]],
            'no first day' => [['validfrom' => 0]],
        ];
    }

    /**
     * A series may span 366 days from its first to its last day, one more day is rejected.
     *
     * @return void
     */
    public function test_series_length_is_limited(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $entity = $this->person();
        $seriesid = unavailability::create_series($entity, $this->rule([
            'weekdays' => [1],
            'validfrom' => $this->ts('2026-01-01 00:00'),
            'validuntil' => $this->ts('2027-01-02 00:00'),
        ]));
        $this->assertGreaterThan(0, $seriesid);
        $this->assertGreaterThan(50, $DB->count_records('local_entities_unavailability', ['seriesid' => $seriesid]));

        $this->expectException(\invalid_parameter_exception::class);
        unavailability::create_series($entity, $this->rule([
            'validfrom' => $this->ts('2026-01-01 00:00'),
            'validuntil' => $this->ts('2027-01-03 00:00'),
        ]));
    }

    /**
     * Blocks of a series are never merged with single blocks, with blocks of another series or with
     * blocks of another reason, even if they touch each other.
     *
     * @return void
     */
    public function test_series_blocks_are_not_merged_with_others(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $entity = $this->person();
        $first = unavailability::create_series($entity, $this->rule([
            'weekdays' => [1],
            'validuntil' => $this->ts('2026-10-19 12:00'),
        ]));
        $second = unavailability::create_series($entity, $this->rule([
            'weekdays' => [1],
            'validuntil' => $this->ts('2026-10-19 12:00'),
            'daystart' => 10 * HOURSECS,
            'dayend' => 12 * HOURSECS,
        ]));

        // A single block 12:00-14:00 touches the block of the second series, and another one overlaps both.
        unavailability::replace_in_window($entity, $this->ts('2026-10-19 00:00'), $this->ts('2026-10-20 00:00'), [], [
            ['starttime' => $this->ts('2026-10-19 12:00'), 'endtime' => $this->ts('2026-10-19 14:00'), 'reason' => 'Lecture'],
            ['starttime' => $this->ts('2026-10-19 08:00'), 'endtime' => $this->ts('2026-10-19 10:00'),
                'reason' => 'Lecture', 'seriesid' => $first],
            ['starttime' => $this->ts('2026-10-19 10:00'), 'endtime' => $this->ts('2026-10-19 12:00'),
                'reason' => 'Lecture', 'seriesid' => $second],
        ]);

        $this->assertSame(['2026-10-19 08:00-10:00'], $this->described($entity, $first));
        $this->assertSame(['2026-10-19 10:00-12:00'], $this->described($entity, $second));
        $this->assertSame([
            '2026-10-19 08:00-10:00',
            '2026-10-19 10:00-12:00',
            '2026-10-19 12:00-14:00',
        ], $this->described($entity));
    }

    /**
     * A block can only refer to a series of its own entity. A foreign or unknown series id is dropped,
     * the block is then stored as a single block.
     *
     * @return void
     */
    public function test_block_can_not_refer_to_a_foreign_series(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $mine = $this->person();
        $other = $this->person();
        $foreign = unavailability::create_series($other, $this->rule(['weekdays' => [1]]));

        unavailability::replace_in_window($mine, $this->ts('2026-10-19 00:00'), $this->ts('2026-10-20 00:00'), [], [
            ['starttime' => $this->ts('2026-10-19 08:00'), 'endtime' => $this->ts('2026-10-19 10:00'), 'seriesid' => $foreign],
            ['starttime' => $this->ts('2026-10-19 12:00'), 'endtime' => $this->ts('2026-10-19 14:00'), 'seriesid' => 99999],
        ]);

        $this->assertSame(2, $DB->count_records('local_entities_unavailability', ['entityid' => $mine]));
        $this->assertSame(0, $DB->count_records_select(
            'local_entities_unavailability',
            'entityid = :entityid AND seriesid IS NOT NULL',
            ['entityid' => $mine]
        ));
    }

    /**
     * Local date of today plus an offset in days, in the timezone of the tests.
     *
     * @param int $offset
     * @return string e.g. "2026-10-19"
     */
    private function day(int $offset): string {
        $today = new \DateTimeImmutable('today', new \DateTimeZone(self::TZ));
        return $today->modify(($offset >= 0 ? '+' : '') . $offset . ' days')->format('Y-m-d');
    }

    /**
     * Inserts a single block (no series, everywhere) directly, next to the blocks of the series.
     *
     * replace_in_window() is not used for this, as it would replace the series blocks in its window.
     *
     * @param int $entityid
     * @param int $start
     * @param int $end
     * @return void
     */
    private function single_block(int $entityid, int $start, int $end): void {
        global $DB;
        $DB->insert_record('local_entities_unavailability', (object)[
            'entityid' => $entityid,
            'starttime' => $start,
            'endtime' => $end,
            'component' => '',
            'area' => '',
            'instanceid' => 0,
            'reason' => '',
            'timecreated' => 1,
            'timemodified' => 1,
            'usermodified' => 0,
        ]);
    }

    /**
     * A rule for a daily series from three days ago to three days ahead, 10:00 to 12:00.
     *
     * @param array $overrides
     * @return array
     */
    private function dailyrule(array $overrides = []): array {
        return $overrides + $this->rule([
            'weekdays' => [1, 2, 3, 4, 5, 6, 7],
            'validfrom' => $this->ts($this->day(-3) . ' 12:00'),
            'validuntil' => $this->ts($this->day(3) . ' 12:00'),
            'daystart' => 10 * HOURSECS,
            'dayend' => 12 * HOURSECS,
        ]);
    }

    /**
     * Changing a series replaces its blocks from today on and leaves the past as it was.
     *
     * @return void
     */
    public function test_update_replaces_blocks_from_today_on(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $entity = $this->person();
        $seriesid = unavailability::create_series($entity, $this->dailyrule());
        $this->assertCount(7, $this->described($entity, $seriesid));

        unavailability::update_series($seriesid, $this->dailyrule(['daystart' => 14 * HOURSECS, 'dayend' => 16 * HOURSECS]));

        $this->assertSame([
            $this->day(-3) . ' 10:00-12:00',
            $this->day(-2) . ' 10:00-12:00',
            $this->day(-1) . ' 10:00-12:00',
            $this->day(0) . ' 14:00-16:00',
            $this->day(1) . ' 14:00-16:00',
            $this->day(2) . ' 14:00-16:00',
            $this->day(3) . ' 14:00-16:00',
        ], $this->described($entity, $seriesid));
    }

    /**
     * A shorter series loses its blocks after the new last day, a changed weekday list takes effect.
     *
     * @return void
     */
    public function test_update_can_shorten_the_series(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $entity = $this->person();
        $seriesid = unavailability::create_series($entity, $this->dailyrule());

        unavailability::update_series($seriesid, $this->dailyrule(['validuntil' => $this->ts($this->day(1) . ' 12:00')]));

        $this->assertSame(5, $DB->count_records('local_entities_unavailability', ['seriesid' => $seriesid]));
        $series = $DB->get_record('local_entities_unavail_series', ['id' => $seriesid], '*', MUST_EXIST);
        $this->assertEquals($this->ts($this->day(1) . ' 00:00'), $series->validuntil);
    }

    /**
     * A block that reaches over the start of today is cut at midnight, so it is not duplicated by the
     * new blocks of today.
     *
     * @return void
     */
    public function test_update_cuts_a_block_that_reaches_into_today(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $entity = $this->person();
        $seriesid = unavailability::create_series($entity, $this->dailyrule([
            'validuntil' => $this->ts($this->day(-3) . ' 12:00'),
        ]));
        $DB->delete_records('local_entities_unavailability', ['seriesid' => $seriesid]);
        $DB->insert_record('local_entities_unavailability', (object)[
            'entityid' => $entity,
            'starttime' => $this->ts($this->day(-1) . ' 12:00'),
            'endtime' => $this->ts($this->day(0) . ' 12:00'),
            'component' => '',
            'area' => '',
            'instanceid' => 0,
            'reason' => 'Lecture',
            'seriesid' => $seriesid,
            'timecreated' => 1,
            'timemodified' => 1,
            'usermodified' => 0,
        ]);

        unavailability::update_series($seriesid, $this->dailyrule([
            'weekdays' => [1, 2, 3, 4, 5, 6, 7],
            'validuntil' => $this->ts($this->day(0) . ' 12:00'),
        ]));

        $this->assertSame([
            $this->day(-1) . ' 12:00-' . $this->day(0) . ' 00:00',
            $this->day(0) . ' 10:00-12:00',
        ], $this->described($entity, $seriesid));
    }

    /**
     * Changing a series leaves other series and single blocks of the same entity alone.
     *
     * @return void
     */
    public function test_update_does_not_touch_other_blocks(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $entity = $this->person();
        $seriesid = unavailability::create_series($entity, $this->dailyrule());
        $otherid = unavailability::create_series($entity, $this->dailyrule(['reason' => 'Other']));
        $this->single_block($entity, $this->ts($this->day(1) . ' 18:00'), $this->ts($this->day(1) . ' 20:00'));
        $othersbefore = $this->described($entity, $otherid);
        $totalbefore = count($this->described($entity));

        unavailability::update_series($seriesid, $this->dailyrule(['daystart' => 14 * HOURSECS, 'dayend' => 16 * HOURSECS]));

        $this->assertSame($othersbefore, $this->described($entity, $otherid));
        $this->assertCount($totalbefore, $this->described($entity), 'The blocks of the entity stay as many as before.');
        $this->assertContains($this->day(1) . ' 18:00-20:00', $this->described($entity));
    }

    /**
     * A rule that is not valid changes nothing.
     *
     * @return void
     */
    public function test_update_with_an_invalid_rule_changes_nothing(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $entity = $this->person();
        $seriesid = unavailability::create_series($entity, $this->dailyrule());
        $before = $this->described($entity, $seriesid);

        try {
            unavailability::update_series($seriesid, $this->dailyrule(['weekdays' => []]));
            $this->fail('The rule should have been rejected.');
        } catch (\invalid_parameter_exception $e) {
            $this->assertSame($before, $this->described($entity, $seriesid));
        }
    }

    /**
     * An unknown series can not be changed.
     *
     * @return void
     */
    public function test_update_of_an_unknown_series_fails(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->expectException(\dml_missing_record_exception::class);
        unavailability::update_series(99999, $this->dailyrule());
    }

    /**
     * Deleting a series removes its blocks from today on. The past stays, and the series then ends
     * yesterday, so it can no longer be changed into the future by mistake.
     *
     * @return void
     */
    public function test_delete_removes_blocks_from_today_on(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $entity = $this->person();
        $seriesid = unavailability::create_series($entity, $this->dailyrule());

        unavailability::delete_series($seriesid);

        $this->assertSame([
            $this->day(-3) . ' 10:00-12:00',
            $this->day(-2) . ' 10:00-12:00',
            $this->day(-1) . ' 10:00-12:00',
        ], $this->described($entity, $seriesid));
        $series = $DB->get_record('local_entities_unavail_series', ['id' => $seriesid], '*', MUST_EXIST);
        $this->assertEquals($this->ts($this->day(-1) . ' 00:00'), $series->validuntil);
    }

    /**
     * A series that has no blocks left is deleted completely.
     *
     * @return void
     */
    public function test_delete_removes_a_future_series_completely(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $entity = $this->person();
        $seriesid = unavailability::create_series($entity, $this->dailyrule([
            'validfrom' => $this->ts($this->day(2) . ' 12:00'),
            'validuntil' => $this->ts($this->day(5) . ' 12:00'),
        ]));
        $this->assertCount(4, $this->described($entity, $seriesid));

        unavailability::delete_series($seriesid);

        $this->assertFalse($DB->record_exists('local_entities_unavail_series', ['id' => $seriesid]));
        $this->assertSame(0, $DB->count_records('local_entities_unavailability', ['entityid' => $entity]));
    }

    /**
     * Deleting a series leaves other series and single blocks alone.
     *
     * @return void
     */
    public function test_delete_does_not_touch_other_blocks(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $entity = $this->person();
        $seriesid = unavailability::create_series($entity, $this->dailyrule());
        $otherid = unavailability::create_series($entity, $this->dailyrule(['reason' => 'Other']));
        $this->single_block($entity, $this->ts($this->day(1) . ' 18:00'), $this->ts($this->day(1) . ' 20:00'));

        unavailability::delete_series($seriesid);

        $this->assertCount(7, $this->described($entity, $otherid));
        $this->assertSame(1, $DB->count_records('local_entities_unavailability', ['entityid' => $entity, 'seriesid' => null]));
    }

    /**
     * The series of an entity can be listed, optionally for scopes and only if not ended yet.
     *
     * @return void
     */
    public function test_get_series_by_entity_scope_and_end(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $entity = $this->person();
        $otherentity = $this->person();
        $scope = ['component' => 'mod_booking', 'area' => 'option', 'instanceid' => 7];

        $everywhere = unavailability::create_series($entity, $this->dailyrule());
        $scoped = unavailability::create_series($entity, $this->dailyrule(), $scope);
        $ended = unavailability::create_series($entity, $this->dailyrule([
            'validfrom' => $this->ts($this->day(-30) . ' 12:00'),
            'validuntil' => $this->ts($this->day(-20) . ' 12:00'),
        ]));
        unavailability::create_series($otherentity, $this->dailyrule());

        $ids = static fn(array $series): array => array_map('intval', array_keys($series));

        $this->assertEqualsCanonicalizing([$everywhere, $scoped, $ended], $ids(unavailability::get_series($entity)));
        $this->assertEqualsCanonicalizing([$everywhere, $ended], $ids(unavailability::get_series($entity, [])));
        $this->assertEqualsCanonicalizing([$everywhere, $scoped, $ended], $ids(unavailability::get_series($entity, [$scope])));
        $this->assertEqualsCanonicalizing(
            [$everywhere, $scoped],
            $ids(unavailability::get_series($entity, null, $this->ts($this->day(0) . ' 00:00')))
        );
    }

    /**
     * Deleting an entity also removes its unavailability and its series, so nothing is left behind.
     *
     * @return void
     */
    public function test_deleting_an_entity_removes_its_series(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        /** @var \local_entities_generator $gen */
        $gen = $this->getDataGenerator()->get_plugin_generator('local_entities');
        $roomid = $gen->create_entities(['name' => 'Room B', 'shortname' => 'rb', 'entitytype' => 'location']);
        $otherid = $gen->create_entities(['name' => 'Room C', 'shortname' => 'rc', 'entitytype' => 'location']);
        unavailability::create_series($roomid, $this->rule(['validfrom' => time(), 'validuntil' => time() + 14 * DAYSECS]));
        $keptrule = $this->rule(['validfrom' => time(), 'validuntil' => time() + 14 * DAYSECS]);
        $kept = unavailability::create_series($otherid, $keptrule);
        $this->assertNotEmpty($DB->get_records('local_entities_unavailability', ['entityid' => $roomid]));

        (new \local_entities\settings_manager($roomid))->delete();

        $this->assertSame(0, $DB->count_records('local_entities_unavail_series', ['entityid' => $roomid]));
        $this->assertSame(0, $DB->count_records('local_entities_unavailability', ['entityid' => $roomid]));
        $this->assertTrue($DB->record_exists('local_entities_unavail_series', ['id' => $kept]), 'Other entities keep theirs.');
    }
}
