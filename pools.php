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
 * Page on which the pools of persons (for example the examiners of one subject) are managed.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_entities\output\pools_page;
use local_entities\pool\pool_access;
use local_entities\local\views\secondary;

require_once(dirname(__DIR__, 2) . '/config.php');

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/entities/pools.php'));
require_login(null, false);
pool_access::require_manage();
$typefilter = optional_param('type', '', PARAM_ALPHANUMEXT);

$secondarynav = new secondary($PAGE);
$secondarynav->initialise();
$PAGE->set_secondarynav($secondarynav);
$PAGE->set_secondary_navigation(true);

$PAGE->set_title(get_string('pools', 'local_entities'));
$PAGE->set_heading(get_string('pluginname', 'local_entities'));
$PAGE->requires->js_call_amd('local_entities/pools', 'init');

echo $OUTPUT->header();
echo $OUTPUT->render_from_template(
    'local_entities/pools_page',
    (new pools_page($typefilter))->export_for_template($OUTPUT)
);
echo $OUTPUT->footer();
