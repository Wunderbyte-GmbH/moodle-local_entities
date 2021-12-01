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

$entityid = optional_param('id', 0, PARAM_INT);
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
$renderer = $PAGE->get_renderer('local_entities');

$entitytoedit = \local_entities\entity::load($entityid, true);
$renderer->save_entity($entitytoedit);
// Print the page header.
$PAGE->set_title(get_string('entitiesetup_title', 'local_entities'));
$PAGE->set_heading(get_string('entitiesetup_heading', 'local_entities'));


    echo $OUTPUT->header();

    printf('<h1 class="page__title">%s<a style="float:right;font-size:15px" href="' .
        new moodle_url($CFG->wwwroot . '/local/entities/entities.php') . '"> '.
        get_string('backtolist', 'local_entities') .'</a></h1>',
        get_string('entity_title', 'local_entities'));

    echo $renderer->edit_entity($entitytoedit);


    echo $OUTPUT->footer();

