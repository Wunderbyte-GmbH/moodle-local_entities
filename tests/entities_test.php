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

/**
 * Unit tests for the live hierarchy derivations that back the multilevel entity filter.
 *
 * @package    local_entities
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_entities\entities::get_descendant_ids
 * @covers     \local_entities\entities::get_ancestor_path
 * @covers     \local_entities\entities::get_filter_tree
 * @covers     \local_entities\entities::get_entity_map
 */
final class entities_test extends \advanced_testcase {
    /**
     * Static caches are process-global; clear them before each test so a prior test's entity map
     * cannot leak into this one.
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        entities::reset_caches();
    }

    /**
     * Creates an entity via the plugin generator and returns its id.
     *
     * @param string $name
     * @param int $parentid
     * @param int|null $sortorder optional explicit sortorder (written directly for deterministic ordering)
     * @return int
     */
    private function make_entity(string $name, int $parentid = 0, ?int $sortorder = null): int {
        global $DB;
        $gen = $this->getDataGenerator()->get_plugin_generator('local_entities');
        $id = $gen->create_entities([
            'name' => $name,
            'shortname' => strtolower(str_replace(' ', '', $name)),
            'parentid' => $parentid,
        ]);
        if ($sortorder !== null) {
            $DB->set_field('local_entities', 'sortorder', $sortorder, ['id' => $id]);
        }
        return $id;
    }

    /**
     * get_descendant_ids returns the whole subtree (self + all descendants, any depth) and behaves
     * identically whether it uses the shared request map or an explicitly passed map.
     *
     * @return void
     */
    public function test_get_descendant_ids_subtree(): void {
        $this->resetAfterTest();

        // Root → Building → Floor → Room (+ Room2 sibling); Root → BuildingB; separate Root2.
        $root = $this->make_entity('Root');
        $building = $this->make_entity('Building', $root);
        $floor = $this->make_entity('Floor', $building);
        $room = $this->make_entity('Room', $floor);
        $room2 = $this->make_entity('Room2', $floor);
        $buildingb = $this->make_entity('BuildingB', $root);
        $root2 = $this->make_entity('Root2');

        $this->assertEqualsCanonicalizing(
            [$root, $building, $floor, $room, $room2, $buildingb],
            entities::get_descendant_ids($root)
        );
        $this->assertEqualsCanonicalizing(
            [$building, $floor, $room, $room2],
            entities::get_descendant_ids($building)
        );
        // A leaf resolves to just itself.
        $this->assertSame([$room], entities::get_descendant_ids($room));
        // A separate root is not part of another root's subtree.
        $this->assertSame([$root2], entities::get_descendant_ids($root2));

        // Batch == single: passing the shared map explicitly gives the same answer.
        $map = entities::get_entity_map();
        $this->assertEqualsCanonicalizing(
            entities::get_descendant_ids($building),
            entities::get_descendant_ids($building, $map)
        );
    }

    /**
     * get_descendant_ids is robust to invalid input: id <= 0 and unknown/removed ids yield an empty set.
     *
     * @return void
     */
    public function test_get_descendant_ids_invalid(): void {
        $this->resetAfterTest();
        $this->make_entity('Root');

        $this->assertSame([], entities::get_descendant_ids(0));
        $this->assertSame([], entities::get_descendant_ids(-5));
        $this->assertSame([], entities::get_descendant_ids(9999999));
    }

    /**
     * A broken tree must never hang: a parent cycle is guarded and yields a finite set.
     *
     * @return void
     */
    public function test_get_descendant_ids_cycle_guarded(): void {
        global $DB;
        $this->resetAfterTest();

        $a = $this->make_entity('A');
        $b = $this->make_entity('B', $a);
        // Introduce a cycle: A's parent becomes B (A → B → A).
        $DB->set_field('local_entities', 'parentid', $b, ['id' => $a]);
        entities::reset_caches();

        $result = entities::get_descendant_ids($a);
        sort($result);
        $this->assertSame([$a, $b], $result);
    }

    /**
     * An entity whose parentid points at a non-existent parent is treated as a subtree root and still
     * returns its own descendants.
     *
     * @return void
     */
    public function test_get_descendant_ids_missing_parent(): void {
        global $DB;
        $this->resetAfterTest();

        $orphan = $this->make_entity('Orphan');
        $child = $this->make_entity('Child', $orphan);
        $DB->set_field('local_entities', 'parentid', 9999999, ['id' => $orphan]);
        entities::reset_caches();

        $this->assertEqualsCanonicalizing([$orphan, $child], entities::get_descendant_ids($orphan));
    }

    /**
     * get_ancestor_path returns the lineage root-first including self, with the correct depth, at
     * several tree depths.
     *
     * @return void
     */
    public function test_get_ancestor_path(): void {
        $this->resetAfterTest();

        $root = $this->make_entity('Root');
        $building = $this->make_entity('Building', $root);
        $floor = $this->make_entity('Floor', $building);
        $room = $this->make_entity('Room', $floor);

        [$depth, $ids, $names] = entities::get_ancestor_path($room);
        $this->assertSame(3, $depth);
        $this->assertSame([$root, $building, $floor, $room], $ids);
        $this->assertSame(['Root', 'Building', 'Floor', 'Room'], $names);

        [$rootdepth, $rootids, $rootnames] = entities::get_ancestor_path($root);
        $this->assertSame(0, $rootdepth);
        $this->assertSame([$root], $rootids);
        $this->assertSame(['Root'], $rootnames);
    }

    /**
     * get_filter_tree keeps only nodes whose subtree contains options ("occupied" nodes plus the
     * ancestors that connect them), and aggregates counts up the tree.
     *
     * @return void
     */
    public function test_get_filter_tree_occupied_only_with_counts(): void {
        $this->resetAfterTest();

        // Sortorder set so the root order is deterministic (Root before Empty).
        $root = $this->make_entity('Root', 0, 1);
        $building = $this->make_entity('Building', $root, 1);
        $floor = $this->make_entity('Floor', $building, 1);
        $room = $this->make_entity('Room', $floor, 1);
        $buildingb = $this->make_entity('BuildingB', $root, 2);
        $emptyroot = $this->make_entity('EmptyRoot', 0, 2);

        // Result set: two options at Room, one at BuildingB, none anywhere under EmptyRoot.
        $tree = entities::get_filter_tree([$room, $room, $buildingb]);

        // Only the occupied root (Root) survives; EmptyRoot is pruned.
        $this->assertCount(1, $tree);
        $rootnode = $tree[0];
        $this->assertSame($root, $rootnode->id);
        $this->assertSame(0, $rootnode->count);
        $this->assertSame(3, $rootnode->total);

        // Root has two occupied children: Building (→Floor→Room, total 2) and BuildingB (total 1).
        $childrenbyid = [];
        foreach ($rootnode->children as $child) {
            $childrenbyid[$child->id] = $child;
        }
        $this->assertArrayHasKey($building, $childrenbyid);
        $this->assertArrayHasKey($buildingb, $childrenbyid);
        $this->assertSame(2, $childrenbyid[$building]->total);
        $this->assertSame(1, $childrenbyid[$buildingb]->total);
        $this->assertSame(1, $childrenbyid[$buildingb]->count);

        // The Room leaf carries the direct count of 2.
        $floornode = $childrenbyid[$building]->children[0];
        $this->assertSame($floor, $floornode->id);
        $roomnode = $floornode->children[0];
        $this->assertSame($room, $roomnode->id);
        $this->assertSame(2, $roomnode->count);
        $this->assertSame(2, $roomnode->total);
        $this->assertSame([], $roomnode->children);
    }

    /**
     * An empty result set produces an empty tree (no nodes are "occupied").
     *
     * @return void
     */
    public function test_get_filter_tree_empty(): void {
        $this->resetAfterTest();
        $this->make_entity('Root');
        $this->assertSame([], entities::get_filter_tree([]));
    }
}
