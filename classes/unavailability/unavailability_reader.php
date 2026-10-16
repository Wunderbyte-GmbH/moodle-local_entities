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

use local_entities\unavailability_access;

/**
 * Reads the unavailability of an entity for the page: the blocks and the series of a time window.
 *
 * Only blocks and series that apply everywhere are returned, as the page does not offer the scopes
 * of other plugins. The result is plain data, ready to be passed on by a web service.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class unavailability_reader {
    /** @var int Longest window that can be read at once, in days. */
    public const MAX_WINDOW_DAYS = 370;

    /** @var block_repository Storage of the single blocks. */
    private block_repository $blocks;

    /** @var series_repository Storage of the series. */
    private series_repository $series;

    /**
     * Constructor.
     *
     * @param block_repository|null $blocks the default one if not given
     * @param series_repository|null $series the default one if not given
     */
    public function __construct(?block_repository $blocks = null, ?series_repository $series = null) {
        $this->blocks = $blocks ?? new block_repository();
        $this->series = $series ?? new series_repository($this->blocks);
    }

    /**
     * Reads the blocks and series of an entity in a time window.
     *
     * @param int $entityid
     * @param int $from start of the window (unix timestamp)
     * @param int $until end of the window (unix timestamp)
     * @return array blocks (list of id, starttime, endtime, reason, seriesid; seriesid is 0 for single
     *               blocks) and series (list of id, weekdays, allday, daystart, dayend, validfrom,
     *               validuntil, timezone, reason) that are not over at the start of the window
     * @throws \required_capability_exception if the user may not manage the entity
     * @throws \invalid_parameter_exception if the window is empty or too long
     */
    public function read(int $entityid, int $from, int $until): array {
        global $DB;

        if ($until <= $from || $until - $from > self::MAX_WINDOW_DAYS * DAYSECS) {
            throw new \invalid_parameter_exception(
                'The window must be longer than zero and at most ' . self::MAX_WINDOW_DAYS . ' days.'
            );
        }

        $entity = $DB->get_record('local_entities', ['id' => $entityid], '*', MUST_EXIST);
        unavailability_access::require_manage_entity($entity);

        $filter = scope_filter::global_and([]);

        $blocks = [];
        foreach ($this->blocks->find($entityid, $from, $until, $filter) as $block) {
            $blocks[] = [
                'id' => (int)$block->id,
                'starttime' => (int)$block->starttime,
                'endtime' => (int)$block->endtime,
                'reason' => (string)($block->reason ?? ''),
                'seriesid' => (int)($block->seriesid ?? 0),
            ];
        }

        $series = [];
        foreach ($this->series->find($entityid, $filter, $from) as $record) {
            $series[] = ['id' => (int)$record->id] + $this->rule_data(series_rule::from_record($record));
        }

        return ['blocks' => $blocks, 'series' => $series];
    }

    /**
     * The rule as plain data.
     *
     * @param series_rule $rule
     * @return array weekdays, allday, daystart, dayend, validfrom, validuntil, timezone, reason
     */
    private function rule_data(series_rule $rule): array {
        return [
            'weekdays' => $rule->weekdays,
            'allday' => $rule->allday,
            'daystart' => $rule->daystart,
            'dayend' => $rule->dayend,
            'validfrom' => $rule->validfrom,
            'validuntil' => $rule->validuntil,
            'timezone' => $rule->timezone,
            'reason' => $rule->reason,
        ];
    }
}
