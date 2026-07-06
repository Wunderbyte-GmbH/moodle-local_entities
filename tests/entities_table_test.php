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

use local_entities\table\entities_table;

/**
 * Tests the depth-first ordered query that backs the hierarchical entities list.
 *
 * @package    local_entities
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_entities\table\entities_table::arrange_as_tree
 */
final class entities_table_test extends \advanced_testcase {
    /**
     * arrange_as_tree() orders entities depth-first (parent → children) with the correct depth and a
     * root→self name path. This PHP ordering is what lets pagination keep the tree intact, and it is
     * fully database-agnostic (no DB-specific SQL involved).
     *
     * @return void
     */
    public function test_arrange_as_tree_orders_depth_first(): void {
        global $DB;
        $this->resetAfterTest();

        $gen = $this->getDataGenerator()->get_plugin_generator('local_entities');
        $root = $gen->create_entities(['name' => 'Root', 'shortname' => 'root']);
        $childa = $gen->create_entities(['name' => 'ChildA', 'shortname' => 'ca', 'parentid' => $root]);
        $gen->create_entities(['name' => 'Leaf', 'shortname' => 'leaf', 'parentid' => $childa]);
        $gen->create_entities(['name' => 'ChildB', 'shortname' => 'cb', 'parentid' => $root]);
        $gen->create_entities(['name' => 'Root2', 'shortname' => 'root2']);

        $input = $DB->get_records('local_entities', null, '', 'id, name, parentid, entitytype');
        $table = new entities_table('test_entities_table');
        $rows = $table->arrange_as_tree($input);

        $order = array_values(array_map(static fn($r) => $r->name, $rows));
        $this->assertSame(['Root', 'ChildA', 'Leaf', 'ChildB', 'Root2'], $order);

        $byname = [];
        foreach ($rows as $r) {
            $byname[$r->name] = $r;
        }
        $this->assertEquals(0, (int)$byname['Root']->entitydepth);
        $this->assertEquals(1, (int)$byname['ChildA']->entitydepth);
        $this->assertEquals(2, (int)$byname['Leaf']->entitydepth);
        $this->assertEquals(0, (int)$byname['Root2']->entitydepth);

        // The name path runs from the root down to the entity itself.
        $this->assertStringContainsString('Root / ChildA', $byname['Leaf']->namepath);
        $this->assertStringEndsWith('Leaf', $byname['Leaf']->namepath);
    }
}
