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

namespace local_entities\external;

use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_entities\unavailability\conflict_detector;
use local_entities\unavailability\entities_occupation_source;
use local_entities\unavailability\unavailability_reader;

/**
 * Loads the unavailability of an entity for a time window: its blocks and its series.
 *
 * The rights are checked by the reader. This class only describes the parameters and the result.
 *
 * @package     local_entities
 * @category    external
 * @copyright   2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_unavailability extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'entityid' => new external_value(PARAM_INT, 'Id of the entity'),
            'from' => new external_value(PARAM_INT, 'Start of the window (unix timestamp)'),
            'until' => new external_value(PARAM_INT, 'End of the window (unix timestamp)'),
        ]);
    }

    /**
     * Loads the blocks and series of the entity in the window.
     *
     * @param int $entityid
     * @param int $from
     * @param int $until
     * @return array blocks and series
     */
    public static function execute(int $entityid, int $from, int $until): array {
        $params = self::validate_parameters(
            self::execute_parameters(),
            ['entityid' => $entityid, 'from' => $from, 'until' => $until]
        );
        self::validate_context(context_system::instance());

        $result = (new unavailability_reader())->read($params['entityid'], $params['from'], $params['until']);
        $result['occupations'] = (new conflict_detector(new entities_occupation_source()))->find(
            $params['entityid'],
            [['starttime' => $params['from'], 'endtime' => $params['until']]]
        );

        return $result;
    }

    /**
     * Describes the return value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'blocks' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Id of the block'),
                    'starttime' => new external_value(PARAM_INT, 'Start (unix timestamp)'),
                    'endtime' => new external_value(PARAM_INT, 'End (unix timestamp)'),
                    'reason' => new external_value(PARAM_TEXT, 'Reason, may be empty'),
                    'seriesid' => new external_value(PARAM_INT, 'Id of the series, 0 for a single block'),
                ]),
                'Blocks that overlap the window'
            ),
            'series' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Id of the series'),
                    'weekdays' => new external_multiple_structure(
                        new external_value(PARAM_INT, 'Weekday, 1 (Monday) to 7 (Sunday)')
                    ),
                    'allday' => new external_value(PARAM_BOOL, 'Whole days instead of a time of day'),
                    'daystart' => new external_value(PARAM_INT, 'Start of the day in seconds since midnight'),
                    'dayend' => new external_value(PARAM_INT, 'End of the day in seconds since midnight'),
                    'validfrom' => new external_value(PARAM_INT, 'Midnight of the first day'),
                    'validuntil' => new external_value(PARAM_INT, 'Midnight of the last day'),
                    'timezone' => new external_value(PARAM_TIMEZONE, 'Timezone of the rule'),
                    'reason' => new external_value(PARAM_TEXT, 'Reason, may be empty'),
                ]),
                'Series that are not over at the start of the window'
            ),
            'occupations' => new external_multiple_structure(
                new external_single_structure([
                    'starttime' => new external_value(PARAM_INT, 'Start (unix timestamp)'),
                    'endtime' => new external_value(PARAM_INT, 'End (unix timestamp)'),
                    'name' => new external_value(PARAM_TEXT, 'What the entity is assigned to'),
                    'url' => new external_value(PARAM_RAW, 'Link to it, empty if there is none'),
                    'component' => new external_value(PARAM_COMPONENT, 'Plugin the assignment comes from'),
                    'area' => new external_value(PARAM_ALPHANUMEXT, 'Area inside the plugin'),
                    'itemid' => new external_value(PARAM_INT, 'Id of the item'),
                ]),
                'Assignments of the entity that overlap the window'
            ),
        ]);
    }
}
