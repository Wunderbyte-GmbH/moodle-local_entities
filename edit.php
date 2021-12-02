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
 * @author      Kevin Dibble
 * @copyright   2017 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/local/entities/lib.php');
require_once($CFG->dirroot . '/local/entities/forms/edit_form.php');
use core_customfield\api;

$entityid = optional_param('id', 0, PARAM_INT);
$categoryid = optional_param('catid', 0, PARAM_INT);
$context = context_system::instance();

global $USER, $PAGE;

// Set PAGE variables.
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot . '/local/entities/edit.php', array("id" => $pageid));

// Force the user to login/create an account to access this page.
require_login();

// Add chosen Javascript to list.
$PAGE->requires->jquery();

$PAGE->set_pagelayout('standard');

// Get the renderer for this page.
//$renderer = $PAGE->get_renderer('local_entities');

$entitytoedit = \local_entities\entity::load($entityid, true);
$settingsmanager = new \local_entities\settings_manager();
$handler = \local_entities\customfield\entities_handler::create(1);

$editablefields = $handler->get_editable_fields($entityid);
$fieldswithdata = api::get_instance_fields_data($editablefields, $entityid);
$handler->get_editable_fields($entityid);
$lastcategoryid = null;
foreach ($fieldswithdata as $data) {
        $categoryid = $data->get_field()->get_category()->get('id');
}
$categories = $handler->get_categories_with_fields();
foreach ($categories as $category) {
    $categoryid = $category->get('name');
}

$mform = new entities_form($entitytoedit);
$data = \local_entities\settings_manager::get_settings($entitytoedit->id);
$mform->set_data($data);
if ($mform->is_cancelled()) {
    redirect(new moodle_url($CFG->wwwroot . '/local/entities/entities.php'));
} else if ($data = $mform->get_data()) {
    require_once($CFG->libdir . '/formslib.php');
    $context = context_system::instance();
    $data->entitycontent['text'] = file_save_draft_area_files($data->entitycontent['itemid'], $context->id,
        'local_entities', 'entitycontent',
        0, array('subdirs' => true), $data->entitycontent['text']);

    $data->entitydata = '';
    $recordentity = new stdClass();
    $recordentity = $data;
    $recordentity->id = $data->id;
    $recordentity->name = $data->name;
    $recordentity->sortorder = intval($data->sortorder);
    $recordentity->type = $data->type;
    $recordentity->parentid = intval($data->parentid);
    $recordentity->description = $data->description['text'];
    $result = $settingsmanager->update_or_createentity($recordentity);
    if ($result && $result > 0) {
        $options = array('subdirs' => 0, 'maxbytes' => 204800, 'maxfiles' => 1, 'accepted_types' => '*');
        if (isset($data->ogimage_filemanager)) {
            file_postupdate_standard_filemanager($data, 'ogimage', $options, $context, 'local_entities', 'ogimage', $result);
        }
        redirect(new moodle_url($CFG->wwwroot . '/local/entities/edit.php', array('id' => $result)));
    }
}

// Print the page header.
$title = isset($entitytoedit) ? $entitytoedit->name : get_string('entitiesetup_title', 'local_entities');
$heading = isset($entitytoedit->id) ? get_string('edit_entity', 'local_entities') : get_string('new_entity', 'local_entities');
$PAGE->set_title($title);
$PAGE->set_heading($heading);


echo $OUTPUT->header();

printf('<h1 class="page__title">%s<a style="float:right;font-size:15px" href="' .
    new moodle_url($CFG->wwwroot . '/local/entities/entities.php') . '"> '.
    get_string('backtolist', 'local_entities') .'</a></h1>',
    $title);

$mform->display();

echo $OUTPUT->footer();
