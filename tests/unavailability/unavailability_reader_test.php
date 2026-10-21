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

use local_entities\persons;

/**
 * Tests for reading the unavailability of an entity for the page.
 *
 * @package    local_entities
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_entities\unavailability\unavailability_reader
 */
final class unavailability_reader_test extends \advanced_testcase {
    /** @var int Fixed start of the test times. */
    private const BASE = 1900000000;

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
     * Creates a person for a new user and returns [user, entity id].
     *
     * @return array
     */
    private function person(): array {
        $user = $this->getDataGenerator()->create_user();
        $this->setAdminUser();
        return [$user, persons::get_or_create_for_user((int)$user->id)];
    }

    /**
     * The blocks and series in the window are returned with everything the calendar needs, and nothing
     * outside of the window. Blocks and series of a plugin scope are not shown.
     *
     * @return void
     */
    public function test_reads_blocks_and_series_of_the_window(): void {
        $this->resetAfterTest();
        [$user, $entity] = $this->person();
        $blocks = new block_repository();
        $blocks->add($entity, scope::everywhere(), [
            new time_range($this->t(8), $this->t(10), 'Exam'),
            new time_range($this->t(24 * 100), $this->t(24 * 100 + 2)),
        ]);
        $blocks->add($entity, scope::of('mod_booking', 'option', 7), [new time_range($this->t(8), $this->t(10))]);
        $rule = series_rule::from_array([
            'weekdays' => [1, 3], 'allday' => false, 'daystart' => 8 * HOURSECS, 'dayend' => 10 * HOURSECS,
            'validfrom' => $this->t(0), 'validuntil' => $this->t(24 * 20), 'timezone' => 'UTC', 'reason' => 'Lecture',
        ]);
        $seriesid = (new series_repository())->create($entity, $rule, scope::everywhere());
        $this->setUser($user);

        $data = (new unavailability_reader())->read($entity, $this->t(0), $this->t(24 * 30));

        $single = array_values(array_filter($data['blocks'], static fn($b) => $b['seriesid'] === 0));
        $this->assertCount(1, $single, 'Only the single block of the window that applies everywhere.');
        $this->assertSame('Exam', $single[0]['reason']);
        $this->assertSame($this->t(8), $single[0]['starttime']);
        $this->assertSame($this->t(10), $single[0]['endtime']);

        $inseries = array_filter($data['blocks'], static fn($b) => $b['seriesid'] === $seriesid);
        $this->assertGreaterThan(0, count($inseries), 'The blocks of the series are listed with the id of the series.');

        $this->assertCount(1, $data['series']);
        $series = $data['series'][0];
        $this->assertSame($seriesid, $series['id']);
        $this->assertSame([1, 3], $series['weekdays']);
        $this->assertFalse($series['allday']);
        $this->assertSame(8 * HOURSECS, $series['daystart']);
        $this->assertSame(10 * HOURSECS, $series['dayend']);
        $this->assertSame('UTC', $series['timezone']);
        $this->assertSame('Lecture', $series['reason']);
    }

    /**
     * Without the right to manage the entity nothing is returned, an exception is thrown.
     *
     * @return void
     */
    public function test_without_the_right_it_throws(): void {
        $this->resetAfterTest();
        [, $entity] = $this->person();
        $this->setUser($this->getDataGenerator()->create_user());

        $this->expectException(\required_capability_exception::class);
        (new unavailability_reader())->read($entity, $this->t(0), $this->t(24));
    }

    /**
     * A person whose self-editing is locked can still read the own unavailability.
     *
     * @return void
     */
    public function test_a_locked_person_can_still_read(): void {
        $this->resetAfterTest();
        [$user, $entity] = $this->person();
        set_config('unavailabilitylock_person', 1, 'local_entities');
        $this->setUser($user);

        $result = (new unavailability_reader())->read($entity, $this->t(0), $this->t(24));

        $this->assertSame([], $result['blocks']);
    }

    /**
     * A window that is empty or longer than a year is refused, so one request can not read everything.
     *
     * @return void
     */
    public function test_window_must_be_reasonable(): void {
        $this->resetAfterTest();
        [$user, $entity] = $this->person();
        $this->setUser($user);
        $reader = new unavailability_reader();

        try {
            $reader->read($entity, $this->t(24), $this->t(24));
            $this->fail('An empty window must be refused.');
        } catch (\invalid_parameter_exception $e) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $e);
        }

        $this->expectException(\invalid_parameter_exception::class);
        $reader->read($entity, $this->t(0), $this->t(24 * 400));
    }
}
