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

/**
 * This class contains a list of webservice functions related to the Shopping Cart Module by Wunderbyte.
 *
 * @package    local_entities
 * @copyright  2022 Thomas Winkler <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_entities\external;

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use local_entities\entity;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

class get_entity_calendardata extends external_api {

    /**
     * Describes the paramters for get_faq_data.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() : external_function_parameters {
        return new external_function_parameters ([
            'id' => new external_value(PARAM_INT, 'entity id', VALUE_REQUIRED),
        ]);
    }

    /**
     * Webservice to get entity calendardata.
     *
     * @return array
     */
    public static function execute(int $id): array {
        $calendardata['error'] = "";
        $entity = entity::load($id);
        // $calendardata['json'] = $entity->get_calendardata();
        $calendardata['json'] = '[
            {
              "title": "Offen",
              "startTime": "10:45:00",
              "endTime": "12:45:00",
              "backgroundColor" : "#64a44e",
              "daysOfWeek": [1,2,3,4],
              "allDay": false
            },
            {
              "title": "Offen",
              "start": "2022-10-24T08:00:00",
              "end": "2022-10-24T13:00:00",
              "backgroundColor" : "#64a44e"
            },
            {
              "title": "Gebucht - title",
              "start": "2022-10-24T08:00:00",
              "end": "2022-10-24T13:00:00"
            }
          ]';
        return $calendardata;
    }

    /*
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure(array(
            'json' => new external_value(PARAM_TEXT, 'json'),
            'error' => new external_value(PARAM_TEXT, 'error'),
            )
        );
    }
}
