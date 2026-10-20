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

use local_entities\form\pool_dynamic_form;
use local_entities\pool\pool_access;
use local_entities\pool\pool_facade;
use local_entities\pool\pool_repository;
use local_entities\pool\pool_resolver;
use local_entities\pool\pool_service;
use local_entities\pool\pool_types;

/**
 * Tests for the pools of persons: storage, resolving to users, access and clean up.
 *
 * @package    local_entities
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_entities\pool\pool_repository
 * @covers     \local_entities\pool\pool_resolver
 * @covers     \local_entities\pool\pool_facade
 * @covers     \local_entities\pool\pool_access
 */
final class pool_test extends \advanced_testcase {
    /**
     * Creates a user and the person entity of that user.
     *
     * @return array [user record, entity id]
     */
    private function create_person(): array {
        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user();
        return [$user, persons::get_or_create_for_user((int)$user->id)];
    }

    /**
     * Creates a place, which is an entity but no person.
     *
     * @return int entity id
     */
    private function create_place(): int {
        $this->setAdminUser();
        /** @var \local_entities_generator $gen */
        $gen = $this->getDataGenerator()->get_plugin_generator('local_entities');
        return (int)$gen->create_entities(['name' => 'Room A', 'shortname' => 'ra', 'entitytype' => 'location']);
    }

    /**
     * A pool can be created, read, changed and listed with the number of its members.
     *
     * @return void
     */
    public function test_create_update_and_list(): void {
        $this->resetAfterTest();
        $repo = new pool_repository();
        [, $person] = $this->create_person();

        $id = $repo->create('  Examiners Greek ', 'Greek exams', 2);
        $pool = $repo->find($id);
        $this->assertSame('Examiners Greek', $pool->name);
        $this->assertSame('Greek exams', $pool->description);
        $this->assertEquals(2, $pool->usermodified);

        $repo->add_members($id, [$person], 2);
        $repo->update($id, 'Examiners Latin', 'Latin exams', 3);

        $all = $repo->find_all();
        $this->assertCount(1, $all);
        $this->assertSame('Examiners Latin', $all[$id]->name);
        $this->assertEquals(1, $all[$id]->membercount);
        $this->assertNull($repo->find(999999));
    }

    /**
     * Names must be filled, short enough and unique without regard to case.
     *
     * @return void
     */
    public function test_name_rules(): void {
        $this->resetAfterTest();
        $repo = new pool_repository();
        $id = $repo->create('Examiners', '', 2);

        foreach (['', '   ', str_repeat('x', 256)] as $bad) {
            try {
                $repo->create($bad, '', 2);
                $this->fail('A bad name must be refused.');
            } catch (\moodle_exception $e) {
                $this->assertSame('error:poolnameinvalid', $e->errorcode);
            }
        }

        try {
            $repo->create('EXAMINERS', '', 2);
            $this->fail('The same name in other case must be refused.');
        } catch (\moodle_exception $e) {
            $this->assertSame('error:poolnameexists', $e->errorcode);
        }

        // A pool may keep its own name when it is changed, and another pool may not take it.
        $repo->update($id, 'Examiners', 'new description', 2);
        $other = $repo->create('Minute takers', '', 2);
        try {
            $repo->update($other, 'examiners', '', 2);
            $this->fail('A name of another pool must be refused.');
        } catch (\moodle_exception $e) {
            $this->assertSame('error:poolnameexists', $e->errorcode);
        }
    }

    /**
     * Changing a pool that does not exist is an error.
     *
     * @return void
     */
    public function test_unknown_pool_is_an_error(): void {
        $this->resetAfterTest();
        $repo = new pool_repository();

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('error:poolnotfound', 'local_entities'));
        $repo->update(999999, 'Name', '', 2);
    }

    /**
     * Only persons can be members, a person is a member once, and nothing is written if one entity is wrong.
     *
     * @return void
     */
    public function test_members_are_persons_only_and_unique(): void {
        global $DB;
        $this->resetAfterTest();
        $repo = new pool_repository();
        [, $one] = $this->create_person();
        [, $two] = $this->create_person();
        $place = $this->create_place();
        $pool = $repo->create('Examiners', '', 2);

        $this->assertSame(2, $repo->add_members($pool, [$one, $two, $one], 2));
        $this->assertSame(0, $repo->add_members($pool, [$one], 2));
        $this->assertSame([$one, $two], $repo->get_member_entityids($pool));

        [, $three] = $this->create_person();
        try {
            $repo->add_members($pool, [$three, $place], 2);
            $this->fail('A place must not become a member.');
        } catch (\moodle_exception $e) {
            $this->assertSame('error:poolmemberwrongtype', $e->errorcode);
        }
        $this->assertSame([$one, $two], $repo->get_member_entityids($pool), 'Nothing is added if one entity is wrong.');
        $this->assertEquals(2, $DB->count_records('local_entities_pool_member'));
    }

    /**
     * Members can be removed, and a person can be in several pools.
     *
     * @return void
     */
    public function test_remove_members_and_several_pools(): void {
        $this->resetAfterTest();
        $repo = new pool_repository();
        [, $one] = $this->create_person();
        [, $two] = $this->create_person();
        $greek = $repo->create('Greek', '', 2);
        $latin = $repo->create('Latin', '', 2);
        $repo->add_members($greek, [$one, $two], 2);
        $repo->add_members($latin, [$one], 2);

        $this->assertSame([$greek, $latin], $repo->find_poolids_of_entity($one));

        $repo->remove_members($greek, [$one]);
        $this->assertSame([$two], $repo->get_member_entityids($greek));
        $this->assertSame([$latin], $repo->find_poolids_of_entity($one));

        $repo->remove_entity_everywhere($one);
        $this->assertSame([], $repo->find_poolids_of_entity($one));
        $this->assertSame([$two], $repo->get_member_entityids($greek));
    }

    /**
     * Deleting a pool removes its memberships, but not the persons, and not other pools.
     *
     * @return void
     */
    public function test_delete_pool_keeps_persons(): void {
        global $DB;
        $this->resetAfterTest();
        $repo = new pool_repository();
        [, $person] = $this->create_person();
        $greek = $repo->create('Greek', '', 2);
        $latin = $repo->create('Latin', '', 2);
        $repo->add_members($greek, [$person], 2);
        $repo->add_members($latin, [$person], 2);

        $repo->delete($greek);

        $this->assertNull($repo->find($greek));
        $this->assertSame([$latin], $repo->find_poolids_of_entity($person));
        $this->assertTrue($DB->record_exists('local_entities', ['id' => $person]));
    }

    /**
     * Pools turn into users: each user once, in ascending order, deleted users and unknown pools left out.
     *
     * @return void
     */
    public function test_resolver_returns_each_user_once(): void {
        $this->resetAfterTest();
        $repo = new pool_repository();
        $resolver = new pool_resolver();
        [$userone, $one] = $this->create_person();
        [$usertwo, $two] = $this->create_person();
        [$userthree, $three] = $this->create_person();
        $greek = $repo->create('Greek', '', 2);
        $latin = $repo->create('Latin', '', 2);
        $repo->add_members($greek, [$two, $one], 2);
        $repo->add_members($latin, [$one, $three], 2);

        $expected = [(int)$userone->id, (int)$usertwo->id, (int)$userthree->id];
        sort($expected);
        $this->assertSame($expected, $resolver->get_userids([$greek, $latin]));
        $this->assertSame([(int)$userone->id, (int)$usertwo->id], $resolver->get_userids([$greek]));
        $this->assertSame([], $resolver->get_userids([]));
        $this->assertSame([], $resolver->get_userids([999999, 0]));

        // A user that was deleted in Moodle is no longer returned, whatever is left in the pool.
        $this->setAdminUser();
        delete_user($userthree);
        $this->assertNotContains((int)$userthree->id, $resolver->get_userids([$latin]));
    }

    /**
     * The facade lists pools, names them, and tells a pool that no longer exists by leaving it out.
     *
     * @return void
     */
    public function test_facade(): void {
        $this->resetAfterTest();
        $repo = new pool_repository();
        [$user, $person] = $this->create_person();
        $greek = $repo->create('Greek', '', 2);
        $latin = $repo->create('Latin', '', 2);
        $repo->add_members($greek, [$person], 2);

        $this->assertSame([
            ['id' => $greek, 'name' => 'Greek', 'pooltype' => 'person', 'membercount' => 1],
            ['id' => $latin, 'name' => 'Latin', 'pooltype' => 'person', 'membercount' => 0],
        ], pool_facade::list_pools());
        $this->assertCount(2, pool_facade::list_pools('person'));
        $this->assertSame([], pool_facade::list_pools('location'));

        $repo->delete($latin);
        $this->assertSame([$greek => 'Greek'], pool_facade::get_pool_names([$greek, $latin]));
        $this->assertSame([(int)$user->id], pool_facade::get_userids_for_pools([$greek, $latin]));
    }

    /**
     * Only users with the right to manage the unavailability of everyone may see and change pools.
     *
     * @return void
     */
    public function test_access(): void {
        $this->resetAfterTest();
        $manager = $this->getDataGenerator()->create_user();
        $roleid = $this->getDataGenerator()->create_role();
        $context = \context_system::instance();
        assign_capability('local/entities:manageunavailability', CAP_ALLOW, $roleid, $context->id, true);
        role_assign($roleid, $manager->id, $context->id);
        $plain = $this->getDataGenerator()->create_user();

        $this->assertTrue(pool_access::can_manage((int)$manager->id));
        $this->assertFalse(pool_access::can_manage((int)$plain->id));

        $this->setUser($plain);
        $this->assertFalse(pool_access::can_manage());
        try {
            pool_access::require_manage();
            $this->fail('A user without the right must be stopped.');
        } catch (\required_capability_exception $e) {
            $this->assertSame('nopermissions', $e->errorcode);
        }

        $this->setUser($manager);
        pool_access::require_manage();
        $this->assertTrue(pool_access::can_manage());

        $this->setGuestUser();
        $this->assertFalse(pool_access::can_manage());
        $this->setUser(0);
        $this->assertFalse(pool_access::can_manage());
    }

    /**
     * Deleting a person entity removes it from all pools, the pools stay.
     *
     * @return void
     */
    public function test_deleting_a_person_removes_the_memberships(): void {
        global $DB;
        $this->resetAfterTest();
        $repo = new pool_repository();
        [, $one] = $this->create_person();
        [, $two] = $this->create_person();
        $pool = $repo->create('Greek', '', 2);
        $repo->add_members($pool, [$one, $two], 2);

        (new settings_manager($one))->delete();

        $this->assertSame([$two], $repo->get_member_entityids($pool));
        $this->assertNotNull($repo->find($pool));
        $this->assertEquals(0, $DB->count_records('local_entities_pool_member', ['entityid' => $one]));
    }

    /**
     * A pool has a type, person by default. Only enabled types can be used, and the type is kept.
     *
     * @return void
     */
    public function test_pool_type(): void {
        $this->resetAfterTest();
        $repo = new pool_repository();

        $id = $repo->create('Examiners', '', 2);
        $this->assertSame('person', $repo->find($id)->pooltype);
        $this->assertSame('person', $repo->find($repo->create('Other', '', 2, 'person'))->pooltype);

        foreach (['location', 'equipment', 'nonsense', ''] as $bad) {
            try {
                $repo->create('Pool ' . $bad, '', 2, $bad);
                $this->fail('A type that is not enabled must be refused.');
            } catch (\moodle_exception $e) {
                $this->assertSame('error:pooltypeinvalid', $e->errorcode);
            }
        }

        $repo->update($id, 'Renamed', '', 2);
        $this->assertSame('person', $repo->find($id)->pooltype, 'Changing a pool does not change its type.');
        $this->assertSame(['person' => get_string('pooltype_person', 'local_entities')], pool_types::all());
    }

    /**
     * Pools can be listed by type.
     *
     * @return void
     */
    public function test_find_all_by_type(): void {
        global $DB;
        $this->resetAfterTest();
        $repo = new pool_repository();
        $person = $repo->create('Examiners', '', 2);
        // A pool of another type is not possible yet, so one is written directly.
        $room = $DB->insert_record('local_entities_pool', (object)[
            'name' => 'Rooms', 'pooltype' => 'location', 'description' => '', 'timecreated' => 1,
            'timemodified' => 1, 'usermodified' => 2,
        ]);

        // Ordered by name: Examiners before Rooms.
        $this->assertSame([$person, $room], array_map('intval', array_keys($repo->find_all())));
        $this->assertSame([$person], array_map('intval', array_keys($repo->find_all('person'))));
        $this->assertSame([$room], array_map('intval', array_keys($repo->find_all('location'))));
    }

    /**
     * Members must have the type of the pool, a pool of another type takes places but no persons.
     *
     * @return void
     */
    public function test_members_follow_the_type_of_the_pool(): void {
        global $DB;
        $this->resetAfterTest();
        $repo = new pool_repository();
        [, $person] = $this->create_person();
        $place = $this->create_place();
        $roompool = $DB->insert_record('local_entities_pool', (object)[
            'name' => 'Rooms', 'pooltype' => 'location', 'description' => '', 'timecreated' => 1,
            'timemodified' => 1, 'usermodified' => 2,
        ]);

        $this->assertSame(1, $repo->add_members($roompool, [$place], 2));
        try {
            $repo->add_members($roompool, [$person], 2);
            $this->fail('A person must not become a member of a pool of places.');
        } catch (\moodle_exception $e) {
            $this->assertSame('error:poolmemberwrongtype', $e->errorcode);
        }
    }

    /**
     * Only pools of type person turn into users. The members of another pool have no user.
     *
     * @return void
     */
    public function test_resolver_ignores_pools_of_other_types(): void {
        global $DB;
        $this->resetAfterTest();
        $resolver = new pool_resolver();
        [$user, $person] = $this->create_person();
        $place = $this->create_place();
        $roompool = $DB->insert_record('local_entities_pool', (object)[
            'name' => 'Rooms', 'pooltype' => 'location', 'description' => '', 'timecreated' => 1,
            'timemodified' => 1, 'usermodified' => 2,
        ]);
        (new pool_repository())->add_members($roompool, [$place], 2);
        $personpool = (new pool_repository())->create('Examiners', '', 2);
        (new pool_repository())->add_members($personpool, [$person], 2);

        $this->assertSame([], $resolver->get_userids([$roompool]));
        $this->assertSame([(int)$user->id], $resolver->get_userids([$roompool, $personpool]));
    }

    /**
     * The members that can be chosen are the persons for a person pool, by entity id, with their names.
     *
     * @return void
     */
    public function test_member_options_of_a_person_pool(): void {
        $this->resetAfterTest();
        [$user, $person] = $this->create_person();
        [$deleted] = $this->create_person();
        $this->create_place();
        $this->setAdminUser();
        delete_user($deleted);

        $options = pool_types::member_options('person');

        $this->assertSame([$person => fullname($user)], $options);
        $this->assertSame([], pool_types::member_options('nonsense'));
    }

    /**
     * The service saves a pool with its members in one step and keeps the members exactly as given.
     *
     * @return void
     */
    public function test_service_saves_a_pool_with_its_members(): void {
        $this->resetAfterTest();
        $repo = new pool_repository();
        $service = new pool_service();
        [, $one] = $this->create_person();
        [, $two] = $this->create_person();
        [, $three] = $this->create_person();

        $id = $service->save_pool(0, 'Examiners', 'Greek', [$one, $two, $one], 2);
        $this->assertSame([$one, $two], $repo->get_member_entityids($id));
        $this->assertSame('person', $repo->find($id)->pooltype);

        $same = $service->save_pool($id, 'Examiners Greek', 'Greek exams', [$two, $three], 3, 'nonsense');
        $this->assertSame($id, $same);
        $this->assertSame([$two, $three], $repo->get_member_entityids($id));
        $this->assertSame('Examiners Greek', $repo->find($id)->name);
        $this->assertSame('person', $repo->find($id)->pooltype, 'The type of an existing pool is not changed.');
    }

    /**
     * If a member does not fit, the service changes nothing: no new pool, no half changed pool.
     *
     * @return void
     */
    public function test_service_changes_nothing_if_a_member_does_not_fit(): void {
        // The service works in a transaction, which can only be rolled back for real outside the one
        // PHPUnit wraps around each test.
        $this->preventResetByRollback();
        $this->resetAfterTest();
        $repo = new pool_repository();
        $service = new pool_service();
        [, $one] = $this->create_person();
        $place = $this->create_place();

        try {
            $service->save_pool(0, 'New pool', '', [$one, $place], 2);
            $this->fail('A place must not become a member of a person pool.');
        } catch (\moodle_exception $e) {
            $this->assertSame('error:poolmemberwrongtype', $e->errorcode);
        }
        $this->assertSame([], $repo->find_all());

        $id = $service->save_pool(0, 'Examiners', '', [$one], 2);
        try {
            $service->save_pool($id, 'Changed', 'new', [$place], 2);
            $this->fail('A place must not become a member of a person pool.');
        } catch (\moodle_exception $e) {
            $this->assertSame('error:poolmemberwrongtype', $e->errorcode);
        }
        $this->assertSame('Examiners', $repo->find($id)->name);
        $this->assertSame([$one], $repo->get_member_entityids($id));
    }

    /**
     * The service deletes a pool, and refuses one that does not exist.
     *
     * @return void
     */
    public function test_service_deletes_a_pool(): void {
        $this->resetAfterTest();
        $repo = new pool_repository();
        $service = new pool_service();
        $id = $service->save_pool(0, 'Examiners', '', [], 2);

        $service->delete_pool($id);
        $this->assertNull($repo->find($id));

        $this->expectException(\moodle_exception::class);
        $service->delete_pool($id);
    }

    /**
     * The dialog checks the name, and only lets users in who may manage pools.
     *
     * @return void
     */
    public function test_dialog_checks_name_and_access(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        (new pool_repository())->create('Examiners', '', 2);

        $form = new pool_dynamic_form(null, null, 'post', '', [], true, ['id' => 0, 'pooltype' => 'person']);
        $this->assertArrayHasKey('name', $form->validation(['id' => 0, 'name' => 'examiners'], []));
        $this->assertArrayHasKey('name', $form->validation(['id' => 0, 'name' => '  '], []));
        $this->assertArrayNotHasKey('name', $form->validation(['id' => 0, 'name' => 'Minute takers'], []));

        $check = new \ReflectionMethod($form, 'check_access_for_dynamic_submission');
        $check->setAccessible(true);
        $check->invoke($form);

        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(\required_capability_exception::class);
        $check->invoke($form);
    }

    /**
     * The service sets the pools of one person: it joins the new ones and leaves the others, and
     * changes nothing if one pool does not fit.
     *
     * @return void
     */
    public function test_service_sets_the_pools_of_a_person(): void {
        global $DB;
        $this->preventResetByRollback();
        $this->resetAfterTest();
        $repo = new pool_repository();
        $service = new pool_service();
        [, $person] = $this->create_person();
        [, $other] = $this->create_person();
        $greek = $repo->create('Greek', '', 2);
        $latin = $repo->create('Latin', '', 2);
        $math = $repo->create('Math', '', 2);
        $repo->add_members($greek, [$person, $other], 2);

        $service->set_pools_of_entity($person, [$latin, $math, $latin], 3);
        $this->assertSame([$latin, $math], $repo->find_poolids_of_entity($person));
        $this->assertSame([$other], $repo->get_member_entityids($greek), 'Other persons stay where they are.');

        $service->set_pools_of_entity($person, [], 3);
        $this->assertSame([], $repo->find_poolids_of_entity($person));

        // A pool that does not exist, or has a type other than the entity, changes nothing at all.
        $service->set_pools_of_entity($person, [$greek], 3);
        $roompool = $DB->insert_record('local_entities_pool', (object)[
            'name' => 'Rooms', 'pooltype' => 'location', 'description' => '', 'timecreated' => 1,
            'timemodified' => 1, 'usermodified' => 2,
        ]);
        foreach ([[$latin, 999999], [$latin, $roompool]] as $wanted) {
            try {
                $service->set_pools_of_entity($person, $wanted, 3);
                $this->fail('A pool that does not fit must be refused.');
            } catch (\moodle_exception $e) {
                $this->assertContains($e->errorcode, ['error:poolnotfound', 'error:poolmemberwrongtype']);
            }
            $this->assertSame([$greek], $repo->find_poolids_of_entity($person));
        }
    }

    /**
     * The names of the pools of all entities come in one list, ordered by pool name.
     *
     * @return void
     */
    public function test_pool_names_by_entity(): void {
        $this->resetAfterTest();
        $repo = new pool_repository();
        [, $one] = $this->create_person();
        [, $two] = $this->create_person();
        [, $none] = $this->create_person();
        $latin = $repo->create('Latin', '', 2);
        $greek = $repo->create('Greek', '', 2);
        $repo->add_members($latin, [$one, $two], 2);
        $repo->add_members($greek, [$one], 2);

        $names = $repo->find_pool_names_by_entity();

        $this->assertSame(['Greek', 'Latin'], $names[$one]);
        $this->assertSame(['Latin'], $names[$two]);
        $this->assertArrayNotHasKey($none, $names);
    }
}
