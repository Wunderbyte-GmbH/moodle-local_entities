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
 * Categories page
 * @package    local_entities
 * @copyright  2021 Wunderbyte GmbH
 * @author     Thomas Winkler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
use local_entities\local\views\secondary;

$context = \context_system::instance();
$PAGE->set_context($context);
require_login();

$PAGE->set_url(new moodle_url('/local/entities/categories.php'));

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
$categories = \local_entities\customfield\entities_cf_helper::get_all_cf_categories_with_subcategories();
$templatedata['categories'] = array();

foreach ($categories as $category) {
    $cat = new stdClass();
    $cat = $category;
    if (isset($olditemid) && ($category->itemid == $olditemid)) {
        $cat->sub = true;
    }
    $olditemid = $category->itemid;
    $cat->categoryname = $category->name;
    $cat->url = new moodle_url('/local/entities/customfield.php', array('id' => $cat->itemid));
    $templatedata['categories'][] = $cat;
}

echo $OUTPUT->render_from_template('local_entities/cfcategories', $templatedata);

echo $OUTPUT->footer();
