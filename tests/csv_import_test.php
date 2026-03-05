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
 * Tests for CSV import.
 *
 * @package local_entities
 * @category test
 * @copyright 2026 Wunderbyte GmbH
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_entities;

use local_entities\csv_import;
use local_entities\form\import_form;

/**
 * Testcases for local_entities CSV import.
 * @runInSeparateProcess
 * @runTestsInSeparateProcesses
 */
final class csv_import_test extends \advanced_testcase {
    /**
     * Import the semicolon-delimited fixture and verify records are created.
     *
     * @covers ::process_data
     */
    public function test_process_data_imports_semicolon_fixture(): void {
        global $CFG, $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // The csv_import class relies on csv_import_reader from csvlib.class.php.
        require_once($CFG->libdir . '/csvlib.class.php');

        $fixturepath = $CFG->dirroot . '/local/entities/tests/fixtures/entitiesimport.csv';
        $csvcontent = file_get_contents($fixturepath);

        $this->assertNotFalse($csvcontent);

        $importer = new csv_import('semicolon');
        csv_import::check_for_import_conflicts();
        $result = $importer->process_data($csvcontent, (object)[]);

        $this->assertTrue($result, $importer->get_error());
        $this->assertSame('', $importer->get_error());

        $entitycount = $DB->count_records('local_entities');
        $this->assertGreaterThan(0, $entitycount);

        $citywave = $DB->get_record('local_entities', ['shortname' => 'ENT019']);
        $this->assertNotFalse($citywave);

        $address = $DB->get_record('local_entities_address', ['entityidto' => $citywave->id, 'city' => 'Bristol']);
        $this->assertNotFalse($address);
        $this->assertSame('Willow Avenue 20', $address->streetname);

        $ent = entities::list_all_entities();
        $this->assertCount(150, $ent);
    }
}
