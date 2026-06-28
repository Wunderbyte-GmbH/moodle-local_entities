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
 * Tests for entity CRUD via settings_manager + entity::load.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_entities\settings_manager
 * @covers \local_entities\entity
 */
final class settings_manager_test extends advanced_testcase {
    /**
     * Create -> load -> update -> delete round-trip.
     */
    public function test_crud_round_trip(): void {
        global $DB;
        $this->resetAfterTest();

        // Create (via the plugin generator, which uses settings_manager::update_or_createentity).
        $gen = $this->getDataGenerator()->get_plugin_generator('local_entities');
        $id = (int)$gen->create_entities([
            'name' => 'Original Name',
            'shortname' => 'orig',
            'entitytype' => 'location',
        ]);
        $this->assertGreaterThan(0, $id);

        // Read via entity::load.
        $entity = entity::load($id);
        $this->assertSame('Original Name', (string)$entity->__get('name'));

        // Read via settings_manager::get_settings.
        $settings = settings_manager::get_settings($id);
        $this->assertSame('orig', (string)$settings->shortname);

        // Update.
        (new settings_manager())->update_or_createentity((object)[
            'id' => $id,
            'name' => 'Renamed',
            'shortname' => 'renamed',
            'entitytype' => 'location',
        ]);
        $this->assertSame('Renamed', (string)settings_manager::get_settings($id)->name);

        // Delete.
        (new settings_manager($id))->delete();
        $this->assertFalse(
            $DB->record_exists('local_entities', ['id' => $id]),
            'Entity row must be gone after delete.'
        );
    }
}
