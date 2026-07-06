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

    /**
     * The parentid guard rejects cycles (parent inside the entity's own subtree, incl. itself)
     * and unknown parents, while legal reparents keep working. The form select already excludes
     * these targets; this guards the webservice/import path.
     *
     * @return void
     */
    public function test_parentid_cycle_guard(): void {
        $this->resetAfterTest();

        $gen = $this->getDataGenerator()->get_plugin_generator('local_entities');
        $root = $gen->create_entities(['name' => 'Root', 'shortname' => 'r', 'entitytype' => 'location']);
        $child = $gen->create_entities(
            ['name' => 'Child', 'shortname' => 'c', 'entitytype' => 'location', 'parentid' => $root]
        );
        $grandchild = $gen->create_entities(
            ['name' => 'Grandchild', 'shortname' => 'g', 'entitytype' => 'location', 'parentid' => $child]
        );

        $sm = new settings_manager();

        // Moving the root below its own grandchild must be rejected.
        try {
            $sm->update_or_createentity((object)['id' => $root, 'name' => 'Root', 'parentid' => $grandchild]);
            $this->fail('Expected a parentcycle exception.');
        } catch (\moodle_exception $e) {
            $this->assertStringContainsString('parentcycle', $e->errorcode);
        }

        // The entity itself is not a valid parent either.
        try {
            $sm->update_or_createentity((object)['id' => $root, 'name' => 'Root', 'parentid' => $root]);
            $this->fail('Expected a parentcycle exception.');
        } catch (\moodle_exception $e) {
            $this->assertStringContainsString('parentcycle', $e->errorcode);
        }

        // Unknown parent ids are rejected.
        try {
            $sm->update_or_createentity((object)['id' => $root, 'name' => 'Root', 'parentid' => 999999]);
            $this->fail('Expected an invalidparent exception.');
        } catch (\moodle_exception $e) {
            $this->assertStringContainsString('invalidparent', $e->errorcode);
        }

        // A legal reparent (grandchild directly under root) still works.
        $sm->update_or_createentity((object)[
            'id' => $grandchild,
            'name' => 'Grandchild',
            'parentid' => $root,
        ]);
        $this->assertSame($root, (int)settings_manager::get_settings($grandchild)->parentid);
    }

    /**
     * Every entity write must purge wunderbyte_table's rawdata cache: the entities list
     * (entities_table) is served from it, so without the purge a created/renamed/deleted
     * entity keeps being served from the stale cached row set.
     *
     * @covers \local_entities\entities::update_entity
     */
    public function test_entity_writes_purge_wunderbyte_rawdata_cache(): void {
        $this->resetAfterTest();

        $cache = \cache::make('local_wunderbyte_table', 'cachedrawdata');
        $gen = $this->getDataGenerator()->get_plugin_generator('local_entities');

        // Create.
        $cache->set('wbsentinel', ['stale']);
        $id = (int)$gen->create_entities([
            'name' => 'Cache Check',
            'shortname' => 'cachecheck',
            'entitytype' => 'location',
        ]);
        $this->assertFalse(
            $cache->get('wbsentinel'),
            'Creating an entity must purge the wunderbyte_table rawdata cache.'
        );

        // Update via settings_manager.
        $cache->set('wbsentinel', ['stale']);
        (new settings_manager())->update_or_createentity((object)[
            'id' => $id,
            'name' => 'Cache Check Renamed',
            'shortname' => 'cachecheck',
            'entitytype' => 'location',
        ]);
        $this->assertFalse(
            $cache->get('wbsentinel'),
            'Updating an entity must purge the wunderbyte_table rawdata cache.'
        );

        // Update via the webservice path (entities::update_entity, e.g. sortorder changes).
        $cache->set('wbsentinel', ['stale']);
        entities::update_entity('local_entities', (object)['id' => $id, 'sortorder' => 7]);
        $this->assertFalse(
            $cache->get('wbsentinel'),
            'Webservice entity updates must purge the wunderbyte_table rawdata cache.'
        );

        // Delete.
        $cache->set('wbsentinel', ['stale']);
        (new settings_manager($id))->delete();
        $this->assertFalse(
            $cache->get('wbsentinel'),
            'Deleting an entity must purge the wunderbyte_table rawdata cache.'
        );
    }
}
