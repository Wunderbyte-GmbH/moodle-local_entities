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

use local_entities\local\entities\entitydate;
use local_entities\unavailability\conflict_detector;
use local_entities\unavailability\occupation_source;

/**
 * Tests for the conflicts between an unavailability and the occupations of a person.
 *
 * All times are hour offsets from a fixed base time, so "8-10" is base + 8 hours to base + 10 hours.
 *
 * @package    local_entities
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_entities\unavailability
 */
final class unavailability_conflicts_test extends \advanced_testcase {
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
     * An occupation as the booking plugin delivers it.
     *
     * @param int $itemid
     * @param int $from hour offset
     * @param int $until hour offset
     * @param int $status 1 = cancelled
     * @return entitydate
     */
    private function date(int $itemid, int $from, int $until, int $status = 0): entitydate {
        return new entitydate(
            $itemid,
            'mod_booking',
            'optiondate',
            'Exam ' . $itemid,
            $this->t($from),
            $this->t($until),
            $status,
            new \moodle_url('/mod/booking/optionview.php', ['optionid' => $itemid])
        );
    }

    /**
     * Range as it is given to the conflict check.
     *
     * @param int $from hour offset
     * @param int $until hour offset
     * @return array
     */
    private function range(int $from, int $until): array {
        return ['starttime' => $this->t($from), 'endtime' => $this->t($until)];
    }

    /**
     * Item ids of the conflicts, in the order they are returned.
     *
     * @param array $conflicts
     * @return int[]
     */
    private function itemids(array $conflicts): array {
        return array_map(static fn($c) => $c['itemid'], $conflicts);
    }

    /**
     * Runs the conflict check against a fixed list of occupations, as a source would deliver them.
     *
     * @param entitydate[] $dates
     * @param array $ranges
     * @return array
     */
    private function find(array $dates, array $ranges): array {
        $source = new class ($dates) implements occupation_source {
            /** @var entitydate[] */
            private array $dates;

            /**
             * Constructor.
             *
             * @param entitydate[] $dates
             */
            public function __construct(array $dates) {
                $this->dates = $dates;
            }

            /**
             * Returns the fixed occupations.
             *
             * @param int $entityid
             * @return array
             */
            public function get_occupations(int $entityid): array {
                return $this->dates;
            }
        };

        return (new conflict_detector($source))->find(1, $ranges);
    }

    /**
     * Occupations that overlap a range are returned, those that only touch it or lie outside are not.
     *
     * @return void
     */
    public function test_only_overlapping_dates_are_conflicts(): void {
        $dates = [
            $this->date(1, 6, 8),
            $this->date(2, 7, 9),
            $this->date(3, 9, 11),
            $this->date(4, 10, 12),
            $this->date(5, 8, 10),
            $this->date(6, 11, 13),
            $this->date(7, 20, 22),
        ];

        $conflicts = $this->find($dates, [$this->range(8, 10)]);

        $this->assertSame([2, 5, 3], $this->itemids($conflicts), 'Ordered by start time.');
    }

    /**
     * Cancelled dates do not block an unavailability.
     *
     * @return void
     */
    public function test_cancelled_dates_are_no_conflict(): void {
        $dates = [$this->date(1, 8, 10, 1), $this->date(2, 8, 10, 0)];

        $conflicts = $this->find($dates, [$this->range(8, 10)]);

        $this->assertSame([2], $this->itemids($conflicts));
    }

    /**
     * A date that overlaps several ranges is reported once. The result is ordered by start time.
     *
     * @return void
     */
    public function test_each_date_is_reported_once_and_ordered(): void {
        $dates = [$this->date(1, 20, 22), $this->date(2, 8, 30), $this->date(3, 12, 14)];

        $ranges = [$this->range(9, 10), $this->range(11, 13), $this->range(21, 23)];
        $conflicts = $this->find($dates, $ranges);

        $this->assertSame([2, 3, 1], $this->itemids($conflicts));
    }

    /**
     * Ranges without a length or in the wrong order are ignored instead of matching everything.
     *
     * @return void
     */
    public function test_invalid_ranges_are_ignored(): void {
        $dates = [$this->date(1, 8, 10)];

        $this->assertSame([], $this->find($dates, [$this->range(9, 9), $this->range(12, 4)]));
        $this->assertSame([], $this->find($dates, []));
        $this->assertSame([], $this->find([], [$this->range(8, 10)]));
    }

    /**
     * A conflict has what the interface needs to show it: title, time, link and where it comes from.
     *
     * @return void
     */
    public function test_a_conflict_carries_title_time_and_link(): void {
        $conflicts = $this->find([$this->date(42, 8, 10)], [$this->range(9, 11)]);

        $this->assertCount(1, $conflicts);
        $this->assertSame('Exam 42', $conflicts[0]['name']);
        $this->assertSame($this->t(8), $conflicts[0]['starttime']);
        $this->assertSame($this->t(10), $conflicts[0]['endtime']);
        $this->assertSame('mod_booking', $conflicts[0]['component']);
        $this->assertSame('optiondate', $conflicts[0]['area']);
        $this->assertSame(42, $conflicts[0]['itemid']);
        $this->assertStringContainsString('optionid=42', $conflicts[0]['url']);
    }

    /**
     * Without an entity or without ranges there is nothing to check, so the source is not asked at all.
     *
     * @return void
     */
    public function test_source_is_not_asked_without_entity_or_ranges(): void {
        $source = new class implements occupation_source {
            /**
             * Must not be called.
             *
             * @param int $entityid
             * @return array
             */
            public function get_occupations(int $entityid): array {
                throw new \coding_exception('The source must not be asked.');
            }
        };
        $detector = new conflict_detector($source);

        $this->assertSame([], $detector->find(0, [$this->range(8, 10)]));
        $this->assertSame([], $detector->find(5, []));
    }

    /**
     * A person that is not assigned anywhere has no conflicts, and nothing breaks without an entity.
     *
     * @return void
     */
    public function test_person_without_occupations_has_no_conflicts(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $entity = persons::get_or_create_for_user($this->getDataGenerator()->create_user()->id);

        $this->assertSame([], unavailability::get_conflicts($entity, [$this->range(8, 10)]));
        $this->assertSame([], unavailability::get_conflicts(0, [$this->range(8, 10)]));
        $this->assertSame([], unavailability::get_conflicts($entity, []));
    }
}
