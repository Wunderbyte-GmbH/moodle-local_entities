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
 * Tests for the list of changes that is saved at once.
 *
 * @package    local_entities
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_entities\unavailability\unavailability_changes
 */
final class unavailability_changes_test extends \basic_testcase {
    /**
     * A rule as the interface sends it.
     *
     * @param array $overrides
     * @return array
     */
    private function rule(array $overrides = []): array {
        return $overrides + [
            'weekdays' => [1, 3],
            'allday' => false,
            'daystart' => 8 * HOURSECS,
            'dayend' => 10 * HOURSECS,
            'validfrom' => 1792000000,
            'validuntil' => 1793000000,
            'timezone' => 'Europe/Vienna',
            'reason' => '',
        ];
    }

    /**
     * Nothing given means nothing to do.
     *
     * @return void
     */
    public function test_empty_input_is_empty(): void {
        $changes = unavailability_changes::from_array([]);

        $this->assertTrue($changes->is_empty());
        $this->assertSame([], $changes->addblocks);
        $this->assertSame([], $changes->removeblockids);
        $this->assertSame([], $changes->createseries);
        $this->assertSame([], $changes->updateseries);
        $this->assertSame([], $changes->deleteseries);
    }

    /**
     * All parts are read and turned into objects.
     *
     * @return void
     */
    public function test_all_parts_are_read(): void {
        $changes = unavailability_changes::from_array([
            'addblocks' => [['starttime' => 1000, 'endtime' => 2000, 'reason' => ' Exam ']],
            'removeblockids' => ['5', 7, 7, 0],
            'createseries' => [$this->rule()],
            'updateseries' => [['id' => 9, 'rule' => $this->rule(['weekdays' => [2]])]],
            'deleteseries' => [11, '12'],
        ]);

        $this->assertFalse($changes->is_empty());
        $this->assertCount(1, $changes->addblocks);
        $this->assertSame(1000, $changes->addblocks[0]->starttime);
        $this->assertSame(2000, $changes->addblocks[0]->endtime);
        $this->assertSame('Exam', $changes->addblocks[0]->reason);
        $this->assertSame(0, $changes->addblocks[0]->seriesid);
        $this->assertSame([5, 7], $changes->removeblockids);
        $this->assertCount(1, $changes->createseries);
        $this->assertSame([1, 3], $changes->createseries[0]->weekdays);
        $this->assertSame([9], array_keys($changes->updateseries));
        $this->assertSame([2], $changes->updateseries[9]->weekdays);
        $this->assertSame([11, 12], $changes->deleteseries);
    }

    /**
     * Input that makes no sense is rejected as a whole.
     *
     * @dataProvider invalid_input_provider
     * @param array $input
     * @return void
     */
    public function test_invalid_input_is_rejected(array $input): void {
        $this->expectException(\invalid_parameter_exception::class);
        unavailability_changes::from_array($input);
    }

    /**
     * Invalid input.
     *
     * @return array
     */
    public static function invalid_input_provider(): array {
        $badrule = ['weekdays' => [], 'validfrom' => 1792000000, 'validuntil' => 1793000000];
        $goodrule = ['weekdays' => [1], 'allday' => true, 'validfrom' => 1792000000, 'validuntil' => 1793000000];
        return [
            'block ends before it starts' => [['addblocks' => [['starttime' => 2000, 'endtime' => 1000]]]],
            'block without length' => [['addblocks' => [['starttime' => 1000, 'endtime' => 1000]]]],
            'block without times' => [['addblocks' => [['reason' => 'x']]]],
            'series with an invalid rule' => [['createseries' => [$badrule]]],
            'series update without id' => [['updateseries' => [['rule' => $badrule]]]],
            'series update with an invalid rule' => [['updateseries' => [['id' => 3, 'rule' => $badrule]]]],
            'series changed and deleted at once' => [
                ['updateseries' => [['id' => 3, 'rule' => $goodrule]], 'deleteseries' => [3]],
            ],
        ];
    }

    /**
     * Too many changes at once are rejected, so one request can not create thousands of rows.
     *
     * @return void
     */
    public function test_too_many_changes_are_rejected(): void {
        $blocks = [];
        for ($i = 0; $i <= unavailability_changes::MAX_CHANGES; $i++) {
            $blocks[] = ['starttime' => 1000 + $i * 10, 'endtime' => 1005 + $i * 10];
        }

        $this->expectException(\invalid_parameter_exception::class);
        unavailability_changes::from_array(['addblocks' => $blocks]);
    }
}
