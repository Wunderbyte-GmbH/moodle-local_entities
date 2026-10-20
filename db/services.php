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
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
        'local_entities_list_all_parent_entities' => [
                'classname' => 'local_entities_external',
                'methodname' => 'list_all_parent_entities',
                'classpath' => 'local/entities/classes/external.php',
                'description' => 'fetches all top-level entities',
                'type' => 'read',
                'ajax' => true,
                'services' => [],
                'capabilities' => 'local/entities:edit',
        ],
        'local_entities_update_entity' => [
                'classname' => 'local_entities_external',
                'methodname' => 'update_entity',
                'classpath' => 'local/entities/classes/external.php',
                'description' => 'updates the given record with the new values.',
                'type' => 'write',
                'ajax' => true,
                'services' => [],
                'capabilities' => 'local/entities:edit',
        ],
        'local_entities_list_all_subentities' => [
                'classname' => 'local_entities_external',
                'methodname' => 'list_all_subentities',
                'classpath' => 'local/entities/classes/external.php',
                'description' => 'fetches all subentities of a given parent',
                'type' => 'read',
                'ajax' => true,
                'services' => [],
                'capabilities' => '',
        ],
        'local_entities_delete_entity' => [
                'classname' => 'local_entities_external',
                'methodname' => 'delete_entity',
                'classpath' => 'local/entities/classes/external.php',
                'description' => 'deletes an entity',
                'type' => 'read',
                'ajax' => true,
                'services' => [],
                'capabilities' => 'local/entities:delete',
        ],
        'local_entities_get_entity_calendardata' => [
                'classname' => 'local_entities\external\get_entity_calendardata',
                'classpath' => '',
                'description' => 'Get calendardata from specific entity',
                'type' => 'read',
                'ajax' => true,
                'loginrequired' => false,
        ],
        'local_entities_search_entities' => [
                'classname' => 'local_entities\external\search_entities',
                'classpath' => '',
                'description' => 'Get entities from query',
                'type' => 'read',
                'ajax' => true,
        ],
        'local_entities_set_active_view_template' => [
                'classname' => 'local_entities\external\set_active_view_template',
                'classpath' => '',
                'description' => 'Save the global active detail-view template',
                'type' => 'write',
                'ajax' => true,
                'capabilities' => 'local/entities:changeviewtemplate',
        ],
        'local_entities_get_unavailability' => [
                'classname' => 'local_entities\external\get_unavailability',
                'classpath' => '',
                'description' => 'Loads the blocks and series of the unavailability of an entity for a time window',
                'type' => 'read',
                'ajax' => true,
        ],
        'local_entities_save_unavailability' => [
                'classname' => 'local_entities\external\save_unavailability',
                'classpath' => '',
                'description' => 'Saves the changes to the unavailability of an entity, or returns the assignments that prevent it',
                'type' => 'write',
                'ajax' => true,
        ],
        'local_entities_delete_pool' => [
                'classname' => 'local_entities\external\delete_pool',
                'classpath' => '',
                'description' => 'Deletes a pool of persons, the persons stay',
                'type' => 'write',
                'ajax' => true,
        ],
];

$services = [
        'Wunderbyte entities external' => [
                'functions' => [
                        'local_entities_list_all_parent_entities',
                        'local_entities_update_entity',
                        'local_entities_list_all_subentities',
                ],
                'restrictedusers' => 0,
                'shortname' => 'local_entities_external',
                'enabled' => 1,
        ],
];
