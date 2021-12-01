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
 * Moodec entities dynamic Form
 *
 * @package     local_entities
 * @author      Thomas Winkler
 * @copyright   2021 Wunderbyte
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');
require_once(dirname(__FILE__) . '/../lib.php');

/**
 * Class entities_form
 *
 * @copyright   2021 Wunderbyte
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class entities_form extends moodleform {
    /**
     * @var $_entitydata
     */
    public $_entitydata;

    /**
     * @var $callingentity
     */
    public $callingentity;

    /**
     * entities_edit_product_form constructor.
     * @param mixed $entity
     */
    public function __construct($entity) {
        if ($entity) {
            $this->_entitydata = $entity->entitydata;
            $this->callingentity = $entity->id;
        }
        parent::__construct();
    }

    /**
     *
     * Set the entity data.
     *
     * @param mixed $defaults
     * @return mixed
     */
    public function set_data($defaults) {
        $context = context_system::instance();
        $draftideditor = file_get_submitted_draft_itemid('description');
        $defaults->description['text'] = file_prepare_draft_area($draftideditor, $context->id,
            'local_entities', 'description', 0, array('subdirs' => true), $defaults->description['text']);
        $defaults->description['itemid'] = $draftideditor;
        $defaults->description['format'] = FORMAT_HTML;

        $options = array('maxbytes' => 204800, 'maxfiles' => 1, 'accepted_types' => ['jpg, png']);
        $defaults->ogimage = file_prepare_standard_filemanager(
            $defaults,
            'ogimage',
            $options,
            $context,
            'local_entities',
            'ogimage',
            $defaults->id);

        return parent::set_data($defaults);
    }

    /**
     * Get a list of all entities
     */
    public function definition() {
        global $DB, $entity;

        // Get a list of all entities.
        $none = get_string("none", "local_entities");
        $entities = array(0 => $none);
        $allentities = $DB->get_records('local_entities');
        foreach ($allentities as $entity) {
            if ($entity->id != $this->callingentity) {
                $entities[$entity->id] = $entity->name;
            }
        }
        $mform = $this->_form;

        // entity DETAILS.
        $mform->addElement('header', 'details', get_string('edit_details', 'local_entities'));

        $mform->addElement('text', 'name', get_string('entity_name', 'local_entities'));
        $mform->setType('entityname', PARAM_TEXT);
        // IMAGE CONTENT.
        $options['subdirs'] = 0;
        $options['maxbytes'] = 204800;
        $options['maxfiles'] = 1;
        $options['accepted_types'] = ['jpg', 'jpeg', 'png', 'svg', 'webp'];
        $mform->addElement('filemanager', 'image_filemanager', get_string('edit_image', 'local_entities'), null, $options);
        // Entity DISPLAY.
        $mform->addElement('header', 'htmlbody', "entity Display");
        $mform->addElement('select', 'parentid', get_string('entity_parent', 'local_entities'), $entities);
        $mform->addElement('text', 'sortorder', get_string('entity_order', 'local_entities'));
        $mform->setType('sortorder', PARAM_INT);
        // Get categories from entities.
        $mform->addElement('select', 'entitytype', get_string('entity_entitytype', 'local_entities'),
            array("test" => get_string("entity", "local_entities"),
                "test" => get_string("form", "local_entities")), 'entity');

        $context = context_system::instance();
        $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean' => true, 'context' => $context);

        $mform->addElement('editor', 'description', get_string('entity_content', 'local_entities'),
            get_string('entity_content_description', 'local_entities'), $editoroptions);

        $mform->addRule('description', null, 'required', null, 'client');
        $mform->setType('description', PARAM_RAW); // XSS is prevented when printing the block contents and serving files.

        $mform->addHelpButton('entitycontent', 'entitycontent_description', 'local_entities');

        // FORM BUTTONS.
        $this->add_action_buttons();

        $mform->addElement('hidden', 'id', null);
        $mform->setType('id', PARAM_INT);
    }

    /**
     *
     * Validate the form
     *
     * @param mixed $data
     * @param mixed $files
     * @return mixed
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }

    /**
     *
     * Build the HTML form elements
     *
     * @return string
     */
    private function build_html_form() {
        global $DB;
        $usertable = $DB->get_record_sql("select * FROM {user} LIMIT 1");
        $records = json_decode($this->_entitydata);

        // PHP 7.2 now gives an error if the item cannot be counted - pre 7.2 it returned 0.
        $limit = intval(@count($records));

        $i = 0;
        $html = '<div class="form-builder row" id="form-builder">' .
            '<h3 style="width:100%"><a href="#" id="showform-builder">'. get_string('formbuilder', 'local_entities') .'  ' .
            '<span id="showEdit">' . get_string('show', 'local_entities') .
            '</span> <span id="hideEdit">' . get_string('hide', 'local_entities') .
            '</span></a></h3><div class="formbuilderform">';
        do {
            $html .= '<div class="formrow row"><div class="col-sm-12 col-md-2 span2"><label>' .
                get_string('label_name', 'local_entities') .' </label>' .
                '<textarea class="form-control field-name" name="fieldname[]" ' .
                'placeholder="' . get_string('placeholder_fieldname', 'local_entities') .
                '" style="height:25px;resize:none;overflow:hidden">' .
                (isset($records[$i]) ? $records[$i]->name : '') .
                '</textarea></div>';
            $html .= '<div class="col-sm-12 col-md-2 span2"><label>'.
                get_string('label_placeholder', 'local_entities') . '</label>' .
                '<textarea type="text" class="form-control default-name" ' .
                'name="defaultvalue[]" style="height:25px;resize:none;overflow:hidden" placeholder="' .
                get_string('placeholder_text', 'local_entities') . '">' .
                (isset($records[$i]) ? $records[$i]->defaultvalue : '') .
                '</textarea></div>';

            $html .= '<div class="col-sm-12 col-md-2 span2"><label>' .
                get_string('label_relatesto', 'local_entities') .' </label>' .
                '<select class="form-control field-readsfrom" name="readsfrom[]">' .
                '<option value="">'. get_string('select_nothing', 'local_entities')  .' </option>';
            $keys = array_keys((array)$usertable);
            foreach ($keys as $key) {
                $html .= '<option ' . ((isset($records[$i]) &&
                        isset($records[$i]->readsfrom) &&
                        $records[$i]->readsfrom == $key) ? 'selected="selected"' : '') . '>' . $key . '</option>';
            }
            $html .= '<option value="fullname" ' . ((isset($records[$i]) &&
                    isset($records[$i]->readsfrom) &&
                    $records[$i]->readsfrom == "fullname") ? 'selected="selected"' : '') . '>' .
                get_string('select_fullname', 'local_entities') . '</option>';
            $html .= '</select></div>';

            $html .= '<div class="col-sm-12 col-md-2 span2"><label>' .
                get_string('label_required', 'local_entities') .'</label>' .
                '<select class="form-control field-required" name="fieldrequired[]">' .
                '<option value="Yes" ' . (isset($records[$i]) &&
                $records[$i]->required == 'Yes' ? 'selected="selected"' : '') . '>' .
                get_string('select_yes', 'local_entities') .'</option>' .
                '<option value="No" ' . (isset($records[$i]) &&
                $records[$i]->required == 'No' ? 'selected="selected"' : '') . '>' .
                get_string('select_no', 'local_entities').'</option>' .
                '</select></div>';

            $html .= '<div class="col-sm-12 col-md-2 span2"><label>' .
                get_string('type', 'local_entities') . '</label>' .
                '<select class="form-control field-type" name="fieldtype[]">' .
                '<option value="Text" ' . (isset($records[$i]) &&
                $records[$i]->type == 'Text' ? 'selected="selected"' : '') . ' >' .
                get_string('select_text', 'local_entities') . '</option>' .
                '<option value="Email" ' . (isset($records[$i]) &&
                $records[$i]->type == 'Email' ? 'selected="selected"' : '') . ' >' .
                get_string('select_email', 'local_entities') . '</option>' .
                '<option value="Number" ' . (isset($records[$i]) &&
                $records[$i]->type == 'Number' ? 'selected="selected"' : '') . '  >' .
                get_string('select_number', 'local_entities')  . '</option>' .
                '<option value="Checkbox" ' . (isset($records[$i]) &&
                $records[$i]->type == 'Checkbox' ? 'selected="selected"' : '') . ' >' .
                get_string('select_checkbox', 'local_entities') . '</option>' .
                '<option value="Text Area"' . (isset($records[$i]) &&
                $records[$i]->type == 'Text Area' ? 'selected="selected"' : '') . ' >' .
                get_string('select_text_area', 'local_entities') . '</option>' .
                '<option value="Select" ' . (isset($records[$i]) &&
                $records[$i]->type == 'Select' ? 'selected="selected"' : '') . ' >' .
                get_string('select_select', 'local_entities') . '</option>' .
                '<option value="HTML" ' . (isset($records[$i]) &&
                $records[$i]->type == 'HTML' ? 'selected="selected"' : '') . ' >' .
                get_string('select_html', 'local_entities') . '</option>' .
                '</select></div>';

            $html .= '<div class="col-sm-12 col-md-2 span2"><label style="width:100%"> &nbsp;</label>' .
                '<input type="button" value="' . get_string('label_add', 'local_entities') . '" ' .
                'class="form-submit form-addrow btn btn-primary" name="submitbutton" type="button" />' .
                '<input type="button" value="' . get_string('label_remove', 'local_entities') .'" ' .
                'class="form-submit form-removerow btn btn-danger" name="cancel" type="button" />' .
                '</div>' .
                '</div>';
            $i++;
        } while ($i < $limit);

        $html .= '</div></div>';
        return $html;
    }
}
