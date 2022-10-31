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

/**
 * Class entity
 * @author      Thomas Winkler
 * @copyright   2021 Wunderbyte GmbH
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class entity {

    /**
     * @var $_data
     */
    private $_data;

    /**
     * entity constructor.
     * @param mixed $data
     */
    public function __construct($data) {
        $this->_data = $data;
    }

    public function isopen($data) {

    }

    /**
     *
     * A getter to get items form the entity object
     *
     * @param string $item
     * @return mixed
     */
    public function __get($item) {
        if (isset($this->_data->$item)) {
            return $this->_data->$item;
        }
    }

    /**
     *
     * This is to load the entity based on the enitity id
     *
     * @param integer $id
     * @return object
     */
    public static function load($id, $editor = false) {
        global $DB;
        $data = new \stdClass();
        $data = $DB->get_record_sql("SELECT * FROM {local_entities} WHERE id=? LIMIT 1", array(intval($id)));
        return new entity($data);
    }
}
