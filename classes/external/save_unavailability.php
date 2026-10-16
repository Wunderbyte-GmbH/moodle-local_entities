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
use local_entities\unavailability\unavailability_changes;
use local_entities\unavailability\unavailability_saver;

/**
 * Saves the changes a user made to the unavailability of an entity.
 *
 * The rights, the collisions with assignments and the transaction are handled by the saver. This class
 * only describes the parameters and the result. If the entity is assigned somewhere in the time of a
 * change, nothing is saved and the assignments are returned.
 *
 * @package     local_entities
 * @category    external
 * @copyright   2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_unavailability extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'entityid' => new external_value(PARAM_INT, 'Id of the entity'),
            'changes' => new external_single_structure([
                'addblocks' => new external_multiple_structure(
                    new external_single_structure([
                        'starttime' => new external_value(PARAM_INT, 'Start (unix timestamp)'),
                        'endtime' => new external_value(PARAM_INT, 'End (unix timestamp)'),
                        'reason' => new external_value(PARAM_TEXT, 'Reason', VALUE_DEFAULT, ''),
                    ]),
                    'New single blocks',
                    VALUE_DEFAULT,
                    []
                ),
                'removeblockids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'Id of a block to remove'),
                    'Blocks to remove',
                    VALUE_DEFAULT,
                    []
                ),
                'createseries' => new external_multiple_structure(
                    self::rule_structure(),
                    'New series',
                    VALUE_DEFAULT,
                    []
                ),
                'updateseries' => new external_multiple_structure(
                    new external_single_structure([
                        'id' => new external_value(PARAM_INT, 'Id of the series'),
                        'rule' => self::rule_structure(),
                    ]),
                    'Series to change',
                    VALUE_DEFAULT,
                    []
                ),
                'deleteseries' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'Id of a series to delete'),
                    'Series to delete',
                    VALUE_DEFAULT,
                    []
                ),
            ], 'The changes, only what is new or different'),
        ]);
    }

    /**
     * Saves the changes.
     *
     * @param int $entityid
     * @param array $changes
     * @return array saved and conflicts
     */
    public static function execute(int $entityid, array $changes): array {
        $params = self::validate_parameters(
            self::execute_parameters(),
            ['entityid' => $entityid, 'changes' => $changes]
        );
        self::validate_context(context_system::instance());

        return (new unavailability_saver())->save(
            $params['entityid'],
            unavailability_changes::from_array($params['changes'])
        );
    }

    /**
     * Describes the return value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'saved' => new external_value(PARAM_BOOL, 'Whether the changes were saved'),
            'conflicts' => new external_multiple_structure(
                new external_single_structure([
                    'starttime' => new external_value(PARAM_INT, 'Start of the assignment (unix timestamp)'),
                    'endtime' => new external_value(PARAM_INT, 'End of the assignment (unix timestamp)'),
                    'name' => new external_value(PARAM_TEXT, 'Title of the assignment'),
                    'url' => new external_value(PARAM_RAW, 'Link to the assignment, may be empty'),
                    'component' => new external_value(PARAM_TEXT, 'Plugin the assignment comes from'),
                    'area' => new external_value(PARAM_TEXT, 'Area inside of the plugin'),
                    'itemid' => new external_value(PARAM_INT, 'Id of the assignment'),
                ]),
                'Assignments that prevented saving, empty if saved'
            ),
        ]);
    }

    /**
     * Describes the rule of a series.
     *
     * @return external_single_structure
     */
    private static function rule_structure(): external_single_structure {
        return new external_single_structure([
            'weekdays' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Weekday, 1 (Monday) to 7 (Sunday)'),
                'Weekdays of the series'
            ),
            'allday' => new external_value(PARAM_BOOL, 'Whole days instead of a time of day', VALUE_DEFAULT, 0),
            'daystart' => new external_value(PARAM_INT, 'Start in seconds since midnight', VALUE_DEFAULT, 0),
            'dayend' => new external_value(PARAM_INT, 'End in seconds since midnight', VALUE_DEFAULT, 0),
            'validfrom' => new external_value(PARAM_INT, 'Any time on the first day'),
            'validuntil' => new external_value(PARAM_INT, 'Any time on the last day'),
            'timezone' => new external_value(PARAM_TIMEZONE, 'Timezone of the rule', VALUE_DEFAULT, '99'),
            'reason' => new external_value(PARAM_TEXT, 'Reason', VALUE_DEFAULT, ''),
        ]);
    }
}
