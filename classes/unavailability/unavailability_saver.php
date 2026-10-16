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
 * Saves the changes a user made on the unavailability page.
 *
 * The saver checks the right to manage the entity, refuses series that belong to another entity and
 * looks for collisions with the occupations of the entity. If there is any collision nothing is
 * saved at all. Otherwise all changes are applied in one transaction. Blocks and series saved here
 * apply everywhere, scopes of other plugins are not offered by the page.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class unavailability_saver {
    /** @var block_repository Storage of the single blocks. */
    private block_repository $blocks;

    /** @var series_repository Storage of the series. */
    private series_repository $series;

    /** @var conflict_detector Finds occupations that collide with the changes. */
    private conflict_detector $detector;

    /**
     * Constructor.
     *
     * @param block_repository|null $blocks the default one if not given
     * @param series_repository|null $series the default one if not given
     * @param conflict_detector|null $detector the default one, reading the live occupations, if not given
     */
    public function __construct(
        ?block_repository $blocks = null,
        ?series_repository $series = null,
        ?conflict_detector $detector = null
    ) {
        $this->blocks = $blocks ?? new block_repository();
        $this->series = $series ?? new series_repository($this->blocks);
        $this->detector = $detector ?? new conflict_detector(new entities_occupation_source());
    }

    /**
     * Saves the changes for an entity.
     *
     * @param int $entityid
     * @param unavailability_changes $changes
     * @return array saved (bool) and conflicts (list as returned by conflict_detector::find(), empty if saved)
     * @throws \required_capability_exception if the user may not manage the entity
     * @throws \invalid_parameter_exception if a series belongs to another entity
     */
    public function save(int $entityid, unavailability_changes $changes): array {
        global $DB;

        $entity = $DB->get_record('local_entities', ['id' => $entityid], '*', MUST_EXIST);
        unavailability_access::require_manage_entity($entity);

        if ($changes->is_empty()) {
            return ['saved' => true, 'conflicts' => []];
        }

        $this->require_series_of_entity($entityid, array_merge(array_keys($changes->updateseries), $changes->deleteseries));

        $conflicts = $this->detector->find($entityid, $this->ranges_to_check($changes));
        if (!empty($conflicts)) {
            return ['saved' => false, 'conflicts' => $conflicts];
        }

        $scope = scope::everywhere();
        $transaction = $DB->start_delegated_transaction();
        $this->blocks->remove_by_ids($entityid, $changes->removeblockids);
        foreach ($changes->deleteseries as $seriesid) {
            $this->series->delete($seriesid);
        }
        foreach ($changes->updateseries as $seriesid => $rule) {
            $this->series->update($seriesid, $rule);
        }
        foreach ($changes->createseries as $rule) {
            $this->series->create($entityid, $rule, $scope);
        }
        foreach ($changes->addblocks as $range) {
            $this->blocks->add_merged($entityid, $scope, $range);
        }
        $transaction->allow_commit();

        return ['saved' => true, 'conflicts' => []];
    }

    /**
     * The time ranges the changes would block: new blocks and the days of new or changed series.
     *
     * Removing something can not collide with anything, so removals are not looked at. A changed series
     * only counts from today on, as the past stays as it was.
     *
     * @param unavailability_changes $changes
     * @return array list of ['starttime' => ..., 'endtime' => ...]
     */
    private function ranges_to_check(unavailability_changes $changes): array {
        $ranges = $changes->addblocks;
        foreach ($changes->createseries as $rule) {
            $ranges = array_merge($ranges, $rule->occurrences());
        }
        foreach ($changes->updateseries as $rule) {
            $ranges = array_merge($ranges, $rule->occurrences(time()));
        }

        return array_map(
            static fn(time_range $range): array => ['starttime' => $range->starttime, 'endtime' => $range->endtime],
            $ranges
        );
    }

    /**
     * Refuses series that do not belong to the entity.
     *
     * @param int $entityid
     * @param int[] $seriesids
     * @return void
     * @throws \invalid_parameter_exception
     */
    private function require_series_of_entity(int $entityid, array $seriesids): void {
        if (empty($seriesids)) {
            return;
        }
        $owned = array_map('intval', array_keys($this->series->find($entityid, scope_filter::unrestricted())));
        if (array_diff($seriesids, $owned)) {
            throw new \invalid_parameter_exception('A series does not belong to this entity.');
        }
    }
}
