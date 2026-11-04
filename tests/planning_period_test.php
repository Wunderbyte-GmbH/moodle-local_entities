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

use local_entities\admin\setting_date;
use local_entities\output\unavailability_page;
use local_entities\unavailability\unavailability_changes;
use local_entities\unavailability\unavailability_saver;

/**
 * Tests for the planning period: reading the setting, limiting persons and leaving planners free.
 *
 * @package    local_entities
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_entities\planning_period
 * @covers     \local_entities\admin\setting_date
 * @covers     \local_entities\unavailability\unavailability_saver
 * @covers     \local_entities\output\unavailability_page
 */
final class planning_period_test extends \advanced_testcase {
    /**
     * Midnight of a day in the time zone of the server.
     *
     * @param string $day as 2030-03-01
     * @return int
     */
    private function midnight(string $day): int {
        return (new \DateTimeImmutable($day, \core_date::get_server_timezone_object()))->getTimestamp();
    }

    /**
     * Sets the planning period to March 2030.
     *
     * @return void
     */
    private function set_march(): void {
        set_config(planning_period::CONFIG_START, '2030-03-01', 'local_entities');
        set_config(planning_period::CONFIG_END, '2030-03-31', 'local_entities');
    }

    /**
     * Creates the person of a new user, logs in as that user and returns the entity id.
     *
     * @return int
     */
    private function login_as_person(): int {
        $user = $this->getDataGenerator()->create_user();
        $this->setAdminUser();
        $entityid = persons::get_or_create_for_user((int)$user->id);
        $this->setUser($user);
        return $entityid;
    }

    /**
     * A block on a day, from 08:00 until 10:00.
     *
     * @param string $day
     * @return array
     */
    private function block(string $day): array {
        $start = $this->midnight($day) + 8 * HOURSECS;
        return ['starttime' => $start, 'endtime' => $start + 2 * HOURSECS];
    }

    /**
     * Without both days, with a day that does not exist or with the last day before the first, there is no period.
     *
     * @return void
     */
    public function test_no_period_unless_both_days_are_valid(): void {
        $this->resetAfterTest();
        $this->assertNull(planning_period::get());

        set_config(planning_period::CONFIG_START, '2030-03-01', 'local_entities');
        $this->assertNull(planning_period::get(), 'Only the first day is set.');

        set_config(planning_period::CONFIG_END, '2030-02-30', 'local_entities');
        $this->assertNull(planning_period::get(), 'The last day does not exist.');

        set_config(planning_period::CONFIG_END, '2030-02-28', 'local_entities');
        $this->assertNull(planning_period::get(), 'The last day is before the first.');
    }

    /**
     * The period runs from the start of the first day until the end of the last day, in the time zone of the server.
     *
     * @return void
     */
    public function test_period_covers_the_whole_last_day(): void {
        $this->resetAfterTest();
        $this->set_march();

        $period = planning_period::get();

        $this->assertSame($this->midnight('2030-03-01'), $period->starttime);
        $this->assertSame($this->midnight('2030-04-01'), $period->endtime);
        $this->assertTrue(planning_period::contains($period, $this->midnight('2030-03-31'), $period->endtime));
        $this->assertFalse(planning_period::contains($period, $period->starttime - 1, $period->starttime + HOURSECS));
        $this->assertFalse(planning_period::contains($period, $period->endtime - HOURSECS, $period->endtime + 1));
    }

    /**
     * A person is limited, an admin is not.
     *
     * @return void
     */
    public function test_planners_and_admins_are_not_limited(): void {
        $this->resetAfterTest();
        $this->set_march();

        $this->login_as_person();
        $this->assertNotNull(planning_period::for_user());

        $this->setAdminUser();
        $this->assertNull(planning_period::for_user());
    }

    /**
     * The setting stores real days and empty values, and refuses everything else.
     *
     * @return void
     */
    public function test_setting_accepts_only_real_days(): void {
        $this->resetAfterTest();
        $setting = new setting_date('local_entities/' . planning_period::CONFIG_START, 'Start', '', '');

        $this->assertSame('', $setting->write_setting('2030-03-01'));
        $this->assertSame('2030-03-01', get_config('local_entities', planning_period::CONFIG_START));

        $this->assertNotSame('', $setting->write_setting('2030-02-30'));
        $this->assertNotSame('', $setting->write_setting('1.3.2030'));
        $this->assertSame('2030-03-01', get_config('local_entities', planning_period::CONFIG_START), 'Nothing was overwritten.');

        $this->assertSame('', $setting->write_setting(''));
        $this->assertSame('', get_config('local_entities', planning_period::CONFIG_START));
    }

    /**
     * A person can save a block inside the period, but not outside.
     *
     * @return void
     */
    public function test_person_can_only_save_inside_the_period(): void {
        global $DB;
        $this->resetAfterTest();
        $this->set_march();
        $entityid = $this->login_as_person();
        $saver = new unavailability_saver();

        $result = $saver->save($entityid, unavailability_changes::from_array(['addblocks' => [$this->block('2030-03-10')]]));
        $this->assertTrue($result['saved']);

        try {
            $saver->save($entityid, unavailability_changes::from_array(['addblocks' => [$this->block('2030-04-01')]]));
            $this->fail('A block after the period is refused.');
        } catch (\moodle_exception $e) {
            $this->assertSame('planningperiod_outside', $e->errorcode);
        }
        $this->assertSame(1, $DB->count_records('local_entities_unavailability', ['entityid' => $entityid]));
    }

    /**
     * A series that goes on after the end of the period is refused as a whole.
     *
     * @return void
     */
    public function test_series_beyond_the_period_is_refused(): void {
        global $DB;
        $this->resetAfterTest();
        $this->set_march();
        $entityid = $this->login_as_person();
        $rule = [
            'weekdays' => [1, 2, 3, 4, 5],
            'allday' => true,
            'daystart' => 0,
            'dayend' => 0,
            'validfrom' => $this->midnight('2030-03-25'),
            'validuntil' => $this->midnight('2030-04-05'),
            'timezone' => \core_date::get_server_timezone(),
            'reason' => '',
        ];

        try {
            (new unavailability_saver())->save($entityid, unavailability_changes::from_array(['createseries' => [$rule]]));
            $this->fail('The series is refused.');
        } catch (\moodle_exception $e) {
            $this->assertSame('planningperiod_outside', $e->errorcode);
        }
        $this->assertSame(0, $DB->count_records('local_entities_unavail_series', ['entityid' => $entityid]));
    }

    /**
     * An admin saves outside the period.
     *
     * @return void
     */
    public function test_admin_saves_outside_the_period(): void {
        $this->resetAfterTest();
        $this->set_march();
        $user = $this->getDataGenerator()->create_user();
        $this->setAdminUser();
        $entityid = persons::get_or_create_for_user((int)$user->id);

        $result = (new unavailability_saver())->save(
            $entityid,
            unavailability_changes::from_array(['addblocks' => [$this->block('2030-05-02')]])
        );

        $this->assertTrue($result['saved']);
    }

    /**
     * The page hands the period to the calendar of a person, and none to an admin.
     *
     * @return void
     */
    public function test_page_hands_the_period_to_persons_only(): void {
        global $PAGE;
        $this->resetAfterTest();
        $this->set_march();
        $entityid = $this->login_as_person();
        $PAGE->set_url(new \moodle_url('/local/entities/unavailability.php'));

        $data = (new unavailability_page($entityid))->export_for_template($PAGE->get_renderer('core'));

        $this->assertSame($this->midnight('2030-03-01'), $data['planningperiod']['start']);
        $this->assertSame($this->midnight('2030-04-01'), $data['planningperiod']['end']);
        $this->assertStringContainsString(planning_period::describe(planning_period::get()), $data['planningperiod']['text']);

        $this->setAdminUser();
        $data = (new unavailability_page($entityid))->export_for_template($PAGE->get_renderer('core'));
        $this->assertNull($data['planningperiod']);
    }
}
