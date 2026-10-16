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

use local_entities\local\entities\entitydate;
use local_entities\persons;

/**
 * Tests for saving the changes of a user: rights, conflicts and applying everything at once.
 *
 * @package    local_entities
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_entities\unavailability\unavailability_saver
 */
final class unavailability_saver_test extends \advanced_testcase {
    /** @var int Fixed start of the test times, far in the future so no "today" logic gets in the way. */
    private const BASE = 1900000000;

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
     * A saver that takes its occupations from a fixed list instead of the booking plugin.
     *
     * @param entitydate[] $occupations
     * @return unavailability_saver
     */
    private function saver(array $occupations = []): unavailability_saver {
        $source = new class ($occupations) implements occupation_source {
            /** @var entitydate[] */
            private array $occupations;

            /**
             * Constructor.
             *
             * @param entitydate[] $occupations
             */
            public function __construct(array $occupations) {
                $this->occupations = $occupations;
            }

            /**
             * Returns the fixed occupations.
             *
             * @param int $entityid
             * @return array
             */
            public function get_occupations(int $entityid): array {
                return $this->occupations;
            }
        };

        return new unavailability_saver(null, null, new conflict_detector($source));
    }

    /**
     * Creates the person of a new user and returns [user, entity id].
     *
     * @return array
     */
    private function person(): array {
        $user = $this->getDataGenerator()->create_user();
        $this->setAdminUser();
        return [$user, persons::get_or_create_for_user((int)$user->id)];
    }

    /**
     * A rule for a series on Mondays, in the far future.
     *
     * @param array $overrides
     * @return array
     */
    private function rule(array $overrides = []): array {
        return $overrides + [
            'weekdays' => [1],
            'allday' => false,
            'daystart' => 8 * HOURSECS,
            'dayend' => 10 * HOURSECS,
            'validfrom' => $this->t(0),
            'validuntil' => $this->t(24 * 20),
            'timezone' => 'UTC',
            'reason' => '',
        ];
    }

    /**
     * Number of blocks and series of an entity: [blocks, series].
     *
     * @param int $entityid
     * @return int[]
     */
    private function counts(int $entityid): array {
        global $DB;
        return [
            $DB->count_records('local_entities_unavailability', ['entityid' => $entityid]),
            $DB->count_records('local_entities_unavail_series', ['entityid' => $entityid]),
        ];
    }

    /**
     * New single blocks are stored and merged with their neighbours, listed blocks are removed.
     *
     * @return void
     */
    public function test_adds_merges_and_removes_single_blocks(): void {
        global $DB;
        $this->resetAfterTest();
        [$user, $entity] = $this->person();
        (new block_repository())->add($entity, scope::everywhere(), [
            new time_range($this->t(6), $this->t(8)),
            new time_range($this->t(50), $this->t(52)),
        ]);
        $remove = (int)$DB->get_field('local_entities_unavailability', 'id', ['starttime' => $this->t(50)]);
        $this->setUser($user);

        $result = $this->saver()->save($entity, unavailability_changes::from_array([
            'addblocks' => [['starttime' => $this->t(8), 'endtime' => $this->t(10)]],
            'removeblockids' => [$remove],
        ]));

        $this->assertTrue($result['saved']);
        $this->assertSame([], $result['conflicts']);
        $blocks = $DB->get_records('local_entities_unavailability', ['entityid' => $entity]);
        $this->assertCount(1, $blocks);
        $block = reset($blocks);
        $this->assertEquals($this->t(6), $block->starttime);
        $this->assertEquals($this->t(10), $block->endtime);
    }

    /**
     * Series are created, changed and deleted, each with the blocks it describes.
     *
     * @return void
     */
    public function test_creates_changes_and_deletes_series(): void {
        global $DB;
        $this->resetAfterTest();
        [$user, $entity] = $this->person();
        $repository = new series_repository();
        $tochange = $repository->create($entity, series_rule::from_array($this->rule()), scope::everywhere());
        $todelete = $repository->create($entity, series_rule::from_array($this->rule(['reason' => 'gone'])), scope::everywhere());
        $this->setUser($user);

        $result = $this->saver()->save($entity, unavailability_changes::from_array([
            'createseries' => [$this->rule(['reason' => 'new'])],
            'updateseries' => [[
                'id' => $tochange,
                'rule' => $this->rule(['daystart' => 14 * HOURSECS, 'dayend' => 16 * HOURSECS]),
            ]],
            'deleteseries' => [$todelete],
        ]));

        $this->assertTrue($result['saved']);
        $this->assertFalse($DB->record_exists('local_entities_unavail_series', ['id' => $todelete]));
        $this->assertSame(1, $DB->count_records('local_entities_unavail_series', ['entityid' => $entity, 'reason' => 'new']));
        $this->assertSame(2, $DB->count_records('local_entities_unavail_series', ['entityid' => $entity]));
        $changed = $DB->get_record('local_entities_unavail_series', ['id' => $tochange], '*', MUST_EXIST);
        $this->assertEquals(14 * HOURSECS, $changed->daystart);
        $this->assertGreaterThan(0, $DB->count_records('local_entities_unavailability', ['seriesid' => $tochange]));
        $this->assertSame(0, $DB->count_records('local_entities_unavailability', ['seriesid' => $todelete]));
    }

    /**
     * If the person is assigned somewhere in the time of a change, nothing at all is saved and the
     * assignments are returned.
     *
     * @return void
     */
    public function test_conflict_saves_nothing(): void {
        $this->resetAfterTest();
        [$user, $entity] = $this->person();
        (new block_repository())->add($entity, scope::everywhere(), [new time_range($this->t(100), $this->t(102))]);
        $before = $this->counts($entity);
        $occupation = new entitydate(5, 'mod_booking', 'optiondate', 'Exam 5', $this->t(9), $this->t(11), 0);
        $this->setUser($user);

        $result = $this->saver([$occupation])->save($entity, unavailability_changes::from_array([
            'addblocks' => [['starttime' => $this->t(8), 'endtime' => $this->t(10)]],
            'createseries' => [$this->rule()],
        ]));

        $this->assertFalse($result['saved']);
        $this->assertCount(1, $result['conflicts']);
        $this->assertSame('Exam 5', $result['conflicts'][0]['name']);
        $this->assertSame($before, $this->counts($entity));
    }

    /**
     * Without the right to manage the entity nothing is saved.
     *
     * @return void
     */
    public function test_without_the_right_nothing_is_saved(): void {
        $this->resetAfterTest();
        [, $entity] = $this->person();
        $stranger = $this->getDataGenerator()->create_user();
        $this->setUser($stranger);
        $before = $this->counts($entity);

        try {
            $this->saver()->save($entity, unavailability_changes::from_array([
                'addblocks' => [['starttime' => $this->t(8), 'endtime' => $this->t(10)]],
            ]));
            $this->fail('The save should have been refused.');
        } catch (\required_capability_exception $e) {
            $this->assertSame($before, $this->counts($entity));
        }
    }

    /**
     * A series of another entity can neither be changed nor deleted, and nothing else is saved then.
     *
     * @return void
     */
    public function test_foreign_series_is_refused(): void {
        $this->resetAfterTest();
        [$user, $entity] = $this->person();
        [, $other] = $this->person();
        $foreign = (new series_repository())->create($other, series_rule::from_array($this->rule()), scope::everywhere());
        $beforeother = $this->counts($other);
        $beforemine = $this->counts($entity);
        $this->setUser($user);

        try {
            $this->saver()->save($entity, unavailability_changes::from_array([
                'addblocks' => [['starttime' => $this->t(8), 'endtime' => $this->t(10)]],
                'deleteseries' => [$foreign],
            ]));
            $this->fail('The save should have been refused.');
        } catch (\invalid_parameter_exception $e) {
            $this->assertSame($beforeother, $this->counts($other));
            $this->assertSame($beforemine, $this->counts($entity));
        }
    }

    /**
     * Nothing to change is a successful save that does nothing.
     *
     * @return void
     */
    public function test_empty_changes_are_saved_without_effect(): void {
        $this->resetAfterTest();
        [$user, $entity] = $this->person();
        $this->setUser($user);

        $result = $this->saver()->save($entity, unavailability_changes::from_array([]));

        $this->assertTrue($result['saved']);
        $this->assertSame([0, 0], $this->counts($entity));
    }
}
