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

use advanced_testcase;

/**
 * Tests for the search_entities and get_entity_calendardata external services.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_entities\external\search_entities
 * @covers \local_entities\external\get_entity_calendardata
 */
final class external_search_calendar_test extends advanced_testcase {
    /**
     * Create an entity of a given type and return its id.
     *
     * @param string $name
     * @param string $shortname
     * @param string $entitytype
     * @return int
     */
    private function make_entity(string $name, string $shortname, string $entitytype = 'location'): int {
        $gen = $this->getDataGenerator()->get_plugin_generator('local_entities');
        return (int)$gen->create_entities([
            'name' => $name,
            'shortname' => $shortname,
            'entitytype' => $entitytype,
        ]);
    }

    /**
     * search_entities returns matching location entities and excludes equipment + non-matches.
     */
    public function test_search_entities_returns_locations_only(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $room = $this->make_entity('Conference Room', 'confroom', 'location');
        $equipment = $this->make_entity('Conference Beamer', 'confbeamer', 'equipment');
        $other = $this->make_entity('Storage', 'storage', 'location');

        $result = external\search_entities::execute('Conference');

        $this->assertArrayHasKey('list', $result);
        $this->assertArrayHasKey($room, $result['list'], 'Matching location must be returned.');
        $this->assertArrayNotHasKey($equipment, $result['list'], 'Equipment must be excluded from the search.');
        $this->assertArrayNotHasKey($other, $result['list'], 'Non-matching name must not be returned.');
        $this->assertSame('Conference Room', (string)$result['list'][$room]->name);
    }

    /**
     * search_entities enforces local/entities:view.
     */
    public function test_search_entities_requires_capability(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());

        // local/entities:view is granted to all authenticated users by default — prohibit it to assert the gate.
        $userrole = (int)$DB->get_field('role', 'id', ['shortname' => 'user'], MUST_EXIST);
        assign_capability('local/entities:view', CAP_PROHIBIT, $userrole, \context_system::instance()->id, true);
        accesslib_clear_all_caches_for_unit_testing();

        $this->expectException(\required_capability_exception::class);
        external\search_entities::execute('anything');
    }

    /**
     * get_entity_calendardata returns valid JSON and an empty error for an entity without dates.
     */
    public function test_get_entity_calendardata_returns_valid_json(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $id = $this->make_entity('Calendar Entity', 'calentity', 'location');
        $result = external\get_entity_calendardata::execute($id);

        $this->assertArrayHasKey('json', $result);
        $this->assertArrayHasKey('error', $result);
        $this->assertSame('', (string)$result['error']);
        $decoded = json_decode((string)$result['json']);
        $this->assertIsArray($decoded, 'Calendar payload must be a JSON array.');
        $this->assertSame([], $decoded, 'A fresh entity has no calendar entries.');
    }
}
