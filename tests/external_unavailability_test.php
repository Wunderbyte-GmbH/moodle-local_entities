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

use core_external\external_api;
use local_entities\external\get_unavailability;
use local_entities\external\save_unavailability;

/**
 * Tests for the web services that load and save the unavailability of an entity.
 *
 * @package    local_entities
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_entities\external\get_unavailability
 * @covers     \local_entities\external\save_unavailability
 */
final class external_unavailability_test extends \advanced_testcase {
    /** @var int Fixed start of the test times, in the far future. */
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
     * A person saves a block and a series, and gets both back when loading.
     *
     * @return void
     */
    public function test_save_and_load_round_trip(): void {
        $this->resetAfterTest();
        [$user, $entity] = $this->person();
        $this->setUser($user);

        $saved = save_unavailability::execute($entity, [
            'addblocks' => [['starttime' => $this->t(8), 'endtime' => $this->t(10), 'reason' => 'Exam']],
            'createseries' => [[
                'weekdays' => [1],
                'allday' => 0,
                'daystart' => 8 * HOURSECS,
                'dayend' => 10 * HOURSECS,
                'validfrom' => $this->t(24 * 7),
                'validuntil' => $this->t(24 * 28),
                'timezone' => 'UTC',
                'reason' => 'Lecture',
            ]],
        ]);
        $saved = external_api::clean_returnvalue(save_unavailability::execute_returns(), $saved);

        $this->assertTrue($saved['saved']);
        $this->assertSame([], $saved['conflicts']);

        $loaded = get_unavailability::execute($entity, $this->t(0), $this->t(24 * 40));
        $loaded = external_api::clean_returnvalue(get_unavailability::execute_returns(), $loaded);

        $this->assertContains('Exam', array_column($loaded['blocks'], 'reason'));
        $this->assertContains('Lecture', array_column($loaded['blocks'], 'reason'));
        $this->assertCount(1, $loaded['series']);
        $this->assertSame([1], $loaded['series'][0]['weekdays']);
        $this->assertSame('Lecture', $loaded['series'][0]['reason']);
    }

    /**
     * Without the right to manage the entity neither function works.
     *
     * @return void
     */
    public function test_without_the_right_both_functions_refuse(): void {
        $this->resetAfterTest();
        [, $entity] = $this->person();
        $this->setUser($this->getDataGenerator()->create_user());

        try {
            get_unavailability::execute($entity, $this->t(0), $this->t(24));
            $this->fail('Loading should have been refused.');
        } catch (\required_capability_exception $e) {
            $this->assertInstanceOf(\required_capability_exception::class, $e);
        }

        $this->expectException(\required_capability_exception::class);
        save_unavailability::execute($entity, [
            'addblocks' => [['starttime' => $this->t(8), 'endtime' => $this->t(10)]],
        ]);
    }

    /**
     * An invalid change is refused as a parameter error.
     *
     * @return void
     */
    public function test_invalid_changes_are_refused(): void {
        $this->resetAfterTest();
        [$user, $entity] = $this->person();
        $this->setUser($user);

        $this->expectException(\invalid_parameter_exception::class);
        save_unavailability::execute($entity, [
            'addblocks' => [['starttime' => $this->t(10), 'endtime' => $this->t(8)]],
        ]);
    }
}
