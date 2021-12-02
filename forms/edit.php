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
    public function set_data_for_dynamic_submission() {

        global $DB;
        if ($id = $this->optional_param('id', 0, PARAM_INT)) {
            $this->set_data($DB->get_record('local_entities', ['id' => $id]));
        }
        /*
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
        */
        //return parent::set_data($defaults);
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
        $handler = local_entities\customfield\entities_handler::create();
        $handler->instance_form_definition($mform,0);
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
}
