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
require_once("entities.php");

use local_entities\entities;

/**
 * Class local_entities_external
 */
class local_entities_external extends external_api {

    /**
     * Describes the parameters for list_all_parameters.
     *
     * @return external_function_parameters
     */
    public static function list_all_entities_parameters() {
        return new external_function_parameters(
            array( )
            );
    }

    /**
     * returns id, name and description of all local entities
     *
     * @return array of entities
     */
    public static function list_all_parent_entities() {
        $returnedentities = array();
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
     * Describes the list_all_entities return value.
     *
     * @return external_single_structure
     */
    public static function list_all_entities_returns() {
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
                'field' => new external_value(PARAM_TEXT, VALUE_REQUIRED),
                'oldvalue' => new external_value(PARAM_RAW, VALUE_REQUIRED),
                'newvalue' => new external_value(PARAM_RAW, VALUE_REQUIRED),
            )
        );
    }
    
    /**
     * updates a number of fields in table local_entities from oldvalue to newvalue
     *
     * @return array of booleans
     */
    public static function update_entities($values) {
        global $DB;
        $params = self::validate_parameters(self::update_entities_parameters(), (array('values' => $values)));
        $transaction = $DB->start_delegated_transaction();
        $table = '{local_entities}';
        $allupdated = array();
        
        foreach ($params['values'] as $value) {
            $value = (object)$value;
            
            if($value->name == '' or $value->oldvalue == '' or $value->newvalue == ''){
                throw new invalid_parameter_exception('Invalid values.');
            }
            
            $conditions = array($value->name=>$value->oldvalue);
            $id = $DB->get_record($table, $conditions, 'id');
            if(!$id) {
                throw new invalid_parameter_exception('Invalid values');
            }
            $value->id = $id;
            // TODO security checks -> after capabilities are implemented.
            
            $updated = entities::update_entities($value);
            $allupdated[] = $updated;
        }
        $transaction->allow_commit();
        
        return $allupdated;
        
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
