<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_entities\external;
use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;


/**
 * Provides the local_entities_search_entities external function.
 *
 * @package     local_entities
 * @category    external
 * @copyright   2022 Thomas Winkler <thomas.winkler@wunderbyt.at>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class search_entities extends external_api {

    /**
     * Describes the external function parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {

        return new external_function_parameters([
            'query' => new external_value(PARAM_TEXT, 'The search query', VALUE_REQUIRED),
        ]);
    }

    /**
     * Finds entities with the name matching the given query.
     *
     * @param string $query The search request.
     * @return array
     */
    public static function execute(string $query): array {
        global $DB, $CFG;

        $params = self::validate_parameters(self::execute_parameters(), [
            'query' => $query,
        ]);
        $query = strtolower($params['query']);

        $sql = "SELECT e.*, COALESCE(e2.name, '') as parentname FROM {local_entities} e
        LEFT JOIN {local_entities} e2 ON e.parentid = e2.id
        WHERE LOWER(e.name) LIKE '%{$query}%'
        OR COALESCE(LOWER(e2.name), '') LIKE '%{$query}%'
        ORDER BY
            CASE
                WHEN e.parentid = 0 THEN e.id
            ELSE e.parentid
        END ASC, e.id ASC";
        $rs = $DB->get_recordset_sql($sql);
        $count = 0;
        $list = [];
        $extrafields = ['parentname'];

        foreach ($rs as $record) {
            $entity = (object)[
                'id' => $record->id,
                'name' => $record->name,
                'shortname' => $record->shortname,
                'parentname' => $record->parentname,
                'extrafields' => [],
            ];

            foreach ($extrafields as $extrafield) {
                // Sanitize the extra fields to prevent potential XSS exploit.
                $entity->extrafields[] = (object)[
                    'name' => $extrafield,
                    'value' => s($record->$extrafield)
                ];
            }

            $count++;
            $list[$record->id] = $entity;
        }

        $rs->close();

        return [
            'list' => $list,
        ];
    }

    /**
     * Describes the external function result value.
     *
     * @return external_description
     */
    public static function execute_returns(): \external_description {

        return new \external_single_structure([
            'list' => new \external_multiple_structure(
                new \external_single_structure([
                    'id' => new \external_value(\core_user::get_property_type('id'), 'ID of the user'),
                    'name' => new \external_value(PARAM_TEXT, 'The fullname of the entity'),
                    'shortname' => new \external_value(PARAM_TEXT, 'The shortname of the entity', VALUE_OPTIONAL),
                    'parentname' => new \external_value(PARAM_TEXT, 'The shortname of the entity', VALUE_OPTIONAL),
                    'extrafields' => new \external_multiple_structure(
                        new \external_single_structure([
                            'name' => new \external_value(PARAM_TEXT, 'Name of the extrafield.'),
                            'value' => new \external_value(PARAM_TEXT, 'Value of the extrafield.'),
                        ]), 'List of extra fields', VALUE_OPTIONAL)
                ])
            ),
        ]);
    }
}
