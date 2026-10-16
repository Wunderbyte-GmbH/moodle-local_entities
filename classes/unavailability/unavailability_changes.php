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
 * The changes the user made on the unavailability page, saved all at once.
 *
 * Only what changed is listed, not the whole window: new single blocks, blocks to remove, and series
 * to create, change or delete. Everything is checked when the object is created from the input, so
 * what reaches the saver is valid.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class unavailability_changes {
    /** @var int Most changes in one request, so one request can not create thousands of rows. */
    public const MAX_CHANGES = 500;

    /**
     * Constructor, use from_array() instead.
     *
     * @param time_range[] $addblocks new single blocks
     * @param int[] $removeblockids ids of blocks to remove
     * @param series_rule[] $createseries rules of new series
     * @param series_rule[] $updateseries new rules of existing series, keyed by series id
     * @param int[] $deleteseries ids of series to delete
     */
    private function __construct(
        public readonly array $addblocks,
        public readonly array $removeblockids,
        public readonly array $createseries,
        public readonly array $updateseries,
        public readonly array $deleteseries,
    ) {
    }

    /**
     * Checks the input and builds the changes.
     *
     * @param array $data addblocks (list of starttime, endtime, reason), removeblockids, createseries
     *                    (list of rules), updateseries (list of id and rule), deleteseries (list of ids)
     * @return self
     * @throws \invalid_parameter_exception if something is not valid
     */
    public static function from_array(array $data): self {
        $addblocks = [];
        foreach ((array)($data['addblocks'] ?? []) as $block) {
            $start = (int)($block['starttime'] ?? 0);
            $end = (int)($block['endtime'] ?? 0);
            if ($start <= 0 || $end <= $start) {
                throw new \invalid_parameter_exception('A block needs a start and an end after it.');
            }
            $addblocks[] = new time_range($start, $end, \core_text::substr(trim((string)($block['reason'] ?? '')), 0, 255));
        }

        $createseries = [];
        foreach ((array)($data['createseries'] ?? []) as $rule) {
            $createseries[] = series_rule::from_array((array)$rule);
        }

        $updateseries = [];
        foreach ((array)($data['updateseries'] ?? []) as $item) {
            $seriesid = (int)($item['id'] ?? 0);
            if ($seriesid <= 0) {
                throw new \invalid_parameter_exception('A series to change needs its id.');
            }
            $updateseries[$seriesid] = series_rule::from_array((array)($item['rule'] ?? []));
        }

        $removeblockids = self::ids($data['removeblockids'] ?? []);
        $deleteseries = self::ids($data['deleteseries'] ?? []);

        if (array_intersect($deleteseries, array_keys($updateseries))) {
            throw new \invalid_parameter_exception('A series can not be changed and deleted at once.');
        }

        $count = count($addblocks) + count($removeblockids) + count($createseries) + count($updateseries) + count($deleteseries);
        if ($count > self::MAX_CHANGES) {
            throw new \invalid_parameter_exception('Too many changes at once, at most ' . self::MAX_CHANGES . ' are allowed.');
        }

        return new self($addblocks, $removeblockids, $createseries, $updateseries, $deleteseries);
    }

    /**
     * Is there nothing to do?
     *
     * @return bool
     */
    public function is_empty(): bool {
        return empty($this->addblocks) && empty($this->removeblockids) && empty($this->createseries)
            && empty($this->updateseries) && empty($this->deleteseries);
    }

    /**
     * Turns a list into unique positive integers.
     *
     * @param array $ids
     * @return int[]
     */
    private static function ids(array $ids): array {
        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }
}
