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
 * The highest number of planned slots per day an entity takes on.
 *
 * Every type of entity can have a limit, empty means no limit. Entities only stores the number, planners
 * like the exam planning count the slots of a day themselves. Whoever may see the unavailability of an
 * entity may change its limit, also when the self-editing of the type is locked.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class daily_limit {
    /** @var int Highest limit that can be entered. */
    public const MAX = 99;

    /** @var string Setting of the limit for entities without an own one, empty or 0 for no limit. */
    public const CONFIG_DEFAULT = 'dailylimit_default';

    /**
     * The limits that apply to several entities: the own one, or the default if there is none.
     *
     * @param int[] $entityids
     * @return array entity id => limit, null for no limit; unknown entities are left out
     */
    public static function get(array $entityids): array {
        $default = self::get_default();
        return array_map(static fn(?int $own): ?int => $own ?? $default, self::get_own($entityids));
    }

    /**
     * The own limits of several entities, with one query, without the default.
     *
     * @param int[] $entityids
     * @return array entity id => own limit, null if the entity has none; unknown entities are left out
     */
    public static function get_own(array $entityids): array {
        global $DB;

        $ids = array_values(array_unique(array_filter(array_map('intval', $entityids))));
        if (empty($ids)) {
            return [];
        }
        [$insql, $params] = $DB->get_in_or_equal($ids);
        $records = $DB->get_records_select('local_entities', "id $insql", $params, '', 'id, maxslotsperday');

        $limits = [];
        foreach ($records as $record) {
            $limits[(int)$record->id] = $record->maxslotsperday === null ? null : (int)$record->maxslotsperday;
        }
        return $limits;
    }

    /**
     * The limit for entities without an own one.
     *
     * @return int|null null for no limit
     */
    public static function get_default(): ?int {
        $default = (int)get_config('local_entities', self::CONFIG_DEFAULT);
        return $default >= 1 && $default <= self::MAX ? $default : null;
    }

    /**
     * May the user change the limit of the entity? The lock of the type does not matter.
     *
     * @param \stdClass $entity record of local_entities with entitytype and userid
     * @param int|null $userid null for the current user
     * @return bool
     */
    public static function can_change(\stdClass $entity, ?int $userid = null): bool {
        return unavailability_access::can_view_entity($entity, $userid);
    }

    /**
     * Stores the limit of an entity.
     *
     * @param int $entityid
     * @param int|null $limit 1 to MAX, null for no limit
     * @return void
     * @throws \required_capability_exception if the user may not change it
     * @throws \invalid_parameter_exception if the limit is out of range
     */
    public static function set(int $entityid, ?int $limit): void {
        global $DB;

        $entity = $DB->get_record('local_entities', ['id' => $entityid], '*', MUST_EXIST);
        unavailability_access::require_view_entity($entity);
        if ($limit !== null && ($limit < 1 || $limit > self::MAX)) {
            throw new \invalid_parameter_exception('The limit must be between 1 and ' . self::MAX . '.');
        }
        $DB->set_field('local_entities', 'maxslotsperday', $limit, ['id' => $entityid]);
    }
}
