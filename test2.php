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
 * Test file for entities
 * @package    local_entities
 * @copyright  2021 Wunderbyte GmbH
 * @author     Thomas Winkler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_entities\entity;

require_once('../../config.php');

global $USER, $DB;

$context = \context_system::instance();
$id = required_param('id', PARAM_INT);
$PAGE->set_context($context);
require_login();
require_admin();

$entity = entity::load($id);

$PAGE->set_url(new moodle_url('/local/entities/test2.php', ['id' => $id]));

$title = get_string('calendar', 'local_entities') . " " . $entity->name;
$PAGE->set_title($title);
$PAGE->set_heading($title);

$PAGE->requires->css('/local/entities/js/main.css');

$templatedata = new stdClass();
$templatedata->id = $id;
$templatedata->locale = 'de';
global $DB;
$query = "";
$sql = "Select * FROM {local_entities} WHERE name LIKE '%{$query}%'";
$rs = $DB->get_recordset_sql($sql);
$count = 0;
$list = [];

foreach ($rs as $record) {
    $entity = (object)[
        'id' => $record->id,
        'name' => $record->name,
        'shortname' => $record->shortname,
        'extrafields' => [],
    ];

    $count++;
    $list[$record->id] = $entity;
}

$rs->close();
echo $OUTPUT->header();

echo $OUTPUT->render_from_template('local_entities/entitiescalendar', $templatedata);

echo $OUTPUT->footer();
