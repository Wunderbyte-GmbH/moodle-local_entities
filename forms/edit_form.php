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
     * @var stdClass
     */
    public $entity;

    /**
     * @var int
     */
    public $callingentity;

    /**
     * entities_edit_product_form constructor.
     * @param mixed $entity
     */
    public function __construct($entity = null) {
        if ($entity) {
            $this->entity = $entity;
            $this->callingentity = $entity->id;
        } else {
            $this->entity = new stdClass();
            $this->entity->id = 0;
        }
        parent::__construct();
    }

    /**
     *
     * Set the entity data.
     *
     * @return void
     */
    public function set_data_for_dynamic_submission() {
        global $DB;
        if ($id = $this->optional_param('id', 0, PARAM_INT)) {
            $entity = $DB->get_record('local_entities', ['id' => $id]);
            $handler = local_entities\customfield\entities_handler::create();
            $handler->instance_form_before_set_data($this->entity);
            $this->set_data($this->entity);
        }
    }

    /**
     * Get a list of all entities
     */
    public function definition() {
        global $DB;

        // Get a list of all entities.
        $none = get_string("none", "local_entities");
        $entities = array(0 => $none);
        $allentities = local_entities\entities::list_all_parent_entities();
        foreach ($allentities as $entity) {
            if ($entity->id != $this->callingentity) {
                $entities[$entity->id] = $entity->name;
            }
        }

        $mform = $this->_form;

        // Entity DETAILS.
        $mform->addElement('header', 'details', get_string('edit_details', 'local_entities'));
        $renderer =& $this->_form->defaultRenderer();
        $highlightheadertemplate = str_replace('ftoggler', 'ftoggler highlight', $renderer->_headerTemplate);
        $renderer->setElementTemplate($highlightheadertemplate , 'details');

        $mform->addElement('text', 'name', get_string('entity_name', 'local_entities'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addElement('checkbox', 'active', get_string('open', 'local_entities'));
        $mform->setType('active', PARAM_BOOL);
        // IMAGE CONTENT.
        $options['subdirs'] = 0;
        $options['maxbytes'] = 204800;
        $options['maxfiles'] = 1;
        $options['accepted_types'] = ['jpg', 'jpeg', 'png', 'svg', 'webp'];
        $handler = local_entities\customfield\entities_handler::create();
        $categorynames = $this->get_customfieldcategories($handler);
        $mform->addElement('filemanager', 'image_filemanager', get_string('edit_image', 'local_entities'), null, $options);
        $mform->addElement('select', 'type', get_string('entity_category', 'local_entities'), $categorynames);

        $context = context_system::instance();
        $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean' => true, 'context' => $context);

        $mform->addElement('editor', 'description', get_string('entity_description', 'local_entities'),
            '', $editoroptions);
        $mform->addRule('description', null, 'required', null, 'client');
        $mform->setType('description', PARAM_RAW);
        $mform->addElement('select', 'parentid', get_string('entity_parent', 'local_entities'), $entities);
        $mform->addElement('text', 'sortorder', get_string('entity_order', 'local_entities'));
        $mform->setType('sortorder', PARAM_INT);

        // ADDRESS BLOCK.
        // Later Iteration Add more than one address.
        $this->entity->addresscount = isset($this->entity->addresscount) && $this->entity->addresscount > 0
        ? $this->entity->addresscount : 1;
        $mform->addElement('hidden', 'addresscount', $this->entity->addresscount);
        $mform->setType('addresscount', PARAM_INT);
        for ($i = 0; $i < $this->entity->addresscount; $i++) {
            $mform->addElement('hidden', 'addressid_'.$i, null);
            $mform->setType('addressid_'.$i, PARAM_INT);
            $mform->addElement('header', 'address', get_string('address', 'local_entities'));
            $mform->addElement('text', 'country_'.$i, get_string('address_country', 'local_entities'));
            $mform->setType('country_'.$i, PARAM_TEXT);
            $mform->addElement('text', 'city_'.$i, get_string('address_city', 'local_entities'));
            $mform->setType('city_'.$i, PARAM_TEXT);
            $mform->addElement('text', 'postcode_'.$i, get_string('address_postcode', 'local_entities'));
            $mform->setType('postcode_'.$i, PARAM_INT);
            $mform->addElement('text', 'streetname_'.$i, get_string('address_streetname', 'local_entities'));
            $mform->setType('streetname_'.$i, PARAM_TEXT);
            $mform->addElement('text', 'streetnumber_'.$i, get_string('address_streetnumber', 'local_entities'));
            $mform->setType('streetnumber_'.$i, PARAM_TEXT);
        }

        // Contact BLOCK.
        // Later Iteration Add more than one contact.
        $this->entity->contactscount = isset($this->entity->contactscount) && $this->entity->contactscount > 0 ?
        $this->entity->contactscount : 1;
        $mform->addElement('hidden', 'contactscount', $this->entity->contactscount);
        $mform->setType('contactscount', PARAM_INT);
        for ($j = 0; $j < $this->entity->contactscount; $j++) {
            $mform->addElement('hidden', 'contactsid_'.$j, null);
            $mform->setType('contactsid_'.$j, PARAM_INT);
            $mform->addElement('header', 'htmlbody', get_string('contacts', 'local_entities'));
            $mform->addElement('text', 'givenname_'.$j, get_string('contacts_givenname', 'local_entities'));
            $mform->setType('givenname_'.$j, PARAM_TEXT);
            $mform->addElement('text', 'surname_'.$j, get_string('contacts_surname', 'local_entities'));
            $mform->setType('surname_'.$j, PARAM_TEXT);
            $mform->addElement('text', 'mail_'.$j, get_string('contacts_mail', 'local_entities'));
            $mform->setType('mail_'.$j, PARAM_TEXT);
        }

        $mform->addElement('header', 'meta', 'Meta Infos');
        $handler->instance_form_definition($mform, $this->entity->id);

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
     * Add contacts.
     *
     * @return void
     */
    public function add_contacts() {
        $mform = $this->_form;
        $j = 1;
        $mform->addElement('header', 'htmlbody', get_string('contacts', 'local_entities'));
        $mform->addElement('text', 'givenname_'.$j, get_string('contacts_givenname', 'local_entities'));
        $mform->addElement('text', 'surname_'.$j, get_string('contacts_surname', 'local_entities'));
        $mform->addElement('text', 'mailaddress_'.$j, get_string('contacts_mailaddress', 'local_entities'));
    }


    /**
     *
     * Set the page data.
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
        $defaults->active = $defaults->active;
        $options = array('maxbytes' => 204800, 'maxfiles' => 1, 'accepted_types' => ['jpg, png']);
        $defaults->picture = file_prepare_standard_filemanager(
            $defaults,
            'image',
            $options,
            $context,
            'local_entities',
            'image',
            $defaults->id);

        return parent::set_data($defaults);
    }

    /**
     *
     * Get Categories from Customfield Categories
     *
     * @param local_entities\customfield\entities_handler $handler
     * @return array
     */
    public function get_customfieldcategories(local_entities\customfield\entities_handler $handler): array {
        $categories = $handler->get_categories_with_fields();
        $categorynames['0_none'] = get_string("none", "local_entities");
        foreach ($categories as $category) {
            $name = $category->get('name');
            $id = $category->get('id');
            $categorynames[$id . '_' . $name] = $name;
        }
        return $categorynames;
    }
}
