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

/**
 * Tests for the rule of a series: checking, normalising and calculating the days.
 *
 * The tests run in the timezone Europe/Vienna, where daylight saving time ends on Sunday 2026-10-25.
 *
 * @package    local_entities
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_entities\unavailability\series_rule
 */
final class series_rule_test extends \basic_testcase {
    /** @var string Timezone of the rules in this test. */
    private const TZ = 'Europe/Vienna';

    /**
     * Timestamp of a local date and time.
     *
     * @param string $datetime e.g. "2026-10-19 08:00"
     * @return int
     */
    private function ts(string $datetime): int {
        return (new \DateTimeImmutable($datetime, new \DateTimeZone(self::TZ)))->getTimestamp();
    }

    /**
     * A rule as callers pass it: Monday and Wednesday, 08:00 to 10:00, in two weeks.
     *
     * @param array $overrides
     * @return array
     */
    private function rule(array $overrides = []): array {
        return $overrides + [
            'weekdays' => [3, 1, 3],
            'allday' => false,
            'daystart' => 8 * HOURSECS,
            'dayend' => 10 * HOURSECS,
            'validfrom' => $this->ts('2026-10-19 12:00'),
            'validuntil' => $this->ts('2026-11-01 12:00'),
            'timezone' => self::TZ,
            'reason' => '  Lecture ',
        ];
    }

    /**
     * Describes ranges in local time: "2026-10-19 08:00-10:00".
     *
     * @param time_range[] $ranges
     * @return string[]
     */
    private function d(array $ranges): array {
        $tz = new \DateTimeZone(self::TZ);
        return array_map(static function (time_range $range) use ($tz): string {
            $start = (new \DateTimeImmutable('@' . $range->starttime))->setTimezone($tz);
            $end = (new \DateTimeImmutable('@' . $range->endtime))->setTimezone($tz);
            $same = $start->format('Y-m-d') === $end->format('Y-m-d');
            return $start->format('Y-m-d H:i') . '-' . ($same ? $end->format('H:i') : $end->format('Y-m-d H:i'));
        }, $ranges);
    }

    /**
     * The rule is normalised: weekdays unique and sorted, first and last day at midnight, reason trimmed.
     *
     * @return void
     */
    public function test_rule_is_normalised(): void {
        $rule = series_rule::from_array($this->rule());

        $this->assertSame([1, 3], $rule->weekdays);
        $this->assertFalse($rule->allday);
        $this->assertSame(8 * HOURSECS, $rule->daystart);
        $this->assertSame(10 * HOURSECS, $rule->dayend);
        $this->assertSame($this->ts('2026-10-19 00:00'), $rule->validfrom);
        $this->assertSame($this->ts('2026-11-01 00:00'), $rule->validuntil);
        $this->assertSame(self::TZ, $rule->timezone);
        $this->assertSame('Lecture', $rule->reason);
    }

    /**
     * An all-day rule has no times, even if some are passed.
     *
     * @return void
     */
    public function test_allday_rule_has_no_times(): void {
        $rule = series_rule::from_array($this->rule(['allday' => true]));

        $this->assertTrue($rule->allday);
        $this->assertSame(0, $rule->daystart);
        $this->assertSame(0, $rule->dayend);
    }

    /**
     * Rules that make no sense are rejected.
     *
     * @dataProvider invalid_rules_provider
     * @param array $overrides
     * @return void
     */
    public function test_invalid_rules_are_rejected(array $overrides): void {
        $this->expectException(\invalid_parameter_exception::class);
        series_rule::from_array($this->rule($overrides));
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
     * A rule may span 366 days from its first to its last day, one more day is rejected.
     *
     * @return void
     */
    public function test_length_is_limited(): void {
        $longest = series_rule::from_array($this->rule([
            'validfrom' => $this->ts('2026-01-01 00:00'),
            'validuntil' => $this->ts('2027-01-02 00:00'),
        ]));
        $this->assertSame($this->ts('2027-01-02 00:00'), $longest->validuntil);

        $this->expectException(\invalid_parameter_exception::class);
        series_rule::from_array($this->rule([
            'validfrom' => $this->ts('2026-01-01 00:00'),
            'validuntil' => $this->ts('2027-01-03 00:00'),
        ]));
    }

    /**
     * One range per matching day, in local time, each carrying reason and series id.
     *
     * @return void
     */
    public function test_occurrences_are_one_range_per_matching_day(): void {
        $rule = series_rule::from_array($this->rule());

        $ranges = $rule->occurrences(0, 5);

        $this->assertSame([
            '2026-10-19 08:00-10:00',
            '2026-10-21 08:00-10:00',
            '2026-10-26 08:00-10:00',
            '2026-10-28 08:00-10:00',
        ], $this->d($ranges));
        $this->assertSame('Lecture', $ranges[0]->reason);
        $this->assertSame(5, $ranges[0]->seriesid);
    }

    /**
     * The local time stays the same across the end of daylight saving time.
     *
     * @return void
     */
    public function test_occurrences_keep_local_time_across_daylight_saving_change(): void {
        $rule = series_rule::from_array($this->rule(['weekdays' => [1]]));

        $ranges = $rule->occurrences();

        $this->assertCount(2, $ranges);
        $this->assertSame(7 * DAYSECS + HOURSECS, $ranges[1]->starttime - $ranges[0]->starttime);
        $this->assertSame(2 * HOURSECS, $ranges[1]->endtime - $ranges[1]->starttime);
    }

    /**
     * An all-day rule covers whole local days, also the one with 25 hours.
     *
     * @return void
     */
    public function test_allday_occurrences_cover_whole_days(): void {
        $rule = series_rule::from_array($this->rule(['weekdays' => [7], 'allday' => true]));

        $ranges = $rule->occurrences();

        $this->assertSame([
            '2026-10-25 00:00-2026-10-26 00:00',
            '2026-11-01 00:00-2026-11-02 00:00',
        ], $this->d($ranges));
        $this->assertSame(25 * HOURSECS, $ranges[0]->endtime - $ranges[0]->starttime);
    }

    /**
     * Days before the given time are left out. A time after the end gives nothing.
     *
     * @return void
     */
    public function test_occurrences_start_at_the_given_day(): void {
        $rule = series_rule::from_array($this->rule());

        $this->assertSame(
            ['2026-10-26 08:00-10:00', '2026-10-28 08:00-10:00'],
            $this->d($rule->occurrences($this->ts('2026-10-22 15:30')))
        );
        $this->assertSame([], $rule->occurrences($this->ts('2026-11-05 00:00')));
        $this->assertCount(4, $rule->occurrences($this->ts('2026-01-01 00:00')), 'Not before the first day.');
    }

    /**
     * A rule survives being stored and read back.
     *
     * @return void
     */
    public function test_rule_survives_storage(): void {
        $rule = series_rule::from_array($this->rule());

        $record = (object)$rule->to_record_fields();
        $this->assertSame('1,3', $record->weekdays);
        $again = series_rule::from_record($record);

        $this->assertEquals($rule, $again);

        $allday = series_rule::from_array($this->rule(['allday' => true]));
        $record = (object)$allday->to_record_fields();
        $this->assertNull($record->daystart);
        $this->assertEquals($allday, series_rule::from_record($record));
    }
}
