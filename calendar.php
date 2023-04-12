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
 * Entities calendar view page.
 *
 * @package         local_entities
 * @author          Thomas Winkler
 * @copyright       2021 Wunderbyte GmbH
 * @license         http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */

use local_entities\local\views\secondary;

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

// Get the id of the page to be displayed.
$id = required_param('id', PARAM_INT);

// Setup the page.
$PAGE->set_context(\context_system::instance());
$PAGE->set_url("{$CFG->wwwroot}/local/entities/calendarprint.php", ['id' => $id]);

require_once("{$CFG->dirroot}/local/entities/lib.php");

// Set the page layout.
require_login();
require_capability('local/entities:view', \context_system::instance());
$PAGE->set_pagelayout('popup');

$PAGE->requires->css('/local/entities/js/main.css');
// Output the header.
echo $OUTPUT->header();

echo '<script src=
"https://cdn.jsdelivr.net/npm/html2canvas@1.0.0-rc.5/dist/html2canvas.min.js">
    </script>';

$entity = \local_entities\settings_manager::get_settings($id);

$templatedata = [
    'id' => $id,
    'locale' => current_language(),
    'name' => $entity->name,
    'shortname' => $entity->shortname
];
echo $OUTPUT->render_from_template('local_entities/calendarprintbtn', $templatedata);
echo $OUTPUT->render_from_template('local_entities/entitiescalendar', $templatedata);

// Now output the footer.
echo $OUTPUT->footer();
