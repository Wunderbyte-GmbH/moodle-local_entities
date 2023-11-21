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
 * entities view page
 * @package    local_entities
 * @copyright  2021 Wunderbyte GmbH
 * @author     Thomas Winkler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_entities\entity;
use local_entities\entities;
use local_entities\settings_manager;
use local_entities\local\views\secondary;

require_once('../../config.php');

$delid = optional_param('del', 0, PARAM_INT);
$context = \context_system::instance();
$PAGE->set_context($context);
require_login();
require_admin();

$secondarynav = new secondary($PAGE);
$secondarynav->initialise();
$PAGE->set_secondarynav($secondarynav);
$PAGE->set_secondary_navigation(true);

// Add capability.
if ($delid !== 0 && (is_siteadmin() || has_capability('local_entities/edit', $context))) {
    $entity = new settings_manager($delid);
    $entity->delete();
}

$PAGE->set_url(new moodle_url('/local/entities/entities.php', []));

$title = get_string('pluginname', 'local_entities');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$renderer = $PAGE->get_renderer('local_entities');

echo $OUTPUT->header();

echo $renderer->list_entities();

echo $OUTPUT->footer();
