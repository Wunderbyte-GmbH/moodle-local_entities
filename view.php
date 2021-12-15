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
 * Pages main view page.
 *
 * @package         local_entities
 * @author          Thomas Winkler
 * @copyright       2021 Wunderbyte GmbH
 * @license         http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */
use local_entities\output\viewpage;

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

// Get the id of the page to be displayed.
$id = optional_param('id', 0, PARAM_INT);

// Setup the page.
$PAGE->set_context(\context_system::instance());
$PAGE->set_url("{$CFG->wwwroot}/local/entities/index.php", ['id' => $id]);

require_once("{$CFG->dirroot}/local/entities/lib.php");

// Set the page layout.

require_login();

$PAGE->set_pagelayout('standard');

// Add a class to the body that identifies this page.
if ($id) {
        // Make the page name lowercase.
    $entity = \local_entities\settings_manager::get_settings($id);
    $PAGE->set_title($entity->name);
    $classname = "{$entity->type}-local-pages-{$entity->name}-{$id}";
    $PAGE->add_body_class($classname);
}

// Output the header.

echo $OUTPUT->header();
printf('<a class="btn btn-primary" style="float:right; font-size:15px" href="' .
new moodle_url($CFG->wwwroot . '/local/entities/entities.php') . '"> '.
get_string('backtolist', 'local_entities') .'</a>');
$PAGE->requires->js_call_amd('local_entities/view', 'init');
$output = $PAGE->get_renderer('local_entities');

$viewpage = new viewpage($id);
$out = $output->render_viewpage($viewpage);
echo $out;

// Now output the footer.
echo $OUTPUT->footer();
