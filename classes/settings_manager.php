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
 * local pages
 *
 * @package     local_entities
 * @author      Thomas Winkler
 * @copyright   2021 Wunderbyte GmbH
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_entities;

use local_entities_external;
use stdClass;

defined('MOODLE_INTERNAL') || die;

/**
 * Class settings_manager
 * @author      Thomas Winkler
 * @copyright   2021 Wunderbyte GmbH
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class settings_manager {

    private $id;

    private $name;

    private $description;

    private $type;

    private $picture;

    private $parentid;

    private $sortorder;

    private $data;

    /**
     * entity constructor.
     * 
     */
    public function __construct(int $id = null) {
        $this->id = $id;
        $this->data = new stdClass();
        $this->data->id = $this->id;
    }

    /**
     *
     * This is to create a new entity in the database
     *
     * @param stdClass $data
     *
     */
    private function create_entity(stdClass $data): int {
        global $DB;
        $handler = \local_entities\customfield\entities_handler::create();
        $id = $DB->insert_record('local_entities', $data);
        // Custom fields save needs id.
        $data->id  = $id;
        $handler->instance_form_save($data);
        return $id;
    }

    /**
     *
     * This is to update the entity based on the data object
     *
     * @param stdClass $data
     * @return int
     */
    private function update_entity(stdClass $data): int {
        global $DB;
        $handler = \local_entities\customfield\entities_handler::create();
        $handler->instance_form_save($data);
        return $DB->update_record('local_entities', $data);
    }

    /**
     *
     * This is to update or create an entity if it does not exist
     *
     * @param stdClass $data
     * @return int $result
     */
    public function update_or_createentity(stdClass $data): int {
        global $USER;
        $data->createdby = $USER->id;
        $data->timemodified = time();
        if (!isset($data->parentid)) {
            $data->parentid = 0;
        }
        if (isset($data->id) && $data->id > 0) {
            $result = $this->update_entity($data);
            if ($result) {
                // TODO Check if address id exists -> than update else create new address  
                for ($i = 0; $i < $data->addresscount; $i++) {
                    $this->update_address($data, $result, $i);
                }
                for ($i = 0; $i < $data->contactscount; $i++) {
                    if ($this->get_all_contacts($data)) {
                        $this->update_contacts($data, $result, $i);
                    } else {
                        $this->update_contacts($data, $result, $i);
                    }
                }
            }
        } else {
            $data->timecreated = time();
            $result = $this->create_entity($data);
            if ($result) {
                for ($i = 0; $i < $data->addresscount; $i++) {
                    $this->create_address($data, $result, $i);
                }
                for ($i = 0; $i < $data->contactscount; $i++) {
                    $this->create_contacts($data, $result, $i);
                }
            }
        }
        return $result;
    }

    /**
     *
     * This is to update the entity based on the data object
     *
     * @param mixed $data
     * @return mixed
     */
    private function update_address(stdClass $data, int $index, int $id): int {
        global $DB;
        $recordaddress = $this->prepare_address($data, $id);
        return $DB->update_record('local_entities_address', $recordaddress);
    }

    /**
     *
     * This is to update the entity based on the data object
     *
     * @param mixed $data
     * @return mixed
     */
    private function create_address(stdClass $data, int $id, int $i): int {
        global $DB;
        $recordaddress = $this->prepare_address($data, $i);
        if ($recordaddress) {
            $recordaddress->entityidto = $id;
            return $DB->insert_record('local_entities_address', $recordaddress);
        }
    }

    private function update_contacts(stdClass $data, int $id): int {
        global $DB;
        $recordcontacts = $this->prepare_contacts($data, $id);
        return $DB->update_record('local_entities_contacts', $recordcontacts);
    }

    /**
     *
     * This is to update the entity based on the data object
     *
     * @param mixed $data
     * @return mixed
     */
    private function create_contacts(stdClass $data, int $id, int $i): int {
        global $DB;
        $recordcontacts = $this->prepare_contacts($data, $i);
        if ($recordcontacts) {
            $recordcontacts->entityidto = $id;
            return $DB->insert_record('local_entities_contacts', $recordcontacts);
        }
        else {
            return false;
        }
    }

    /**
     *
     * This is to update the entity based on the data object
     *
     * @param stdClass $data
     * @return stdClass $addressdata
     * @return null
     */
    public function prepare_address($data, $i): stdClass {
        $addressdata = new stdClass();
        $addressdata->id = isset($data->{'id' . $i}) ? $data->{'id' . $i} : 0;
        $addressdata->country = $data->{'country_' . $i};
        $addressdata->city = $data->{'city_' . $i};
        $addressdata->postcode = $data->{'postcode' . $i};
        $addressdata->streetname = $data->{'streetname_' . $i};
        $addressdata->streetnumber = $data->{'streetnumber_' . $i};
        if ( $addressdata->streetnumber || $addressdata->streetname || $addressdata->postcode || $addressdata->city || $addressdata->country) {
            return $addressdata;
        }
        return null;
    }

    /**
     * Prepare submitted form data for writing to db.
     *
     * @param stdClass $formdata
     * @return stdClass
     */
    public static function form_to_db(stdClass $formdata): stdClass {
        $record = new stdClass();
        $record->id = isset($formdata->id) ? $formdata->id : 0;
        $record->dataid = $formdata->d;
        $record->name = $formdata->name;
        $record->description = $formdata->description;
        $record->required = $formdata->required;

        return $record;
    }

    /**
     * Given a db record make it ready for the form.
     *
     * @param stdClass $record
     * @return stdClass
     */
    public static function db_to_form(stdClass $record): stdClass {
        $formdata = new stdClass();
        $formdata->id = isset($record->id) ? $record->id : 0;
        $formdata->name = $record->name;
        $formdata->description['text'] = $record->description;
        $formdata->name = $record->name;
        $formdata->id = $record->id;
        $formdata->parentid = $record->parentid;
        $formdata->sortorder  = $record->sortorder;
        $formdata->category  = $record->category;
        // Address.
        $i = 0;
        foreach ($record->address as $address) {
            $formdata->{'addressid_' . $i} = $address->id;
            $formdata->{'country_' . $i} = $address->country;
            $formdata->{'city_' . $i} = $address->city;
            $formdata->{'postcode_' . $i} = $address->postcode;
            $formdata->{'streetname_' . $i} = $address->streetname;
            $formdata->{'streetnumber_' . $i} = $address->streetnumber; 
            $i++;
        }
        $formdata->addresscount = $i;
     
        // Contacts.
        $j = 0;
        foreach ($record->contacts as $contact) {
            $formdata->{'contactsid_' . $i} = $address->id;
            $formdata->{'givenname_' . $j} = $contact->givenname;
            $formdata->{'surname_' . $j} = $contact->surname;
            $formdata->{'mail_' . $j} = $contact->mail;
            $j++;
        }
        $formdata->contactscount = $j;

        return $formdata;
    }

    /**
     *
     * Prepare contactdata object for DB (remove postfixes)
     *
     * @param stdClass $data
     * @return stdClass $contactdata
     */
    public function prepare_contacts($data, $i) {
        $contactdata = new stdClass();
        $contactdata->id = isset($data->{'id' . $i}) ? $data->{'id' . $i} : 0;
        $contactdata->givenname = $data->{'givenname' . $i};
        $contactdata->surname = $data->{'surname' . $i};
        $contactdata->mail = $data->{'mail' . $i};
        return $contactdata;
    }


    /**
     * Given the entitiy id, get data from db formatted for moodle form.
     *
     * @param int $entity
     * @return stdClass
     * @throws dml_exception
     */
    public static function get_settings(int $entityid): stdClass {
        global $DB;
        $record = $DB->get_record('local_entities', array('id' => $entityid));
        $record->address[] = $DB->get_record('local_entities_address', array('entityidto' => $entityid));
        $record->contacts[] = $DB->get_record('local_entities_contacts', array('entityidto' => $entityid));
        return self::db_to_form($record);
    }

    /**
     *
     * This is to update or delete an entity if it does not exist
     *
     * @return mixed
     */
    public function delete() {
        global $DB;
        $DB->delete_records('local_entities', array('id' => $this->id));
        //$DB->delete_records('local_addresses', $this->data);
        //$DB->delete_records('local_contacts', $this->data); 
    }

    /**
     *
     * A getter to get items form the entity object
     *
     * @param string $item
     * @return mixed
     */
    public function __get($item) {
        if (isset($this->data->$item)) {
            return $this->data->$item;
        }
    }
}
