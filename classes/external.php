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
require_once($CFG->libdir . '/externallib.php');

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
    public static function list_all_parent_entities_parameters(): external_function_parameters {
        return new external_function_parameters(
                array(// No parameters in this query.
                )
        );
    }

    /**
     * Returns id, name and description of all top-level local entities.
     *
     * @return array of entities
     * @throws invalid_parameter_exception
     */
    public static function list_all_parent_entities(): array {
        $returnedentities = array();

        self::validate_parameters(self::list_all_parent_entities_parameters(), (array()));

        $entities = entities::list_all_parent_entities();
        return self::extract_returnvalues($entities, $returnedentities);
    }

    /**
     * Describes the expected return values of list_all_parent_entities.
     *
     * @return external_multiple_structure
     */
    public static function list_all_parent_entities_returns(): external_multiple_structure {
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
     * Describes the parameters for update_entity.
     *
     * @return external_function_parameters
     */
    public static function update_entity_parameters(): external_function_parameters {
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
                        ),
                )
        );
    }



    /**
     * Updates a number of fields in table local_entities to newvalue.
     *
     * @param int $id
     * @param array $data
     * @return array true for success
     * @throws moodle_exception
     */
    public static function update_entity(int $id, array $data): array {
        $context = \context_system::instance();
        require_capability('local/entities:canedit', $context);
        $messages = array();
        $warnings = array();
        $params = [
                'id' => $id,
                'data' => $data,
        ];
        self::validate_parameters(self::update_entity_parameters(), $params);

        $table = self::find_table($params['data']);
        // Todo tests do not find entity in db, because the table doesn't exist.
        // Need to find out how to add relevant tables in tests.
        // Todo $messages[] = self::does_entity_exist($table, $params['id']);.
        $returnvalue = self::are_params_set($params);
        $messages[] = self::has_params($params);

        $data = $returnvalue['data'];
        $messages[] = $returnvalue['warning'];
        foreach ($messages as $message) {
            if (!empty($message)) {
                $warnings[] = $message;
                if ($message['warningcode'] === 'nosuchid' || $message['warningcode'] === 'noparams') {
                    return [
                            'updated' => false,
                            'warnings' => $warnings,
                    ];
                }
            }
        }
        $dataobject = self::builddataobject($id, $data);
        $updated = self::update_entity_in_db($table, $dataobject);
        return [
                'updated' => $updated,
                'warnings' => $warnings,
        ];
    }

    /**
     * all return values should be booleans
     *
     * @return external_single_structure
     */
    public static function update_entity_returns(): external_single_structure {
        return new external_single_structure(
                array(
                        'updated' => new external_value(PARAM_BOOL, 'did things get updated?'),
                        'warnings' => new external_warnings(),
                )
        );
    }

    /**
     * Describes the parameters for update_entity.
     *
     * @return external_function_parameters
     */
    public static function delete_entity_parameters(): external_function_parameters {
        return new external_function_parameters(
                array(
                        'id' => new external_value(PARAM_INT, VALUE_REQUIRED),
                )
        );
    }

    /**
     * Delete an entity.
     *
     * @param int $id
     * @return array true for success
     * @throws moodle_exception
     */
    public static function delete_entity(int $id): array {
        global $DB;
        $context = \context_system::instance();
        require_capability('local/entities:canedit', $context);
        $messages = array();
        $warnings = array();
        $params = self::validate_parameters(self::delete_entity_parameters(),
        array(
            'id' => $id,
        ));
        $DB->delete_records('local_entities', ['id' => $params['id']]);

        return [
                'deleted' => $DB->record_exists('local_entities', ['id' => $params['id']])
        ];
    }

    /**
     * all return values should be booleans
     *
     * @return external_single_structure
     */
    public static function delete_entity_returns(): external_single_structure {
        return new external_single_structure(
                array(
                        'deleted' => new external_value(PARAM_BOOL, 'did things got deleted?')
                )
        );
    }

    /**
     * @param array $data
     * @return string
     */
    private static function find_table(array $data): string {
        // TODO find matching table.
        $field = '';
        foreach ($data as $item) {
            $field = $item['name'];
        }
        return 'local_entities';
    }

    /**
     * @param string $table
     * @param $id
     * @return array
     * @throws dml_exception
     */
    private static function does_entity_exist(string $table, $id): array {
        global $DB;
        $warning = array();
        // Todo invalid response exception,
        $matchingentities = $DB->count_records($table, array('id' => $id));
        if ($matchingentities != 1) {
            $warning['itemid'] = $id;
            $warning['warningcode'] = 'nosuchid';
            $warning['message'] = 'There is no entity with the given id.';
        }
        return $warning;
    }

    /**
     * @param array $params
     * @return array
     */
    private static function are_params_set(array $params): array {
        $warning = array();
        $data = $params['data'];
        $validdata = array();
        foreach ($data as $item) {
            if (empty($item['name']) || ctype_space($item['value'] . ' ')) {
                $warning = array(
                        'item' => $item['name'] . ', ' . $item['value'],
                        'warningcode' => 'invalidparams',
                        'message' => 'These parameters are invalid.',
                );
            } else {
                $validdata[] = $item;
            }
        }
        return [
                'data' => $validdata,
                'warning' => $warning,
        ];
    }

    /**
     * checks if all parameters are valid
     *
     * @throws moodle_exception
     */
    private static function verify_param_contents(string $table, array $params) {
        global $DB;
        $matchingentities = $DB->count_records($table, array('id' => $params['id']));
        if ($matchingentities != 1) {
            throw new moodle_exception('nosuchentry', 'local_entities', null, $params['id'], 'there is no entity with this id.');
        }
        if (count($params['data']) < 1) {
            throw new moodle_exception('noparams', 'local_entities', null, null, 'No params found');
        }
        foreach ($params['data'] as $item) {
            if (!isset($item['name']) || !isset($item['value'])) {
                throw new moodle_exception('invalidparams', 'local_entities', null, $item, 'No params found');
            }
        }
    }

    /**
     * @param string $table
     * @param stdClass $dataobject
     * @return bool|true
     * @throws dml_transaction_exception
     * @throws invalid_parameter_exception
     */
    public static function update_entity_in_db(string $table, stdClass $dataobject): bool {
        global $DB;
        $transaction = $DB->start_delegated_transaction();
        $success = entities::update_entity($table, $dataobject);
        if ($success) {
            $transaction->allow_commit();
        } else {
            $transaction->rollback(new moodle_exception('update failed', 'local_entities', '', null, 'update failed'));
        }
        return $success;
    }

    /**
     * @return external_function_parameters
     */
    public static function list_all_subentities_parameters(): external_function_parameters {
        return new external_function_parameters(
                array(
                        'parentid' => new external_value(PARAM_INT, VALUE_REQUIRED),
                )
        );
    }

    /**
     * @param int $parentid
     * @return array
     * @throws invalid_parameter_exception
     */
    public static function list_all_subentities(int $parentid): array {
        $returnedentities = array();
        self::validate_parameters(self::list_all_subentities_parameters(), array('parentid' => $parentid));
        $entities = entities::list_all_subentities($parentid);
        return self::extract_returnvalues($entities, $returnedentities);
    }

    /**
     * @return external_multiple_structure
     */
    public static function list_all_subentities_returns(): external_multiple_structure {
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
     * @param array $entities
     * @param array $returnedentities
     * @return array
     */
    public static function extract_returnvalues(array $entities, array $returnedentities): array {
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
     * @param array $params
     * @return array
     */
    // This should never be reached. invalid params exception should be thrown before.
    private static function has_params(array $params): array {
        $warning = array();
        if (count($params['data']) < 1) {
            $warning = array(
                    'itemid' => $params['id'],
                    'warningcode' => 'noparams',
                    'message' => 'There are no valid parameters.',
            );
        }
        return $warning;
    }

    /**
     * @param int $id
     * @param array $data
     * @return stdClass
     */
    private static function builddataobject(int $id, array $data): stdClass {
        $dataobject = new stdClass();
        $dataobject->id = $id;
        foreach ($data as $item) {
            $name = $item['name'];
            $value = $item['value'];
            $dataobject->$name = $value;
        }
        return $dataobject;
    }



}
