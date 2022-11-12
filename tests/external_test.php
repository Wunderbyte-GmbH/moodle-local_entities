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

namespace local_entities;

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
     * data for unit tests of update_entity that are expected to run without exceptions
     *
     * These test cases are expected to throw an invalid_parameter_exception.
     * Note how a field name that is an empty string will evaluate as valid,
     * while a field name containing only whitespace will not.
     */
    public function update_data_provider(): array {
        return [
                // Todo these two are not yet tested because tests don't find the table. will have to find out how to solve this.
                // ...'valid data' => $this->get_valid_data(),.
                // ...'unavailable id' => $this->get_unavailable_id(),.
                'empty field name should update remaining fields' => $this->get_data_with_empty_field_name(),
                'whitespace value should update remaining fields' => $this->get_data_with_whitespace_value(),
                'empty value should update remaining fields' => $this->get_data_with_empty_value(),

        ];
    }

    /**
     * data for unit tests of update_entity that are expected to throw an exception
     *
     * These test cases are expected to throw an invalid_parameter_exception.
     * Note how a field name that is an empty string will evaluate as valid,
     * while a field name containing only whitespace will not.
     */
    public function update_exceptions_data_provider(): array {
        return [
                'whitespace field_name should throw exception' => $this->get_data_with_whitespace_field_name(),
                'invalid format should throw exception' => $this->get_data_with_invalid_format(),
                'value not set should throw exception' => $this->get_data_with_value_not_set(),
        ];
    }

    /**
     * test local_entities_update_entity
     *
     * @param int $id
     * @param array $data
     * @param mixed $expected
     * @dataProvider update_data_provider
     * @covers \local_entities_external::update_entity
     * @return void
     */
    public function test_local_entities_update_entity($id, $data, $expected) {
        $this->setAdminUser();
        $this->resetAfterTest(true);
        $resultraw = \local_entities_external::update_entity($id, $data);
        $result = \external_api::clean_returnvalue(\local_entities_external::update_entity_returns(), $resultraw);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test local_entities_update_entity_exceptions
     * @param int $id
     * @param array $data
     * @param mixed $expected
     * @return void
     * @dataProvider update_exceptions_data_provider
     * @covers \local_entities_external::update_entity
     * @throws invalid_response_exception
     * @throws moodle_exception
     */
    public function test_local_entities_update_entity_exceptions($id, $data, $expected) {
        $this->setAdminUser();
        $this->expectException(\invalid_parameter_exception::class);
        \local_entities_external::update_entity($id, $data);
    }

    /**
     * get_valid_data
     *
     * @return array
     */
    private function get_valid_data(): array {
        $id = 1;
        $field1 = array(
                'name' => 'name',
                'value' => 'Bad',
        );
        $field2 = array(
                'name' => 'description',
                'value' => '2_freibad',
        );
        $data = array(
                $field1,
                $field2,
        );
        $expected = [
                'updated' => true,
                'warnings' => [],
        ];
        return [$id, $data, $expected];
    }

    /**
     * get_unavailable_id
     *
     * @return array
     */
    private function get_unavailable_id(): array {
        $id = 100;
        $field1 = array(
                'name' => 'name',
                'value' => 'Bad',
        );
        $field2 = array(
                'name' => 'description',
                'value' => '2_freibad',
        );
        $data = array(
                $field1,
                $field2,
        );
        $warning = [
                'itemid' => $id,
                'warningcode' => 'nosuchid',
                'message' => 'There is no entity with the given id.',
        ];
        $expected = [
                'updated' => false,
                'warnings' => [$warning],
        ];
        return [$id, $data, $expected];
    }

    /**
     * get_data_with_empty_field_name
     *
     * @return array
     */
    private function get_data_with_empty_field_name(): array {
        $id = 1;
        $field1 = array(
                'name' => 'name',
                'value' => 'Bad',
        );
        $field2 = array(
                'name' => '',
                'value' => '2_freibad',
        );
        $data = array(
                $field1,
                $field2,
        );
        $warning = [
                'item' => $field2['name'] . ', ' . $field2['value'],
                'warningcode' => 'invalidparams',
                'message' => 'These parameters are invalid.',
        ];
        $expected = [
                'updated' => true,
                'warnings' => [$warning],
        ];
        return [$id, $data, $expected];
    }

    /**
     * get_data_with_empty_value
     *
     * @return array
     */
    private function get_data_with_empty_value(): array {
        $id = 1;
        $field1 = array(
                'name' => 'name',
                'value' => '',
        );
        $field2 = array(
                'name' => 'shortname',
                'value' => '2_freibad',
        );
        $data = array(
                $field1,
                $field2,
        );
        $warning = [
                'item' => $field1['name'] . ', ' . $field1['value'],
                'warningcode' => 'invalidparams',
                'message' => 'These parameters are invalid.',
        ];
        $expected = [
                'updated' => true,
                'warnings' => [$warning],
        ];
        return [$id, $data, $expected];
    }

    /**
     * get_data_with_whitespace_value
     *
     * @return array
     */
    private function get_data_with_whitespace_value(): array {
        $id = 1;
        $field1 = array(
                'name' => 'name',
                'value' => ' ',
        );
        $field2 = array(
                'name' => 'shortname',
                'value' => '2_freibad',
        );
        $data = array(
                $field1,
                $field2,
        );
        $warning = [
                'item' => $field1['name'] . ', ' . $field1['value'],
                'warningcode' => 'invalidparams',
                'message' => 'These parameters are invalid.',
        ];
        $expected = [
                'updated' => true,
                'warnings' => [$warning],
        ];
        return [$id, $data, $expected];
    }

    /**
     * get_data_with_whitespace_field_name
     *
     * @return array
     */
    private function get_data_with_whitespace_field_name(): array {
        $id = 1;
        $field1 = array(
                'name' => 'name',
                'value' => 'Bad',
        );
        $field2 = array(
                'name' => '  ',
                'value' => '2_freibad',
        );
        $data = array(
                $field1,
                $field2,
        );
        $warning = [
                'item' => $field2['name'] . ', ' . $field2['value'],
                'warningcode' => 'invalidparams',
                'message' => 'These parameters are invalid.',
        ];
        $expected = [
                'updated' => true,
                'warnings' => [$warning],
        ];
        return [$id, $data, $expected];
    }

    /**
     * get_data_with_invalid_format
     *
     * @return array
     */
    private function get_data_with_invalid_format(): array {
        $id = 1;
        $field1 = array(
                'name' => 'name',
                'value' => 'Bad',
        );
        $field2 = array(
                'name' => '',
                'value' => '2_freibad',
        );
        $data = array(
                $id => [
                        $field1,
                        $field2,
                ],

        );
        $expected = new \invalid_parameter_exception();
        return [$id, $data, $expected];
    }

    /**
     * get_data_with_value_not_set
     *
     * @return array
     */
    private function get_data_with_value_not_set(): array {
        $id = 100;
        $field1 = array(
                'name' => 'name',
        );
        $field2 = array(
                'name' => 'description',
                'value' => '2_freibad',
        );
        $data = array(
                $field1,
                $field2,
        );
        $warning = [
                'itemid' => $id,
                'warningcode' => 'invalidparam',
                'message' => 'These parameters are invalid',
        ];
        $expected = [
                'updated' => false,
                'warnings' => [$warning],
        ];
        return [$id, $data, $expected];
    }
}
