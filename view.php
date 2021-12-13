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

// Rendere Func       
$fs = get_file_storage();
$files = $fs->get_area_files($context->id, 'local_entities', 'image', $id);     
foreach ($files as $file) {
    $filename = $file->get_filename();
    if ($file->get_filesize() > 0) {
        $url = moodle_url::make_file_url('/pluginfile.php', '/1/local_entities/image/' . $id . '/' . $filename);
    }
}


$handler = local_entities\customfield\entities_handler::create();
$datas = $handler->get_instance_data($id);
$metadata = '';
foreach ($datas as $data) {
    if (empty($data->get_value())) {
        continue;
    }
    $cat = $data->get_field()->get_category()->get('name');
    $metakey = $data->get_field()->get('name');
    $metadata .= '<span><b>' . $metakey . '</b>: ' . $data->get_value() .'</span></br>';
}
$entity->metadata = $metadata;
$entity->description = file_rewrite_pluginfile_urls($entity->description, 'pluginfile.php',
$context->id, 'local_entity', 'description', null);

$entity->isopen = $entity->open ? 'checked' : '';
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
if (isset($entity->type)) {
    $type = explode('_', $entity->type, 2);
    $entity->type = $type[1];
}
$entity->editurl = new moodle_url('/local/entities/edit.php', array( 'id' => $id));
$entity->delurl = new moodle_url('/local/entities/entities.php', array( 'del' => $id , 'sesskey' => $USER->sesskey));
$entity->description = format_text($entity->description, FORMAT_HTML);



echo $OUTPUT->render_from_template('local_entities/view', $entity);




// Now output the footer.
echo $OUTPUT->footer();
