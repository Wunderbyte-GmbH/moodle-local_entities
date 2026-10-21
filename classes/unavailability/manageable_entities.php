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

use local_entities\local\views\entity_types;
use local_entities\persons;
use local_entities\unavailability_access;

/**
 * The entities whose unavailability a user may manage, for a selection.
 *
 * Which entity a user may manage is decided by unavailability_access, this class only collects the
 * entities and orders them. Persons of deleted users are left out.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class manageable_entities {
    /**
     * Returns the entities the user may manage, grouped by type.
     *
     * @param int|null $userid null for the current user
     * @return array<string, array<int, string>> name of the type => [entity id => name], ordered by type
     *         (locations, equipment, persons) and by name within a type; types without entities are left out
     */
    public function find(?int $userid = null): array {
        global $DB;

        $namefields = \core_user\fields::for_name()->get_sql('u', false, '', '', false)->selects;
        $records = $DB->get_records_sql(
            "SELECT e.id, e.name, e.entitytype, e.userid, $namefields
               FROM {local_entities} e
          LEFT JOIN {user} u ON u.id = e.userid AND u.deleted = 0"
        );

        $names = [];
        $sortkeys = [];
        foreach ($records as $record) {
            $type = (string)($record->entitytype ?? '') ?: 'location';
            $isperson = $type === persons::TYPE;

            if ($isperson && $record->lastname === null) {
                continue;
            }
            $name = $isperson ? fullname($record) : (string)$record->name;
            if ($name === '' || !unavailability_access::can_view_entity($record, $userid)) {
                continue;
            }

            $names[$type][(int)$record->id] = $name;
            $sortkeys[$type][(int)$record->id] = $isperson ? $record->lastname . ' ' . $record->firstname : $name;
        }

        $grouped = [];
        foreach ($this->ordered_types(array_keys($names)) as $type) {
            \core_collator::asort($sortkeys[$type]);
            foreach (array_keys($sortkeys[$type]) as $id) {
                $grouped[entity_types::name($type)][$id] = $names[$type][$id];
            }
        }

        return $grouped;
    }

    /**
     * Orders the types: the known ones in their usual order, unknown ones after them.
     *
     * @param string[] $types
     * @return string[]
     */
    private function ordered_types(array $types): array {
        $known = array_values(array_intersect(entity_types::TYPES, $types));
        $unknown = array_values(array_diff($types, entity_types::TYPES));
        sort($unknown);
        return array_merge($known, $unknown);
    }
}
