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
            $this->_entity = $entity;
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
            $entity = $DB->get_record('local_entities', ['id' => $id]);
            $handler = local_entities\customfield\entities_handler::create();
            $handler->instance_form_before_set_data($entity);
            $this->set_data($entity);
        }
    }

    /**
     * Get a list of all entities
     */
    public function definition() {
        global $DB, $entity;

        // Get a list of all entities.
        $none = get_string("none", "local_entities");
        $entities = array(0 => $none);
        $allentities = local_entities\entities::list_all_entities();
        foreach ($allentities as $entity) {
            if ($entity->id != $this->callingentity) {
                $entities[$entity->id] = $entity->name;
            }
        }
        $mform = $this->_form;

        // entity DETAILS.
        $mform->addElement('header', 'details', get_string('edit_details', 'local_entities'));

        $mform->addElement('text', 'name', get_string('entity_name', 'local_entities'));
        $mform->setType('name', PARAM_TEXT);
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
        $mform->addElement('select', 'category', get_string('entity_category', 'local_entities'),
            array("test" => get_string("entity", "local_entities"),
                "test" => get_string("form", "local_entities")), 'entity');

        $context = context_system::instance();
        $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean' => true, 'context' => $context);

        $mform->addElement('editor', 'description', get_string('entity_description', 'local_entities'),
            get_string('entity_content_description', 'local_entities'), $editoroptions);

        $mform->addRule('description', null, 'required', null, 'client');
        $mform->setType('description', PARAM_RAW); 
        // XSS is prevented when printing the block contents and serving files.
        $mform->addHelpButton('entitycontent', 'entitycontent_description', 'local_entities');    
        // ADDRESS BLOCK.
        $mform->addElement('header', 'htmlbody', get_string('address', 'local_entities'));
        $mform->addElement('text', 'country', get_string('address_country', 'local_entities'));
        $mform->addElement('text', 'postcode', get_string('address_postcode', 'local_entities'));
        $mform->addElement('text', 'streetname', get_string('address_streetname', 'local_entities'));
        $mform->addElement('text', 'streenumber', get_string('address_streenumber', 'local_entities'));
        

        // Contact BLOCK.
        $mform->addElement('header', 'htmlbody', get_string('contacts', 'local_entities'));
        $mform->addElement('text', 'givenname', get_string('contacts_givenname', 'local_entities'));
        $mform->addElement('text', 'surname', get_string('contacts_surname', 'local_entities'));
        $mform->addElement('text', 'mailaddress', get_string('contacts_mailaddress', 'local_entities'));
        
        $handler = local_entities\customfield\entities_handler::create();
        $handler->instance_form_definition($mform, 0);

        // FORM BUTTONS.
        $this->add_action_buttons();
        //$handler->instance_form_before_set_data($course);
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
     * Show the entity information to edit
     *
     * @param bool $entity
     */
    public function edit_entity($entity = false) {
        $forform = new stdClass();
        $forform->description['text'] = $entity->description;
        $forform->name = $entity->name;
        $forform->id = $entity->id;
        $forform->parentid = $entity->parentid;
        $forform->sortorder  = $entity->sortorder;
        $this->set_data($forform);
        $this->display();
    }
}
