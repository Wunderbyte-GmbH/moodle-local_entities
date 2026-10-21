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

use local_entities\output\unavailability_page;

/**
 * Tests for the context of the unavailability page.
 *
 * @package    local_entities
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_entities\output\unavailability_page
 */
final class unavailability_page_test extends \advanced_testcase {
    /**
     * Exports the context the page hands to its template.
     *
     * @param int $entityid
     * @return array
     */
    private function export(int $entityid): array {
        global $PAGE;
        $PAGE->set_url(new \moodle_url('/local/entities/unavailability.php'));
        $page = new unavailability_page($entityid);
        return $page->export_for_template($PAGE->get_renderer('core'));
    }

    /**
     * Someone who may only manage the own person sees the heading of this person and no selection,
     * as there is nothing to choose.
     *
     * @return void
     */
    public function test_one_manageable_entity_shows_no_selection(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user(['firstname' => 'Billy', 'lastname' => 'Teachy']);
        $this->setAdminUser();
        $entityid = persons::get_or_create_for_user((int)$user->id);
        $this->setUser($user);

        $data = $this->export($entityid);

        $this->assertSame(get_string('unavailabilityof', 'local_entities', 'Billy Teachy'), $data['heading']);
        $this->assertNull($data['entityselect']);
    }

    /**
     * The page is read only when the self-editing of the type is locked, and editable otherwise. A manager is never
     * read only.
     *
     * @return void
     */
    public function test_the_page_is_read_only_when_the_type_is_locked(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setAdminUser();
        $entityid = persons::get_or_create_for_user((int)$user->id);

        $this->setUser($user);
        $this->assertFalse($this->export($entityid)['readonly']);

        set_config('unavailabilitylock_person', 1, 'local_entities');
        $this->assertTrue($this->export($entityid)['readonly']);

        $this->setAdminUser();
        $this->assertFalse($this->export($entityid)['readonly']);
    }

    /**
     * Someone who may manage several entities gets one list with all of them. The type is part of the
     * text, so a search finds both, and the entity on display is the chosen one.
     *
     * @return void
     */
    public function test_several_entities_show_a_selection_to_search(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        /** @var \local_entities_generator $gen */
        $gen = $this->getDataGenerator()->get_plugin_generator('local_entities');
        $roomid = $gen->create_entities(['name' => 'Room A', 'shortname' => 'ra', 'entitytype' => 'location']);
        $anna = $this->getDataGenerator()->create_user(['firstname' => 'Anna', 'lastname' => 'Adams']);
        $annaid = persons::get_or_create_for_user((int)$anna->id);

        $data = $this->export($roomid);

        $this->assertSame(get_string('unavailabilityof', 'local_entities', 'Room A'), $data['heading']);
        $select = $data['entityselect'];
        $this->assertNotNull($select);
        $this->assertSame(get_string('unavailability_entity', 'local_entities'), $select['label']);
        $this->assertStringContainsString('/local/entities/unavailability.php', $select['url']);

        $byid = array_column($select['options'], null, 'id');
        $this->assertArrayHasKey($roomid, $byid);
        $this->assertArrayHasKey($annaid, $byid);
        $this->assertStringStartsWith('Room A (', $byid[$roomid]['name']);
        $this->assertStringStartsWith('Anna Adams (', $byid[$annaid]['name']);
        $this->assertTrue($byid[$roomid]['selected']);
        $this->assertFalse($byid[$annaid]['selected']);
        $this->assertCount(1, array_filter($select['options'], fn($option) => $option['selected']));
    }

    /**
     * The heading of a person is the name of its user.
     *
     * @return void
     */
    public function test_heading_of_a_person_is_the_name_of_the_user(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $anna = $this->getDataGenerator()->create_user(['firstname' => 'Anna', 'lastname' => 'Adams']);
        $entityid = persons::get_or_create_for_user((int)$anna->id);

        $data = $this->export($entityid);

        $this->assertSame(get_string('unavailabilityof', 'local_entities', 'Anna Adams'), $data['heading']);
    }

    /**
     * The template renders the context: heading, selection and the place for the calendar.
     *
     * @return void
     */
    public function test_template_renders_the_context(): void {
        global $PAGE;
        $this->resetAfterTest();
        $this->setAdminUser();
        $anna = $this->getDataGenerator()->create_user(['firstname' => 'Anna', 'lastname' => 'Adams']);
        $zora = $this->getDataGenerator()->create_user(['firstname' => 'Zora', 'lastname' => 'Zeller']);
        $entityid = persons::get_or_create_for_user((int)$anna->id);
        persons::get_or_create_for_user((int)$zora->id);

        $html = $PAGE->get_renderer('core')->render_from_template(
            'local_entities/unavailability_page',
            $this->export($entityid)
        );

        $this->assertStringContainsString('data-region="unavailability-page"', $html);
        $this->assertStringContainsString('data-region="unavailability-calendar"', $html);
        $this->assertStringContainsString(get_string('unavailabilityof', 'local_entities', 'Anna Adams'), $html);
        $this->assertStringContainsString('Zora Zeller', $html);
        $this->assertStringContainsString('data-region="unavailability-entity-select"', $html);
        $this->assertStringContainsString('<option value="' . $entityid . '" selected>', $html);
    }

    /**
     * An entity that does not exist can not be shown.
     *
     * @return void
     */
    public function test_unknown_entity_fails(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->expectException(\dml_missing_record_exception::class);
        $this->export(99999);
    }
}
