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
 * Tests for the list of entities whose unavailability a user may manage.
 *
 * @package    local_entities
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_entities\unavailability\manageable_entities
 */
final class manageable_entities_test extends \advanced_testcase {
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
     * Creates two locations and two persons, and returns the persons' users.
     *
     * @return \stdClass[] anna, zora
     */
    private function create_entities(): array {
        $this->setAdminUser();
        /** @var \local_entities_generator $gen */
        $gen = $this->getDataGenerator()->get_plugin_generator('local_entities');
        $gen->create_entities(['name' => 'Room B', 'shortname' => 'rb', 'entitytype' => 'location']);
        $gen->create_entities(['name' => 'Room A', 'shortname' => 'ra', 'entitytype' => 'location']);
        $zora = $this->getDataGenerator()->create_user(['firstname' => 'Zora', 'lastname' => 'Zeller']);
        $anna = $this->getDataGenerator()->create_user(['firstname' => 'Anna', 'lastname' => 'Adams']);
        persons::get_or_create_for_user((int)$zora->id);
        persons::get_or_create_for_user((int)$anna->id);
        return [$anna, $zora];
    }

    /**
     * Names of all entities in a result, group by group.
     *
     * @param array $grouped
     * @return array label => names
     */
    private function names(array $grouped): array {
        return array_map(static fn($options) => array_values($options), $grouped);
    }

    /**
     * Someone who may manage everything gets the entities grouped by type, locations before persons,
     * each group ordered by name. Types without entities do not appear.
     *
     * @return void
     */
    public function test_admin_gets_all_entities_grouped_by_type(): void {
        $this->resetAfterTest();
        $this->create_entities();
        $this->setAdminUser();

        $grouped = (new manageable_entities())->find();

        $this->assertSame([
            get_string('entitytype_location', 'local_entities') => ['Room A', 'Room B'],
            get_string('entitytype_person', 'local_entities') => ['Anna Adams', 'Zora Zeller'],
        ], $this->names($grouped));
    }

    /**
     * The right to edit entities gives the places but not the persons.
     *
     * @return void
     */
    public function test_editor_gets_places_but_no_persons(): void {
        $this->resetAfterTest();
        $this->create_entities();
        $this->setUser($this->user_with(['local/entities:edit']));

        $grouped = (new manageable_entities())->find();

        $this->assertSame([get_string('entitytype_location', 'local_entities') => ['Room A', 'Room B']], $this->names($grouped));
    }

    /**
     * The right for all entities gives the places and the persons.
     *
     * @return void
     */
    public function test_secretary_gets_places_and_persons(): void {
        $this->resetAfterTest();
        $this->create_entities();
        $this->setUser($this->user_with(['local/entities:manageunavailability']));

        $grouped = (new manageable_entities())->find();

        $this->assertSame([
            get_string('entitytype_location', 'local_entities') => ['Room A', 'Room B'],
            get_string('entitytype_person', 'local_entities') => ['Anna Adams', 'Zora Zeller'],
        ], $this->names($grouped));
    }

    /**
     * A person gets only the own person, and someone who is no person gets nothing.
     *
     * @return void
     */
    public function test_a_person_gets_only_the_own_person(): void {
        $this->resetAfterTest();
        [$anna] = $this->create_entities();
        $nobody = $this->getDataGenerator()->create_user();

        $this->setUser($nobody);
        $this->assertSame([], (new manageable_entities())->find());

        $this->setUser($anna);
        $grouped = (new manageable_entities())->find();

        $this->assertSame([fullname($anna)], array_values(reset($grouped)));
        $this->assertCount(1, $grouped);
    }

    /**
     * Persons of deleted users are left out, and so is someone without any right.
     *
     * @return void
     */
    public function test_deleted_users_and_users_without_rights_get_nothing(): void {
        global $DB;
        $this->resetAfterTest();
        [$anna] = $this->create_entities();
        $DB->set_field('user', 'deleted', 1, ['id' => $anna->id]);

        $this->setAdminUser();
        $grouped = (new manageable_entities())->find();
        $this->assertSame(['Zora Zeller'], array_values($grouped[get_string('entitytype_person', 'local_entities')]));

        $this->setUser($this->getDataGenerator()->create_user());
        $this->assertSame([], (new manageable_entities())->find());
    }
}
