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

namespace local_entities\customfield;

use core_customfield\api;
use core_customfield\field_controller;

/**
 * Course handler for custom fields
 *
 * @package   local_entities
 * @copyright 2021 Wunderbyte
 * @author    Thomas Winkler
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class entities_handler extends \core_customfield\handler {

    /**
     * @var entities_handler
     */
    static protected $singleton;

    /**
     * @var \context
     */
    protected $parentcontext;

    /** @var int Field is displayed in the course listing, visible to everybody */
    const VISIBLETOALL = 2;
    /** @var int Field is displayed in the course listing but only for teachers */
    const VISIBLETOTEACHERS = 1;
    /** @var int Field is not displayed in the course listing */
    const NOTVISIBLE = 0;

    /**
     * Undocumented function
     *
     * @param int $itemid
     * @return entities_handler
     */
    public static function create($itemid = 0): \core_customfield\handler {
        if (empty($itemid)) {
            $itemid = 0; // Postgres fix.
        }
        self::$singleton = new static($itemid);
        return self::$singleton;
    }

    /**
     * Run reset code after unit tests to reset the singleton usage.
     */
    public static function reset_caches(): void {
        if (!PHPUNIT_TEST) {
            throw new \coding_exception('This feature is only intended for use in unit tests');
        }

        static::$singleton = null;
    }

    /**
     * The current user can configure custom fields on this component.
     *
     * @return bool true if the current can configure custom fields, false otherwise
     */
    public function can_configure() : bool {
        return has_capability('moodle/course:configurecustomfields', $this->get_configuration_context());
    }

    /**
     * The current user can edit custom fields on the given course.
     *
     * @param field_controller $field
     * @param int $instanceid id of the course to test edit permission
     * @return bool true if the current can edit custom fields, false otherwise
     */
    public function can_edit(field_controller $field, int $instanceid = 0) : bool {
        if ($instanceid) {
            $context = $this->get_instance_context($instanceid);
            return (!$field->get_configdata_property('locked') ||
                    has_capability('local/entities:canedit', $context));
        } else {
            $context = $this->get_parent_context();
            if ($context->contextlevel == CONTEXT_SYSTEM) {
                return (!$field->get_configdata_property('locked') ||
                    has_capability('local/entities:canedit', $context));
            } else {
                return (!$field->get_configdata_property('locked') ||
                    guess_if_creator_will_have_course_capability('local/entities:canedit', $context));
            }
        }
    }

    /**
     * The current user can view custom fields on the given course.
     *
     * @param field_controller $field
     * @param int $instanceid id of the course to test edit permission
     * @return bool true if the current can edit custom fields, false otherwise
     */
    public function can_view(field_controller $field, int $instanceid) : bool {
        $visibility = $field->get_configdata_property('visibility');
        if ($visibility == self::NOTVISIBLE) {
            return false;
        } else if ($visibility == self::VISIBLETOTEACHERS) {
            return has_capability('moodle/course:update', $this->get_instance_context($instanceid));
        } else {
            return true;
        }
    }

    /**
     * Uses categories
     *
     * @return bool
     */
    public function uses_categories() : bool {
        return true;
    }

    /**
     * Sets parent context for the course
     *
     * This may be needed when course is being created, there is no course context but we need to check capabilities
     *
     * @param \context $context
     */
    public function set_parent_context(\context $context) {
        $this->parentcontext = $context;
    }

    /**
     * Returns the parent context for the course
     *
     * @return \context
     */
    protected function get_parent_context() : \context {
        global $PAGE;
        if ($this->parentcontext) {
            return $this->parentcontext;
        } else if ($PAGE->context && $PAGE->context instanceof \context_coursecat) {
            return $PAGE->context;
        }
        return \context_system::instance();
    }




    /**
     * Context that should be used for new categories created by this handler
     *
     * @return \context the context for configuration
     */
    public function get_configuration_context() : \context {
        return \context_system::instance();
    }

    /**
     * URL for configuration of the fields on this handler.
     *
     * @return \moodle_url The URL to configure custom fields for this component
     */
    public function get_configuration_url() : \moodle_url {
        return new \moodle_url('/local/entities/customfield.php');
    }

    /**
     * Returns the context for the data associated with the given instanceid.
     *
     * @param int $instanceid id of the record to get the context for
     * @return \context the context for the given record
     */
    public function get_instance_context(int $instanceid = 0) : \context {
            return \context_system::instance();
    }

    /**
     * Creates or updates custom field data.
     *
     * @param \restore_task $task
     * @param array $data
     */
    public function restore_instance_data_from_backup(\restore_task $task, array $data) {

    }

    /**
     * Gets a list of all the categorynames.
     *
     * @return array
     */
    public function get_customfieldcategory_names(): array {
        $categories = $this->get_categories_with_fields();
        $categorynames = array();
        if (isset($categories)) {
            foreach ($categories as $category) {
                $name = $category->get('name');
                $name = $category->get('name');
                $id = $category->get('id');
                $categorynames[$id] = $name;
            }
            return $categorynames;
        }
        return null;
    }

    /**
     * Gets a list of all the categorynames.
     *
     * @return array
     */
    public function get_standard_categories(\MoodleQuickForm $mform, int $instanceid = 0,
    ?string $headerlangidentifier = null, ?string $headerlangcomponent = null) {

        $allcategories = $this->get_categories_with_fields();
        $lastcategoryid = null;
        $categorycfg = get_config('local_entities', 'categories');
        $categorycfgids = array_flip(explode(",", $categorycfg));

        if (isset($categorycfg)) {
            $standardcategories = array_intersect_key($allcategories , $categorycfgids);
            $editablefields = $this->get_editable_specific_category_fields($instanceid, $standardcategories);
        } else {
            return;
        }
        $fieldswithdata = api::get_instance_fields_data($editablefields, $instanceid);
        foreach ($fieldswithdata as $data) {
            $categoryid = $data->get_field()->get_category()->get('id');
            if ($categoryid != $lastcategoryid) {
                $categoryname = format_string($data->get_field()->get_category()->get('name'));

                // Load category header lang string if specified.
                if (!empty($headerlangidentifier)) {
                    $categoryname = get_string($headerlangidentifier, $headerlangcomponent, $categoryname);
                }

                $mform->addElement('header', 'categorystandard_' . $categoryid, $categoryname);
                $lastcategoryid = $categoryid;
            }
            $data->instance_form_definition($mform);
            $field = $data->get_field()->to_record();
            if (strlen($field->description)) {
                // Add field description.
                $context = $this->get_configuration_context();
                $value = file_rewrite_pluginfile_urls($field->description, 'pluginfile.php',
                    $context->id, 'core_customfield', 'description', $field->id);
                $value = format_text($value, $field->descriptionformat, ['context' => $context]);
                $mform->addElement('static', 'customfield_' . $field->shortname . '_static', '', $value);
            }
        }
    }


    public function get_alternative_categories(\MoodleQuickForm $mform, int $instanceid = 0,
    ?string $headerlangidentifier = null, ?string $headerlangcomponent = null) {
        $allcategories = $this->get_categories_with_fields();
        $lastcategoryid = null;
        $categorycfg = get_config('local_entities', 'categories');
        $categorycfgids = array_flip(explode(",", $categorycfg));

        if (isset($categorycfg)) {
            $nonestandardcategories = array_diff_key($allcategories , $categorycfgids);
            $editablefields = $this->get_editable_specific_category_fields($instanceid, $nonestandardcategories);
        } else {
            return;
        }
        $fieldswithdata = api::get_instance_fields_data($editablefields, $instanceid);
        foreach ($fieldswithdata as $data) {
            $categoryid = $data->get_field()->get_category()->get('id');
            if ($categoryid != $lastcategoryid) {
                $categoryname = format_string($data->get_field()->get_category()->get('name'));

                // Load category header lang string if specified.
                if (!empty($headerlangidentifier)) {
                    $categoryname = get_string($headerlangidentifier, $headerlangcomponent, $categoryname);
                }

                $mform->addElement('header', 'categorymeta_' . $categoryid, $categoryname);
                $lastcategoryid = $categoryid;
            }
            $data->instance_form_definition($mform);
            $field = $data->get_field()->to_record();
            if (strlen($field->description)) {
                // Add field description.
                $context = $this->get_configuration_context();
                $value = file_rewrite_pluginfile_urls($field->description, 'pluginfile.php',
                    $context->id, 'core_customfield', 'description', $field->id);
                $value = format_text($value, $field->descriptionformat, ['context' => $context]);
                $mform->addElement('static', 'customfield_' . $field->shortname . '_static', '', $value);
            }
        }
    }

    /**
     * Get editable fields
     *
     * @param int $instanceid
     * @return field_controller[]
     */
    public function get_editable_specific_category_fields(int $instanceid, array $categories) : array {
        $handler = $this;
        return array_filter($this->get_specific_category_fields($categories),
            function($field) use($handler, $instanceid) {
                return $handler->can_edit($field, $instanceid);
            }
        );
    }

    /**
     * Returns list of fields defined for this instance as an array (not groupped by categories)
     *
     * Fields are sorted in the same order they would appear on the instance edit form
     *
     * Note that this function returns all fields in all categories regardless of whether the current user
     * can view or edit data associated with them
     *
     * @return field_controller[]
     */
    public function get_specific_category_fields(array $categories) : array {
        $fields = [];
        foreach ($categories as $category) {
            foreach ($category->get_fields() as $field) {
                $fields[$field->get('id')] = $field;
            }
        }
        return $fields;
    }

}
