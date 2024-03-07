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
 * Customfield page
 * @package    local_entities
 * @copyright  2021 Wunderbyte GmbH
 * @author     Thomas Winkler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_entities\local\views\secondary;
require_once('../../config.php');

$id = optional_param('id', -1, PARAM_INT);

$context = \context_system::instance();

require_login();
require_capability('local/entities:edit', $context);

$PAGE->set_context($context);

if ($id == -1) {
    $id = \local_entities\customfield\entities_cf_helper::get_next_itemid();
    redirect(new moodle_url('/local/entities/customfield.php', ['id' => $id]));
}

$PAGE->set_url(new moodle_url('/local/entities/customfield.php', ['id' => $id]));

$secondarynav = new secondary($PAGE);
$secondarynav->initialise();
$PAGE->set_secondarynav($secondarynav);
$PAGE->set_secondary_navigation(true);

$title = get_string('categories', 'local_entities');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$renderer = $PAGE->get_renderer('local_entities');

echo $OUTPUT->header();
$output = $PAGE->get_renderer('core_customfield');
$handler = local_entities\customfield\entities_handler::create((int) $id);
$outputpage = new \core_customfield\output\management($handler);
echo $output->render($outputpage);

echo $OUTPUT->footer();
