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
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class local_entities_external
 */
class local_entities_external extends external_api {

    /**
     * Function to actually copy a module to a new course and course section.
     * @param int $sourcecmid
     * @param string $sourcemodulename
     * @param null|string $targetcourseidnumber
     * @param null|string $targetcourseshortname
     * @param null|string $targetsectionname
     * @param null|int $targetslot
     * @param null|string $idnumber
     * @param null|string $shortname
     * @return int[]
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws restricted_context_exception
     */
    public static function copy_module(
            int $sourcecmid,
            string $sourcemodulename,
            $targetcourseidnumber = null,
            $targetcourseshortname = null,
            $targetsectionname = null,
            $targetslot = null,
            $idnumber = null,
            $shortname = null) {

        global $DB;

        $params = array(
                'sourcecmid' => $sourcecmid,
                'sourcemodulename' => $sourcemodulename,
                'targetcourseidnumber' => $targetcourseidnumber,
                'targetcourseshortname' => $targetcourseshortname,
                'targetsectionname' => $targetsectionname,
                'targetslot' => $targetslot,
                'idnumber' => $idnumber,
                'shortname' => $shortname
        );

        $params = self::validate_parameters(self::copy_module_parameters(), $params);

        // First find out if the module name exists at all.
        if (!core_component::is_valid_plugin_name('mod', $params['sourcemodulename'])) {
            throw new moodle_exception('invalidcoursemodulename', 'local_entities', null, null,
                    "Invalid source module name " . $params['sourcemodulename']);
        }

        // Now do some security checks.
        if (!$cm = get_coursemodule_from_id($params['sourcemodulename'], $params['sourcecmid'])) {
            throw new moodle_exception('invalidcoursemodule ' . $params['sourcecmid'], 'local_entities', null, null,
                    "Invalid source module" . $params['sourcecmid'] . ' ' . $params['sourcemodulename']);
        }

        $context = context_module::instance($cm->id);
        self::validate_context($context);

        // We try to copy the module to the target.
        if (local_entities\entities::copy_module(
                $cm,
                $params['targetcourseidnumber'],
                $params['targetcourseshortname'],
                $params['targetsectionname'],
                $params['targetslot'],
                $params['idnumber'],
                $params['shortname'])) {
            $success = 1;
        } else {
            $success = 0;
        }

        return ['status' => $success];
    }

    /**
     * Defines the parameters for copy_module.
     * @return external_function_parameters
     */
    public static function copy_module_parameters() {
        return new external_function_parameters(array(
                'sourcecmid' => new external_value(PARAM_INT,
                    'The cmid of the module to copy.'),
                'sourcemodulename' => new external_value(PARAM_RAW,
                    'The module type of the module to copy (eg. quiz or mooduell)'),
                'targetcourseidnumber' => new external_value(PARAM_RAW,
                    'The course to copy to, identified by the value in the idnumber column in the course table.',
                    VALUE_DEFAULT, null),
                'targetcourseshortname' => new external_value(PARAM_RAW,
                    'The course to copy to, identified by the value in the shortname column in the course table.',
                    VALUE_DEFAULT, null),
                'targetsectionname' => new external_value(PARAM_RAW,
                    'The section name, identified by the name column in the course_sections table.
                        "top" is for section 0, null for last.',
                    VALUE_DEFAULT, null),
                'targetslot' => new external_value(PARAM_INT,
                    'The slot for the new activity, where 0 is the top place in the activity. -1 is last.',
                    VALUE_DEFAULT, null),
                'idnumber' => new external_value(PARAM_RAW,
                    'To set the idnumber of the new activity.',
                    VALUE_DEFAULT, null),
                'shortname' => new external_value(PARAM_RAW,
                    'To set the shortname of the new activity.',
                    VALUE_DEFAULT, null)
        ));
    }

    /**
     * Defines the return values for copy_module.
     * @return external_single_structure
     */
    public static function copy_module_returns() {
        return new external_single_structure(array(
                        'status' => new external_value(PARAM_INT, 'status')
                )
        );
    }

    /**
     * Generic function to update module.
     *
     * @param integer $sourcecmid
     * @param string $sourcemodulename
     * @param [type] $targetcourseidnumber
     * @param [type] $targetcourseshortname
     * @param [type] $targetsectionname
     * @param [type] $targetslot
     * @param [type] $idnumber
     * @param [type] $shortname
     * @return void
     */
    public static function update_module(
        string $sourcemodulename,
        int $sourcecmid = null,
        $sourcemoduleidnumber = null,
        $paramsarray = null) {

        global $DB;

        $params = array(
                'sourcemodulename' => $sourcemodulename,
                'sourcecmid' => $sourcecmid,
                'sourcemoduleidnumber' => $sourcemoduleidnumber,
                'paramsarray' => $paramsarray
        );

        $params = self::validate_parameters(self::update_module_parameters(), $params);

        // First find out if the module name exists at all.
        if (!core_component::is_valid_plugin_name('mod', $params['sourcemodulename'])) {
            throw new moodle_exception('invalidcoursemodulename', 'local_entities', null, null,
                    "Invalid source module name " . $params['sourcemodulename']);
        }

        if (!$params['sourcecmid'] && $params['sourcemoduleidnumber']) {
            $params['sourcecmid'] = $DB->get_field('course_modules', array('idnumber' => $params['sourcemoduleidnumber']));
        } else if (!$params['sourcecmid'] && !$params['sourcemoduleidnumber']) {
            throw new moodle_exception('undefinedsourcemodule ' . $params['sourcecmid'], 'local_entities', null, null,
                "Undefined source module");
        }

        // Now do some security checks.
        if (!$cm = get_coursemodule_from_id($params['sourcemodulename'], $params['sourcecmid'])) {
            throw new moodle_exception('invalidcoursemodule ' . $params['sourcecmid'], 'local_entities', null, null,
                    "Invalid source module" . $params['sourcecmid'] . ' ' . $params['sourcemodulename']);
        }

        $context = context_module::instance($cm->id);
        self::validate_context($context);

        // We try to copy the module to the target.
        if (local_entities\entities::update_module(
                $cm,
                $params['paramsarray'])) {
            $success = 1;
        } else {
            $success = 0;
        }

        return ['status' => $success];
    }

    /**
     * Defines the parameters for copy_module.
     * @return external_function_parameters
     */
    public static function update_module_parameters() {
        return new external_function_parameters(array(
                'sourcemodulename' => new external_value(PARAM_RAW,
                    'The module type of the module to update (eg. quiz or mooduell)'),
                'sourcecmid' => new external_value(PARAM_INT,
                    'The cmid of the module to copy.', VALUE_DEFAULT, null),
                'sourcemoduleidnumber' => new external_value(PARAM_RAW,
                    'The idnumber of dthe module to update',
                    VALUE_DEFAULT, null),
                'paramsarray' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'keyname' => new external_value(PARAM_RAW, 'Keyname', VALUE_OPTIONAL),
                            'value' => new external_value(PARAM_RAW, 'Value', VALUE_OPTIONAL)
                        )
                    )
                )
            )
        );
    }

    /**
     * Defines the return values for copy_module.
     * @return external_single_structure
     */
    public static function update_module_returns() {
        return new external_single_structure(array(
                        'status' => new external_value(PARAM_INT, 'status')
                )
        );
    }

    /**
     * @param string $targetmodulename
     * @param null|string $targetcourseidnumber
     * @param null|string $targetcourseshortname
     * @param null|string $targetsectionname
     * @param null|int $targetslot
     * @param null|string $targetidnumber
     * @param false $deleteall
     * @return int[]
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function delete_module(
            string $targetmodulename,
            $targetidnumber = null,
            $targetcourseidnumber = null,
            $targetcourseshortname = null,
            $targetsectionname = null,
            $targetslot = null,
            $deleteall = false) {

        global $DB;

        $params = array(
                'targetmodulename' => $targetmodulename,
                'targetcourseidnumber' => $targetcourseidnumber,
                'targetcourseshortname' => $targetcourseshortname,
                'targetsectionname' => $targetsectionname,
                'targetslot' => $targetslot,
                'targetidnumber' => $targetidnumber,
                'deleteall' => $deleteall
        );

        $params = self::validate_parameters(self::delete_module_parameters(), $params);

        // First find out if the module name exists at all.
        if (!core_component::is_valid_plugin_name('mod', $params['targetmodulename'])) {
            throw new moodle_exception('invalidcoursemodulename', 'local_entities', null, null,
                    "Invalid module name " . $params['sourcemodulename']);
        }

        // We try to delete the module.
        if (local_entities\entities::delete_module(
                $params['targetmodulename'],
                $params['targetidnumber'],
                $params['targetcourseidnumber'],
                $params['targetcourseshortname'],
                $params['targetsectionname'],
                $params['targetslot'],
                $params['deleteall'])) {
            $success = 1;
        } else {
            $success = 0;
        }

        return ['status' => $success];
    }

    public static function delete_module_parameters() {
        return new external_function_parameters(array(
                'targetmodulename' => new external_value(PARAM_RAW,
                        'The module type of the module to delete (eg. quiz or mooduell)'),
                'targetidnumber' => new external_value(PARAM_RAW,
                        'Most precise way to delete exactly one activity.',
                        VALUE_DEFAULT, null),
                'targetcourseidnumber' => new external_value(PARAM_RAW,
                        'The course where to find the activity to delete.
                        Identified by the value in the idnumber column in the course table.',
                        VALUE_DEFAULT, null),
                'targetcourseshortname' => new external_value(PARAM_RAW,
                        'The course where to find the activity to delete.
                        Identified by the value in the shortname column in the course table.',
                        VALUE_DEFAULT, null),
                'targetsectionname' => new external_value(PARAM_RAW,
                        'The section name, identified by the name column in the course_sections table. "top" is for section 0.',
                        VALUE_DEFAULT, null),
                'targetslot' => new external_value(PARAM_INT,
                        'The slot of the activity to delete, where 0 is the top place in the activity. -1 is last.',
                        VALUE_DEFAULT, null),
                'deleteall' => new external_value(PARAM_BOOL, 'TRUE to delete more than one instance.',
                        VALUE_DEFAULT, false)
        ));
    }

    /**
     * Defines the return values for delete.
     * @return external_single_structure
     */
    public static function delete_module_returns() {
        return new external_single_structure(array(
                        'status' => new external_value(PARAM_INT, 'status')
                )
        );
    }

    /**
     * Function to copy a course and create a new course with a new name.
     *
     * @param integer $sourcecourseid
     * @param string $newcourseshortname
     * @param string|null $newcoursename
     * @return void
     */
    public static function create_course(
        int $sourcecourseid,
        string $newcourseshortname,
        string $newcoursename = null) {

        global $DB;

        $params = array(
                'sourcecourseid' => $sourcecourseid,
                'newcourseshortname' => $newcourseshortname,
                'newcoursename' => $newcoursename
        );

        $params = self::validate_parameters(self::create_course_parameters(), $params);

        // We try to copy the module to the target.
        if (local_entities\entities::create_course(
                $params['sourcecourseid'],
                $params['newcourseshortname'],
                $params['newcoursename'])) {
            $success = 1;
        } else {
            $success = 0;
        }

        return ['status' => $success];
    }

    /**
     * Defines the parameters for create_course.
     * @return external_function_parameters
     */
    public static function create_course_parameters() {
        return new external_function_parameters(array(
                'sourcecourseid' => new external_value(PARAM_INT,
                    'The cmid of the module to copy.'),
                'newcourseshortname' => new external_value(PARAM_RAW,
                    'The shortname of the new course'),
                'newcoursename' => new external_value(PARAM_RAW,
                    'The name of the new course',
                    VALUE_DEFAULT, null)
        ));
    }

    /**
     * Defines the return values for create_course.
     * @return external_single_structure
     */
    public static function create_course_returns() {
        return new external_single_structure(array(
                        'status' => new external_value(PARAM_INT, 'status')
                )
        );
    }
}
