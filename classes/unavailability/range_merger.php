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
 * Merges time ranges that overlap or touch each other.
 *
 * Only ranges of the same group are merged, which means the same reason and the same series. It does
 * not touch the database, so it can be tested and reused on its own.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class range_merger {
    /**
     * Merges overlapping and directly adjacent ranges of the same group.
     *
     * Ranges without a length are dropped.
     *
     * @param time_range[] $ranges
     * @return time_range[] merged ranges, ordered by start time
     */
    public static function merge(array $ranges): array {
        $groups = [];
        foreach ($ranges as $range) {
            if ($range->has_length()) {
                $groups[$range->group_key()][] = $range;
            }
        }

        $merged = [];
        foreach ($groups as $group) {
            usort($group, static fn(time_range $a, time_range $b) => $a->starttime <=> $b->starttime);

            $current = null;
            foreach ($group as $range) {
                if ($current !== null && $range->starttime <= $current->endtime) {
                    $current = $current->with_end(max($current->endtime, $range->endtime));
                    continue;
                }
                if ($current !== null) {
                    $merged[] = $current;
                }
                $current = $range;
            }
            if ($current !== null) {
                $merged[] = $current;
            }
        }

        usort($merged, static fn(time_range $a, time_range $b) => [$a->starttime, $a->endtime] <=> [$b->starttime, $b->endtime]);

        return $merged;
    }
}
