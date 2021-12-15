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
    public static function list_all_entities() {
        $returnedentities = array();
        $entities = entities::list_all_entities();
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
}
