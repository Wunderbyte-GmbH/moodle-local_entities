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
 * Import options or just add new users from CSV
 *
 * @package Booking
 * @copyright 2014 Andraž Prinčič www.princic.net
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_entities;

require_once(__DIR__ . '/../../config.php');
require_once("lib.php");
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->libdir . "/csvlib.class.php");

use local_entities\form\import_form;
use moodle_url;
use context_module;
use context_system;
use html_writer;

$PAGE->set_context(\context_system::instance());

require_login();

global $OUTPUT;

$PAGE->set_context(context_system::instance());

$url = new moodle_url('/local/entities/import.php');
$PAGE->set_url($url);

$context = context_system::instance();
require_capability('mod/booking:updatebooking', $context);


$PAGE->navbar->add(get_string("import", "local_entities"));

$PAGE->set_title(get_string("import", "local_entities"));
$PAGE->set_heading(get_string("import", "local_entities"));
$PAGE->set_pagelayout('standard');

echo $OUTPUT->header();

$importform = new import_form(null, null, 'post', '', [], true);

$importform->set_data_for_dynamic_submission();
echo html_writer::div($importform->render(), '', ['id' => 'importformcontainer']);

$csvimporter = new csv_import();
$csvimporter->check_for_import_conflicts();

$PAGE->requires->js_call_amd(
    'local_entities/import',
    'init'
);

echo $OUTPUT->footer();
