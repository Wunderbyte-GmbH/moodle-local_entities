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
 * Tests for local entities web service
 *
 * @package local_entities
 * @category test
 * @copyright 2021 Wunderbyte GmbH
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//use local_entities\external;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for the external class of local_entities.
 *
 * Tests the following webservices of plugin local_entities
 *      - list_all_parent_entities,
 *      - update_entity
 *      - list_all_subentries
 * Tests are parameterised, i.e. there's a data provider for each
 */
class external_test extends \advanced_testcase {

    /**
     * class to provide data for parameterised unit tests
     *
     * this class provides data for parameterised testing of the update_entity webservice.
     */
    public function update_data_provider(): array {
        return [
                'valid data' => $this->get_valid_data(),
                'unavailable id' => $this->get_unavailable_id(),
                'empty field name' => $this->get_data_with_empty_field_name(),
                'empty value' => $this->get_data_with_whitespace_value(),
                'invalid format' => $this->get_data_with_invalid_format()
        ];
    }


    /**
     * @return void
     * @dataProvider update_data_provider
     */
    public function test_local_entities_update_entity($id, $data, $expected) {
        $this->resetAfterTest(true);
        // generate test data
        $result_raw = local_entities_external::update_entity($id, $data);
        $result = external_api::clean_returnvalue(local_entities_external::update_entity_returns(), $result_raw);
        $this->assertEquals($expected, $result);
    }

    private function get_valid_data(): array {
        $id = 1;
        $field1 = array(
                'name' => 'name',
                'value' => 'Bad'
        );
        $field2 = array(
                'name' => 'description',
                'value' => '2_freibad'
        );
        $data = array(
                $field1,
                $field2
        );
        $expected = true;
        return [$id, $data, $expected];
    }

    private function get_unavailable_id(): array {
        $id = 100;
        $field1 = array(
                'name' => 'name',
                'value' => 'Bad'
        );
        $field2 = array(
                'name' => 'description',
                'value' => '2_freibad'
        );
        $data = array(
                $field1,
                $field2
        );
        $expected = false;
        return [$id, $data, $expected];
    }

    private function get_data_with_empty_field_name(): array {
        $id = 1;
        $field1 = array(
                'name' => 'name',
                'value' => 'Bad'
        );
        $field2 = array(
                'name' => '',
                'value' => '2_freibad'
        );
        $data = array(
                $field1,
                $field2
        );
        $expected = false;
        return [$id, $data, $expected];
    }

    private function get_data_with_whitespace_value(): array {
        $id = 1;
        $field1 = array(
                'name' => 'name',
                'value' => ' '
        );
        $field2 = array(
                'name' => 'type',
                'value' => '2_freibad'
        );
        $data = array(
                $field1,
                $field2
        );
        $expected = false;
        return [$id, $data, $expected];
    }

    private function get_data_with_invalid_format(): array {
        $id = 1;
        $field1 = array(
                'name' => 'name',
                'value' => 'Bad'
        );
        $field2 = array(
                'name' => '',
                'value' => '2_freibad'
        );
        $data = array(
                $id => [
                        $field1,
                        $field2
                ]

        );
        $expected = false;
        return [$id, $data, $expected];
    }
}
