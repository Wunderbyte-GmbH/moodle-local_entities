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

/**
 * Tests for the scope of an unavailability and for the filter built from scopes.
 *
 * @package    local_entities
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_entities\unavailability\scope
 * @covers     \local_entities\unavailability\scope_filter
 */
final class scope_test extends \advanced_testcase {
    /**
     * Inserts a block with a scope, so the filter can be tried against real rows.
     *
     * @param string $component
     * @param string $area
     * @param int $instanceid
     * @return int id of the block
     */
    private function block(string $component, string $area, int $instanceid): int {
        global $DB;
        return (int)$DB->insert_record('local_entities_unavailability', (object)[
            'entityid' => 1,
            'starttime' => 1000,
            'endtime' => 2000,
            'component' => $component,
            'area' => $area,
            'instanceid' => $instanceid,
            'reason' => '',
            'timecreated' => 1,
            'timemodified' => 1,
            'usermodified' => 0,
        ]);
    }

    /**
     * Ids of the blocks that a filter lets through, ordered.
     *
     * @param scope_filter $filter
     * @param string $alias
     * @return int[]
     */
    private function matching(scope_filter $filter, string $alias = 'u'): array {
        global $DB;
        [$sql, $params] = $filter->to_sql($alias, 'sc');
        $from = $alias === '' ? '{local_entities_unavailability}' : "{local_entities_unavailability} $alias";
        $ids = $DB->get_fieldset_sql("SELECT id FROM $from WHERE $sql ORDER BY id", $params);
        return array_map('intval', $ids);
    }

    /**
     * A scope without component is the "everywhere" scope, whatever area and instance are given.
     *
     * @return void
     */
    public function test_scope_without_component_is_everywhere(): void {
        $this->assertTrue(scope::everywhere()->is_everywhere());
        $this->assertTrue(scope::of('', 'option', 7)->is_everywhere());
        $this->assertSame(['component' => '', 'area' => '', 'instanceid' => 0], scope::of('', 'option', 7)->to_array());
        $this->assertFalse(scope::of('mod_booking', 'option', 7)->is_everywhere());
    }

    /**
     * A scope can be built from the array form that callers use, missing keys count as empty.
     *
     * @return void
     */
    public function test_scope_from_array(): void {
        $scope = scope::from_array(['component' => 'mod_booking', 'area' => 'booking', 'instanceid' => '5']);
        $this->assertSame('mod_booking', $scope->component);
        $this->assertSame('booking', $scope->area);
        $this->assertSame(5, $scope->instanceid);

        $this->assertTrue(scope::from_array([])->is_everywhere());
        $this->assertTrue($scope->equals(scope::of('mod_booking', 'booking', 5)));
        $this->assertFalse($scope->equals(scope::of('mod_booking', 'booking', 6)));
    }

    /**
     * Without restriction every block is returned.
     *
     * @return void
     */
    public function test_unrestricted_matches_everything(): void {
        $this->resetAfterTest();
        $global = $this->block('', '', 0);
        $option = $this->block('mod_booking', 'option', 7);

        $this->assertSame([$global, $option], $this->matching(scope_filter::unrestricted()));
        $this->assertSame([$global, $option], $this->matching(scope_filter::from_nullable(null)));
    }

    /**
     * An empty list of scopes returns only the blocks that apply everywhere.
     *
     * @return void
     */
    public function test_empty_list_matches_only_global_blocks(): void {
        $this->resetAfterTest();
        $global = $this->block('', '', 0);
        $this->block('mod_booking', 'option', 7);

        $this->assertSame([$global], $this->matching(scope_filter::global_and([])));
        $this->assertSame([$global], $this->matching(scope_filter::from_nullable([])));
    }

    /**
     * A list of scopes returns the global blocks and the blocks of exactly the listed scopes.
     *
     * @return void
     */
    public function test_list_matches_global_and_listed_scopes(): void {
        $this->resetAfterTest();
        $global = $this->block('', '', 0);
        $option7 = $this->block('mod_booking', 'option', 7);
        $this->block('mod_booking', 'option', 8);
        $booking7 = $this->block('mod_booking', 'booking', 7);
        $this->block('mod_other', 'option', 7);

        $filter = scope_filter::global_and([
            scope::of('mod_booking', 'option', 7),
            scope::of('mod_booking', 'booking', 7),
        ]);
        $this->assertSame([$global, $option7, $booking7], $this->matching($filter));

        $fromarrays = scope_filter::from_nullable([['component' => 'mod_booking', 'area' => 'option', 'instanceid' => 7]]);
        $this->assertSame([$global, $option7], $this->matching($fromarrays));
    }

    /**
     * "Exactly" a scope returns only the blocks of that scope, without the global ones. Exactly
     * "everywhere" returns only the global blocks.
     *
     * @return void
     */
    public function test_exactly_matches_one_scope_only(): void {
        $this->resetAfterTest();
        $global = $this->block('', '', 0);
        $option7 = $this->block('mod_booking', 'option', 7);
        $this->block('mod_booking', 'option', 8);

        $this->assertSame([$option7], $this->matching(scope_filter::exactly(scope::of('mod_booking', 'option', 7))));
        $this->assertSame([$global], $this->matching(scope_filter::exactly(scope::everywhere())));
    }

    /**
     * The filter works without a table alias, for queries on the plain table.
     *
     * @return void
     */
    public function test_filter_works_without_alias(): void {
        $this->resetAfterTest();
        $global = $this->block('', '', 0);
        $this->block('mod_booking', 'option', 7);

        $this->assertSame([$global], $this->matching(scope_filter::global_and([]), ''));
    }
}
