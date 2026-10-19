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
 * Tests for the rule who may see a person as an entity.
 *
 * @package    local_entities
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_entities\person_visibility
 */
final class person_visibility_test extends \advanced_testcase {
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
     * Creates a place and returns its record.
     *
     * @return \stdClass
     */
    private function create_place(): \stdClass {
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
     * Places and equipment are visible to everyone, also to guests and users that are not logged in.
     *
     * @return void
     */
    public function test_places_are_visible_to_everyone(): void {
        $this->resetAfterTest();
        $room = $this->create_place();
        $nobody = $this->getDataGenerator()->create_user();

        $this->assertTrue(person_visibility::can_see($room, (int)$nobody->id));
        $this->setGuestUser();
        $this->assertTrue(person_visibility::can_see($room));
        $this->setUser(0);
        $this->assertTrue(person_visibility::can_see($room));
    }

    /**
     * A person sees the own person, and no other person.
     *
     * @return void
     */
    public function test_a_person_sees_only_the_own_person(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $own = $this->create_person($user);
        $foreign = $this->create_person($other);
        $this->setUser($user);

        $this->assertTrue(person_visibility::can_see($own));
        $this->assertFalse(person_visibility::can_see($foreign));
    }

    /**
     * The right to manage the unavailability of everyone opens all persons. The right to edit entities
     * does not, as in the rules for the unavailability itself.
     *
     * @return void
     */
    public function test_only_the_right_for_all_opens_other_persons(): void {
        $this->resetAfterTest();
        $secretary = $this->user_with(['local/entities:manageunavailability']);
        $editor = $this->user_with(['local/entities:edit', 'local/entities:view']);
        $person = $this->create_person($this->getDataGenerator()->create_user());

        $this->assertTrue(person_visibility::can_see($person, (int)$secretary->id));
        $this->assertFalse(person_visibility::can_see($person, (int)$editor->id));
    }

    /**
     * Guests and users that are not logged in never see a person, whatever the roles say.
     *
     * @return void
     */
    public function test_guest_and_anonymous_never_see_a_person(): void {
        $this->resetAfterTest();
        $person = $this->create_person($this->getDataGenerator()->create_user());

        $this->setGuestUser();
        $this->assertFalse(person_visibility::can_see($person));
        $this->setUser(0);
        $this->assertFalse(person_visibility::can_see($person));
    }

    /**
     * Asking with the id: a missing entity can not be seen, the others follow the rule.
     *
     * @return void
     */
    public function test_can_see_id(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $own = $this->create_person($user);
        $foreign = $this->create_person($other);
        $room = $this->create_place();
        $this->setUser($user);

        $this->assertTrue(person_visibility::can_see_id((int)$own->id));
        $this->assertTrue(person_visibility::can_see_id((int)$room->id));
        $this->assertFalse(person_visibility::can_see_id((int)$foreign->id));
        $this->assertFalse(person_visibility::can_see_id(999999));
    }

    /**
     * The sql condition leaves out exactly the persons the user may not see.
     *
     * @return void
     */
    public function test_sql_condition_filters_a_query(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $secretary = $this->user_with(['local/entities:manageunavailability']);
        $own = $this->create_person($user);
        $foreign = $this->create_person($other);
        $room = $this->create_place();

        $ids = function (int $userid) use ($DB): array {
            [$sql, $params] = person_visibility::sql_condition('e', $userid);
            $found = array_keys($DB->get_records_sql("SELECT e.id FROM {local_entities} e WHERE {$sql} ORDER BY e.id", $params));
            return array_map('intval', $found);
        };

        $visible = $ids((int)$user->id);
        $this->assertContains((int)$room->id, $visible);
        $this->assertContains((int)$own->id, $visible);
        $this->assertNotContains((int)$foreign->id, $visible);

        $all = $ids((int)$secretary->id);
        $this->assertContains((int)$own->id, $all);
        $this->assertContains((int)$foreign->id, $all);
        $this->assertContains((int)$room->id, $all);
    }

    /**
     * Without a user only the entities that are no persons are found, and the condition can be used
     * twice in one query with different prefixes.
     *
     * @return void
     */
    public function test_sql_condition_for_guests_and_twice_in_a_query(): void {
        global $DB;
        $this->resetAfterTest();
        $person = $this->create_person($this->getDataGenerator()->create_user());
        $room = $this->create_place();
        $this->setGuestUser();

        [$first, $firstparams] = person_visibility::sql_condition('a');
        [$second, $secondparams] = person_visibility::sql_condition('b', null, 'pw');
        $sql = "SELECT a.id FROM {local_entities} a JOIN {local_entities} b ON b.id = a.id WHERE {$first} AND {$second}";
        $found = array_map('intval', array_keys($DB->get_records_sql($sql, $firstparams + $secondparams)));

        $this->assertContains((int)$room->id, $found);
        $this->assertNotContains((int)$person->id, $found);
    }
}
