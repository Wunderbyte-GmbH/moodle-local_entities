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
use local_entities\external\save_daily_limit;
use local_entities\output\unavailability_page;

/**
 * Tests for the highest number of slots per day of an entity.
 *
 * @package    local_entities
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_entities\daily_limit
 * @covers     \local_entities\external\save_daily_limit
 * @covers     \local_entities\output\unavailability_page
 */
final class daily_limit_test extends \advanced_testcase {
    /**
     * Creates the person of a new user and returns [user, entity id].
     *
     * @return array
     */
    private function person(): array {
        // Without a default, an entity without an own limit has no limit.
        set_config(daily_limit::CONFIG_DEFAULT, '', 'local_entities');
        $user = $this->getDataGenerator()->create_user();
        $this->setAdminUser();
        return [$user, persons::get_or_create_for_user((int)$user->id)];
    }

    /**
     * A new entity has no limit, a set limit is read back, and null removes it again.
     *
     * @return void
     */
    public function test_limit_is_stored_and_removed(): void {
        $this->resetAfterTest();
        [$user, $entityid] = $this->person();
        $this->setUser($user);

        $this->assertSame([$entityid => null], daily_limit::get([$entityid]));

        daily_limit::set($entityid, 3);
        $this->assertSame([$entityid => 3], daily_limit::get([$entityid]));

        daily_limit::set($entityid, null);
        $this->assertSame([$entityid => null], daily_limit::get([$entityid]));
    }

    /**
     * Several entities are read at once, unknown ones are left out.
     *
     * @return void
     */
    public function test_several_entities_at_once(): void {
        $this->resetAfterTest();
        [, $first] = $this->person();
        [, $second] = $this->person();
        daily_limit::set($first, 2);

        $limits = daily_limit::get([$first, $second, 999999, $first]);
        ksort($limits);

        $this->assertSame([$first => 2, $second => null], $limits);
        $this->assertSame([], daily_limit::get([]));
    }

    /**
     * Limits below 1 or above the highest value are refused.
     *
     * @return void
     */
    public function test_limit_out_of_range_is_refused(): void {
        $this->resetAfterTest();
        [, $entityid] = $this->person();

        foreach ([0, -1, daily_limit::MAX + 1] as $limit) {
            try {
                daily_limit::set($entityid, $limit);
                $this->fail("The limit $limit is refused.");
            } catch (\invalid_parameter_exception $e) {
                $this->assertSame([$entityid => null], daily_limit::get([$entityid]));
            }
        }
    }

    /**
     * Somebody else can not change the limit of a person.
     *
     * @return void
     */
    public function test_stranger_can_not_change_the_limit(): void {
        $this->resetAfterTest();
        [, $entityid] = $this->person();
        $this->setUser($this->getDataGenerator()->create_user());

        $this->expectException(\required_capability_exception::class);
        daily_limit::set($entityid, 3);
    }

    /**
     * The person changes the own limit also when the self-editing of persons is locked.
     *
     * @return void
     */
    public function test_lock_does_not_stop_the_limit(): void {
        $this->resetAfterTest();
        [$user, $entityid] = $this->person();
        set_config('unavailabilitylock_person', 1, 'local_entities');
        $this->setUser($user);

        daily_limit::set($entityid, 4);

        $this->assertSame([$entityid => 4], daily_limit::get([$entityid]));
    }

    /**
     * The web service stores the limit, 0 means no limit.
     *
     * @return void
     */
    public function test_web_service_translates_zero_to_no_limit(): void {
        $this->resetAfterTest();
        [$user, $entityid] = $this->person();
        $this->setUser($user);

        $result = external_api::clean_returnvalue(save_daily_limit::execute_returns(), save_daily_limit::execute($entityid, 5));
        $this->assertSame(['limit' => 5], $result);

        $result = external_api::clean_returnvalue(save_daily_limit::execute_returns(), save_daily_limit::execute($entityid, 0));
        $this->assertSame(['limit' => 0], $result);
        $this->assertSame([$entityid => null], daily_limit::get([$entityid]));
    }

    /**
     * The page hands the limit and the right to change it to the template, also when the page is locked.
     *
     * @return void
     */
    public function test_page_hands_the_limit_to_the_template(): void {
        global $PAGE;
        $this->resetAfterTest();
        [$user, $entityid] = $this->person();
        daily_limit::set($entityid, 2);
        set_config('unavailabilitylock_person', 1, 'local_entities');
        $this->setUser($user);
        $PAGE->set_url(new \moodle_url('/local/entities/unavailability.php'));

        $data = (new unavailability_page($entityid))->export_for_template($PAGE->get_renderer('core'));

        $this->assertTrue($data['readonly']);
        $this->assertSame(2, $data['dailylimit']['value']);
        $this->assertTrue($data['dailylimit']['canchange']);
        $this->assertSame(daily_limit::MAX, $data['dailylimit']['max']);
        $this->assertSame(get_string('dailylimit_none', 'local_entities'), $data['dailylimit']['placeholder']);
    }

    /**
     * Entities without an own limit get the default, an own limit wins, and the default can be switched off.
     *
     * @return void
     */
    public function test_default_applies_without_an_own_limit(): void {
        $this->resetAfterTest();
        [, $withdefault] = $this->person();
        [, $withown] = $this->person();
        daily_limit::set($withown, 5);
        set_config(daily_limit::CONFIG_DEFAULT, '2', 'local_entities');

        $limits = daily_limit::get([$withdefault, $withown]);
        $this->assertSame(2, $limits[$withdefault]);
        $this->assertSame(5, $limits[$withown]);
        $own = daily_limit::get_own([$withdefault, $withown]);
        $this->assertNull($own[$withdefault], 'The own limit stays empty.');

        foreach (['0', '', '-3', (string)(daily_limit::MAX + 1)] as $off) {
            set_config(daily_limit::CONFIG_DEFAULT, $off, 'local_entities');
            $this->assertNull(daily_limit::get_default(), "The default '$off' means no limit.");
        }
    }

    /**
     * The page shows the default as placeholder and leaves the field empty, and the web service returns the own limit.
     *
     * @return void
     */
    public function test_page_and_web_service_show_the_own_limit_and_the_default(): void {
        global $PAGE;
        $this->resetAfterTest();
        [$user, $entityid] = $this->person();
        set_config(daily_limit::CONFIG_DEFAULT, '2', 'local_entities');
        $this->setUser($user);
        $PAGE->set_url(new \moodle_url('/local/entities/unavailability.php'));

        $data = (new unavailability_page($entityid))->export_for_template($PAGE->get_renderer('core'));
        $this->assertNull($data['dailylimit']['value']);
        $this->assertSame(get_string('dailylimit_default_placeholder', 'local_entities', 2), $data['dailylimit']['placeholder']);

        $result = external_api::clean_returnvalue(save_daily_limit::execute_returns(), save_daily_limit::execute($entityid, 0));
        $this->assertSame(['limit' => 0], $result, 'An empty field stays empty, the default is not shown as own limit.');
    }
}
