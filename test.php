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

use local_entities\entitiesrelation_handler;
use local_entities\calendar\reoccuringevent;

require_once('../../config.php');

global $USER, $DB;

$context = \context_system::instance();

$PAGE->set_context($context);
require_login();
require_admin();
$PAGE->set_url(new moodle_url('/local/entities/test.php', array()));

$title = "Test cases";
$PAGE->set_title($title);
$PAGE->set_heading($title);



echo $OUTPUT->header();

$out = entitiesrelation_handler::get_pricefactor_by_entityid(1);
$jsonpretty = json_encode($out, JSON_PRETTY_PRINT);

$tree = \local_entities\entities::build_whole_entitytree();

echo $OUTPUT->render_from_template('local_entities/entitiestree', $tree);



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


$b = reoccuringevent::json_to_form('[{"title":"openinghours","daysOfWeek":"1,2,3","startTime":"10:00","endTime":"19:00"},{"title":"openinghours","daysOfWeek":"1,2,3,4","startTime":"21:00","endTime":"21:00"}]');

echo $OUTPUT->footer();
