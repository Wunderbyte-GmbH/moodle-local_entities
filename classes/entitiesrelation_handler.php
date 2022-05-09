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

namespace local_entities;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("$CFG->libdir/formslib.php");

use core_form\external\dynamic_form;
use moodle_recordset;
use MoodleQuickForm;
use stdClass;

/**
 * Control and manage option dates.
 *
 * @copyright Wunderbyte GmbH <info@wunderbyte.at>
 * @author Thomas Winkler
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class entitiesrelation_handler {

    /** @var int $entityid */
    public $entityid = 0;

    /** @var int $instanceid */
    public $instanceid = 0;

    /** @var string $modulename */
    public $modulename = "";



    /**
     * Constructor.
     * @param int $entityid
     * @param int $instanceid
     * @param string $modulename
     */
    public function __construct(string $modulename, int $instanceid = 0) {
        $this->instanceid = $instanceid;
        $this->modulename = $modulename;
    }

    /**
     * Add form fields to be passed on mform.
     *
     * @param MoodleQuickForm $mform
     * @param bool $loadexistingdates Only if this param is set to true, we'll load the already existing dates.
     * @return void
     */
    public function instance_form_definition(MoodleQuickForm &$mform, int $instanceid = 0,
    ?string $headerlangidentifier = null, ?string $headerlangcomponent = null) {
        global $PAGE;

        if (!empty($headerlangidentifier)) {
            $header = get_string($headerlangidentifier, $headerlangcomponent);
        } else {
            $header = get_string('addentity', 'local_entities');
        }
        $mform->addElement('header', 'entitiesrelation', $header);
        $mform->addElement('html', '<div id="entitiesrelation-form">');
        $PAGE->requires->js_call_amd('local_entities/dynamicform', 'init');
        $renderer = $PAGE->get_renderer('local_entities');
        $searchbar = "<input class='m-2' type='text' id='entitysearch' value='search entity'>";
        $mform->addElement('html', $searchbar);
        $html = $renderer->list_entities_select();
        $mform->addElement('html', $html);
        $mform->addElement('hidden', 'local_entities_entityid');
        $mform->setType('local_entities_entityid', PARAM_INT);
        $mform->addElement('hidden', 'local_entities_relationid');
        $mform->setType('local_entities_relationid', PARAM_INT);
        $options = array('disabled' => true);
        $mform->addElement('text', 'local_entities_entityname', get_string('er_entitiesname', 'local_entities'), $options);
        $mform->setType('local_entities_entityname', PARAM_TEXT);
        $mform->addElement('html', '</div>');
        return $mform;
    }
    /**
     * Function to delete relation between module and entities
     *
     * @param integer $instanceid
     * @return void
     */
    public function delete_relation(int $instanceid) {
        global $DB;
        $select = sprintf("modulename = :modulename AND %s = :instanceid", $DB->sql_compare_text('instanceid'));
        $DB->delete_records_select('local_entities_relations', $select, array('modulename' => $this->modulename, 'instanceid' => $instanceid));
    }
    /**
     * Returns the data for the form
     *
     * @param integer $instanceid
     * @return stdClass
     */
    public function get_instance_data(int $instanceid): stdClass {
        global $DB;
        $sql = "SELECT r.*, e.name
                 FROM {local_entities_relations} r
                 JOIN {local_entities} e
                 ON e.id = r.entityid
                 WHERE r.modulename = '{$this->modulename}' AND r.instanceid = {$instanceid}";
        $fieldsdata = $DB->get_record_sql($sql);
        if (!$fieldsdata) {
            $stdclass = new stdClass();
            return $stdclass;
        }
        return $fieldsdata;
    }

    /**
     * Sets the fields from entitiesrelations to the given form if entry is found in DB
     *
     * @param MoodleQuickForm $mform
     * @param stdClass $instance
     * @param integer $instanceid
     * @return void
     */
    public function instance_form_before_set_data(MoodleQuickForm &$mform, stdClass $instance, $instanceid = 0) {
        $instanceid = !empty($instanceid) ? $instanceid : 0;
        $fromdb = $this->get_instance_data($instanceid);
        $entityid = isset($fromdb->entityid) ? $fromdb->entityid : 0;
        $entityname = isset($fromdb->name) ? $fromdb->name : "";
        $erid = isset($fromdb->id) ? $fromdb->id : 0;
        $mform->setDefaults(array('local_entities_relationid' => $erid));
        $mform->setDefaults(array('local_entities_entityid' => $entityid));
        $mform->setDefaults(array('local_entities_entityname' => $entityname));
    }


    /**
     * Saves the given data for entitiesrelations, must be called after the instance is saved and id is present
     *
     * Example:
     *   if ($data = $form->get_data()) {
     *     // ... save main instance, set $data->id if instance was created.
     *     $handler->instance_form_save($data);
     *     redirect(...);
     *   }
     *
     * @param stdClass $instance data received from a form
     */
    public function instance_form_save(stdClass $instance, int $instanceid) {
        if (empty($instanceid)) {
            throw new \coding_exception('Caller must ensure that id is already set in data before calling this method');
        }
        if (!preg_grep('/^local_entities/', array_keys((array)$instance))) {
            // For performance.
            return;
        }
        if (empty($instance->local_entities_entityid)) {
            throw new \coding_exception('No entitiy set');
        }
        $data = new stdClass();
        if (isset($instance->local_entities_relationid)) {
            $data->id = $instance->local_entities_relationid;
        }
        $data->instanceid = $instanceid;
        $data->modulename = $this->modulename;
        $data->entityid = $instance->local_entities_entityid;
        $data->timecreated = time();
        if ($this->er_record_exists($data)) {
            $this->update_db($data);
        } else {
            $this->save_to_db($data);
        }
    }

    /**
     * Saves relation data to DB
     *
     * @param stdClass $data
     * @return void
     */
    public function save_to_db(stdClass $data) {
        global $DB;
        $DB->insert_record('local_entities_relations', $data);
    }

    /**
     * Update relation DB
     *
     * @param stdClass $data
     * @return void
     */
    public function update_db(stdClass $data) {
        global $DB;
        $DB->update_record('local_entities_relations', $data);
    }
    /**
     * Checks if record exists
     *
     * @param stdClass $data
     * @return void
     */
    public function er_record_exists(stdClass $data) {
        global $DB;
        $select = sprintf("modulename = :modulename AND %s = :instanceid", $DB->sql_compare_text('instanceid'));
        if ($DB->record_exists_select('local_entities_relations', $select, array('modulename' => $this->modulename, 'instanceid' => $data->instanceid))) {
            return true;
        }
        return false;
    }

    /**
     * Get date array for a specific weekday and time between two dates.
     *
     * @param int $semesterid
     * @param string $reoccuringdatestring
     * @return array
     */
    public static function get_entities_list(int $semesterid, string $reoccurringdatestring): array {
        return array();
    }
}
