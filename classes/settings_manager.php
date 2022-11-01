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

namespace local_entities;

use cache_helper;
use local_entities_external;
use stdClass;

/**
 * Class settings_manager
 * @author      Thomas Winkler
 * @copyright   2021 Wunderbyte GmbH
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class settings_manager {

    /** @var int $id */
    private $id;

    /** @var stdClass $data */
    private $data;

    /**
     * Entity constructor.
     *
     */
    public function __construct(int $id = null) {
        $this->id = $id;
        $this->data = new stdClass();
        $this->data->id = $this->id;
    }

    /**
     * This is to create a new entity in the database
     * @param stdClass $data
     */
    private function create_entity(stdClass $data): int {
        global $DB;
        $id = $DB->insert_record('local_entities', $data);
        // Custom fields save needs id.
        $data->id  = $id;
        // Unset empty hidden customfields (otherwise persistence error is thrown).
        foreach ($data as $key => $property) {
            if (strpos( $key , 'customfield' ) === 0) {
                if (!$property) {
                    unset($data->{$key});
                }
                if (is_array($property)) {
                    if (!$property['text']) {
                        unset($data->{$key});
                    }
                }
            }
        }
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

        // Purge cache for options table.
        cache_helper::purge_by_event('setbackoptionstable');

        /* When we update an entity, we need to purge caches
        for all options associated with the entity. */
        $affectedoptionids = $DB->get_fieldset_sql(
            "SELECT DISTINCT instanceid FROM {local_entities_relations}
             WHERE component = 'mod_booking'
             AND area = 'option'
             AND entityid = :entityid",
            ['entityid' => $data->id]
            // TODO: Needs to be updated when table is changed!
        );
        cache_helper::invalidate_by_event('setbackoptionsettings', $affectedoptionids);

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

        // Addresscount is unrelyable...
        if (isset($data->country_0)
            || isset($data->city_0)
            || isset($data->postcode_0)
            || isset($data->streetname)
            || isset($data->streetnumber_0)
            || isset($data->maplink_0)
            || isset($data->mapembed_0)) {
                $data->addresscount = 1;
        }

        // Addresscount is unrelyable...
        if (isset($data->givenname_0)
            || isset($data->surname_0)
            || isset($data->mail_0)) {
                $data->contactscount = 1;
    }

        if (isset($data->id) && $data->id > 0) {
            $this->update_entity($data);
            // TODO: Check if address id exists -> then update, else create new address.

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
        return $result;
    }

    /**
     *
     * This is to update the entity based on the data object
     *
     * @param stdClass $data
     * @return mixed
     */
    private function update_address(stdClass $data, int $index): int {
        global $DB;
        $recordaddress = $this->prepare_address($data, $index);
        $recordaddress->entityidto = $data->id;
        if (empty($recordaddress->id)) {
            $result = $DB->insert_record('local_entities_address', $recordaddress);
        } else {
            $result = $DB->update_record('local_entities_address', $recordaddress);
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
        $recordcontacts->entityidto = $data->id;
        if ($recordcontacts->id == 0) {
            return $DB->insert_record('local_entities_contacts', $recordcontacts);
        }
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
        $addressdata->id = isset($data->{'addressid_' . $i}) ? $data->{'addressid_' . $i} : 0;
        $addressdata->country = $data->{'country_' . $i};
        $addressdata->city = $data->{'city_' . $i};
        $addressdata->postcode = $data->{'postcode_' . $i};
        $addressdata->streetname = $data->{'streetname_' . $i};
        $addressdata->streetnumber = $data->{'streetnumber_' . $i};
        $addressdata->maplink = $data->{'map_link_' . $i};
        $addressdata->mapembed = $data->{'map_embed_' . $i};

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
        $record->shortname = $formdata->shortname;
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
        $formdata->id = $record->id ?? 0;
        $formdata->name = $record->name;
        $formdata->shortname = $record->shortname;
        $formdata->description['text'] = $record->description;
        $formdata->pricefactor = floatval(str_replace(',', '.', $record->pricefactor));
        $formdata->parentid = $record->parentid;
        $formdata->sortorder  = $record->sortorder;
        $formdata->status  = $record->status;
        $formdata->openinghours  = $record->openinghours;
        $formdata->cfitemid = $record->cfitemid;
        // Address.
        $i = 0;
        if ($record->address[0]) {
            foreach ($record->address[0] as $address) {
                $formdata->{'addressid_' . $i} = $address->id;
                $formdata->{'country_' . $i} = $address->country;
                $formdata->{'city_' . $i} = $address->city;
                $formdata->{'postcode_' . $i} = $address->postcode;
                $formdata->{'streetname_' . $i} = $address->streetname;
                $formdata->{'streetnumber_' . $i} = $address->streetnumber;
                $formdata->{'map_link_' . $i} = $address->maplink;
                $formdata->{'map_embed_' . $i} = $address->mapembed;
                $i++;
            }
        } else {
            $i = 1;
        }

        $formdata->addresscount = $i;

        // Contacts.
        $j = 0;
        if (($record->contacts[0])) {
            foreach ($record->contacts[0] as $contact) {
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
     * @return stdClass $contactdatalocal_entities_address
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
     * Gets array of categories out of categoriy setting
     *
     * @return array
     */
    public static function get_standardcategories(): array {
        $categorycfg = get_config('local_entities', 'categories');
        $categories = explode(',', $categorycfg);
        return $categories;
    }


    /**
     * Given the entitiy id, get data from db formatted for moodle form.
     *
     * @param int $entity
     * @return stdClass
     * @throws dml_exception
     */
    public static function get_settings_forform(int $entityid): stdClass {
        global $DB;
        $record = $DB->get_record('local_entities', array('id' => $entityid));
        $address = $DB->get_records('local_entities_address', array('entityidto' => $entityid));
        $contacts = $DB->get_records('local_entities_contacts', array('entityidto' => $entityid));
        $record->address[] = $address ?? null;
        $record->contacts[] = $contacts ?? null;
        return self::db_to_form($record);
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
        $record->address = $address ?? null;
        $record->contacts = $contacts ?? null;
        return $record;
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
        $DB->delete_records('local_entities_address', array('entityidto' => $this->id));
        $DB->delete_records('local_entities_contacts', array('entityidto' => $this->id));
        $handler = \local_entities\customfield\entities_handler::create();
        $handler->delete_instance($this->id);
    }

    public function delete_address($id) {
        global $DB;
        $DB->delete_records('local_entities_address', array('id' => $id));
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
