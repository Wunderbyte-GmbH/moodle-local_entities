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
 * Finds the occupations of an entity that collide with time ranges.
 *
 * Used to refuse an unavailability while the person is already assigned to something. Where the
 * occupations come from is decided by the source that is passed in.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class conflict_detector {
    /** @var occupation_source Where the occupations come from. */
    private occupation_source $source;

    /**
     * Constructor.
     *
     * @param occupation_source $source
     */
    public function __construct(occupation_source $source) {
        $this->source = $source;
    }

    /**
     * Returns the occupations of an entity that overlap one of the given time ranges.
     *
     * Cancelled occupations (status 1) do not count. Occupations that only touch a range do not
     * overlap it. An occupation that overlaps several ranges is returned once. Ranges that end
     * before they start, or have no length, are ignored.
     *
     * @param int $entityid
     * @param array $ranges list of ['starttime' => ..., 'endtime' => ...]
     * @return array list of ['starttime', 'endtime', 'name', 'url', 'component', 'area', 'itemid'],
     *               ordered by start time
     */
    public function find(int $entityid, array $ranges): array {
        if ($entityid <= 0 || empty($ranges)) {
            return [];
        }

        $conflicts = [];
        foreach ($this->source->get_occupations($entityid) as $date) {
            if ((int)($date->status ?? 0) === 1) {
                continue;
            }
            if (!$this->overlaps_any($date, $ranges)) {
                continue;
            }

            $conflicts[] = [
                'starttime' => (int)$date->starttime,
                'endtime' => (int)$date->endtime,
                'name' => (string)($date->name ?? ''),
                'url' => !empty($date->link) ? $date->link->out(false) : '',
                'component' => (string)($date->component ?? ''),
                'area' => (string)($date->area ?? ''),
                'itemid' => (int)($date->itemid ?? 0),
            ];
        }

        usort($conflicts, static fn($a, $b) => [$a['starttime'], $a['endtime']] <=> [$b['starttime'], $b['endtime']]);

        return $conflicts;
    }

    /**
     * Does an occupation overlap at least one of the ranges?
     *
     * @param object $date occupation with starttime and endtime
     * @param array $ranges list of ['starttime' => ..., 'endtime' => ...]
     * @return bool
     */
    private function overlaps_any(object $date, array $ranges): bool {
        foreach ($ranges as $range) {
            $start = (int)($range['starttime'] ?? 0);
            $end = (int)($range['endtime'] ?? 0);
            if ($end > $start && (int)$date->starttime < $end && (int)$date->endtime > $start) {
                return true;
            }
        }
        return false;
    }
}
