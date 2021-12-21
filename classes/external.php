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
 * Moolde external API
 *
 * @package local_entities
 * @category external
 * @copyright 2021 Wunderbyte Gmbh <info@wunderbyte.at>
 * @author Rea Sutter
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("$CFG->libdir/externallib.php");

use local_entities\entities;

/**
 * Class local_entities_external
 */
class local_entities_external extends external_api {

    /**
     * Describes the parameters for list_all_parent_entities.
     * This query doesn't need any parameters, so the array is empty.
     *
     * @return external_function_parameters
     */
    public static function list_all_parent_entities_parameters() {
        return new external_function_parameters(
            array(
                // no parameters in this query
            )
        );
    }

    /**
     * Returns id, name and description of all top-level local entities.
     *
     * @return array of entities
     */
    public static function list_all_parent_entities() {
        $returnedentities = array();

        self::validate_parameters(self::list_all_parent_entities_parameters(), (array()));

        $entities = entities::list_all_parent_entities();
        foreach ($entities as $entity) {
            $entityrecord = array();
            $entityrecord['id'] = $entity->id;
            $entityrecord['name'] = $entity->name;
            $entityrecord['description'] = $entity->description;
            $entityrecord['type'] = $entity->type;
            $returnedentities[] = $entityrecord;
        }

        return $returnedentities;

    }

    /**
     * Describes the expected return values of list_all_parent_entities.
     *
     * @return external_single_structure
     */
    public static function list_all_parent_entities_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'id of the entity', VALUE_REQUIRED),
                    'name' => new external_value(PARAM_RAW, 'name of the entity', VALUE_REQUIRED),
                    'description' => new external_value(PARAM_RAW, 'description of the entity', VALUE_OPTIONAL),
                    'type' => new external_value(PARAM_RAW, 'type of the entity', VALUE_OPTIONAL),
                )
            )
        );
    }

    /**
     * Describes the parameters for update_entities.
     *
     * @return external_function_parameters
     */
    public static function update_entity_parameters() {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, VALUE_REQUIRED),
                'data' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'name' => new external_value(PARAM_ALPHANUMEXT, 'data name'),
                            'value' => new external_value(PARAM_RAW, 'data value'),
                        )
                    ), 'Name of the column where to pass to the new value.', VALUE_DEFAULT, array()
                )
            )
        );
    }

    /**
     * Updates a number of fields in table local_entities to newvalue.
     * @param int $id
     * @param array $data
     * @return void
     */
    public static function update_entity($id, $data) {
        global $DB;

        $params = [
            'id' => $id,
            'data' => $data
        ];

        $params = self::validate_parameters(self::update_entity_parameters(), $params);

        self::verify_param_contents($data);

        // TODO security checks -> after capabilities are implemented.

        $transaction = $DB->start_delegated_transaction();
        $table = self::find_table($data);
        $success = array();
        $dataobject = new stdClass();
        $dataobject->id = $id;
        foreach ($data as $item) {
            $dataobject->name = $item['name'];
            $dataobject->value = $item['value'];
            $success[] = entities::update_entity($table, $dataobject);
        }

        if (in_array(false, $success)) {
            $transaction->rollback();
            return false;
        } else {
            $transaction->allow_commit();
            return true;
        }
    }

    /**
     * all return values should be booleans
     *
     * @return external_value (boolean)
     */
    public static function update_entity_returns() {
        return new external_value(PARAM_BOOL, 'status: true for success');
    }

    private static function find_table(array $data):string {
            // TODO find matching table.
            return 'local_entities';
    }

    /**
     * checks if all parameters are valid
     *
     * @throws moodle_exception
     */
    private static function verify_param_contents(array $data) {
        if (count($data) < 1) {
            new moodle_exception('noparams', 'local_entities', null, null, 'No params found');
        }

        foreach ($data as $item) {
            if (!isset($item['name']) || !isset($item['value'])) {
                new moodle_exception('noparams', 'local_entities', null, null, 'No params found');
            }
        }
    }
}
