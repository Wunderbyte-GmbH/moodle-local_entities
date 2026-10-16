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

use core_user\hook\extend_user_menu;

/**
 * Tests for the callbacks of the plugin on hooks of Moodle.
 *
 * @package    local_entities
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_entities\hook_callbacks
 */
final class hook_callbacks_test extends \advanced_testcase {
    /**
     * Runs the callback for the user menu and returns the items it added.
     *
     * @return array
     */
    private function menu_items(): array {
        $hook = new extend_user_menu();
        hook_callbacks::extend_user_menu($hook);
        return $hook->get_navitems();
    }

    /**
     * A person gets a link to the page in the user menu.
     *
     * @return void
     */
    public function test_person_gets_the_menu_link(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setAdminUser();
        persons::get_or_create_for_user((int)$user->id);
        $this->setUser($user);

        $items = $this->menu_items();

        $this->assertCount(1, $items);
        $this->assertSame('link', $items[0]->itemtype);
        $this->assertSame(get_string('unavailability', 'local_entities'), $items[0]->title);
        $this->assertSame('/local/entities/unavailability.php', $items[0]->url->out_as_local_url(false));
        $this->assertNotEmpty($items[0]->titleidentifier);
    }

    /**
     * Someone who may manage other entities gets the link too.
     *
     * @return void
     */
    public function test_manager_of_unavailability_gets_the_menu_link(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $roleid = $this->getDataGenerator()->create_role();
        $context = \context_system::instance();
        assign_capability('local/entities:manageunavailability', CAP_ALLOW, $roleid, $context->id, true);
        role_assign($roleid, $user->id, $context->id);
        $this->setUser($user);

        $this->assertCount(1, $this->menu_items());
    }

    /**
     * Someone who is no person and has no right, and a guest, get no link.
     *
     * @return void
     */
    public function test_nobody_else_gets_a_menu_link(): void {
        $this->resetAfterTest();

        $this->setUser($this->getDataGenerator()->create_user());
        $this->assertSame([], $this->menu_items());

        $this->setGuestUser();
        $this->assertSame([], $this->menu_items());
    }
}
