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
 * Tests the confirmation of a person to be available the whole planning period.
 *
 * @package    local_entities
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_entities\always_free
 */
final class always_free_test extends \advanced_testcase {
    /**
     * Sets the planning period.
     *
     * @param string $start
     * @param string $end
     * @return void
     */
    private static function period(string $start, string $end): void {
        set_config(planning_period::CONFIG_START, $start, 'local_entities');
        set_config(planning_period::CONFIG_END, $end, 'local_entities');
    }

    /**
     * A person.
     *
     * @return int entity id
     */
    private static function person(): int {
        return persons::get_or_create_for_user((int)self::getDataGenerator()->create_user()->id);
    }

    /**
     * Confirming, taking back and confirming twice.
     *
     * @return void
     */
    public function test_confirm_and_take_back(): void {
        $this->resetAfterTest();
        self::period('2026-11-16', '2026-11-30');
        $anna = self::person();
        $ben = self::person();

        $this->assertFalse(always_free::is_confirmed($anna));
        always_free::set($anna, true, 2);
        always_free::set($anna, true, 2);
        $this->assertTrue(always_free::is_confirmed($anna));
        $this->assertSame([$anna], always_free::confirmed([$anna, $ben]));

        always_free::set($anna, false, 2);
        $this->assertFalse(always_free::is_confirmed($anna));
        $this->assertSame([], always_free::confirmed([$anna, $ben]));
    }

    /**
     * A confirmation belongs to its planning period: in a new period it does not count, back in the old one it does.
     *
     * @return void
     */
    public function test_new_period_needs_a_new_confirmation(): void {
        $this->resetAfterTest();
        self::period('2026-11-16', '2026-11-30');
        $anna = self::person();
        always_free::set($anna, true, 2);

        self::period('2026-12-01', '2026-12-18');
        $this->assertFalse(always_free::is_confirmed($anna));

        self::period('2026-11-16', '2026-11-30');
        $this->assertTrue(always_free::is_confirmed($anna));
    }

    /**
     * Without a planning period nothing is confirmed and nothing can be confirmed.
     *
     * @return void
     */
    public function test_no_period(): void {
        $this->resetAfterTest();
        self::period('', '');
        $anna = self::person();

        $this->assertSame([], always_free::confirmed([$anna]));
        $this->expectException(\moodle_exception::class);
        always_free::set($anna, true, 2);
    }
}
