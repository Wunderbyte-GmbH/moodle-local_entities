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
use local_entities\entities;

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

        $params = self::validate_parameters(self::execute_parameters(), [
            'id' => $id,
        ]);

        $entity = entity::load($params['id']);
        $openinghours = $entity->__get('openinghours') ?? '[]';
        $openinghours = json_decode($openinghours, false);

        $openinghours = entities::prepare_datearray_for_calendar($openinghours, '#64a44e');
        $relationdata = entities::get_all_dates_for_entity($params['id']);
        $relationdata = entities::prepare_datearray_for_calendar($relationdata, 'red');
        $calendardata['json'] = json_encode([...$openinghours, ...$relationdata]);

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
