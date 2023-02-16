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
 * local entities
 *
 * @package     local_entities
 * @author      Thomas Winkler
 * @copyright   2021 Wunderbyte GmbH
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
use local_entities\calendar\fullcalendar_helper;
use local_entities\calendar\reoccuringevent;
use stdClass;


/**
 * Dynamic optiondate form.
 * @copyright Wunderbyte GmbH <info@wunderbyte.at>
 * @author Georg Maißer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_dynamic_form extends dynamic_form {

    /**
     * @var array $standardhandlers - These handlers add customfields to all the entities.
     */
    public $standardhandlers;

    /**
     * @var entities_handler $customhandler - This handler specifies the type of an entitiy and only the category-specific fields.
     */
    public $customhandler;

    /**
     * @var int $entityid - This handler specifies the type of an entitiy and only the category-specific fields.
     */
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

        $entityid = $this->_customdata['entityid'] ?? $data->entityid ?? $data->id ?? 0;

        $entities = array(0 => $none);

        $allentities = entities::list_all_entities();
        foreach ($allentities as $entity) {
            if ($entity->id != $entityid) {
                $entities[$entity->id] = $entity->newname;
            }
        }

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
        $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES,
         'noclean' => true,
         'context' => $context,
          'format' => FORMAT_HTML);

        $mform->addElement('editor', 'description', get_string('entity_description', 'local_entities'),
            '', $editoroptions);

        $mform->addRule('description', null, 'required', null, 'client');

        // Repeated elements.
        $repeatedopeninghours = [];

        // Options to store help button texts etc.
        $repeateoptions = [];

        $openinghourslabel = \html_writer::tag('b', get_string('openinghours', 'local_entities') . ' {no}',
            array('class' => 'openinghourslabel'));
        $repeatedopeninghours[] = $mform->createElement('static', 'openinghourslabel', $openinghourslabel);
        $dayofweekoptions = [
            'tags' => false,
            'multiple' => true
        ];
        $repeatedopeninghours[] = $mform->createElement('select', 'daysofweek', get_string('daysofweek', 'local_entities'),
        fullcalendar_helper::get_weekdays(),
        $dayofweekoptions);

        $hours = fullcalendar_helper::get_hours_select();
        $minutes = fullcalendar_helper::get_minutes_select();

        $repeatedopeninghours[] = $mform->createElement('select', 'starthours', get_string('starthours', 'local_entities'),
        $hours);
        $repeatedopeninghours[] = $mform->createElement('select', 'startminutes', get_string('startminutes', 'local_entities'),
        $minutes);

        $repeatedopeninghours[] = $mform->createElement('select', 'endhours', get_string('endhours', 'local_entities'),
        $hours);
        $repeatedopeninghours[] = $mform->createElement('select', 'endminutes', get_string('endminutes', 'local_entities'),
        $minutes);

        $numberofopeninghours = is_array(json_decode($data->openinghours)) ? count(json_decode($data->openinghours)) : 1;

        $repeatedopeninghours[] = $mform->createElement('submit', 'deleteopeninghours',
        get_string('deleteopeninghours', 'local_entities'));

        $this->repeat_elements($repeatedopeninghours, $numberofopeninghours,
            $repeateoptions, 'openinghour', 'addopeninghours', 1,
            get_string('addopeninghours', 'local_entities'), true, 'deleteopeninghours');

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
            $mform->addGroup($categoryselect, 'tagsgroup', get_string('categories', 'local_entities'), [' '], false);
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
        $this->standardhandlers = \local_entities\customfield\entities_cf_helper::create_std_handlers();
        if (!empty($this->standardhandlers)) {
            foreach ($this->standardhandlers as $handler) {
                $handler->instance_form_definition($mform, (int) $entityid);
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

            $this->customhandler = entities_handler::create((int) $cfitemid);
            $this->customhandler->instance_form_definition($mform, (int) $entityid);
            $this->customhandler->instance_form_before_set_data($data);
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
        $data = (object)$this->_ajaxformdata;
        $context = context_system::instance();

        $data->entitydata = '';
        if (isset($data->description['itemid'])) {
            $description = $data->description['text'];
            $draftitemid = $data->description['itemid'];
        }

        $recordentity = new stdClass();
        // We copy the whole element.
        $recordentity = $data;
        // But need to override a few values.
        $recordentity->id = $data->id;
        $recordentity->name = $data->name;
        $recordentity->shortname = $data->shortname;
        $recordentity->sortorder = intval($data->sortorder);
        $recordentity->parentid = intval($data->parentid);
        $recordentity->description = $data->description['text'] ?? $data->description ?? '';
        $recordentity->openinghours = $data->openinghours ?? '';
        $recordentity->status = $data->status ?? 0;
        $recordentity->pricefactor = floatval(str_replace(',', '.', $data->pricefactor));
        $recordentity->cfitemid = intval($data->cfitemid);

        $events = [];
        $eventarray = [];
        for ($i = 0; $i < count($data->daysofweek); $i++) {
            $eventarray['title'] = 'openinghours';
            $eventarray['daysofweek'] = implode(',', $data->daysofweek[$i]);
            $eventarray['starthours'] = sprintf("%02d", $data->starthours[$i]);
            $eventarray['startminutes'] = sprintf("%02d", $data->startminutes[$i]);
            $eventarray['endhours'] = sprintf("%02d", $data->endhours[$i]);
            $eventarray['endminutes'] = sprintf("%02d", $data->endminutes[$i]);
            if ($eventarray['starthours'].$eventarray['startminutes'] !=
            $eventarray['endhours'].$eventarray['endminutes']) {
                $events[] = new reoccuringevent($eventarray);
            }
        }
        if (!empty($events)) {
            $recordentity->openinghours = reoccuringevent::events_to_json($events);
        }

        $settingsmanager = new \local_entities\settings_manager();

        $result = $settingsmanager->update_or_createentity($recordentity);
        if ($result && $result > 0) {
            $data->id = $result;
            $options = array('subdirs' => 0, 'maxbytes' => 204800, 'maxfiles' => 1, 'accepted_types' => '*');
            if (isset($data->image_filemanager)) {
                file_postupdate_standard_filemanager($data, 'image', $options, $context, 'local_entities', 'image', $result);
            }
            if (isset($draftitemid)) {
                file_save_draft_area_files($draftitemid, $context->id,
                'local_entities', 'entitycontent',
                $data->id, array('subdirs' => true), $description);
            }
        }
        if (!empty($this->standardhandlers) && !empty($data->id)) {
            foreach ($this->standardhandlers as $handler) {
                $handler->instance_form_save($data);
            }
        }
        if (!empty($this->customhandler) && !empty($data->id)) {
            $this->customhandler->instance_form_save($data);
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
     * Validate data.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     * or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        $errors = array();
        return $errors;
    }

    /**
     * Returns data from form
     * @return object submitted data; NULL if not valid or not submitted or cancelled
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

        $options = array('subdirs' => 0, 'maxbytes' => 204800, 'maxfiles' => 20, 'accepted_types' => '*', 'context' => $context);
        $defaults->descriptionformat = FORMAT_HTML;

        if (!empty($defaults->id)) {
            file_prepare_standard_editor($defaults, 'description',
                $options, $context, 'local_entities', 'entitycontent', $defaults->id);

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

        $description = $defaults->description;
        unset($defaults->description);
        $defaults->description['text'] = $description;
        $defaults->description['format'] = 1;

        if (!empty($defaults->openinghours)) {
            $openinghours = reoccuringevent::json_to_form($defaults->openinghours);
            $defaults->daysofweek = $openinghours->daysofweek ?? 1;
            $defaults->starthours = $openinghours->starthours ?? "00";
            $defaults->startminutes = $openinghours->startminutes ?? "00";
            $defaults->endhours = $openinghours->endhours ?? "00";
            $defaults->endminutes = $openinghours->endminutes ?? "00";
        }

        $this->standardhandlers = \local_entities\customfield\entities_cf_helper::create_std_handlers();
        if (!empty($this->standardhandlers)) {
            foreach ($this->standardhandlers as $handler) {
                $handler->instance_form_before_set_data($defaults);
            }
        }
        if (!empty($this->customhandler)) {
            $this->customhandler->instance_form_before_set_data($defaults);
        }
        $this->entityid = !empty($defaults->id) ? (int) $defaults->id : 0;
        return parent::set_data($defaults);
    }
}
