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

use local_entities\entities;
use local_entities\entity;
use local_entities\entity_manager;
use local_entities\settings_manager;

require_once('../../config.php');

global $USER, $DB;

$context = \context_system::instance();
$PAGE->set_context($context);
require_login();
$PAGE->set_url(new moodle_url('/local/entities/test.php', array()));

$title = "Test cases";
$PAGE->set_title($title);
$PAGE->set_heading($title);



echo $OUTPUT->header();
echo $renderer->list_entities_select();
$test = $DB->get_record('local_entities', ['id' => 1]);
/* test input */

$enitymanager = new settings_manager();
//$out = $enitymanager->get_settings('5');

$out = entities::list_all_entities();
$jsonpretty = json_encode($out, JSON_PRETTY_PRINT);
echo "<pre>".$jsonpretty."<pre/>";

/*
$data = new stdClass();
$data->country_1 = 'country_1';
$data->city_1 = 'city_1';
$data->streetname_1 = 'streetname_1';
$data->streetnumber_1 = 'streetnumber_1';
$data->country_2 = 'country_2';
$data->city_2 = 'city_2';
$data->streetname_2 = 'streetname_2';
$data->streetnumber_2 = 'streetnumber_2';
$id = 1;
$out = $enity_manager->prepareaddress($data, $id);

$json_pretty = json_encode($out, JSON_PRETTY_PRINT);
echo "<pre>".$json_pretty."<pre/>";
$id = 2;
$out = $enity_manager->prepareaddress($data, $id);
$json_pretty = json_encode($out,  JSON_PRETTY_PRINT);
echo "<pre>".$json_pretty."<pre/>";
*/

/*
$input = 0;
$update = 0;
$list = 0;
if ($input) {
    $data = new stdClass();
    $data->name = "asdasfd";
    $data->description = "asd";
    $data->type = "category";
    $data->timecreated = time();
    $data->timemodified = time();
    $data->createdby = $USER->id;
    $data->parentid = 0;
    $entity = new entity($data);
    $entity->update($data);
}

if ($update) {
    $data = new stdClass();
    $data->id = 1;
    $data->name = "asdasfd";
    $data->description = "asd";
    $data->type = "category";
    $data->timecreated = time();
    $data->timemodified = time();
    $data->createdby = $USER->id;
    $data->parentid = 0;
    $entity = new entity($data);
    $entity->update($data);
}

if ($list) {
    $entities = new entities();
    $list = $entities->list_all_entities();
}
*/



echo $OUTPUT->footer();
