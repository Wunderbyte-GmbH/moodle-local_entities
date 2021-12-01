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
 * Module Wizard external functions and service definitions.
 *
 * @package local_entities
 * @category external
 * @copyright 2021 Wunderbyte GmbH (info@wunderbyte.at)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 3.1
 */

defined('MOODLE_INTERNAL') || die();

$services = array(
        'Wunderbyte entities external' => array(
                'functions' => array (
                        'local_entities_copy_module'
                ),
                'restrictedusers' => 1,
                'shortname' => 'local_entities_external',
                'enabled' => 1
        )
);

$functions = array(
        'local_entities_copy_module' => array(
                'classname' => 'local_entities_external',
                'methodname' => 'copy_module',
                'classpath' => 'local/entities/classes/external.php',
                'description' => 'Copies a module to a new place',
                'type' => 'write',
                'ajax' => true,
                'capabilities' => 'local/entities:copymodule',
                'services' => array(
                        'local_entities_external'
                )
        ),
        'local_entities_update_module' => array(
                'classname' => 'local_entities_external',
                'methodname' => 'update_module',
                'classpath' => 'local/entities/classes/external.php',
                'description' => 'Update a module with new information',
                'type' => 'write',
                'ajax' => true,
                'capabilities' => 'local/entities:copymodule',
                'services' => array(
                        'local_entities_external'
                )
        ),
        'local_entities_delete_module' => array(
                'classname' => 'local_entities_external',
                'methodname' => 'delete_module',
                'classpath' => 'local/entities/classes/external.php',
                'description' => 'Deletes a module from a certain place.',
                'type' => 'write',
                'ajax' => true,
                'capabilities' => 'local/entities:copymodule',
                'services' => array(
                        'local_entities_external'
                )
        ),
        'local_entities_create_course' => array(
                'classname' => 'local_entities_external',
                'methodname' => 'create_course',
                'classpath' => 'local/entities/classes/external.php',
                'description' => 'Copies a course by id.',
                'type' => 'write',
                'ajax' => true,
                'capabilities' => 'local/entities:copymodule',
                'services' => array(
                        'local_entities_external'
                )
        )
);
