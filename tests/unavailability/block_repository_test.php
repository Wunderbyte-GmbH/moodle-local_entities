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

use local_entities\persons;

/**
 * Tests for removing blocks by id and for adding a single block that is merged with its neighbours.
 *
 * Times are hour offsets from a fixed base time, "8-10" is base + 8 hours to base + 10 hours.
 *
 * @package    local_entities
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_entities\unavailability\block_repository
 */
final class block_repository_test extends \advanced_testcase {
    /** @var int Fixed base time of all test ranges. */
    private const BASE = 1700000000;

    /**
     * Timestamp of an hour offset from the base time.
     *
     * @param int $hours
     * @return int
     */
    private function t(int $hours): int {
        return self::BASE + $hours * HOURSECS;
    }

    /**
     * Creates a person and returns its entity id.
     *
     * @return int
     */
    private function person(): int {
        $this->setAdminUser();
        return persons::get_or_create_for_user($this->getDataGenerator()->create_user()->id);
    }

    /**
     * Inserts a block directly.
     *
     * @param int $entityid
     * @param int $from hour offset
     * @param int $until hour offset
     * @param string $reason
     * @param int|null $seriesid
     * @param scope|null $scope
     * @return int id of the block
     */
    private function block(
        int $entityid,
        int $from,
        int $until,
        string $reason = '',
        ?int $seriesid = null,
        ?scope $scope = null
    ): int {
        global $DB;
        $scope = $scope ?? scope::everywhere();
        return (int)$DB->insert_record('local_entities_unavailability', (object)([
            'entityid' => $entityid,
            'starttime' => $this->t($from),
            'endtime' => $this->t($until),
            'reason' => $reason,
            'seriesid' => $seriesid,
            'timecreated' => 1,
            'timemodified' => 1,
            'usermodified' => 0,
        ] + $scope->to_array()));
    }

    /**
     * Describes the stored blocks of an entity: "8-10", "8-10#reason", "8-10~3" (series), "8-10@option7".
     *
     * @param int $entityid
     * @return string[]
     */
    private function stored(int $entityid): array {
        global $DB;
        $result = [];
        foreach ($DB->get_records('local_entities_unavailability', ['entityid' => $entityid], 'starttime, id') as $r) {
            $result[] = (($r->starttime - self::BASE) / HOURSECS) . '-' . (($r->endtime - self::BASE) / HOURSECS)
                . ((string)$r->reason !== '' ? '#' . $r->reason : '')
                . ($r->seriesid ? '~' . $r->seriesid : '')
                . (!empty($r->component) ? '@' . $r->area . $r->instanceid : '');
        }
        return $result;
    }

    /**
     * Only the blocks of the given entity and the given ids are removed, the number is returned.
     *
     * @return void
     */
    public function test_remove_by_ids_removes_only_blocks_of_the_entity(): void {
        $this->resetAfterTest();
        $mine = $this->person();
        $other = $this->person();
        $a = $this->block($mine, 8, 10);
        $this->block($mine, 12, 14);
        $foreign = $this->block($other, 8, 10);

        $removed = (new block_repository())->remove_by_ids($mine, [$a, $foreign, 99999]);

        $this->assertSame(1, $removed);
        $this->assertSame(['12-14'], $this->stored($mine));
        $this->assertSame(['8-10'], $this->stored($other));
        $this->assertSame(0, (new block_repository())->remove_by_ids($mine, []));
    }

    /**
     * A single block without neighbours is stored as it is.
     *
     * @return void
     */
    public function test_add_merged_stores_a_block_without_neighbours(): void {
        $this->resetAfterTest();
        $entity = $this->person();
        $this->block($entity, 20, 22);

        (new block_repository())->add_merged($entity, scope::everywhere(), new time_range($this->t(8), $this->t(10), 'a'));

        $this->assertSame(['8-10#a', '20-22'], $this->stored($entity));
    }

    /**
     * Overlapping and adjacent single blocks of the same reason become one, also over several blocks.
     *
     * @return void
     */
    public function test_add_merged_merges_overlapping_and_adjacent_neighbours(): void {
        $this->resetAfterTest();
        $entity = $this->person();
        $this->block($entity, 6, 8);
        $this->block($entity, 10, 12);
        $this->block($entity, 30, 32);

        (new block_repository())->add_merged($entity, scope::everywhere(), new time_range($this->t(8), $this->t(10)));

        $this->assertSame(['6-12', '30-32'], $this->stored($entity));
    }

    /**
     * Blocks of another reason, of a series or of another scope are neither merged nor touched.
     *
     * @return void
     */
    public function test_add_merged_leaves_other_groups_alone(): void {
        global $DB;
        $this->resetAfterTest();
        $entity = $this->person();
        $seriesid = (int)$DB->insert_record('local_entities_unavail_series', (object)[
            'entityid' => $entity, 'component' => '', 'area' => '', 'instanceid' => 0, 'weekdays' => '1', 'allday' => 1,
            'validfrom' => 1, 'validuntil' => 2, 'timezone' => 'UTC', 'reason' => '', 'timecreated' => 1, 'timemodified' => 1,
        ]);
        $this->block($entity, 8, 10, 'other');
        $this->block($entity, 8, 10, '', $seriesid);
        $this->block($entity, 8, 10, '', null, scope::of('mod_booking', 'option', 7));

        (new block_repository())->add_merged($entity, scope::everywhere(), new time_range($this->t(9), $this->t(11)));

        $this->assertSame(
            ['8-10#other', '8-10@option7', '8-10~' . $seriesid, '9-11'],
            $this->sorted($this->stored($entity))
        );
    }

    /**
     * Sorts descriptions so that the check does not depend on the order of blocks that start together.
     *
     * @param string[] $list
     * @return string[]
     */
    private function sorted(array $list): array {
        sort($list);
        return $list;
    }
}
