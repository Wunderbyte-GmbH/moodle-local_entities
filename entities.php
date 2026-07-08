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
use local_entities\settings_manager;
use local_entities\local\views\secondary;

require_once('../../config.php');

$delid = optional_param('del', 0, PARAM_INT);
$context = \context_system::instance();
$PAGE->set_context($context);
require_login();
require_capability('local/entities:view', $context);

$secondarynav = new secondary($PAGE);
$secondarynav->initialise();
$PAGE->set_secondarynav($secondarynav);
$PAGE->set_secondary_navigation(true);

if ($delid !== 0) {
    require_capability('local/entities:delete', $context);
    $entity = new settings_manager($delid);
    $entity->delete();
}

$PAGE->set_url(new moodle_url('/local/entities/entities.php', []));

$title = get_string('pluginname', 'local_entities');
$PAGE->set_title($title);
$PAGE->set_heading($title);

echo $OUTPUT->header();

// The classic list stays the default. The new hierarchical wunderbyte_table list (tree filter,
// full-text search, pagination) is opt-in via the local_entities/usetreelist setting.
if (get_config('local_entities', 'usetreelist')) {
    // Toolbar: create entity / category.
    if (has_capability('local/entities:edit', $context)) {
        $toolbar = html_writer::link(
            new moodle_url('/local/entities/edit.php'),
            html_writer::tag('i', '', ['class' => 'fa fa-plus me-1']) . get_string('addentity', 'local_entities'),
            ['class' => 'btn btn-primary me-2']
        );
        $toolbar .= html_writer::link(
            new moodle_url('/local/entities/customfield.php'),
            html_writer::tag('i', '', ['class' => 'fa fa-plus me-1']) . get_string('addcategory', 'local_entities'),
            ['class' => 'btn btn-outline-primary']
        );
        echo html_writer::div($toolbar, 'mb-3');
    }

    // Hierarchical, searchable list of all entities.
    $table = new \local_entities\table\entities_table('local_entities_list');
    $table->define_headers([
        get_string('name'),
        get_string('entitytype', 'local_entities'),
        get_string('usecount', 'local_entities'),
        get_string('actions'),
    ]);
    $table->define_columns(['name', 'entitytype', 'usecount', 'actions']);
    $table->define_fulltextsearchcolumns(['name', 'shortname']);

    // Plain, database-agnostic query; the depth-first tree order and pagination are applied in PHP by
    // entities_table::query_db(), so the hierarchy is preserved across pages without DB-specific SQL.
    $fields = "e.id, e.name, e.shortname, e.parentid, e.entitytype,
        (SELECT COUNT(DISTINCT r.instanceid)
           FROM {local_entities_relations} r
          WHERE r.entityid = e.id AND r.area = 'option') AS usecount";
    $from = "{local_entities} e";
    $table->set_filter_sql($fields, $from, "1=1", '');
    $table->showcountlabel = true;

    // Paginated (20 per page), rendered server-side (no lazy spinner).
    // The admin list must always show the live entity set, so the shared wunderbyte_table rawdata
    // cache is bypassed here: entity writes can also happen on paths that don't purge it (e.g.
    // direct DB manipulation, other plugins), and this small table is cheap to query fresh.
    $table->pageable(true);
    $table->bypasscache = true;
    $table->out(20, false);
} else {
    // The classic list is shown by default.
    echo $PAGE->get_renderer('local_entities')->list_entities();
}

echo $OUTPUT->footer();
