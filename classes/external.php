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
     * returns id, name and description of all top-level 
     * local entities
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
     * Describes the parameters for list_all_parameters.
     *
     * @return external_function_parameters
     */
    public static function update_entities_parameters() {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, VALUE_REQUIRED),
                'field' => new external_value(PARAM_TEXT, VALUE_REQUIRED),
                'newvalue' => new external_value(PARAM_RAW, VALUE_REQUIRED),
            )
        );
    }
    
    /**
     * updates a number of fields in table local_entities to newvalue
     * @param records of values to be updated, including record id, field name, and new value
     * @return boolean
     */
    public static function update_entities($records) {
        global $DB;
        $params = self::validate_parameters(self::update_entities_parameters(), (array('records' => $records)));
        
        $transaction = $DB->start_delegated_transaction();
        foreach ($params['records'] as $record) {
            $record = (object)$record;

            $fieldname = $record->field;
            $table = self::find_table($fieldname);
            
            try {
                self::validate_input($record);
            } catch (invalid_parameter_exception $e) {
                // TODO what to do in case of error?
            }
            $dataobject = new stdClass();
            $dataobject->id = $record->id;
            $dataobject->$fieldname = $record->newvalue;
            
            // TODO security checks -> after capabilities are implemented.
            error_log('hello error!');
            error_log('dataobject is ' . var_export($dataobject, true));
            if(!entities::update_entities($table, $dataobject)) {
                $transaction->rollback();
                return false;
            }
        }
        $transaction->allow_commit();
        
        return true;
    }
    /**
     * makes sure we have non-empty inputs
     * @param the record to check
     * @throws invalid_parameter_exception
     */private static function validate_input(array $record) {
        if (trim($record->field) == '') {
            throw new invalid_parameter_exception('no field name given.');
        }
        if(trim($record->id) == '') {
            throw new invalid_parameter_exception('no id given.');
        }
        
        if(trim($record->newvalue) == '') {
            throw new invalid_parameter_exception('no new value given');
        }
    }

    
     private static function find_tables(string $name):string {
            // TODO find matching table
            return '{local_entities}';
    }

    
    /**
     * all return values should be booleans
     *
     * @return external_value (boolean)
     */
    public static function update_entities_returns() {
        return new external_value(
            PARAM_BOOL, 'indicates success of update', VALUE_REQUIRED
        );
    }
}
