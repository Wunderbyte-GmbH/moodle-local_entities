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
 * Page on which the unavailability of an entity is managed.
 *
 * Without an entity id the page opens the own person of an examiner, or the first entity that a user
 * who manages others may open.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_entities\output\unavailability_page;
use local_entities\unavailability\default_entity;
use local_entities\unavailability_access;
use local_entities\output\entity_view;

require_once(dirname(__DIR__, 2) . '/config.php');

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/entities/unavailability.php'));
require_login(null, false);

$entityid = optional_param('entityid', 0, PARAM_INT);
if ($entityid <= 0) {
    $entityid = (new default_entity())->resolve();
    if ($entityid === null) {
        throw new required_capability_exception($context, unavailability_access::CAP_ANY, 'nopermissions', '');
    }
}

$entity = $DB->get_record('local_entities', ['id' => $entityid], '*', MUST_EXIST);
unavailability_access::require_view_entity($entity);

$PAGE->set_url(new moodle_url('/local/entities/unavailability.php', ['entityid' => $entityid]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('unavailability', 'local_entities'));
$PAGE->set_heading(get_string('unavailability', 'local_entities'));

$display = entity_view::calendar_display_settings();
$PAGE->requires->css('/local/entities/js/main.css');
$PAGE->requires->js_call_amd('local_entities/unavailability/main', 'init', [
    $entityid,
    str_replace('_', '-', current_language()),
    $display['calendarfirstday'],
    $display['calendartimeformat'],
]);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template(
    'local_entities/unavailability_page',
    (new unavailability_page($entityid))->export_for_template($OUTPUT)
);
echo $OUTPUT->footer();
