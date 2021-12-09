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

    // More page setup.
    $PAGE->set_title($entity->name);
    //$PAGE->set_heading($entity->name);

    // Generate the class name with the following naming convention {pagetype}-local-pages-{pagename}-{pageid}.
    $classname = "{$entity->type}-local-pages-{$entity->name}-{$id}";

    // Now add that class name to the body of this page :).
    $PAGE->add_body_class($classname);
}

// Output the header.
echo $OUTPUT->header();

// Output the page content.
//echo $renderer->showpage($custompage);
$context = \context_system::instance();
$entity->description = file_rewrite_pluginfile_urls($entity->description, 'pluginfile.php',
$context->id, 'local_entity', 'description', null);

$url = moodle_url::make_pluginfile_url($context->id, 'local_entities', 'image', $entity->id, $file->get_filepath(), $file->get_filename(), false);
$entity->picture = file_rewrite_pluginfile_urls($entity->id, 'pluginfile.php',
$context->id, 'local_entity', 'image', $entity->id, null);

echo '<img src="'.$url.'">';
$entity->description = format_text($entity->description, FORMAT_HTML);
echo $OUTPUT->render_from_template('local_entities/view', $entity);

// Now output the footer.
echo $OUTPUT->footer();
