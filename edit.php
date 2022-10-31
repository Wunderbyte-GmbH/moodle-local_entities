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

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/local/entities/lib.php');
use local_entities\form\edit_dynamic_form;
use local_entities\local\views\secondary;
use local_entities\settings_manager;

$entityid = optional_param('id', 0, PARAM_INT);
$categoryid = optional_param('catid', 0, PARAM_INT);
$context = context_system::instance();
require_capability('local/entities:canedit', $context);

global $USER, $PAGE, $DB;

// Set PAGE variables.
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot . '/local/entities/edit2.php', array("id" => $entityid));

// Force the user to login/create an account to access this page.
require_login();

$secondarynav = new secondary($PAGE);
$secondarynav->initialise();
$PAGE->set_secondarynav($secondarynav);
$PAGE->set_secondary_navigation(true);

$settingsmanager = new \local_entities\settings_manager();

if (!empty($entityid)) {

    // Here, we need to preload the form, because of the handler loading in definition.
    $entity = settings_manager::get_settings_forform($entityid);

    $data = (array)$entity;
    $mform = new edit_dynamic_form(null, null, 'post', '', [], true, $data);

    $mform->set_data($entity);

} else {
    $mform = new edit_dynamic_form(null, null, 'post', '', [], true, ['entityid' => 0]);
}

// Print the page header.
$title = isset($data) ? $data['name'] : get_string('new_entity', 'local_entities');
$heading = isset($data['entityid']) ? $data['name'] : get_string('new_entity', 'local_entities');

$PAGE->set_title($title);
$PAGE->set_heading($heading);
echo $OUTPUT->header();

$PAGE->requires->js_call_amd(
    'local_entities/dynamiceditform',
    'init',
    ['#local_entities_formcontainer', edit_dynamic_form::class], $entityid);
echo html_writer::div($mform->render(), '', ['id' => 'local_entities_formcontainer']);
echo $OUTPUT->footer();
