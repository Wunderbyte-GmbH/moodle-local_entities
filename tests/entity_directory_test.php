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
 * Tests that entities are described by type, name, icon and user, whatever their type is.
 *
 * @package    local_entities
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_entities\entity_directory
 */
final class entity_directory_test extends \advanced_testcase {
    /**
     * A person is shown with the name of its user, other types with their own name.
     *
     * @return void
     */
    public function test_describe_gives_name_icon_and_user_by_type(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        /** @var \local_entities_generator $gen */
        $gen = $this->getDataGenerator()->get_plugin_generator('local_entities');
        $user = $this->getDataGenerator()->create_user(['firstname' => 'Erika', 'lastname' => 'Muster']);
        $person = persons::get_or_create_for_user((int)$user->id);
        $car = (int)$gen->create_entities(['name' => 'Car 1', 'shortname' => 'c1', 'entitytype' => 'equipment']);
        $room = (int)$gen->create_entities(['name' => 'Room A', 'shortname' => 'ra', 'entitytype' => 'location']);

        $result = entity_directory::describe([$car, $person, $room, $car]);

        $this->assertEqualsCanonicalizing([$car, $person, $room], array_keys($result));
        $this->assertSame(
            ['id' => $person, 'type' => 'person', 'name' => fullname($user), 'icon' => 'fa-user', 'userid' => (int)$user->id],
            $result[$person]
        );
        $this->assertSame(
            ['id' => $car, 'type' => 'equipment', 'name' => 'Car 1', 'icon' => 'fa-cube', 'userid' => 0],
            $result[$car]
        );
        $this->assertSame('location', $result[$room]['type']);
        $this->assertSame('fa-map-marker', $result[$room]['icon']);
    }

    /**
     * Entities that do not exist and persons of deleted users are missing, so the caller can tell.
     *
     * @return void
     */
    public function test_missing_entities_and_deleted_users_are_left_out(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $kept = $this->getDataGenerator()->create_user();
        $gone = $this->getDataGenerator()->create_user();
        $keptperson = persons::get_or_create_for_user((int)$kept->id);
        $goneperson = persons::get_or_create_for_user((int)$gone->id);
        // Only the flag is set, so the person still exists and the check can not rely on an observer.
        $DB->set_field('user', 'deleted', 1, ['id' => $gone->id]);

        $result = entity_directory::describe([$keptperson, $goneperson, 999999, 0, -3]);

        $this->assertSame([$keptperson], array_keys($result));
        $this->assertSame([], entity_directory::describe([]));
    }

    /**
     * A type that is not known gets the default icon.
     *
     * @return void
     */
    public function test_icon_of_an_unknown_type(): void {
        $this->assertSame('fa-user', entity_directory::icon('person'));
        $this->assertSame('fa-cube', entity_directory::icon('equipment'));
        $this->assertSame('fa-map-marker', entity_directory::icon('something'));
    }
}
