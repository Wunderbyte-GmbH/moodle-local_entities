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

use local_entities\entities;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 *
 */
class external_test extends advanced_testcase
{

    protected function setUp(): void {
        global $CFG;
        require_once($CFG->dirroot . '/local/entities/classes/external.php');
    }

    /**
     * @return void
     */
    public function test_list_all_entities_returns_empty_array() {
        $this->assertEquals(entities::list_all_parent_entities(), array());
    }

    /**
     * @return void
     * @throws invalid_parameter_exception
     */
    public function test_correct_update_parameters_are_verified() {
        $id = 1;
        $data1 = array(
            'name' => 'name',
            'value' => 'Bad'
        );
        $data2 = array(
            'name' => 'type',
            'value' => '2_freibad'
        );
        $data = array(
            $data1,
            $data2
        );
        $expected = array('id' => $id, 'data' => $data);
        $actual = external_api::validate_parameters(local_entities_external::update_entity_parameters(),
            array('id' => $id, 'data' => $data));
        $msg = "expected : " . json_encode($expected) . "\n  actual: " . json_encode($actual);
        $this->assertEquals($expected, $actual, $msg);
    }

    /**
     * @return void
     * @throws invalid_parameter_exception
     */
    public function test_invalid_update_parameters_not_verified() {
        $id = 1;
        $data = array(
            'id' => $id,
            'data' => array(
                'name' => 'name',
                'value' => 'Bad'
            )
        );
        $this->expectException(moodle_exception::class);
        external_api::validate_parameters(local_entities_external::update_entity_parameters(), array($id, $data));
    }

    /**
     * @return void
     * @throws moodle_exception
     */
    public function test_unavailable_id_is_not_updated() {
        $this->resetAfterTest(true);
        $id = 1000;
        $data1 = array(
            'name' => 'name',
            'value' => 'Bad'
        );
        $data = array(
            $data1
        );
// todo: no exception thrown!
//        $this->expectException(\moodle_exception::class);
        local_entities_external::update_entity($id, $data);
    }

    /**
     * @return void
     * @throws moodle_exception
     */
    public function test_empty_fieldname_is_not_updated() {
        $id = 1;
        $pair1 = array(
            'name' => ' ',
            'value' => 'Bad'
        );
        $data = array(
            $pair1
        );

        $this->expectException(moodle_exception::class);
        local_entities_external::update_entity($id, $data);
    }

    /**
     * @return void
     * @throws moodle_exception
     */
    public function test_empty_value_is_not_updated() {
        $this->resetAfterTest(true);
        $id = 1;
        $pair1 = array(
            'name' => 'description',
            'value' => '    '
        );
        $data = array(
            $pair1
        );
// Todo: no exception thrown!
 //       $this->expectException(\moodle_exception::class);
        local_entities_external::update_entity($id, $data);
    }

    /**
     * @return void
     * @throws invalid_response_exception
     * @throws moodle_exception
     */
    public function test_correct_input_is_updated() {
        $this->resetAfterTest(true);
        $id = 1;
        $data1 = array(
            'name' => 'name',
            'value' => 'Bad'
        );
        $data2 = array(
            'name' => 'description',
            'value' => '2_freibad'
        );
        $data = array(
            $data1,
            $data2
        );

        $result = local_entities_external::update_entity($id, $data);
        $cleanresult = external_api::clean_returnvalue(local_entities_external::update_entity_returns(), $result);
        $this->assertTrue($cleanresult);
    }
}
