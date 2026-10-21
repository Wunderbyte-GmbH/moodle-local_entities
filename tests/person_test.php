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

use local_entities_generator;
use core_external\external_api;
use local_entities\external\search_entities;
use local_entities\form\edit_dynamic_form;
use local_entities\table\entities_table;

/**
 * Tests for entities of type "person": a Moodle user represented as an entity (e.g. an examiner).
 *
 * @package    local_entities
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_entities\entities
 * @covers     \local_entities\persons
 * @covers     \local_entities\form\edit_dynamic_form
 * @covers     \local_entities\table\entities_table
 */
final class person_test extends \advanced_testcase {
    /**
     * Creates a person entity for a user, the same way the edit form does it.
     *
     * @param \stdClass $user
     * @return int entity id
     */
    private function create_person(\stdClass $user): int {
        /** @var local_entities_generator $gen */
        $gen = $this->getDataGenerator()->get_plugin_generator('local_entities');
        return $gen->create_entities(['entitytype' => 'person', 'userid' => $user->id]);
    }

    /**
     * Creates a location entity.
     *
     * @param string $name
     * @param string $shortname
     * @return int entity id
     */
    private function create_location(string $name, string $shortname): int {
        /** @var local_entities_generator $gen */
        $gen = $this->getDataGenerator()->get_plugin_generator('local_entities');
        return $gen->create_entities(['name' => $name, 'shortname' => $shortname, 'entitytype' => 'location']);
    }

    /**
     * A person is saved with its user, without a name of its own, and with the defaults of a single
     * bookable resource: one concurrent reservation, no parent.
     *
     * @return void
     */
    public function test_person_is_saved_with_user_and_defaults(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $user = $this->getDataGenerator()->create_user(['firstname' => 'Billy', 'lastname' => 'Teachy']);
        $id = $this->create_person($user);
        $this->assertGreaterThan(0, $id);

        $record = $DB->get_record('local_entities', ['id' => $id], '*', MUST_EXIST);
        $this->assertSame('person', $record->entitytype);
        $this->assertEquals($user->id, $record->userid);
        $this->assertSame('', (string)$record->name);
        $this->assertSame('exclusive', $record->allocationmode);
        $this->assertEquals(1, $record->maxconcurrent);
        $this->assertEquals(0, $record->parentid);
    }

    /**
     * The display name of a person is the name of its user; other types keep their own name. A
     * deleted user results in an empty name instead of an error.
     *
     * @return void
     */
    public function test_display_name_comes_from_the_user(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $user = $this->getDataGenerator()->create_user(['firstname' => 'Billy', 'lastname' => 'Teachy']);
        $personid = $this->create_person($user);
        $roomid = $this->create_location('Room A', 'ra');

        $person = $DB->get_record('local_entities', ['id' => $personid], '*', MUST_EXIST);
        $room = $DB->get_record('local_entities', ['id' => $roomid], '*', MUST_EXIST);

        $this->assertSame(fullname($user), entities::get_display_name($person));
        $this->assertSame('Room A', entities::get_display_name($room));

        $DB->set_field('user', 'deleted', 1, ['id' => $user->id]);
        $this->assertSame('', entities::get_display_name($person));
    }

    /**
     * Regression: the clean-up that runs on every entity update deletes entities with an empty name.
     * A person has no name of its own and must survive it, nameless locations must still go.
     *
     * @return void
     */
    public function test_cleanup_keeps_persons_but_removes_nameless_locations(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $user = $this->getDataGenerator()->create_user();
        $personid = $this->create_person($user);
        $namelessid = $DB->insert_record('local_entities', (object)[
            'name' => '',
            'shortname' => 'nameless',
            'entitytype' => 'location',
            'parentid' => 0,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        entities::clean_up_entities_db();

        $this->assertTrue($DB->record_exists('local_entities', ['id' => $personid]));
        $this->assertFalse($DB->record_exists('local_entities', ['id' => $namelessid]));
    }

    /**
     * Regression: saving any other entity must not delete persons (the update runs the clean-up).
     *
     * @return void
     */
    public function test_updating_a_location_keeps_persons(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $user = $this->getDataGenerator()->create_user();
        $personid = $this->create_person($user);
        $roomid = $this->create_location('Room A', 'ra');

        $manager = new settings_manager();
        $manager->update_or_createentity((object)[
            'id' => $roomid,
            'name' => 'Room A renamed',
            'shortname' => 'ra',
            'entitytype' => 'location',
            'parentid' => 0,
        ]);

        $this->assertSame('Room A renamed', $DB->get_field('local_entities', 'name', ['id' => $roomid]));
        $this->assertTrue($DB->record_exists('local_entities', ['id' => $personid]));
    }

    /**
     * Persons are no places: they are not offered by the room selector, the webservice list or the
     * root menu. Only the admin list asks for them explicitly.
     *
     * @return void
     */
    public function test_person_is_not_offered_as_a_room(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $user = $this->getDataGenerator()->create_user();
        $personid = $this->create_person($user);
        $roomid = $this->create_location('Room A', 'ra');

        $result = external_api::clean_returnvalue(search_entities::execute_returns(), search_entities::execute(''));
        $ids = array_map('intval', array_column($result['list'], 'id'));
        $this->assertContains($roomid, $ids);
        $this->assertNotContains($personid, $ids);

        $this->assertArrayHasKey($roomid, entities::list_all_parent_entities());
        $this->assertArrayNotHasKey($personid, entities::list_all_parent_entities());
        $this->assertArrayHasKey($personid, entities::list_all_parent_entities(true));
        $this->assertArrayNotHasKey($personid, entities::list_all_parent_entities_select());
    }

    /**
     * The form validation needs an existing user, allows each user only once as a person, lets a
     * person keep its own user when edited, and never accepts a person as parent of a location.
     *
     * @return void
     */
    public function test_validation_of_person_and_parent(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $personid = $this->create_person($user);

        $form = new edit_dynamic_form(
            null,
            null,
            'post',
            '',
            [],
            true,
            edit_dynamic_form::mock_ajax_submit(['entitytype' => 'person']),
            true
        );

        $errors = $form->validation(['entitytype' => 'person', 'userid' => 0, 'id' => 0], []);
        $this->assertArrayHasKey('userid', $errors, 'A person needs a user.');

        $errors = $form->validation(['entitytype' => 'person', 'userid' => $user->id, 'id' => 0], []);
        $this->assertArrayHasKey('userid', $errors, 'A user can be a person only once.');

        $errors = $form->validation(['entitytype' => 'person', 'userid' => $user->id, 'id' => $personid], []);
        $this->assertArrayNotHasKey('userid', $errors, 'Editing a person keeps its own user.');

        $errors = $form->validation(['entitytype' => 'person', 'userid' => $other->id, 'id' => 0], []);
        $this->assertArrayNotHasKey('userid', $errors, 'Another user is fine.');

        $errors = $form->validation(['entitytype' => 'location', 'parentid' => $personid, 'id' => 0], []);
        $this->assertArrayHasKey('parentid', $errors, 'A person can not be the parent of a location.');
    }

    /**
     * In the admin table persons form one block after the location tree, ordered by last and first
     * name, and are shown with the person badge, the user's name and (for editors) username and email.
     *
     * @return void
     */
    public function test_table_orders_and_renders_persons(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $zora = $this->getDataGenerator()->create_user(['firstname' => 'Zora', 'lastname' => 'Zeller']);
        $anna = $this->getDataGenerator()->create_user(['firstname' => 'Anna', 'lastname' => 'Adams']);
        $this->create_person($zora);
        $this->create_person($anna);
        $this->create_location('Root', 'root');

        $input = $DB->get_records_sql(
            "SELECT e.id, e.name, e.parentid, e.entitytype, e.userid, u.firstname, u.lastname, u.username, u.email
               FROM {local_entities} e
          LEFT JOIN {user} u ON u.id = e.userid"
        );
        $table = new entities_table('test_entities_table_persons');
        $rows = $table->arrange_as_tree($input);

        $types = array_values(array_map(static fn($r) => $r->entitytype, $rows));
        $this->assertSame(['location', 'person', 'person'], $types, 'Persons come after the location tree.');

        $persons = array_values(array_filter($rows, static fn($r) => $r->entitytype === 'person'));
        $this->assertSame(['Adams', 'Zeller'], array_map(static fn($r) => $r->lastname, $persons));

        $badge = $table->col_entitytype($persons[0]);
        $this->assertStringContainsString('text-bg-success', $badge);
        $this->assertStringContainsString(get_string('entitytype_person', 'local_entities'), $badge);

        $name = $table->col_name($persons[0]);
        $this->assertStringContainsString('Anna Adams', $name);
        $this->assertStringContainsString($anna->username, $name);
        $this->assertStringContainsString($anna->email, $name);

        // Without the right to edit entities the personal contact data stays hidden.
        $this->setUser($this->getDataGenerator()->create_user());
        $name = $table->col_name($persons[0]);
        $this->assertStringContainsString('Anna Adams', $name);
        $this->assertStringNotContainsString($anna->email, $name);
    }

    /**
     * The persons of many users come back keyed by user in one go. Users who are no person are not created
     * unless asked for, users who do not exist or are deleted are left out.
     *
     * @return void
     */
    public function test_entityids_for_users_in_one_go(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $known = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $gone = $this->getDataGenerator()->create_user();
        $knownentity = $this->create_person($known);
        $this->create_person($gone);
        delete_user($gone);
        $ask = [(int)$other->id, (int)$known->id, 0, 999999, (int)$gone->id, (int)$known->id];

        $result = persons::get_entityids_for_users($ask);

        $this->assertSame([(int)$known->id => $knownentity], $result);
        $this->assertNull(persons::get_entityid_for_user((int)$other->id), 'Nothing is created by default.');
        $this->assertSame([], persons::get_entityids_for_users([]));
    }

    /**
     * Only when asked for, the missing person is created, once.
     *
     * @return void
     */
    public function test_entityids_for_users_can_create_missing_persons(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $known = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $knownentity = $this->create_person($known);

        $result = persons::get_entityids_for_users([(int)$other->id, (int)$known->id, 999999], true);

        $this->assertSame([(int)$other->id, (int)$known->id], array_keys($result));
        $this->assertSame($knownentity, $result[(int)$known->id]);
        $this->assertSame(persons::get_entityid_for_user((int)$other->id), $result[(int)$other->id]);
        $this->assertSame($result, persons::get_entityids_for_users([(int)$other->id, (int)$known->id]));
    }
}
