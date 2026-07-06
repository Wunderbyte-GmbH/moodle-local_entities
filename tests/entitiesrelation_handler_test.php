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
 * Tests for the equipment-relation persistence of entitiesrelation_handler (entity-aware multi-save).
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \entitiesrelation_handler
 */
final class entitiesrelation_handler_test extends advanced_testcase {
    /** Synthetic owner of the relations under test. */
    private const COMPONENT = 'mod_booking';
    /** Synthetic area. */
    private const AREA = 'option';
    /** Synthetic instance (e.g. a booking option id) the equipment is attached to. */
    private const INSTANCEID = 4242;

    /**
     * Create an equipment entity and return its id.
     *
     * @param string $name
     * @return int
     */
    private function create_equipment(string $name): int {
        $gen = $this->getDataGenerator()->get_plugin_generator('local_entities');
        return (int)$gen->create_entities([
            'name' => $name,
            'shortname' => \core_text::strtolower(str_replace(' ', '', $name)),
            'entitytype' => 'equipment',
        ]);
    }

    /**
     * Map a relation result set to entityid => quantity.
     *
     * @param array $relations
     * @return array<int,int>
     */
    private function quantities(array $relations): array {
        $map = [];
        foreach ($relations as $rel) {
            $map[(int)$rel->entityid] = (int)($rel->quantity ?? 0);
        }
        return $map;
    }

    /**
     * Saving equipment relations persists one relation per equipment with its quantity.
     */
    public function test_save_and_get_equipment_relations(): void {
        $this->resetAfterTest();
        $eq1 = $this->create_equipment('Beamer');
        $eq2 = $this->create_equipment('Whiteboard');

        $handler = new entitiesrelation_handler(self::COMPONENT, self::AREA);
        $handler->save_equipment_relations(self::INSTANCEID, [$eq1 => 2, $eq2 => 3]);

        $got = $this->quantities($handler->get_equipment_relations(self::INSTANCEID));
        $this->assertCount(2, $got);
        $this->assertSame(2, $got[$eq1] ?? null);
        $this->assertSame(3, $got[$eq2] ?? null);
    }

    /**
     * Re-saving updates changed quantities, removes de-selected equipment, and inserts new ones
     * (entity-aware multi-relation sync) — never duplicating relations.
     */
    public function test_resave_updates_removes_and_inserts(): void {
        $this->resetAfterTest();
        $eq1 = $this->create_equipment('Beamer');
        $eq2 = $this->create_equipment('Whiteboard');
        $eq3 = $this->create_equipment('Mikrofon');

        $handler = new entitiesrelation_handler(self::COMPONENT, self::AREA);
        $handler->save_equipment_relations(self::INSTANCEID, [$eq1 => 2, $eq2 => 3]);

        // Change eq1 quantity, drop eq2, add eq3.
        $handler->save_equipment_relations(self::INSTANCEID, [$eq1 => 5, $eq3 => 1]);
        $got = $this->quantities($handler->get_equipment_relations(self::INSTANCEID));
        $this->assertSame([$eq1 => 5, $eq3 => 1], $got);

        // Empty set removes all equipment relations.
        $handler->save_equipment_relations(self::INSTANCEID, []);
        $this->assertSame([], $handler->get_equipment_relations(self::INSTANCEID));
    }

    /**
     * Quantities <= 0 and invalid ids are ignored on save.
     */
    public function test_save_ignores_invalid_entries(): void {
        $this->resetAfterTest();
        $eq1 = $this->create_equipment('Beamer');

        $handler = new entitiesrelation_handler(self::COMPONENT, self::AREA);
        $handler->save_equipment_relations(self::INSTANCEID, [$eq1 => 2, 0 => 9, $eq1 + 999 => 0]);

        $got = $this->quantities($handler->get_equipment_relations(self::INSTANCEID));
        $this->assertSame([$eq1 => 2], $got);
    }

    /**
     * get_equipment_relations returns ONLY entities of type 'equipment', never location/other relations.
     */
    public function test_get_equipment_relations_excludes_non_equipment(): void {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator()->get_plugin_generator('local_entities');
        $equipment = $this->create_equipment('Beamer');
        $location = (int)$gen->create_entities(['name' => 'Room 1', 'shortname' => 'room1']); // default (non-equipment) type.

        $handler = new entitiesrelation_handler(self::COMPONENT, self::AREA);
        $handler->save_equipment_relations(self::INSTANCEID, [$equipment => 1, $location => 1]);

        $got = $this->quantities($handler->get_equipment_relations(self::INSTANCEID));
        $this->assertArrayHasKey($equipment, $got);
        $this->assertArrayNotHasKey($location, $got, 'Non-equipment entities must not be returned as equipment.');
    }

    /**
     * Equipment relations are scoped to (component, area, instanceid) — a different instance is unaffected.
     */
    public function test_relations_are_instance_scoped(): void {
        $this->resetAfterTest();
        $eq1 = $this->create_equipment('Beamer');

        $handler = new entitiesrelation_handler(self::COMPONENT, self::AREA);
        $handler->save_equipment_relations(self::INSTANCEID, [$eq1 => 2]);

        $this->assertCount(1, $handler->get_equipment_relations(self::INSTANCEID));
        $this->assertCount(0, $handler->get_equipment_relations(self::INSTANCEID + 1));
    }
}
