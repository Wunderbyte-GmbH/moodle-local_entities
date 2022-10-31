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

namespace local_entities\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("$CFG->libdir/formslib.php");

use core_form\dynamic_form;
use context_system;
use context;
use moodle_url;
use local_entities\entities;
use local_entities\customfield\entities_handler;
use moodleform;
use stdClass;


/**
 * Dynamic optiondate form.
 * @copyright Wunderbyte GmbH <info@wunderbyte.at>
 * @author Georg Maißer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_dynamic_form extends dynamic_form {

    public $handlers;

    public $handler2;

    public $entity;

    public $entityid;

    /**
     * {@inheritdoc}
     * @see moodleform::definition()
     */
    public function definition() {

        // Get a list of all entities.
        $none = get_string("none", "local_entities");

        $mform = $this->_form;
        $data = (Object)$this->_ajaxformdata;

        $entityid = $this->_customdata['entityid'] ?? $data->entityid ?? 0;

        $entities = array(0 => $none);
        $allentities = entities::list_all_entities();

        unset($allentities[$entityid]);

        // Entity DETAILS.
        $mform->addElement('header', 'details', get_string('edit_details', 'local_entities'));
        $mform->addElement('text', 'name', get_string('name'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addElement('text', 'shortname', get_string('shortname'));
        $mform->setType('shortname', PARAM_TEXT);
        // IMAGE CONTENT.
        $options['subdirs'] = 0;
        $options['maxbytes'] = 204800;
        $options['maxfiles'] = 1;
        $options['accepted_types'] = ['jpg', 'jpeg', 'png', 'svg', 'webp'];

        $mform->addElement('filemanager', 'image_filemanager', get_string('edit_image', 'local_entities'), null, $options);

        $context = context_system::instance();
        $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean' => true, 'context' => $context);

        $mform->addElement('editor', 'description', get_string('entity_description', 'local_entities'),
            '', $editoroptions);

        $mform->addRule('description', null, 'required', null, 'client');
        $mform->setType('description', PARAM_RAW);

        $mform->addElement('text', 'maxallocation', get_string('maxallocation', 'local_entities'));
        $mform->setType('maxallocation', PARAM_INT);
        $mform->addHelpButton('maxallocation', 'maxallocation', 'local_entities');

        $mform->addElement('select', 'parentid', get_string('entity_parent', 'local_entities'), $entities);

        $mform->addElement('text', 'sortorder', get_string('entity_order', 'local_entities'));
        $mform->setType('sortorder', PARAM_INT);

        $mform->addElement('float', 'pricefactor', get_string('pricefactor', 'local_entities'), null);
        $mform->setDefault('pricefactor', 1);
        $mform->addHelpButton('pricefactor', 'pricefactor', 'local_entities');

        // Type selection.
        $categories = \local_entities\customfield\entities_cf_helper::get_alternative_cf_categories();
        if (!empty($categories)) {
            $mform->registerNoSubmitButton('btn_cfcategoryid');
            $buttonargs = array('style' => 'visibility:hidden;');
            $categoryselect = [
                $mform->createElement('select', 'cfitemid', get_string('entity_category', 'local_entities'), $categories),
                $mform->createElement('submit', 'btn_cfcategoryid', get_string('categories'), $buttonargs)
            ];
            $mform->addGroup($categoryselect, 'tagsgroup', get_string('categories'), [' '], false);
            $mform->setType('btn_cfcategoryid', PARAM_NOTAGS);
        }

        // ADDRESS BLOCK.
        // Later Iteration Add more than one address.
        $addresscount = 1;
        $mform->addElement('hidden', 'addresscount', $addresscount);
        $mform->setType('addresscount', PARAM_INT);
        for ($i = 0; $i < $addresscount; $i++) {
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
            $mform->addElement('text', 'map_link_'.$i, get_string('address_map_link', 'local_entities'));
            $mform->setType('map_link_'.$i, PARAM_TEXT);
            $mform->addElement('textarea', 'map_embed_'.$i, get_string('address_map_embed', 'local_entities'));
            $mform->setType('map_embed_'.$i, PARAM_TEXT);
        }

        // Contact BLOCK.
        // Later Iteration Add more than one contact.

        $contactscount = 1;
        $mform->addElement('hidden', 'contactscount', $contactscount );
        $mform->setType('contactscount', PARAM_INT);
        for ($j = 0; $j < $contactscount; $j++) {
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

        // Adds all Standard categories defined in settings to the form.
        $this->handlers = \local_entities\customfield\entities_cf_helper::create_std_handlers();
        if (!empty($this->handlers)) {
            foreach ($this->handlers as $handler) {
                $handler->instance_form_definition($mform, $entityid);
                $handler->instance_form_before_set_data($data);
            }
        }

        $mform->addElement('hidden', 'id', null);
        $mform->setType('id', PARAM_INT);

        if (!empty($categories)) {
            $arraykeys = array_flip($categories);
            $cfitemid = reset($arraykeys);
            // Adds the chosen category.
            if (isset($this->_ajaxformdata['cfitemid'])) {
                $cfitemid = $this->_ajaxformdata['cfitemid'];
            } else if (isset($this->_customdata['cfitemid']) && $this->_customdata['cfitemid'] > 0) {
                $cfitemid = $this->_customdata['cfitemid'];
            }

            $this->handler2 = entities_handler::create($cfitemid);
            $this->handler2->instance_form_definition($mform, $entityid);
            $this->handler2->instance_form_before_set_data($data);
        }

        // FORM BUTTONS.
        $this->add_action_buttons();
    }

    /**
     * Check access for dynamic submission.
     *
     * @return void
     */
    protected function check_access_for_dynamic_submission(): void {

    }

    /**
     * Process the form submission, used if form was submitted via AJAX
     *
     * This method can return scalar values or arrays that can be json-encoded, they will be passed to the caller JS.
     *
     * Submission data can be accessed as: $this->get_data()
     *
     * @return mixed
     */
    public function process_dynamic_submission() {
        global $CFG;
        $data = (object)$this->_ajaxformdata;
        $context = context_system::instance();
        if (!isset($data->description['itemid'])) {
            $description = $data->description;
            $data->description = [];
            $data->description['text'] = $description;
        }
        $data->description['text'] = file_save_draft_area_files($data->entityid, $context->id,
        'local_entities', 'entitycontent',
        0, array('subdirs' => true), $data->description['text']);
        $data->entitydata = '';
        $recordentity = new stdClass();
        $recordentity = $data;
        $recordentity->id = $data->id;
        $recordentity->name = $data->name;
        $recordentity->shortname = $data->shortname;
        $recordentity->sortorder = intval($data->sortorder);
        $recordentity->type = $data->cfitemid;
        $recordentity->parentid = intval($data->parentid);
        $recordentity->description = $data->description['text'];
        $recordentity->pricefactor = floatval(str_replace(',', '.', $data->pricefactor));
        $recordentity->cfitemid = intval($data->cfitemid);
        $settingsmanager = new \local_entities\settings_manager();
        $result = $settingsmanager->update_or_createentity($recordentity);
        if ($result && $result > 0) {
            $data->id = $result;
            $options = array('subdirs' => 0, 'maxbytes' => 204800, 'maxfiles' => 1, 'accepted_types' => '*');
            if (isset($data->image_filemanager)) {
                file_postupdate_standard_filemanager($data, 'image', $options, $context, 'local_entities', 'image', $result);
            }
        }
        if (!empty($this->handlers) && !empty($data->id)) {
            foreach ($this->handlers as $handler) {
                $handler->instance_form_save($data);
            }
        }
        if (!empty($this->handler2) && !empty($data->id)) {
            $this->handler2->instance_form_save($data);
        }
        $returnurl = new moodle_url('/local/entities/entities.php');
        $recordentity->returnurl = $returnurl->out(false);
        return $recordentity;
    }


    /**
     * Load in existing data as form defaults
     *
     * Can be overridden to retrieve existing values from db by entity id and also
     * to preprocess editor and filemanager elements
     *
     * Example:
     *     $this->set_data(get_entity($this->_ajaxformdata['cmid']));
     */
    public function set_data_for_dynamic_submission(): void {
        global $DB;
        if (!empty($this->_customdata['entityid'])) {
            $data = \local_entities\settings_manager::get_settings_forform($this->_customdata['entityid']);
        } else {
            $data = (Object)$this->_ajaxformdata;
        }
        $data->addresscount = 1;
        $data->contactscount = 1;

        $this->set_data($data);
    }

    /**
     * Returns form context
     *
     * If context depends on the form data, it is available in $this->_ajaxformdata or
     * by calling $this->optional_param()
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        return context_system::instance();
    }

    /**
     * Returns url to set in $PAGE->set_url() when form is being rendered or submitted via AJAX
     *
     * This is used in the form elements sensitive to the page url, such as Atto autosave in 'editor'
     *
     * If the form has arguments (such as 'cmid' of the element being edited), the URL should
     * also have respective argument.
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        return new moodle_url('/local/entities/edit.php');
    }

    /**
     * Validate dates.
     *
     * {@inheritdoc}
     * @see moodleform::validation()
     */
    public function validation($data, $files) {
        $errors = array();
        return $errors;
    }

    /**
     * {@inheritDoc}
     * @see moodleform::get_data()
     */
    public function get_data() {
        $data = parent::get_data();
        return $data;
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
        if (!empty($defaults->id)) {
            $defaults->description['text'] = file_prepare_draft_area($draftideditor, $context->id,
            'local_entities', 'description', 0, array('subdirs' => true), $defaults->description['text']);
            $defaults->description['itemid'] = $draftideditor;
            $defaults->description['format'] = FORMAT_HTML;

            $options = array('maxbytes' => 204800, 'maxfiles' => 1, 'accepted_types' => ['jpg, png']);
            $defaults->picture = file_prepare_standard_filemanager(
                $defaults,
                'image',
                $options,
                $context,
                'local_entities',
                'image',
                $defaults->id);
        }

        $this->handlers = \local_entities\customfield\entities_cf_helper::create_std_handlers();
        if (!empty($this->handlers)) {
            foreach ($this->handlers as $handler) {
                $handler->instance_form_before_set_data($defaults);
            }
        }
        if (!empty($this->handler2)) {
            $this->handler2->instance_form_before_set_data($defaults);
        }
        $this->entityid = $defaults->id ?? 0;
        return parent::set_data($defaults);
    }
}
