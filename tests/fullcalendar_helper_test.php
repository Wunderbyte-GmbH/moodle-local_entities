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

use advanced_testcase;
use local_entities\calendar\fullcalendar_helper;

/**
 * Tests for the fullcalendar select-option helpers.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_entities\calendar\fullcalendar_helper
 */
final class fullcalendar_helper_test extends advanced_testcase {
    /**
     * Weekdays are keyed 1..7 (Mon..Sun).
     */
    public function test_get_weekdays(): void {
        $days = fullcalendar_helper::get_weekdays();
        $this->assertCount(7, $days);
        $this->assertArrayHasKey(1, $days);
        $this->assertArrayHasKey(7, $days);
    }

    /**
     * Hours are 00..23 (zero-padded), keyed 0..23.
     */
    public function test_get_hours_select(): void {
        $hours = fullcalendar_helper::get_hours_select();
        $this->assertCount(24, $hours);
        $this->assertSame('00', $hours[0]);
        $this->assertSame('23', $hours[23]);
    }

    /**
     * Minutes are 00..59, keyed 0..59.
     */
    public function test_get_minutes_select(): void {
        $minutes = fullcalendar_helper::get_minutes_select();
        $this->assertCount(60, $minutes);
        $this->assertArrayHasKey(0, $minutes);
        $this->assertArrayHasKey(59, $minutes);
        $this->assertStringContainsString('00', $minutes[0]);
        $this->assertStringContainsString('59', $minutes[59]);
    }
}
