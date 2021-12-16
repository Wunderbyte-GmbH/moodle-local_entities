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

class external_test extends \advanced_testcase {
    
    function test_list_all_entities_returns_empty_array(){
        $this->assertEquals(entities::list_all_parent_entities(), array());
    }
    
    function test_list_all_entities_returns_given_entities(){
        $this->assertEquals(true, true);
        // $this->assertEquals(entities::list_all_entities(), $result);
        // TODO
    }
    
}