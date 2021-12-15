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
 * @package    local_entities
 * @copyright  2021 Wunderbyte GmbH
 * @author     Thomas Winkler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_entities\entity;
require_once('../../config.php');


$context = \context_system::instance();
$PAGE->set_context($context);
require_login();

$PAGE->set_url(new moodle_url('/local/entities/customfield.php', array()));

$title = "Dashboard entities";
$PAGE->set_title($title);
$PAGE->set_heading($title);

$renderer = $PAGE->get_renderer('local_entities');


echo $OUTPUT->header();
$output = $PAGE->get_renderer('core_customfield');
$handler = local_entities\customfield\entities_handler::create();
$outputpage = new \core_customfield\output\management($handler);

echo $output->render($outputpage);

echo $OUTPUT->footer();

