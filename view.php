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
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

global $DB;

// Get the id of the page to be displayed.
$id = optional_param('id', 0, PARAM_INT);

// Setup the page.
$PAGE->set_context(\context_system::instance());
$PAGE->set_url("{$CFG->wwwroot}/local/entities/index.php", ['id' => $id]);

require_once("{$CFG->dirroot}/local/entities/lib.php");

// Set the page layout.
require_login();
require_capability('local/entities:view', \context_system::instance());
$PAGE->set_pagelayout('standard');

$secondarynav = new secondary($PAGE);
$secondarynav->initialise();
$PAGE->set_secondarynav($secondarynav);
$PAGE->set_secondary_navigation(true);

// Add a class to the body that identifies this page.

// Make the page name lowercase.
$entity = \local_entities\settings_manager::get_settings($id);

// More page setup.
$PAGE->set_title($entity->name);

// Generate the class name with the following naming convention local-entities-{entityname}-{entityid}.
$classname = "local-entities-{$entity->name}-{$id}";

// Now add that class name to the body of this page :).
$PAGE->add_body_class($classname);

// Output the header.
echo $OUTPUT->header();

// Output the page content.
$context = \context_system::instance();

$fs = get_file_storage();
$files = $fs->get_area_files($context->id, 'local_entities', 'image', $id);
foreach ($files as $file) {
    $filename = $file->get_filename();
    if ($file->get_filesize() > 0) {
        $url = moodle_url::make_file_url('/pluginfile.php', '/1/local_entities/image/' . $id . '/' . $filename);
    }
}

$handlers = local_entities\customfield\entities_cf_helper::create_std_handlers();
if (isset($entity->cfitemid)) {
    $handlers[] = local_entities\customfield\entities_handler::create((int) $entity->cfitemid);
}
$metadata = '';
foreach ($handlers as $handler) {
    $datas = $handler->get_instance_data($id, true);
    foreach ($datas as $data) {
        if (empty($data->get_value())) {
            continue;
        }
        $cat = $data->get_field()->get_category()->get('name');
        $metakey = $data->get_field()->get('name');
        $metadata .= '<span><b>' . $metakey . '</b>: ' . $data->get_value() .'</span></br>';
    }
}

// Affiliated entities.
$subentities = $DB->get_records('local_entities', ['parentid' => $id]);
if (!empty($subentities)) {
    $affiliated = '<ul>';
    foreach ($subentities as $entry) {
        $link = new \moodle_url("/local/entities/view.php", array("id" => $entry->id));
        $affiliated .= '<li><a href="' . $link . '">' . $entry->name . '</a></li>';
    }
    $affiliated .= '</ul>';
    $entity->affiliated = $affiliated;
}

$entity->metadata = $metadata;
$entity->description = file_rewrite_pluginfile_urls($entity->description, 'pluginfile.php',
$context->id, 'local_entity', 'description', null);

$entity->picture = isset($url) ? $url : null;
$entity->hasaddress = isset($entity->address);
$entity->hascontacts = isset($entity->contacts);
$entity->haspicture = isset($entity->picture);
if ($entity->hasaddress) {
    $entity->addresscleaned = array_values($entity->address);
}
if ($entity->hascontacts) {
    $entity->contactscleaned = array_values($entity->contacts);
}

$entity->canedit = has_capability('local/entities:edit', \context_system::instance());
$entity->editurl = new moodle_url('/local/entities/edit.php', array( 'id' => $id));
$entity->calendarurl = new moodle_url('/local/entities/calendar.php', array( 'id' => $id));
$entity->delurl = new moodle_url('/local/entities/entities.php', array( 'del' => $id , 'sesskey' => $USER->sesskey));

echo $OUTPUT->render_from_template('local_entities/view', $entity);

// Now output the footer.
echo $OUTPUT->footer();
