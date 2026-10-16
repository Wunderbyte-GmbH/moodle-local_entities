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
 * Storage of the series of an unavailability (table local_entities_unavail_series) and of the
 * single blocks that belong to a series.
 *
 * What a rule means is decided by series_rule, where blocks are stored by block_repository. This
 * class connects the two: it stores a rule and keeps the blocks of the series in line with it.
 * Changing or deleting a series affects the blocks from the start of today on, the past stays.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class series_repository {
    /** @var string Table of the series. */
    private const TABLE = 'local_entities_unavail_series';

    /** @var block_repository Storage of the single blocks. */
    private block_repository $blocks;

    /**
     * Constructor.
     *
     * @param block_repository|null $blocks storage of the blocks, the default one if not given
     */
    public function __construct(?block_repository $blocks = null) {
        $this->blocks = $blocks ?? new block_repository();
    }

    /**
     * Creates a series: stores its rule and the single blocks the rule describes.
     *
     * @param int $entityid
     * @param series_rule $rule
     * @param scope $scope
     * @return int id of the series
     */
    public function create(int $entityid, series_rule $rule, scope $scope): int {
        global $DB, $USER;

        if ($entityid <= 0) {
            throw new \coding_exception('An entity id is required.');
        }

        $now = time();
        $transaction = $DB->start_delegated_transaction();
        $seriesid = (int)$DB->insert_record(self::TABLE, (object)([
            'entityid' => $entityid,
            'component' => $scope->component,
            'area' => $scope->area,
            'instanceid' => $scope->instanceid,
            'timecreated' => $now,
            'timemodified' => $now,
            'usermodified' => (int)($USER->id ?? 0),
        ] + $rule->to_record_fields()));

        $this->blocks->add($entityid, $scope, $rule->occurrences(0, $seriesid));
        $transaction->allow_commit();

        return $seriesid;
    }

    /**
     * Changes the rule of a series.
     *
     * From the start of today on (in the timezone of the rule) the blocks of the series are replaced
     * by the blocks of the new rule. What lies in the past stays as it was. The entity and the scope
     * of a series can not be changed.
     *
     * @param int $seriesid
     * @param series_rule $rule
     * @return void
     */
    public function update(int $seriesid, series_rule $rule): void {
        global $DB, $USER;

        $series = $DB->get_record(self::TABLE, ['id' => $seriesid], '*', MUST_EXIST);
        $scope = scope::of((string)$series->component, (string)$series->area, (int)$series->instanceid);
        $today = $this->start_of_today($rule->timezone);

        $transaction = $DB->start_delegated_transaction();
        $this->blocks->remove_series_from($seriesid, $today);
        $DB->update_record(self::TABLE, (object)([
            'id' => $seriesid,
            'timemodified' => time(),
            'usermodified' => (int)($USER->id ?? 0),
        ] + $rule->to_record_fields()));
        $this->blocks->add((int)$series->entityid, $scope, $rule->occurrences($today, $seriesid));
        $transaction->allow_commit();
    }

    /**
     * Deletes a series from today on.
     *
     * The blocks from the start of today on (in the timezone of the series) are removed, what lies in
     * the past stays. If no block stays, the series is deleted completely, otherwise it ends yesterday.
     *
     * @param int $seriesid
     * @return void
     */
    public function delete(int $seriesid): void {
        global $DB, $USER;

        $series = $DB->get_record(self::TABLE, ['id' => $seriesid], '*', MUST_EXIST);
        $today = $this->start_of_today((string)$series->timezone);

        $transaction = $DB->start_delegated_transaction();
        $this->blocks->remove_series_from($seriesid, $today);
        if ($this->blocks->series_has_blocks($seriesid)) {
            $yesterday = (new \DateTimeImmutable('@' . $today))
                ->setTimezone(new \DateTimeZone((string)$series->timezone))
                ->modify('-1 day')
                ->getTimestamp();
            $DB->update_record(self::TABLE, (object)[
                'id' => $seriesid,
                'validuntil' => min((int)$series->validuntil, $yesterday),
                'timemodified' => time(),
                'usermodified' => (int)($USER->id ?? 0),
            ]);
        } else {
            $DB->delete_records(self::TABLE, ['id' => $seriesid]);
        }
        $transaction->allow_commit();
    }

    /**
     * Returns the series of an entity.
     *
     * @param int $entityid
     * @param scope_filter $filter which scopes are selected
     * @param int|null $activefrom if set, only series whose last day is not before this time
     * @return \stdClass[] series ordered by first day, keyed by id
     */
    public function find(int $entityid, scope_filter $filter, ?int $activefrom = null): array {
        global $DB;

        if ($entityid <= 0) {
            return [];
        }

        [$scopesql, $scopeparams] = $filter->to_sql('s', 'sc');
        $params = ['entityid' => $entityid] + $scopeparams;
        $activesql = '1 = 1';
        if ($activefrom !== null) {
            $activesql = 's.validuntil >= :activefrom';
            $params['activefrom'] = $activefrom;
        }

        return $DB->get_records_sql(
            "SELECT s.*
               FROM {" . self::TABLE . "} s
              WHERE s.entityid = :entityid
                AND $activesql
                AND $scopesql
           ORDER BY s.validfrom, s.id",
            $params
        );
    }

    /**
     * Start of the current day in a timezone.
     *
     * @param string $timezone
     * @return int unix timestamp
     */
    private function start_of_today(string $timezone): int {
        return (new \DateTimeImmutable('now', new \DateTimeZone($timezone)))->setTime(0, 0)->getTimestamp();
    }
}
