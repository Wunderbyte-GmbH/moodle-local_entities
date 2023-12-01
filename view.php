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
// We even want to show entities when logged out!
// phpcs:ignore moodle.Files.RequireLogin.Missing
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once("{$CFG->dirroot}/local/entities/lib.php");

global $DB;

// Get the id of the page to be displayed.
$id = optional_param('id', 0, PARAM_INT);

$viewphpurl = new moodle_url("/local/entities/view.php", ['id' => $id]);
$returnurl = $viewphpurl->out();

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

// Add a class to the body that identifies this page.

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

$metadata = [];
$cat = "";
foreach ($handlers as $handler) {
    $datas = $handler->get_instance_data($id, true);

    foreach ($datas as $data) {
        if (empty($data->get_value())) {
            continue;
        }
        $cat = $data->get_field()->get_category()->get('name');
        $meta = new stdClass();;
        $meta->key = $data->get_field()->get('name');
        $meta->value = $data->get_value();
        $metadata[] = $meta;
    }
}
$entity->hasmetadata = !empty($metadata);
$entity->metacategory = $cat;
$entity->metadata = $metadata;

// Affiliated entities.
$subentities = $DB->get_records('local_entities', ['parentid' => $id]);
$affiliation = [];
if ($entity->hasaffiliation = !empty($subentities)) {
    foreach ($subentities as $entry) {
        $subentry = new stdClass();
        $subentry->link = new \moodle_url("/local/entities/view.php", ["id" => $entry->id]);
        $subentry->name = $entry->name;
        $subentry->shortname = $entry->shortname;
        $subentry->editurl = new moodle_url('/local/entities/edit.php', [ 'id' => $entry->id]);
        $affiliation[] = $subentry;
    }
    $entity->affiliation = $affiliation;
}

// Parent entity.
$imagefallback = get_config('local_entities', 'fallback_image_parent');
$contactsfallback = get_config('local_entities', 'fallback_contacts_parent');
$addressfallback = get_config('local_entities', 'fallback_address_parent');
$parenthascontacts = false; // Initialize.
$parenthasaddress = false; // Initialize.

if (!empty($entity->parentid)) {
    $parent = \local_entities\settings_manager::get_settings($entity->parentid);
    if (!empty($parent)) {

        $entity->parent = $parent;
        $entity->parent->link = new \moodle_url("/local/entities/view.php", ["id" => $parent->id]);
        $parenthasaddress = !empty($parent->address);
        $parenthascontacts = !empty($parent->contacts);

        if (!isset($url) && $imagefallback) {
            $files = $fs->get_area_files($context->id, 'local_entities', 'image', $parent->id);

            foreach ($files as $file) {
                $filename = $file->get_filename();
                if ($file->get_filesize() > 0) {
                    $url = moodle_url::make_file_url('/pluginfile.php', '/1/local_entities/image/' . $parent->id . '/' . $filename);
                }
            }
        }
    }
}

$entity->metadata = $metadata;
$entity->description = file_rewrite_pluginfile_urls($entity->description, 'pluginfile.php',
$context->id, 'local_entity', 'description', null);



$entity->picture = !empty($url) ? $url : null;
$entity->hasaddress = !empty($entity->address);
$entity->hascontacts = !empty($entity->contacts);
$entity->haspicture = !empty($entity->picture);



if ($entity->hasaddress) {
    $entity->addresscleaned = array_values($entity->address);
} else if ($parenthasaddress && $addressfallback) {
    $entity->hasaddress = $parenthasaddress;
    $entity->addresscleaned = array_values($parent->address);
}
if ($entity->hascontacts) {
    $entity->contactscleaned = array_values($entity->contacts);
} else if ($parenthascontacts && $contactsfallback) {
    $entity->hascontacts = $parenthascontacts;
    $entity->contactscleaned = array_values($parent->contacts);
}

$entity->hasleftsidebar = $entity->hasmetadata || $entity->hasaffiliation;
$entity->hasrightsidebar = $entity->hascontacts || $entity->hasaddress;

$entity->showcalendar = get_config('local_entities', 'show_calendar_on_details_page');
$entity->canedit = has_capability('local/entities:edit', \context_system::instance());
$entity->editurl = new moodle_url('/local/entities/edit.php', [ 'id' => $id]);
$entity->calendarurl = new moodle_url('/local/entities/calendar.php', [ 'id' => $id]);
$entity->delurl = new moodle_url('/local/entities/entities.php', [ 'del' => $id , 'sesskey' => $USER->sesskey]);

echo $OUTPUT->render_from_template('local_entities/view', $entity);

// Now output the footer.
echo $OUTPUT->footer();
