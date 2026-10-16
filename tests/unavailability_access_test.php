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
 * Tests for the rights to manage the unavailability of entities.
 *
 * @package    local_entities
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_entities\unavailability_access
 */
final class unavailability_access_test extends \advanced_testcase {
    /**
     * Creates a user that has a system role with exactly the given capabilities.
     *
     * @param string[] $capabilities
     * @return \stdClass
     */
    private function user_with(array $capabilities): \stdClass {
        $user = $this->getDataGenerator()->create_user();
        $roleid = $this->getDataGenerator()->create_role();
        $context = \context_system::instance();
        foreach ($capabilities as $capability) {
            assign_capability($capability, CAP_ALLOW, $roleid, $context->id, true);
        }
        role_assign($roleid, $user->id, $context->id);
        return $user;
    }

    /**
     * Creates a location and returns its record.
     *
     * @return \stdClass
     */
    private function create_location(): \stdClass {
        global $DB;
        $this->setAdminUser();
        /** @var \local_entities_generator $gen */
        $gen = $this->getDataGenerator()->get_plugin_generator('local_entities');
        $id = $gen->create_entities(['name' => 'Room A', 'shortname' => 'ra', 'entitytype' => 'location']);
        return $DB->get_record('local_entities', ['id' => $id], '*', MUST_EXIST);
    }

    /**
     * Creates the person entity of a user and returns its record.
     *
     * @param \stdClass $user
     * @return \stdClass
     */
    private function create_person(\stdClass $user): \stdClass {
        global $DB;
        $this->setAdminUser();
        $id = persons::get_or_create_for_user((int)$user->id);
        return $DB->get_record('local_entities', ['id' => $id], '*', MUST_EXIST);
    }

    /**
     * Without any right nothing is allowed, and a user who is no person has no person of their own.
     *
     * @return void
     */
    public function test_user_without_rights_can_do_nothing(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $room = $this->create_location();
        $otherperson = $this->create_person($other);
        $this->setUser($user);

        $this->assertFalse(unavailability_access::can_manage_any());
        $this->assertFalse(unavailability_access::can_manage_entity($room));
        $this->assertFalse(unavailability_access::can_manage_entity($otherperson));
    }

    /**
     * Being a person is enough to manage the own unavailability, and nothing else. No role is needed.
     *
     * @return void
     */
    public function test_a_person_may_manage_only_the_own_unavailability(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $own = $this->create_person($user);
        $foreign = $this->create_person($other);
        $room = $this->create_location();
        $this->setUser($user);

        $this->assertTrue(unavailability_access::can_manage_entity($own));
        $this->assertFalse(unavailability_access::can_manage_entity($foreign));
        $this->assertFalse(unavailability_access::can_manage_entity($room));
        $this->assertFalse(unavailability_access::can_manage_any());
    }

    /**
     * The right for all entities opens every person and every place.
     *
     * @return void
     */
    public function test_right_for_all_opens_persons_and_places(): void {
        $this->resetAfterTest();
        $secretary = $this->user_with(['local/entities:manageunavailability']);
        $other = $this->getDataGenerator()->create_user();
        $person = $this->create_person($other);
        $room = $this->create_location();
        $this->setUser($secretary);

        $this->assertTrue(unavailability_access::can_manage_any());
        $this->assertTrue(unavailability_access::can_manage_entity($person));
        $this->assertTrue(unavailability_access::can_manage_entity($room));
    }

    /**
     * The right to edit entities opens places and equipment, but not persons, as the unavailability of
     * a person is personal data.
     *
     * @return void
     */
    public function test_edit_right_opens_places_but_not_persons(): void {
        $this->resetAfterTest();
        $editor = $this->user_with(['local/entities:edit']);
        $other = $this->getDataGenerator()->create_user();
        $person = $this->create_person($other);
        $room = $this->create_location();
        $this->setUser($editor);

        $this->assertTrue(unavailability_access::can_manage_entity($room));
        $this->assertFalse(unavailability_access::can_manage_entity($person));
    }

    /**
     * The checks can be made for another user than the logged in one, e.g. for a menu or a task.
     *
     * @return void
     */
    public function test_checks_can_be_made_for_a_given_user(): void {
        $this->resetAfterTest();
        $examiner = $this->getDataGenerator()->create_user();
        $nobody = $this->getDataGenerator()->create_user();
        $own = $this->create_person($examiner);
        $this->setUser($nobody);

        $this->assertTrue(unavailability_access::can_manage_entity($own, (int)$examiner->id));
        $this->assertFalse(unavailability_access::can_manage_entity($own, (int)$nobody->id));
        $this->assertFalse(unavailability_access::can_manage_entity($own));
    }

    /**
     * Guests and users that are not logged in are never allowed, whatever the roles say.
     *
     * @return void
     */
    public function test_guest_and_anonymous_are_never_allowed(): void {
        $this->resetAfterTest();
        $room = $this->create_location();

        $this->setGuestUser();
        $this->assertFalse(unavailability_access::can_manage_any());
        $this->assertFalse(unavailability_access::can_manage_entity($room));

        $this->setUser(0);
        $this->assertFalse(unavailability_access::can_manage_any());
        $this->assertFalse(unavailability_access::can_manage_entity($room));
    }

    /**
     * The require method lets allowed users pass and throws for the others.
     *
     * @return void
     */
    public function test_require_throws_without_the_right(): void {
        $this->resetAfterTest();
        $examiner = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $own = $this->create_person($examiner);
        $foreign = $this->create_person($other);
        $this->setUser($examiner);

        unavailability_access::require_manage_entity($own);
        $this->assertTrue(true, 'The own person is allowed.');

        $this->expectException(\required_capability_exception::class);
        unavailability_access::require_manage_entity($foreign);
    }

    /**
     * The page is offered to persons and to everyone who may manage other entities, to nobody else.
     *
     * @return void
     */
    public function test_page_is_offered_only_to_those_who_can_use_it(): void {
        $this->resetAfterTest();
        $person = $this->getDataGenerator()->create_user();
        $secretary = $this->user_with(['local/entities:manageunavailability']);
        $editor = $this->user_with(['local/entities:edit']);
        $nobody = $this->getDataGenerator()->create_user();
        $this->create_person($person);

        $this->assertTrue(unavailability_access::can_open_page((int)$person->id));
        $this->assertTrue(unavailability_access::can_open_page((int)$secretary->id));
        $this->assertTrue(unavailability_access::can_open_page((int)$editor->id));
        $this->assertFalse(unavailability_access::can_open_page((int)$nobody->id));

        $this->setUser($person);
        $this->assertTrue(unavailability_access::can_open_page());
        $this->setGuestUser();
        $this->assertFalse(unavailability_access::can_open_page());
        $this->setUser(0);
        $this->assertFalse(unavailability_access::can_open_page());
    }
}
