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
 * @package local_entities
 * @author Georg Maißer
 * @copyright 2021 Wunderbyte GmbH
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_entities;

use csv_import_reader;
use html_writer;
use local_entities\customfield\entities_cf_helper;
use local_entities\form\edit_dynamic_form;
use stdClass;
defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once("$CFG->libdir/externallib.php");

/**
 * Class entity
 *
 * @author Georg Maißer
 * @copyright 2022 Wunderbyte GmbH
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class csv_import {

    /**
     * @var string
     */
    protected $delimiter = 'comma';

    /**
     * @var string
     */
    protected $enclosure = '';

    /**
     * @var string
     */
    protected $encoding = 'UTF-8';

    /**
     * @var array of column names
     */
    protected $columns = [];

    /**
     * @var array of fieldnames imported from csv
     */
    protected $fieldnames = [];

    /**
     * @var string with errors one per line
     */
    protected $csverrors = '';

    /**
     * @var object
     */
    protected $formdata = null;

    /**
     * @var string error message
     */
    protected $error = '';

    /**
     * entities constructor.
     */
    public function __construct() {
        global $DB;
        $this->columns = $DB->get_columns('local_entities');
    }


    public function process_data($csvcontent, $data) {

        global $DB;
        $this->error = '';
        $this->formdata = $data;
        $iid = csv_import_reader::get_new_iid('modbooking');
        $cir = new csv_import_reader($iid, 'modbooking');

        $delimiter = !empty($this->formdata->delimiter_name) ? $this->formdata->delimiter_name : 'comma';
        $enclosure = !empty($this->formdata->enclosure) ? $this->formdata->enclosure : '"';
        $encoding = !empty($this->formdata->encoding) ? $this->formdata->encoding : 'utf-8';
        $updateexisting = !empty($this->formdata->updateexisting) ? $this->formdata->updateexisting : false;
        $readcount = $cir->load_csv_content($csvcontent, $encoding, $delimiter, null, $enclosure);

        if (empty($readcount)) {
            $this->error .= $cir->get_error();
            return false;
        }

        // Csv column headers.
        if (!$fieldnames = $cir->get_columns()) {
            $this->error .= $cir->get_error();
            return false;
        }

        if (empty($readcount)) {
            $this->error .= $cir->get_error();
            return false;
        }

        // Csv column headers.
        if (!$fieldnames = $cir->get_columns()) {
            $this->error .= $cir->get_error();
            return false;
        }
        $this->fieldnames = $fieldnames;

        $i = 0;
        $cir->init();
        while ($line = $cir->next()) {
            // Import every entitiy.

            $csvrecord = array_combine($fieldnames, $line);

            // At this point, we need to unset a few fields a user just can't fill out.
            self::unset_protected_fields($csvrecord);

            // Customfields have to get their prefix.
            $this->identify_fieldtypes($csvrecord);

            $entity = [];

            // We check if there is an id and we can match to an existing entity.
            if (isset($csvrecord['id'])) {
                $record = $DB->get_record('local_entities', ['id' => $csvrecord['id']]);

                if ($record) {
                    $entity = (array)$record;
                }
            }

            // Set type of item.
            if (isset($csvrecord['cfitemid'])) {
                $entity['cfitemid'] = $csvrecord['cfitemid'];
                unset($csvrecord['cfitemid']);
                unset($csvrecord['entitiescategory']);
            } else if (isset($cfitemid['entitiescategory'])) {
                $categories = entities_cf_helper::get_alternative_cf_categories();

                foreach ($categories as $key => $value) {
                    if ($value == $cfitemid['entitiescategory']) {
                        $entity['cfitemid'] = $key;
                    }
                }
                unset($csvrecord['entitiescategory']);
            }

            // Overwrite values.
            foreach ($csvrecord as $key => $value) {
                $entity[$key] = $value;
            }

            // Validate data.
            // if (!$this->validate_data($csvrecord, $i)) {
            //     // Save validated data to db.

            //     $this->add_csverror("This error", $i);
            //                 continue;
            // }

            $formdata = \core_customfield\field_config_form::mock_ajax_submit($entity);
            $entitiesform = new edit_dynamic_form(null, null, 'post', '', [], true, $formdata);

            $entitiesform->set_data_for_dynamic_submission();
            $entitiesform->is_validated();
            $entitiesform->process_dynamic_submission();

            $i++;
        }
        $cir->cleanup(true);
        $cir->close();
        return true;

    }

    /**
     * Validate lines in csv data. Write it to csverrors.
     *
     * @param array $csvrecord
     * @param $linenumber
     * @return bool true on validation false on error
     */
    protected function validate_data(array &$csvrecord, $linenumber) {

        // Set to false if error occured in csv-line.
        if (empty($csvrecord['text'])) {
            $this->add_csverror('There seems to be an empty line.', $linenumber);
                    return false;
        }
        return true;
    }

    /**
     * Map csv fieldnames with table column names.
     *
     */
    protected function map_fieldnames() {
        foreach ($this->fieldnames as $key => $fieldname) {
            switch ($fieldname) {
                case 'startdate':
                    $this->fieldnames[$key] = 'coursestarttime';
                    break;
                case 'enddate':
                    $this->fieldnames[$key] = 'courseendtime';
                    break;
            }
        }
    }

    /**
     * Add error message to $this->csverrors
     *
     * @param $errorstring
     */
    protected function add_csverror($errorstring, $i) {
        $this->csverrors .= html_writer::empty_tag('br');
        $this->csverrors .= "Error in line $i: ";
        $this->csverrors .= $errorstring;
    }

    /**
     * Function to unset fields a user must not fill out.
     *
     * @param array $array
     * @return void
     */
    protected static function unset_protected_fields(array &$array) {
        unset($array['id']);
        unset($array['type']);
        unset($array['timecreated']);
        unset($array['timemodified']);
        unset($array['createdby']);
        unset($array['sortorder']);
    }

    /**
     * Function to unset fields a user must not fill out.
     *
     * @param array $array
     * @return void
     */
    protected function identify_fieldtypes(array &$csvrecord) {

        $keys = array_keys($this->columns);

        foreach ($csvrecord as $key => $value) {

            if (empty($value)) {
                unset($csvrecord[$key]);
            } else if (!in_array($key, $keys)) {
                // If there is already the prefix, we just skip.
                if (strpos($key, 'customfield_') !== false) {
                    continue;
                }
                // Else, we set the prefix and obmit the old record.
                $csvrecord['customfield_' . $key] = $value;
                unset($csvrecord[$key]);
            }
        }
    }


}
