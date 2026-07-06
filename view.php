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
 * Entities main view page.
 *
 * @package         local_entities
 * @author          Thomas Winkler
 * @copyright       2021 Wunderbyte GmbH
 * @license         http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */

use local_entities\local\views\secondary;
use local_entities\output\entity_view;
// We even want to show entities when logged out!
// phpcs:ignore moodle.Files.RequireLogin.Missing
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once("{$CFG->dirroot}/local/entities/lib.php");

global $DB;

// Get the id of the page to be displayed.
$id = optional_param('id', 0, PARAM_INT);

$viewphpurl = new moodle_url("/local/entities/view.php", ['id' => $id]);

// Setup the page.
$PAGE->set_context(\context_system::instance());
$PAGE->set_url($viewphpurl);


// Set the page layout.
// We even want to show entities when logged out!
// phpcs:ignore Squiz.PHP.CommentedOutCode.Found
/* require_capability('local/entities:view', \context_system::instance()); */
$PAGE->set_pagelayout('base');

if (isloggedin()) {
    $secondarynav = new secondary($PAGE);
    $secondarynav->initialise();
    $PAGE->set_secondarynav($secondarynav);
    $PAGE->set_secondary_navigation(true);
}

if (!empty($id) && !$DB->record_exists('local_entities', ['id' => $id])) {
    $PAGE->set_title(get_string('error:entitydoesnotexist', 'local_entities'));
    $PAGE->set_heading(get_string('error:entitydoesnotexist', 'local_entities'));
    echo $OUTPUT->header();
    echo "<div class='alert alert-danger'>" .
        get_string('error:entitydoesnotexist', 'local_entities') . "</div>";
    echo $OUTPUT->footer();
    die();
}

// Make the page name lowercase.
$entity = \local_entities\settings_manager::get_settings($id);

// More page setup.
$PAGE->set_title($entity->name);

// Generate the class name with the following naming convention local-entities-{entityname}-{entityid}.
$classname = "local-entities-{$entity->name}-{$id}";

// Now add that class name to the body of this page :).
$PAGE->add_body_class($classname);
$PAGE->requires->css('/local/entities/js/main.css');

// Output the header.
echo $OUTPUT->header();

// Build the (template-agnostic) rendering context and render the active view template.
$viewdata = entity_view::build_context($id);
$entitytype = $viewdata->entitytype ?? '';
$templatekey = entity_view::resolve_active_template(optional_param('template', '', PARAM_ALPHANUMEXT), $entitytype);

// Managers can switch the view template directly here: preview via link, then save it for this type.
$switcher = entity_view::build_switcher(
    $id,
    $templatekey,
    entity_view::resolve_active_template('', $entitytype),
    $entitytype
);
if ($switcher !== null) {
    $PAGE->requires->js_call_amd('local_entities/viewswitcher', 'init');
    echo $OUTPUT->render_from_template('local_entities/view/parts/switcher', $switcher);
}

echo $OUTPUT->render_from_template('local_entities/view/' . $templatekey, $viewdata);

// Now output the footer.
echo $OUTPUT->footer();
