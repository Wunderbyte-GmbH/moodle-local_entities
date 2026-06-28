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

use local_entities\local\templates\template_seeder;
use local_entities\customfield\entities_handler;

/**
 * Tests for the default entity template seeder.
 *
 * @package    local_entities
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_entities\local\templates\template_seeder
 */
final class template_seeder_test extends \advanced_testcase {

    /**
     * Remove any already-seeded templates and their config so each test exercises a clean seed.
     *
     * The phpunit base install runs db/install.php, which seeds the templates; we wipe that state
     * here so the assertions below test the seeder creating from scratch (not the install snapshot).
     *
     * @return void
     */
    private function reset_seeded_state(): void {
        global $DB;
        $categories = $DB->get_records('customfield_category',
            ['component' => 'local_entities', 'area' => 'entities']);
        foreach ($categories as $category) {
            $DB->delete_records('customfield_field', ['categoryid' => $category->id]);
        }
        $DB->delete_records('customfield_category',
            ['component' => 'local_entities', 'area' => 'entities']);
        unset_config('template_location_itemid', 'local_entities');
        unset_config('template_equipment_itemid', 'local_entities');
        unset_config(template_seeder::SEEDED_FLAG, 'local_entities');
        entities_handler::reset_caches();
    }

    /**
     * Returns the shortnames of all custom fields of a template itemid.
     *
     * @param int $itemid
     * @return string[]
     */
    private function field_shortnames(int $itemid): array {
        $handler = entities_handler::create($itemid);
        return array_values(array_map(static fn($f) => $f->get('shortname'), $handler->get_fields()));
    }

    /**
     * Returns a single field controller of a template by shortname (or null).
     *
     * @param int $itemid
     * @param string $shortname
     * @return \core_customfield\field_controller|null
     */
    private function field_by_shortname(int $itemid, string $shortname) {
        $handler = entities_handler::create($itemid);
        foreach ($handler->get_fields() as $field) {
            if ($field->get('shortname') === $shortname) {
                return $field;
            }
        }
        return null;
    }

    /**
     * Seeding creates the two templates with their fields, and records the itemids in config.
     *
     * @return void
     */
    public function test_seeding_creates_both_templates(): void {
        $this->resetAfterTest();
        $this->reset_seeded_state();

        template_seeder::seed_default_templates();

        $locitemid = (int)get_config('local_entities', 'template_location_itemid');
        $equipitemid = (int)get_config('local_entities', 'template_equipment_itemid');
        $this->assertGreaterThan(0, $locitemid);
        $this->assertGreaterThan(0, $equipitemid);
        $this->assertNotEquals($locitemid, $equipitemid);
        $this->assertEquals(1, (int)get_config('local_entities', template_seeder::SEEDED_FLAG));

        // The two categories exist and carry the expected fields.
        $categories = \local_entities\customfield\entities_cf_helper::get_all_cf_categories();
        $this->assertArrayHasKey($locitemid, $categories);
        $this->assertArrayHasKey($equipitemid, $categories);

        $locfields = $this->field_shortnames($locitemid);
        $this->assertContains('loc_building', $locfields);
        $this->assertContains('loc_amenities', $locfields);
        $this->assertContains('loc_accessible', $locfields);

        $equipfields = $this->field_shortnames($equipitemid);
        $this->assertContains('eq_inventoryno', $equipfields);
        $this->assertContains('eq_condition', $equipfields);
        $this->assertContains('eq_purchasedate', $equipfields);
    }

    /**
     * Seeding is idempotent: a second call neither duplicates categories nor fields.
     *
     * @return void
     */
    public function test_seeding_is_idempotent(): void {
        global $DB;
        $this->resetAfterTest();
        $this->reset_seeded_state();

        template_seeder::seed_default_templates();

        $categorycount = $DB->count_records('customfield_category',
            ['component' => 'local_entities', 'area' => 'entities']);
        $fieldcount = $DB->count_records_sql(
            "SELECT COUNT(f.id) FROM {customfield_field} f
               JOIN {customfield_category} c ON c.id = f.categoryid
              WHERE c.component = ? AND c.area = ?",
            ['local_entities', 'entities']
        );

        // Second run must be a no-op.
        template_seeder::seed_default_templates();

        $this->assertEquals($categorycount, $DB->count_records('customfield_category',
            ['component' => 'local_entities', 'area' => 'entities']));
        $this->assertEquals($fieldcount, $DB->count_records_sql(
            "SELECT COUNT(f.id) FROM {customfield_field} f
               JOIN {customfield_category} c ON c.id = f.categoryid
              WHERE c.component = ? AND c.area = ?",
            ['local_entities', 'entities']
        ));
    }

    /**
     * Field configuration is applied: the condition select has its options and the inventory
     * number enforces unique values.
     *
     * @return void
     */
    public function test_field_configuration_applied(): void {
        $this->resetAfterTest();
        $this->reset_seeded_state();

        template_seeder::seed_default_templates();
        $equipitemid = (int)get_config('local_entities', 'template_equipment_itemid');

        $condition = $this->field_by_shortname($equipitemid, 'eq_condition');
        $this->assertNotNull($condition);
        $this->assertEquals('select', $condition->get('type'));
        $options = $condition->get_configdata_property('options');
        // Options are seeded from plain Moodle language strings (site language at seed time).
        $this->assertStringContainsString(get_string('tplcond_new', 'local_entities'), $options);
        $this->assertStringContainsString(get_string('tplcond_defective', 'local_entities'), $options);

        $inventory = $this->field_by_shortname($equipitemid, 'eq_inventoryno');
        $this->assertNotNull($inventory);
        $this->assertEquals(1, (int)$inventory->get_configdata_property('uniquevalues'));
    }
}
