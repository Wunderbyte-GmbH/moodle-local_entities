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

use context_system;
use local_entities\output\entity_view;
use local_entities\local\views\view_templates;

/**
 * Tests the detail-view template registry, context builder and template resolver.
 *
 * @package    local_entities
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_entities\output\entity_view
 * @covers     \local_entities\local\views\view_templates
 */
final class entity_view_test extends \advanced_testcase {
    /**
     * Every registered template renders without error and shows the entity, using the shared context.
     *
     * @return void
     */
    public function test_all_templates_render(): void {
        global $PAGE, $OUTPUT;
        $this->resetAfterTest();
        $this->setAdminUser();
        $PAGE->set_context(context_system::instance());

        $gen = $this->getDataGenerator()->get_plugin_generator('local_entities');
        $id = $gen->create_entities(['name' => 'Render Test E', 'shortname' => 'rendertest']);

        $ctx = entity_view::build_context($id);

        foreach (array_keys(view_templates::TEMPLATES) as $key) {
            $html = $OUTPUT->render_from_template('local_entities/view/' . $key, $ctx);
            $this->assertNotEmpty($html, "Template $key rendered empty");
            $this->assertStringContainsString('Render Test E', $html, "Template $key missing entity name");
        }
    }

    /**
     * The resolver honours a valid preview key only for users with the change capability, and falls
     * back to the baseline 'classic' otherwise (unknown key or missing permission).
     *
     * @return void
     */
    public function test_resolver_gates_preview_by_capability(): void {
        global $PAGE;
        $this->resetAfterTest();
        $PAGE->set_context(context_system::instance());

        // Manager-capable user: preview honoured.
        $this->setAdminUser();
        $this->assertEquals('image', entity_view::resolve_active_template('image'));
        $this->assertEquals('compact', entity_view::resolve_active_template('compact'));
        // Unknown key always falls back.
        $this->assertEquals('classic', entity_view::resolve_active_template('does-not-exist'));
        $this->assertEquals('classic', entity_view::resolve_active_template(''));

        // Ordinary user without the capability: preview ignored.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->assertEquals('classic', entity_view::resolve_active_template('image'));
    }

    /**
     * A type-specific active template wins over the global one; unset/invalid inherits the global.
     *
     * @return void
     */
    public function test_resolver_per_entity_type(): void {
        $this->resetAfterTest();
        set_config('activeviewtemplate', 'classic', 'local_entities');
        set_config('activeviewtemplate_equipment', 'compact', 'local_entities');

        $this->assertEquals('compact', entity_view::resolve_active_template('', 'equipment'));
        $this->assertEquals('classic', entity_view::resolve_active_template('', 'location'));
        $this->assertEquals('classic', entity_view::resolve_active_template('', ''));

        // Invalid per-type value falls back to the global default.
        set_config('activeviewtemplate_equipment', 'bogus', 'local_entities');
        $this->assertEquals('classic', entity_view::resolve_active_template('', 'equipment'));
    }

    /**
     * The legacy display checkboxes migrate to the right template so the look does not change.
     *
     * @return void
     */
    public function test_legacy_settings_migration_maps_each_combination(): void {
        $this->resetAfterTest();

        // 0/0 → classic.
        unset_config('activeviewtemplate', 'local_entities');
        unset_config('showpictureinsteadofcalendar', 'local_entities');
        unset_config('show_calendar_on_details_page', 'local_entities');
        entity_view::migrate_legacy_view_settings();
        $this->assertEquals('classic', get_config('local_entities', 'activeviewtemplate'));

        // Legacy show_calendar=1 → calendar.
        unset_config('activeviewtemplate', 'local_entities');
        set_config('show_calendar_on_details_page', 1, 'local_entities');
        entity_view::migrate_legacy_view_settings();
        $this->assertEquals('calendar', get_config('local_entities', 'activeviewtemplate'));

        // Legacy showpicture=1 wins over show_calendar → image.
        unset_config('activeviewtemplate', 'local_entities');
        set_config('showpictureinsteadofcalendar', 1, 'local_entities');
        entity_view::migrate_legacy_view_settings();
        $this->assertEquals('image', get_config('local_entities', 'activeviewtemplate'));
    }

    /**
     * Migration never overwrites an already-set active template.
     *
     * @return void
     */
    public function test_legacy_migration_is_idempotent(): void {
        $this->resetAfterTest();
        set_config('activeviewtemplate', 'compact', 'local_entities');
        set_config('showpictureinsteadofcalendar', 1, 'local_entities');
        entity_view::migrate_legacy_view_settings();
        $this->assertEquals('compact', get_config('local_entities', 'activeviewtemplate'));
    }

    /**
     * The external save function persists a valid template and rejects an unknown one.
     *
     * @return void
     */
    public function test_external_set_active_view_template(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // No type → global fallback.
        $result = \local_entities\external\set_active_view_template::execute('compact');
        $this->assertTrue($result['success']);
        $this->assertEquals('compact', get_config('local_entities', 'activeviewtemplate'));

        // With a type → per-type setting only.
        \local_entities\external\set_active_view_template::execute('image', 'equipment');
        $this->assertEquals('image', get_config('local_entities', 'activeviewtemplate_equipment'));
        $this->assertEquals('compact', get_config('local_entities', 'activeviewtemplate'));

        $this->expectException(\invalid_parameter_exception::class);
        \local_entities\external\set_active_view_template::execute('not-a-template');
    }

    /**
     * The OSM geocoder builds a sensible query and embed URL, and never hits the network in tests.
     *
     * @return void
     */
    public function test_osm_geocoder(): void {
        $this->resetAfterTest();

        $query = \local_entities\local\osm_geocoder::build_query([
            'streetname' => 'Hauptstrasse', 'streetnumber' => '1',
            'postcode' => '1010', 'city' => 'Wien', 'country' => 'Austria',
        ]);
        $this->assertEquals('Hauptstrasse 1, 1010 Wien, Austria', $query);
        $this->assertEquals('', \local_entities\local\osm_geocoder::build_query([]));

        $url = \local_entities\local\osm_geocoder::embed_url((object)['lat' => 48.2, 'lon' => 16.37]);
        $this->assertStringContainsString('openstreetmap.org/export/embed.html', $url);
        $this->assertStringContainsString('marker=48.2', $url);

        // No network access during tests: an uncached address resolves to null (link fallback).
        $this->assertNull(\local_entities\local\osm_geocoder::get_coordinates(['city' => 'Wien']));
    }

    /**
     * The external save function is gated by the change capability.
     *
     * @return void
     */
    public function test_external_requires_capability(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());

        $this->expectException(\required_capability_exception::class);
        \local_entities\external\set_active_view_template::execute('compact');
    }
}
