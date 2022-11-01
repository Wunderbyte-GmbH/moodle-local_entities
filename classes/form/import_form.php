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
require_once($CFG->libdir . "/csvlib.class.php");

use core_form\dynamic_form;
use context_system;
use context;
use core_text;
use csv_import_reader;
use html_writer;
use local_entities\csv_import;
use moodle_url;
use moodleform;
use stdClass;


/**
 * Dynamic import csv form
 * @copyright Wunderbyte GmbH <info@wunderbyte.at>
 * @author Georg Maißer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_form extends dynamic_form {

    /**
     * {@inheritdoc}
     * @see moodleform::definition()
     */
    public function definition() {

        global $CFG;

        $mform = $this->_form;
        $mform->addElement('filepicker', 'csvfile', get_string('csvfile', 'booking'), null,
                array('maxbytes' => $CFG->maxbytes, 'accepted_types' => '*'));
        $mform->addRule('csvfile', null, 'required', null, 'client');

        $choices = csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'delimiter_name', get_string('csvdelimiter', 'tool_uploaduser'), $choices);
        if (array_key_exists('cfg', $choices)) {
            $mform->setDefault('delimiter_name', 'cfg');
        } else if (get_string('listsep', 'langconfig') == ';') {
            $mform->setDefault('delimiter_name', 'semicolon');
        } else {
            $mform->setDefault('delimiter_name', 'comma');
        }

        $choices = core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'tool_uploaduser'), $choices);
        $mform->setDefault('encoding', 'UTF-8');

        $mform->addElement('text', 'dateparseformat', get_string('dateparseformat', 'booking'));
        $mform->setType('dateparseformat', PARAM_NOTAGS);
        $mform->setDefault('dateparseformat', get_string('defaultdateformat', 'booking'));
        $mform->addRule('dateparseformat', null, 'required', null, 'client');
        $mform->addHelpButton('dateparseformat', 'dateparseformat', 'mod_booking');

        $this->add_action_buttons(true, get_string('import'));
        // $mform->addElement('header', 'importinfo', get_string('import') . ' ' . get_string('info') );
        // $mform->addElement('html', '<div class="qheader">' . $this->_customdata['importer']->display_importinfo() . '</div>');
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
        global $CFG, $OUTPUT;
        // We don't need the data of the form.
        // $data = (object)$this->_ajaxformdata;

        $importer = new csv_import();
        $csvfile = $this->get_file_content('csvfile');

        // $importer->process_data($csvfile, $data);

        $data = new stdClass();

        if ($importer->process_data($csvfile, $data)) {
            $data->success = true;
            if (!empty($importer->get_line_errors())) {
                $data->lineerrors = $importer->get_line_errors();
            }
        } else {
            // Not ok, write error.
            $data->success = false;
            $data->error = $importer->get_error();
        }

        return $data;
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
        return new moodle_url('/local/entities/import.php');
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

        return parent::set_data($defaults);
    }
}
