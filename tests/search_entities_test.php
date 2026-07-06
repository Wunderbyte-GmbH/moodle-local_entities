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

use core_external\external_api;
use local_entities\external\search_entities;

/**
 * Tests for the entity selector search webservice (form autocomplete).
 *
 * @package    local_entities
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_entities\external\search_entities
 */
final class search_entities_test extends \advanced_testcase {
    /**
     * The selector list is ordered depth-first for any nesting depth: every entity appears
     * directly after its parent, with its full ancestor path and depth. Before the fix, the
     * 2-level ORDER BY placed a grandchild after unrelated root entities.
     *
     * @return void
     */
    public function test_results_are_ordered_depth_first_with_full_path(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $gen = $this->getDataGenerator()->get_plugin_generator('local_entities');
        $roota = $gen->create_entities(['name' => 'Alpha', 'shortname' => 'a', 'entitytype' => 'location']);
        $gen->create_entities(['name' => 'Beta', 'shortname' => 'b', 'entitytype' => 'location']);
        $child = $gen->create_entities(
            ['name' => 'Alpha Child', 'shortname' => 'ac', 'entitytype' => 'location', 'parentid' => $roota]
        );
        $gen->create_entities(
            ['name' => 'Deep Room', 'shortname' => 'dr', 'entitytype' => 'location', 'parentid' => $child]
        );
        // Equipment must stay excluded from the location selector.
        $gen->create_entities(['name' => 'Beamer', 'shortname' => 'bm', 'entitytype' => 'equipment']);

        $result = external_api::clean_returnvalue(search_entities::execute_returns(), search_entities::execute(''));

        $names = array_column($result['list'], 'name');
        $this->assertSame(
            ['Alpha', 'Alpha Child', 'Deep Room', 'Beta'],
            $names,
            'Selector list must be depth-first: the grandchild follows its branch, not the last root.'
        );

        $byname = array_column($result['list'], null, 'name');
        $this->assertSame('Alpha / Alpha Child', $byname['Deep Room']['parentname']);
        $this->assertSame(2, (int)$byname['Deep Room']['depth']);
        $this->assertSame('', $byname['Alpha']['parentname']);
        $this->assertArrayNotHasKey('Beamer', $byname);
    }

    /**
     * Searching matches the whole displayed path (multibyte-safe), so a query for an ancestor
     * name also finds the deeper entities shown with that path in their subtitle.
     *
     * @return void
     */
    public function test_search_matches_ancestors_and_umlauts(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $gen = $this->getDataGenerator()->get_plugin_generator('local_entities');
        $root = $gen->create_entities(['name' => 'Hauptgebäude', 'shortname' => 'hg', 'entitytype' => 'location']);
        $child = $gen->create_entities(
            ['name' => 'Seminarraum', 'shortname' => 'sr', 'entitytype' => 'location', 'parentid' => $root]
        );
        $gen->create_entities(
            ['name' => 'Raumbereich', 'shortname' => 'rb', 'entitytype' => 'location', 'parentid' => $child]
        );
        $gen->create_entities(['name' => 'Anderswo', 'shortname' => 'aw', 'entitytype' => 'location']);

        $result = external_api::clean_returnvalue(
            search_entities::execute_returns(),
            search_entities::execute('hauptgebäude')
        );

        $names = array_column($result['list'], 'name');
        $this->assertSame(
            ['Hauptgebäude', 'Seminarraum', 'Raumbereich'],
            $names,
            'An ancestor-name query must find the whole subtree (its path is displayed), nothing else.'
        );
    }
}
