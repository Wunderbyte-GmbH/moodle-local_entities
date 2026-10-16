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
 * Tests for merging time ranges.
 *
 * @package    local_entities
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_entities\unavailability\range_merger
 * @covers     \local_entities\unavailability\time_range
 */
final class range_merger_test extends \basic_testcase {
    /**
     * Short form of a range for the tests.
     *
     * @param int $start
     * @param int $end
     * @param string $reason
     * @param int $seriesid
     * @return time_range
     */
    private function r(int $start, int $end, string $reason = '', int $seriesid = 0): time_range {
        return new time_range($start, $end, $reason, $seriesid);
    }

    /**
     * Describes ranges as short strings: "8-10", "8-10#reason", "8-10~3" with the series id.
     *
     * @param time_range[] $ranges
     * @return string[]
     */
    private function d(array $ranges): array {
        return array_map(static function (time_range $range): string {
            return $range->starttime . '-' . $range->endtime
                . ($range->reason !== '' ? '#' . $range->reason : '')
                . ($range->seriesid ? '~' . $range->seriesid : '');
        }, $ranges);
    }

    /**
     * Overlapping ranges become one.
     *
     * @return void
     */
    public function test_overlapping_ranges_are_merged(): void {
        $merged = range_merger::merge([$this->r(8, 10), $this->r(9, 12)]);
        $this->assertSame(['8-12'], $this->d($merged));
    }

    /**
     * Ranges that touch each other become one, ranges with a gap stay apart.
     *
     * @return void
     */
    public function test_adjacent_ranges_are_merged_but_gaps_stay(): void {
        $merged = range_merger::merge([$this->r(8, 10), $this->r(10, 12), $this->r(13, 14)]);
        $this->assertSame(['8-12', '13-14'], $this->d($merged));
    }

    /**
     * A range inside another one is absorbed, and the order of the input does not matter.
     *
     * @return void
     */
    public function test_contained_ranges_are_absorbed_and_input_order_is_irrelevant(): void {
        $merged = range_merger::merge([$this->r(20, 22), $this->r(9, 10), $this->r(8, 12)]);
        $this->assertSame(['8-12', '20-22'], $this->d($merged));
    }

    /**
     * Ranges with another reason or another series are never merged, even if they overlap.
     *
     * @return void
     */
    public function test_other_reason_or_series_is_not_merged(): void {
        $merged = range_merger::merge([
            $this->r(8, 10, 'a'),
            $this->r(9, 11, 'b'),
            $this->r(10, 12, 'a', 3),
            $this->r(11, 13, 'a', 4),
        ]);
        $this->assertSame(['8-10#a', '9-11#b', '10-12#a~3', '11-13#a~4'], $this->d($merged));
    }

    /**
     * Ranges of the same reason and series are merged within their group and the result is ordered
     * by start time over all groups.
     *
     * @return void
     */
    public function test_groups_are_merged_separately_and_result_is_ordered(): void {
        $merged = range_merger::merge([
            $this->r(20, 22, 'a'),
            $this->r(8, 10, 'b'),
            $this->r(9, 11, 'b'),
            $this->r(21, 23, 'a'),
        ]);
        $this->assertSame(['8-11#b', '20-23#a'], $this->d($merged));
    }

    /**
     * Two ranges have the same identity only if start, end, reason and series are all the same.
     *
     * @return void
     */
    public function test_identity_tells_equal_ranges_apart_from_different_ones(): void {
        $range = $this->r(8, 10, 'a', 3);

        $this->assertSame($range->identity(), $this->r(8, 10, 'a', 3)->identity());
        $this->assertNotSame($range->identity(), $this->r(9, 10, 'a', 3)->identity());
        $this->assertNotSame($range->identity(), $this->r(8, 11, 'a', 3)->identity());
        $this->assertNotSame($range->identity(), $this->r(8, 10, 'b', 3)->identity());
        $this->assertNotSame($range->identity(), $this->r(8, 10, 'a', 4)->identity());
    }

    /**
     * Ranges without a length are dropped, and nothing in gives nothing out.
     *
     * @return void
     */
    public function test_empty_and_invalid_ranges_are_dropped(): void {
        $this->assertSame([], range_merger::merge([]));
        $this->assertSame(['8-10'], $this->d(range_merger::merge([$this->r(5, 5), $this->r(12, 4), $this->r(8, 10)])));
    }
}
