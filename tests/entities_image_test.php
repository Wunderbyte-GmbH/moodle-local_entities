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

/**
 * Tests for the entity image resolution used by hover cards (own image + ancestor fallback).
 *
 * @package    local_entities
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_entities\entities::get_image_url
 */
final class entities_image_test extends \advanced_testcase {
    /**
     * Creates a fake image file in an entity's image file area.
     *
     * @param int $entityid
     * @param string $filename
     * @return void
     */
    private function add_image(int $entityid, string $filename): void {
        get_file_storage()->create_file_from_string([
            'contextid' => context_system::instance()->id,
            'component' => 'local_entities',
            'filearea' => 'image',
            'itemid' => $entityid,
            'filepath' => '/',
            'filename' => $filename,
        ], 'fake-image-bytes');
    }

    /**
     * Own image wins; without one, the nearest ancestor's image is used when the
     * fallback_image_parent setting is on — across any depth, not just one level.
     *
     * @return void
     */
    public function test_own_image_and_nearest_ancestor_fallback(): void {
        $this->resetAfterTest();

        $gen = $this->getDataGenerator()->get_plugin_generator('local_entities');
        $root = $gen->create_entities(['name' => 'Root', 'shortname' => 'r', 'entitytype' => 'location']);
        $child = $gen->create_entities(
            ['name' => 'Child', 'shortname' => 'c', 'entitytype' => 'location', 'parentid' => $root]
        );
        $grandchild = $gen->create_entities(
            ['name' => 'Grandchild', 'shortname' => 'g', 'entitytype' => 'location', 'parentid' => $child]
        );

        $this->add_image($root, 'root.png');
        set_config('fallback_image_parent', 1, 'local_entities');
        entities::reset_caches();

        // Own image.
        $this->assertStringContainsString('root.png', entities::get_image_url($root)->out(false));

        // Grandchild has none, child has none: falls back across TWO levels to the root image.
        $this->assertStringContainsString('root.png', entities::get_image_url($grandchild)->out(false));

        // Nearest ancestor wins once the child gets its own image.
        $this->add_image($child, 'child.png');
        entities::reset_caches();
        $this->assertStringContainsString('child.png', entities::get_image_url($grandchild)->out(false));

        // Fallback disabled: entities without an own image resolve to null.
        set_config('fallback_image_parent', 0, 'local_entities');
        entities::reset_caches();
        $this->assertNull(entities::get_image_url($grandchild));
        $this->assertStringContainsString('child.png', entities::get_image_url($child)->out(false));

        // Unknown/invalid ids resolve to null.
        $this->assertNull(entities::get_image_url(0));
    }
}
