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
use local_entities\calendar\reoccuringevent;
use local_entities\local\entities\entitydate;

/**
 * Tests for the recurring-event / opening-hours calendar logic.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_entities\calendar\reoccuringevent
 */
final class reoccuringevent_test extends advanced_testcase {
    /**
     * Pin the server timezone so weekday (date('N')) and the Vienna hour comparison agree.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        global $CFG;
        $CFG->timezone = 'Europe/Vienna';
        $CFG->forcetimezone = 'Europe/Vienna';
        \core_date::set_default_server_timezone();
    }

    /**
     * Build a Europe/Vienna timestamp.
     *
     * @param string $datetime e.g. '2024-01-08 10:00:00'
     * @return int
     */
    private function vienna_ts(string $datetime): int {
        return (new \DateTime($datetime, new \DateTimeZone('Europe/Vienna')))->getTimestamp();
    }

    /**
     * Constructor maps fields and toarray() emits fullcalendar.js keys + extra properties.
     */
    public function test_construct_and_toarray(): void {
        $event = new reoccuringevent([
            'title' => 'Open',
            'daysofweek' => '1,2',
            'starttime' => '09:00',
            'endtime' => '17:00',
            'colour' => 'green',
        ]);
        $arr = $event->toarray();
        $this->assertSame('Open', $arr['title']);
        $this->assertSame('1,2', $arr['daysOfWeek']);
        $this->assertSame('09:00', $arr['startTime']);
        $this->assertSame('17:00', $arr['endTime']);
        $this->assertSame('green', $arr['colour'], 'Misc properties are preserved.');
    }

    /**
     * events_to_json and json_to_events round-trip the recurring events.
     */
    public function test_json_round_trip(): void {
        $events = [
            new reoccuringevent(['title' => 'A', 'daysofweek' => '1', 'starttime' => '09:00', 'endtime' => '12:00']),
            new reoccuringevent(['title' => 'B', 'daysofweek' => '3,5', 'starttime' => '13:00', 'endtime' => '18:00']),
        ];
        $json = reoccuringevent::events_to_json($events);
        $this->assertJson($json);

        $back = reoccuringevent::json_to_events($json);
        $this->assertCount(2, $back);
        $this->assertSame('1', $back[0]->daysofweek);
        $this->assertSame('09:00', $back[0]->starttime);
        $this->assertSame('18:00', $back[1]->endtime);
    }

    /**
     * json_to_form splits the stored events into the dynamic-form field arrays.
     */
    public function test_json_to_form(): void {
        $json = reoccuringevent::events_to_json([
            new reoccuringevent(['title' => 'A', 'daysofweek' => '1,2', 'starttime' => '09:30', 'endtime' => '17:45']),
        ]);
        $form = reoccuringevent::json_to_form($json);
        $this->assertSame(1, $form->count);
        $this->assertSame(['1', '2'], $form->daysofweek[0]);
        $this->assertSame('09', $form->starthours[0]);
        $this->assertSame('30', $form->startminutes[0]);
        $this->assertSame('17', $form->endhours[0]);
        $this->assertSame('45', $form->endminutes[0]);
    }

    /**
     * date_within_openinghours: a Monday 10–12 falls inside Monday 09–17; outside / other weekday / empty do not.
     */
    public function test_date_within_openinghours(): void {
        $monday = [new reoccuringevent(
            ['title' => 'Open', 'daysofweek' => '1', 'starttime' => '09:00', 'endtime' => '17:00']
        )];

        $within = new entitydate(0, 'mod_booking', 'option', 'x',
            $this->vienna_ts('2024-01-08 10:00:00'), $this->vienna_ts('2024-01-08 12:00:00'), 0);
        $this->assertTrue(reoccuringevent::date_within_openinghours($monday, $within));

        $beforeopen = new entitydate(0, 'mod_booking', 'option', 'x',
            $this->vienna_ts('2024-01-08 08:00:00'), $this->vienna_ts('2024-01-08 10:00:00'), 0);
        $this->assertFalse(reoccuringevent::date_within_openinghours($monday, $beforeopen));

        $tuesday = new entitydate(0, 'mod_booking', 'option', 'x',
            $this->vienna_ts('2024-01-09 10:00:00'), $this->vienna_ts('2024-01-09 12:00:00'), 0);
        $this->assertFalse(reoccuringevent::date_within_openinghours($monday, $tuesday));

        $notimes = new entitydate(0, 'mod_booking', 'option', 'x', 0, 0, 0);
        $this->assertFalse(reoccuringevent::date_within_openinghours($monday, $notimes));
    }
}
