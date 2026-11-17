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
use local_entities\always_free;
use local_entities\unavailability_access;

/**
 * Gives or takes back the confirmation of a person to be available the whole planning period.
 *
 * Whoever may see the unavailability of the entity may confirm, like the daily limit: also when the self-editing of
 * the type is locked, the person can tell that the empty calendar is on purpose.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_always_free extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'entityid' => new external_value(PARAM_INT, 'Id of the entity'),
            'confirmed' => new external_value(PARAM_BOOL, 'Available the whole planning period'),
        ]);
    }

    /**
     * Saves the confirmation.
     *
     * @param int $entityid
     * @param bool $confirmed
     * @return array the stored state
     */
    public static function execute(int $entityid, bool $confirmed): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), ['entityid' => $entityid, 'confirmed' => $confirmed]);
        self::validate_context(context_system::instance());
        $entity = $DB->get_record('local_entities', ['id' => $params['entityid']], '*', MUST_EXIST);
        unavailability_access::require_view_entity($entity);

        always_free::set((int)$entity->id, (bool)$params['confirmed'], (int)$USER->id);

        return ['confirmed' => always_free::is_confirmed((int)$entity->id)];
    }

    /**
     * Result.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'confirmed' => new external_value(PARAM_BOOL, 'The stored state'),
        ]);
    }
}
