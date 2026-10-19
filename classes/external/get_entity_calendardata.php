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
 * @category   external
 * @copyright  2022 Thomas Winkler <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_entities\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use local_entities\entity;
use local_entities\entities;
use local_entities\person_visibility;

/**
 * get_entity_calendardata webservice
 * @copyright Wunderbyte GmbH <info@wunderbyte.at>
 * @author Thomas Winkler
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_entity_calendardata extends external_api {
    /**
     * Describes the paramters for get_faq_data.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'id' => new external_value(PARAM_INT, 'entity id', VALUE_REQUIRED),
        ]);
    }
    /**
     * Webservice to get entity calendardata.
     *
     * @param int $id
     * @return array
     */
    public static function execute(int $id): array {
        global $PAGE;

        $calendardata['error'] = "";

        $params = self::validate_parameters(self::execute_parameters(), [
            'id' => $id,
        ]);

        // Needed by the format_text() calls further down: the date providers run booking option
        // customfields through the filter chain, and filters like filter_emoticon ask $PAGE for the
        // theme, which throws a coding exception while the context is unset. This webservice is
        // deliberately public (loginrequired = false), so we set the context instead of validating
        // it - validate_context() would call require_login() and lock out logged out visitors.
        $PAGE->set_context(\context_system::instance());

        // A person is personal data and follows the same rule as the detail page. The webservice is public,
        // so for a visitor that may not see the person it is simply not there.
        if (!person_visibility::can_see_id($params['id'])) {
            return ['json' => '[]', 'error' => get_string('error:entitydoesnotexist', 'local_entities')];
        }

        $entity = entity::load($params['id']);
        $openinghours = $entity->__get('openinghours') ?? '[]';
        $openinghours = json_decode($openinghours, false) ?? [];

        $openinghours = entities::prepare_datearray_for_calendar($openinghours, '#64a44e');
        $relationdata = entities::get_all_dates_for_entity($params['id']) ?? [];
        $relationdata = entities::prepare_datearray_for_calendar($relationdata, 'red');
        $calendardata['json'] = json_encode([...$openinghours, ...$relationdata]);

        return $calendardata;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'json' => new external_value(PARAM_RAW, 'json'),
            'error' => new external_value(PARAM_RAW, 'error'),
        ]);
    }
}
