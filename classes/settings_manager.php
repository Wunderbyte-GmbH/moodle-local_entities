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

    private $open;

    private $addresses = array();

    private $contacts = array();

    private $data;

    /**
     * entity constructor.
     *
     */
    public function __construct(int $id = null) {
        if (isset($id) && $id > 0) {
            $this->id = $id;
            $this->data = new stdClass();
            $this->data = $this->get_settings($this->id);
            $this->name = $this->data->name;
            $this->description = $this->data->description;
            $this->parentid = $this->data->parentid;
            $this->open = $this->data->open;
            $this->type = $this->data->type;
            $this->addresses = $this->data->address;
            $this->contacts = $this->data->contacts;
        } else {
            $this->id = 0;
        }
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
            $this->update_entity($data);
            // TODO Check if address id exists -> than update else create new address.
            for ($i = 0; $i < $data->addresscount; $i++) {
                $this->update_address($data, $i);
            }
            for ($i = 0; $i < $data->contactscount; $i++) {
                $this->update_contacts($data, $i);
            }
            $result = $data->id;
        } else {
            $data->timecreated = time();
            $result = $this->create_entity($data);
            if ($result) {
                for ($i = 0; $i < $data->addresscount; $i++) {
                    $this->create_address($data, $i);
                }
                for ($i = 0; $i < $data->contactscount; $i++) {
                    $this->create_contacts($data, $i);
                }
            }
        }
        if (!isset($data->image_filemanager)) {
            $data = $this->prepare_image($data, $result);
        }

        $data->picture = "test";
        return $result;
    }

    /**
     *
     * This is to update the entity based on the data object
     *
     * @param mixed $data
     * @return mixed
     */
    private function update_address(stdClass $data, int $index): int {
        global $DB;
        $recordaddress = $this->prepare_address($data, $index);
        $recordaddress->entityidto = $data->id;
        if ($recordaddress->del == 1) {
            $this->delete_address($recordaddress->id);
            $result = 0;
        } else if ($recordaddress->id != -1) {
            if ($recordaddress->id == 0) {
                $result = $DB->insert_record('local_entities_address', $recordaddress);
            } else {
                $result = $DB->update_record('local_entities_address', $recordaddress);
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
    private function create_address(stdClass $data, int $index): int {
        global $DB;
        $recordaddress = $this->prepare_address($data, $index);
        if (isset($recordaddress->id)) {
            $recordaddress->entityidto = $data->id;
            return $DB->insert_record('local_entities_address', $recordaddress);
        }
        return 0;
    }

    private function update_contacts(stdClass $data, int $index): int {
        global $DB;
        $recordcontacts = $this->prepare_contacts($data, $index);
        $recordcontacts->entityidto = $data->id;
        if ($recordcontacts->id == 0) {
            return $DB->insert_record('local_entities_contacts', $recordcontacts);
        } else {
            return $DB->update_record('local_entities_contacts', $recordcontacts);
        }
    }

    /**
     *
     * This is to update the entity based on the data object
     *
     * @param mixed $data
     * @return mixed
     */
    private function create_contacts(stdClass $data, int $index): int {
        global $DB;
        $recordcontacts = $this->prepare_contacts($data, $index);
        if (isset($recordcontacts->id)) {
            $recordcontacts->entityidto = $data->id;
            return $DB->insert_record('local_entities_address', $recordcontacts);
        }
        return 0;
    }

    /**
     *
     * This is to update the entity based on the data object
     *
     * @param stdClass $data
     * @param int $result
     * @return stdClass $addressdata
     * @return null
     */
    public function prepare_image($data, $i): stdClass {
        if (isset($data->ogimage_filemanager)) {
            $context = \context_system::instance();
            $options = array('subdirs' => 0, 'maxbytes' => 204800, 'maxfiles' => 1, 'accepted_types' => '*');
            $data = file_postupdate_standard_filemanager($data, 'image', $options, $context->id, 'local_entities', 'image', $i);
        }
        return $data;
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
        if (!empty($data->{'city_' . $i}) && $data->{'postcode_' . $i} > 0) {
            $addressdata->id = isset($data->{'addressid_' . $i}) ? $data->{'addressid_' . $i} : 0;
            $addressdata->country = $data->{'country_' . $i};
            $addressdata->city = $data->{'city_' . $i};
            $addressdata->postcode = $data->{'postcode_' . $i};
            $addressdata->streetname = $data->{'streetname_' . $i};
            $addressdata->streetnumber = $data->{'streetnumber_' . $i};
            $addressdata->del = 0;
        } else {
            $addressdata->id = isset($data->{'addressid_' . $i}) ? $data->{'addressid_' . $i} : 0;
            $addressdata->del = isset($data->{'addressid_' . $i}) ? 1 : 0;
        }
        return $addressdata;
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
    public function db_to_form(int $copy = 0): stdClass {
        $formdata = new stdClass();
        if ($copy) {
            $formdata->id = 0;
        } else {
            $formdata->id = isset($this->data->id) ? $this->data->id : 0;
        }
        $formdata->name = $this->data->name;
        $formdata->description['text'] = $this->data->description;
        $formdata->name = $this->data->name;
        $formdata->id = $this->data->id;
        $formdata->parentid = $this->data->parentid;
        $formdata->sortorder  = $this->data->sortorder;
        $formdata->type  = $this->data->type;
        $formdata->open  = $this->data->open;
        // Address.
        $i = 0;
        if ($this->data->address) {
            foreach ($this->data->address as $address) {
                $formdata->{'addressid_' . $i} = $address->id;
                $formdata->{'country_' . $i} = $address->country;
                $formdata->{'city_' . $i} = $address->city;
                $formdata->{'postcode_' . $i} = $address->postcode;
                $formdata->{'streetname_' . $i} = $address->streetname;
                $formdata->{'streetnumber_' . $i} = $address->streetnumber;
                $i++;
            }
        } else {
            $i = 1;
        }

        $formdata->addresscount = $i;

        // Contacts.
        $j = 0;
        if ($this->data->contacts) {
            foreach ($this->data->contacts as $contact) {
                $formdata->{'contactsid_' . $j} = $contact->id;
                $formdata->{'givenname_' . $j} = $contact->givenname;
                $formdata->{'surname_' . $j} = $contact->surname;
                $formdata->{'mail_' . $j} = $contact->mail;
                $j++;
            }
        } else {
            $j = 1;
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
        $contactdata->id = isset($data->{'contactsid_' . $i}) ? $data->{'contactsid_' . $i} : 0;
        $contactdata->givenname = $data->{'givenname_' . $i};
        $contactdata->surname = $data->{'surname_' . $i};
        $contactdata->mail = $data->{'mail_' . $i};
        return $contactdata;
    }


    /**
     * Given the entitiy id, get data from db formatted for moodle form.
     *
     * @param int $copy
     * @return stdClass
     * @throws dml_exception
     */
    public function get_settings_forform(int $copy = 0): stdClass {
        return self::db_to_form($copy);
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
        $address = $DB->get_records('local_entities_address', array('entityidto' => $entityid));
        $contacts = $DB->get_records('local_entities_contacts', array('entityidto' => $entityid));
        $record->address = $address ? $address : null;
        $record->contacts = $contacts ? $contacts : null;
        return $record;
    }


    /**
     *
     * This is to delete an entity
     *
     */
    public function delete() {
        global $DB;
        $DB->delete_records('local_entities', array('id' => $this->id));
        $DB->delete_records('local_entities_address', array('entityidto' => $this->id));
        $DB->delete_records('local_entities_contacts', array('entityidto' => $this->id));
        $handler = \local_entities\customfield\entities_handler::create($this->id);
        $handler->delete_instance();
    }

    /**
     *
     * This is to delete an entity via webservice
     *
     * @param int $id of entitiy
     */
    public static function deletews($id) {
        global $DB;
        $DB->delete_records('local_entities', array('id' => $id));
        $DB->delete_records('local_entities_address', array('entityidto' => $id));
        $DB->delete_records('local_entities_contacts', array('entityidto' => $id));
        $handler = \local_entities\customfield\entities_handler::create($id);
        $handler->delete_instance();
    }

    /**
     *
     * This is to delete a address entry
     *
     * @param int $id of address
     */
    public static function delete_address($id) {
        global $DB;
        $DB->delete_records('local_entities_address', array('id' => $id));
    }

    /**
     *
     * This is to delete a contact entry
     *
     * @param int $id of contact entry
     */
    public static function delete_contacts($id) {
        global $DB;
        $DB->delete_records('local_entities_contacts', array('id' => $id));
    }

    public static function get_children($id) {
        global $DB;
        $sql = "SELECT id, name, parentid FROM {local_entities} Where parentid = {$id}";
        return $DB->get_records_sql($sql);
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
