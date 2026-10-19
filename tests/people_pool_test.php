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

use local_entities\people_pool\people_pool_access;
use local_entities\people_pool\people_pool_facade;
use local_entities\people_pool\people_pool_repository;
use local_entities\people_pool\people_pool_resolver;

/**
 * Tests for the pools of persons: storage, resolving to users, access and clean up.
 *
 * @package    local_entities
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_entities\people_pool\people_pool_repository
 * @covers     \local_entities\people_pool\people_pool_resolver
 * @covers     \local_entities\people_pool\people_pool_facade
 * @covers     \local_entities\people_pool\people_pool_access
 */
final class people_pool_test extends \advanced_testcase {
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
        $repo = new people_pool_repository();
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
        $repo = new people_pool_repository();
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
        $repo = new people_pool_repository();

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
        $repo = new people_pool_repository();
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
            $this->assertSame('error:poolmembernotperson', $e->errorcode);
        }
        $this->assertSame([$one, $two], $repo->get_member_entityids($pool), 'Nothing is added if one entity is wrong.');
        $this->assertEquals(2, $DB->count_records('local_entities_people_pool_member'));
    }

    /**
     * Members can be removed, and a person can be in several pools.
     *
     * @return void
     */
    public function test_remove_members_and_several_pools(): void {
        $this->resetAfterTest();
        $repo = new people_pool_repository();
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
        $repo = new people_pool_repository();
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
        $repo = new people_pool_repository();
        $resolver = new people_pool_resolver();
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
        $repo = new people_pool_repository();
        [$user, $person] = $this->create_person();
        $greek = $repo->create('Greek', '', 2);
        $latin = $repo->create('Latin', '', 2);
        $repo->add_members($greek, [$person], 2);

        $this->assertSame([
            ['id' => $greek, 'name' => 'Greek', 'membercount' => 1],
            ['id' => $latin, 'name' => 'Latin', 'membercount' => 0],
        ], people_pool_facade::list_pools());

        $repo->delete($latin);
        $this->assertSame([$greek => 'Greek'], people_pool_facade::get_pool_names([$greek, $latin]));
        $this->assertSame([(int)$user->id], people_pool_facade::get_userids_for_pools([$greek, $latin]));
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

        $this->assertTrue(people_pool_access::can_manage((int)$manager->id));
        $this->assertFalse(people_pool_access::can_manage((int)$plain->id));

        $this->setUser($plain);
        $this->assertFalse(people_pool_access::can_manage());
        try {
            people_pool_access::require_manage();
            $this->fail('A user without the right must be stopped.');
        } catch (\required_capability_exception $e) {
            $this->assertSame('nopermissions', $e->errorcode);
        }

        $this->setUser($manager);
        people_pool_access::require_manage();
        $this->assertTrue(people_pool_access::can_manage());

        $this->setGuestUser();
        $this->assertFalse(people_pool_access::can_manage());
        $this->setUser(0);
        $this->assertFalse(people_pool_access::can_manage());
    }

    /**
     * Deleting a person entity removes it from all pools, the pools stay.
     *
     * @return void
     */
    public function test_deleting_a_person_removes_the_memberships(): void {
        global $DB;
        $this->resetAfterTest();
        $repo = new people_pool_repository();
        [, $one] = $this->create_person();
        [, $two] = $this->create_person();
        $pool = $repo->create('Greek', '', 2);
        $repo->add_members($pool, [$one, $two], 2);

        (new settings_manager($one))->delete();

        $this->assertSame([$two], $repo->get_member_entityids($pool));
        $this->assertNotNull($repo->find($pool));
        $this->assertEquals(0, $DB->count_records('local_entities_people_pool_member', ['entityid' => $one]));
    }
}
