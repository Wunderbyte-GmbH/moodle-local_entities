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

use core_external\external_multiple_structure;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;


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
     * Finds entities whose name (or any ancestor's name) matches the given query.
     *
     * The result is ordered depth-first (every entity directly after its parent, any nesting
     * depth) and each entry carries its full ancestor path and depth, so the selector can
     * render the hierarchy correctly. Matching against the whole path keeps search consistent
     * with what the suggestion displays.
     *
     * @param string $query The search request.
     * @return array
     */
    public static function execute(string $query): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'query' => $query,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/entities:view', $context);

        // Multibyte-safe lowercase (umlauts!), matching is done in PHP against the full path.
        $queryclean = \core_text::strtolower(trim($params['query']));

        $records = $DB->get_records_select(
            'local_entities',
            "entitytype = 'location' OR entitytype IS NULL",
            [],
            '',
            'id, name, shortname'
        );

        $map = \local_entities\entities::get_entity_map();

        $keyed = [];
        foreach ($records as $record) {
            [$depth, , $names] = \local_entities\entities::get_ancestor_path((int)$record->id, $map);
            // The path runs root → self; the displayed context is everything but the entity itself.
            $selfname = array_pop($names);
            $path = implode(' / ', $names);

            if (
                $queryclean !== ''
                && strpos(\core_text::strtolower($path . ' / ' . $selfname), $queryclean) === false
            ) {
                continue;
            }

            $entity = (object)[
                'id' => $record->id,
                'name' => $record->name,
                'shortname' => $record->shortname,
                'parentname' => $path,
                'depth' => $depth,
                'extrafields' => [
                    // Sanitize the extra fields to prevent potential XSS exploit.
                    (object)[
                        'name' => 'parentname',
                        'value' => s($path),
                    ],
                ],
            ];

            $keyed[] = [\local_entities\entities::get_tree_sortkey((int)$record->id, $map), $entity];
        }

        usort($keyed, static fn($a, $b) => strcmp($a[0], $b[0]));

        $list = [];
        foreach ($keyed as [, $entity]) {
            $list[$entity->id] = $entity;
        }

        return [
            'list' => $list,
        ];
    }

    /**
     * Describes the external function result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {

        return new external_single_structure([
            'list' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(\core_user::get_property_type('id'), 'ID of the user'),
                    'name' => new external_value(PARAM_TEXT, 'The fullname of the entity'),
                    'shortname' => new external_value(PARAM_TEXT, 'The shortname of the entity', VALUE_OPTIONAL),
                    'parentname' => new external_value(PARAM_TEXT, 'Full ancestor path (root / … / parent)', VALUE_OPTIONAL),
                    'depth' => new external_value(PARAM_INT, 'Nesting depth of the entity (0 for root)', VALUE_OPTIONAL),
                    'extrafields' => new external_multiple_structure(
                        new external_single_structure([
                            'name' => new external_value(PARAM_TEXT, 'Name of the extrafield.'),
                            'value' => new external_value(PARAM_TEXT, 'Value of the extrafield.'),
                        ]),
                        'List of extra fields',
                        VALUE_OPTIONAL
                    ),
                ])
            ),
        ]);
    }
}
