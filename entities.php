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
 * @package    local_entites
 * @copyright  2021 Wunderbyte GmbH
 * @author     Thomas Winkler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_entities\entity;
require_once('../../config.php');


$delid = optional_param('entitydel', 0, PARAM_INT);
$context = \context_system::instance();
$PAGE->set_context($context);
require_login();

//add capability
if ($delid !== 0) {
    if (confirm_sesskey()) {
        $entity = new entity(array('id' => $delid));
        $entity->delete();  
    }
}

$PAGE->set_url(new moodle_url('/local/entities/entities.php', array()));

$title = "Dashboard entities";
$PAGE->set_title($title);
$PAGE->set_heading($title);

$renderer = $PAGE->get_renderer('local_entities');


echo $OUTPUT->header();

echo $renderer->list_entities();
echo $OUTPUT->render_from_template('local_entities/entities', $data);
echo $OUTPUT->render_from_template('local_entities/bookingcard', $data);

echo $OUTPUT->footer();
