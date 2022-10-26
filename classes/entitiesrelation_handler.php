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
use moodle_exception;
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

    /** @var string $component */
    public $component = '';

    /** @var string $area */
    public $area = '';

    /** @var int $instanceid */
    public $instanceid = 0;

    /**
     * Constructor.
     * @param string $component
     * @param string $area
     * @param int $instanceid
     */
    public function __construct(string $component, string $area, int $instanceid = 0) {
        $this->component = $component;
        $this->area = $area;
        $this->instanceid = $instanceid;
    }

    /**
     * Add form fields to be passed on mform.
     *
     * @param MoodleQuickForm $mform
     * @param int $instanceid
     * @param string $formmode 'simple' or 'expert' mode
     * @param string $headerlangidentifier
     * @param string $headerlangcomponent
     * @return void
     */
    public function instance_form_definition(MoodleQuickForm &$mform, int $instanceid = 0, string $formmode = 'expert',
        ?string $headerlangidentifier = null, ?string $headerlangcomponent = null) {
        global $DB, $OUTPUT, $PAGE;

        // Workaround: Only show, if it is not turned off in the option form config.
        // We currently need this, because hideIf does not work with headers.
        // In expert mode, we always show everything.
        $showheader = true;

        if (!empty($headerlangidentifier)) {
            $header = get_string($headerlangidentifier, $headerlangcomponent);
        } else {
            $header = get_string('addentity', 'local_entities');
        }

        if ($formmode !== 'expert') {
            $cfgentityheader = $DB->get_field('booking_optionformconfig', 'active',
                ['elementname' => 'entitiesrelation']);
            if ($cfgentityheader == 0) {
                $showheader = false;
            }
        }

        if ($showheader) {
            $header = get_string('addentity', 'local_entities');

            $mform->addElement('header', 'entitiesrelation', $header);

            $records = \local_entities\entities::list_all_parent_entities();

            $select = [0 => get_string('none', 'local_entities')];
            foreach ($records as $record) {
                $select[$record->id] = $record->name;
            }
            $options = [
                'multiple' => false,
                'noselectionstring' => get_string('none', 'local_entities')
            ];

            $mform->addElement('autocomplete', 'local_entities_entityid', get_string('er_entitiesname', 'local_entities'),
                $select, $options);
        }

        $mform->addElement('button', 'openmodal', get_string('opentimetable', 'local_entities'));
        $PAGE->requires->js_call_amd('local_entities/handler', 'init');

        return $mform;
    }

    /**
     * Function to validate the correct input of entity and mainly it's availability.
     * In order to work, the key "datestobook" has to be present as an array of entitydates.
     * If there is an itemid, then the dates are already booked. If itemid is 0, they are new.
     * This distinction is important to no falsly identify conflict with itself.
     *
     * @param array $data
     * @return void
     */
    public function instance_form_validation(array $data, array &$errors) {

        // First, see if an entitiyid is set. If not, we can proceed right away.
        if (!preg_grep('/^local_entities/', array_keys($data))) {
            // For performance.
            return;
        }

        if (!$data['local_entities_entityid']) {
            return;
        }

        // Now determine if there is a conflict.

        $conflicts = entities::return_conflicts($data['local_entities_entityid'],
        $data['datestobook'],
        $data['optionid'] ?? 0,
        'optiondate');

        if (!empty($conflicts)) {

            $errors['local_entities_entityid'] = get_string('errorwiththefollowingdates', 'local_entities');

            foreach ($conflicts as $conflict) {
                $link = $conflict->link->out();
                $errors['local_entities_entityid'] .= "<br><a href='$link'>$conflict->name</a>";
            }
        }
    }

    /**
     * Function to delete relation between module and entities.
     * @param int $instanceid
     * @return void
     */
    public function delete_relation(int $instanceid): void {
        global $DB;
        $select = sprintf("component = :component AND area = :area AND %s = :instanceid", $DB->sql_compare_text('instanceid'));
        $DB->delete_records_select('local_entities_relations', $select, [
            'component' => $this->component,
            'area' => $this->area,
            'instanceid' => $instanceid
        ]);
    }

    /**
     * Returns the data for the form.
     * @param int $instanceid
     * @return stdClass
     */
    public function get_instance_data(int $instanceid): stdClass {
        global $DB;
        $sql = "SELECT r.entityid as id, r.id as relationid, r.component, r.area, r.instanceid,
                    e.name, e.shortname, r.timecreated
                 FROM {local_entities_relations} r
                 JOIN {local_entities} e
                 ON e.id = r.entityid
                 WHERE r.component = '{$this->component}'
                 AND r.area = '{$this->area}'
                 AND r.instanceid = {$instanceid}";
        $fieldsdata = $DB->get_record_sql($sql);
        if (!$fieldsdata) {
            $stdclass = new stdClass();
            return $stdclass;
        }
        return $fieldsdata;
    }

    /**
     * Returns entityid for a given instanceid.
     * @param int $instanceid
     * @return int entityid
     */
    public function get_entityid_by_instanceid(int $instanceid): int {
        global $DB;
        $sql = "SELECT r.entityid
                 FROM {local_entities_relations} r
                 WHERE r.component = '{$this->component}'
                 AND r.area = '{$this->area}'
                 AND r.instanceid = {$instanceid}";
        $entityid = $DB->get_field_sql($sql);
        if (empty($entityid)) {
            return 0;
        }
        return (int) $entityid;
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
        $entityid = isset($fromdb->id) ? $fromdb->id : 0;
        $entityname = isset($fromdb->name) ? $fromdb->name : "";
        $erid = isset($fromdb->relationid) ? $fromdb->relationid : 0;
        $mform->setDefaults(array('local_entities_relationid' => $erid));
        $mform->setDefaults(array('local_entities_entityid' => $entityid));
        $mform->setDefaults(array('local_entities_entityname' => $entityname));
    }


    /**
     * Saves the given data for entitiesrelations, must be called after the instance is saved and id is present
     * Function returns id of newly created or updated entity, if present.
     * Example:
     *   if ($data = $form->get_data()) {
     *     // ... save main instance, set $data->id if instance was created.
     *     $handler->instance_form_save($data);
     *     redirect(...);
     *   }
     *
     * @param stdClass $instance
     * @param integer $instanceid
     * @return
     */
    public function instance_form_save(stdClass $instance, int $instanceid) {
        if (empty($instanceid)) {
            throw new \coding_exception('Caller must ensure that id is already set in data before calling this method');
        }
        if (!preg_grep('/^local_entities/', array_keys((array)$instance))) {
            // If this is called with no result, we must delete the handler.
            $this->delete_relation($instanceid);
            return;
        }
        if (empty($instance->local_entities_entityid)) {
            $this->delete_relation($instanceid);
            return;
        }

        $data = new stdClass();
        if (isset($instance->local_entities_relationid)) {
            $data->id = $instance->local_entities_relationid;
        }
        $data->instanceid = $instanceid;
        $data->component = $this->component;
        $data->area = $this->area;
        $data->entityid = $instance->local_entities_entityid;
        $data->timecreated = time();
        // Delete er if entitiyid is set to -1.
        if ($data->entityid == -1) {
            $this->delete_relation($data->instanceid);
            return;
        }
        if ($this->er_record_exists($data)) {
            return $this->update_db($data);
        } else {
            return $this->save_to_db($data);
        }
    }

    /**
     * This saves a new relation and creates a "fake" form to use the form_save method.
     *
     * @param int $instanceid
     * @param int $entityid
     * @return void
     */
    public function save_entity_relation($instanceid, $entityid) {

        $instance = new stdClass();

        $instance->local_entities_entityid = $entityid;

        $this->instance_form_save($instance, $instanceid);
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
     * @return int
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
    public function er_record_exists(stdClass &$data) {
        global $DB;
        $select = sprintf("component = :component AND area = :area AND %s = :instanceid", $DB->sql_compare_text('instanceid'));
        if ($id = $DB->get_field_select('local_entities_relations', 'id', $select, [
                'component' => $this->component,
                'area' => $this->area,
                'instanceid' => $data->instanceid
        ])) {
            $data->id = $id;
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

    /**
     * Get an array of all the entities with exactly this name.
     * @param string $entityname
     * @return array
     */
    public function get_entities_by_name(string $entityname) {
        global $DB;
        // We see if there are more than one entities with the same name.
        if ($entities = $DB->get_records('local_entities', ['name' => $entityname])) {
            return $entities;
        } else {
            return [];
        }
    }

    /**
     * Get an array of all the entities with exactly this shortname.
     * @param string $shortname
     * @return array
     */
    public function get_entities_by_shortname(string $shortname) {
        global $DB;
        // We see if there are more than one entities with the same shortname.
        if ($entities = $DB->get_records('local_entities', ['shortname' => $shortname])) {
            return $entities;
        } else {
            return [];
        }
    }

    /**
     * Return entity by id.
     *
     * @param integer $entityid
     * @return bool|array
     */
    public static function get_entity_by_id(int $entityid) {
        global $DB;

        $sql = "SELECT  ea.id as addressid, e.id as id, e.name, e.shortname, e.description,
                        e.type, e.timecreated, e.timemodified, e.openentity, e.createdby,
                        e.picture, e.parentid, e.sortorder, ea.country, ea.city, ea.postcode,
                        ea.streetname, ea.streetnumber, ea.maplink, ea.mapembed
                FROM {local_entities} e
                LEFT JOIN {local_entities_address} ea
                ON e.id = ea.entityidto
                WHERE e.id = :entityid";
        $params = ['entityid' => $entityid];

        // We might have more than one record, as there might be more than one address.
        if ($entities = $DB->get_records_sql($sql, $params)) {
            return $entities;
        } else {
            return [];
        }
    }

    /**
     * Helper function to remove all entries in local_entities_relations
     * for a specific booking instance (by bookingid).
     * @param int $bookingid the id of the booking instance
     * @return bool $success - true if successful, false if not
     */
    public static function delete_entities_relations_by_bookingid(int $bookingid): bool {
        global $DB;

        if (empty($bookingid)) {
            throw new moodle_exception('Could not clear entries from local_entities_relations because of missing booking id.');
        }

        // Initialize return value.
        $success = true;

        // TODO: In the future, we'll also need to delete relations for optiondates.

        // Get all currently existing entities relations of the booking instance.
        $existingoptions = $DB->get_records('booking_options', ['bookingid' => $bookingid], '', 'id');
        if (!empty($existingoptions)) {
            foreach ($existingoptions as $existingoption) {
                if (!$DB->delete_records('local_entities_relations', [
                    'component' => 'mod_booking',
                    'area' => 'option',
                    'instanceid' => $existingoption->id
                ])) {
                    $success = false;
                }
            }
        }

        return $success;
    }

    /**
     * Returns pricefactor set in DB. Can be used for automatic pricecalculation used in booking.
     *
     * @param int $id entity id
     * @return float $pricefactor
     */
    public static function get_pricefactor_by_entityid(int $id) {
        global $DB;
        $params = array('id' => $id);
        $pricefactor = $DB->get_field_select('local_entities', 'pricefactor', 'id = :id', $params, IGNORE_MISSING);
        return $pricefactor;
    }

    /**
     * Return a modal
     *
     * @return string
     */
    private static function render_modal() {
        return '<button type="button" class="btn btn-primary" data-toggle="modal" data-target=".bd-example-modal-lg">Large modal</button>
        <div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
        <div class="modal-content">
                ...
                </div>
            </div>
            </div>';
    }
}
