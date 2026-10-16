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
 * Tests for the entity the unavailability page opens when none is asked for.
 *
 * @package    local_entities
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_entities\unavailability\default_entity
 */
final class default_entity_test extends \advanced_testcase {
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
     * Someone who is a person gets the own person, whatever else they may manage.
     *
     * @return void
     */
    public function test_own_person_is_used(): void {
        $this->resetAfterTest();
        $examiner = $this->getDataGenerator()->create_user();
        $this->setAdminUser();
        $entityid = persons::get_or_create_for_user((int)$examiner->id);
        $this->setUser($examiner);

        $this->assertSame($entityid, (new default_entity())->resolve());
    }

    /**
     * Someone who manages others and is no person gets the first entity of the selection, and is not made a person.
     *
     * @return void
     */
    public function test_secretary_gets_the_first_entity_and_is_not_made_a_person(): void {
        $this->resetAfterTest();
        $secretary = $this->user_with(['local/entities:manageunavailability']);
        $anna = $this->getDataGenerator()->create_user(['firstname' => 'Anna', 'lastname' => 'Adams']);
        $zora = $this->getDataGenerator()->create_user(['firstname' => 'Zora', 'lastname' => 'Zeller']);
        $this->setAdminUser();
        $annaid = persons::get_or_create_for_user((int)$anna->id);
        persons::get_or_create_for_user((int)$zora->id);
        $this->setUser($secretary);

        $this->assertSame($annaid, (new default_entity())->resolve());
        $this->assertNull(persons::get_entityid_for_user((int)$secretary->id));
    }

    /**
     * Someone who is no person and has no right, and a guest, get nothing. Nobody becomes a person just
     * by visiting the page.
     *
     * @return void
     */
    public function test_nobody_gets_nothing_and_no_person_is_created(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->assertNull((new default_entity())->resolve());
        $this->assertNull(persons::get_entityid_for_user((int)$user->id));

        $this->setGuestUser();
        $this->assertNull((new default_entity())->resolve());
    }
}
