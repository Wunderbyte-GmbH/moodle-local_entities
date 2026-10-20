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
use local_entities\pool\pool_access;
use local_entities\pool\pool_service;

/**
 * Deletes a pool of persons. The persons stay.
 *
 * Who may do it is decided by {@see pool_access}, the work is done by
 * {@see pool_service}. This class only describes the parameters and the result.
 *
 * @package     local_entities
 * @category    external
 * @copyright   2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_pool extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'poolid' => new external_value(PARAM_INT, 'Id of the pool'),
        ]);
    }

    /**
     * Deletes the pool.
     *
     * @param int $poolid
     * @return array success
     */
    public static function execute(int $poolid): array {
        $params = self::validate_parameters(self::execute_parameters(), ['poolid' => $poolid]);
        self::validate_context(context_system::instance());
        pool_access::require_manage();

        (new pool_service())->delete_pool($params['poolid']);

        return ['success' => true];
    }

    /**
     * Describes the return value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the pool was deleted'),
        ]);
    }
}
