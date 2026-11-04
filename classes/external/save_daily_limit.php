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
use core_external\external_single_structure;
use core_external\external_value;
use local_entities\daily_limit;

/**
 * Saves the highest number of planned slots per day of an entity.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_daily_limit extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'entityid' => new external_value(PARAM_INT, 'Id of the entity'),
            'limit' => new external_value(PARAM_INT, 'Highest number of slots per day, 0 for no limit'),
        ]);
    }

    /**
     * Saves the limit.
     *
     * @param int $entityid
     * @param int $limit 0 for no limit
     * @return array the stored limit, 0 for no limit
     */
    public static function execute(int $entityid, int $limit): array {
        $params = self::validate_parameters(self::execute_parameters(), ['entityid' => $entityid, 'limit' => $limit]);
        self::validate_context(context_system::instance());

        daily_limit::set($params['entityid'], $params['limit'] === 0 ? null : $params['limit']);

        return ['limit' => daily_limit::get_own([$params['entityid']])[$params['entityid']] ?? 0];
    }

    /**
     * Result.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'limit' => new external_value(PARAM_INT, 'The stored limit, 0 for no limit'),
        ]);
    }
}
