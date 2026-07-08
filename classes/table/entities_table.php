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

namespace local_entities\table;

use context_system;
use html_writer;
use local_entities\entities;
use local_wunderbyte_table\wunderbyte_table;
use local_wunderbyte_table\output\table;
use moodle_url;
use stdClass;

/**
 * Hierarchical, paginated list of entities, rendered with wunderbyte_table.
 *
 * The list is ALWAYS shown as a tree: every entity appears under its parent, indented by depth.
 * The tree order is computed in PHP (see {@see self::arrange_as_tree()}) and the page slice is taken
 * after ordering, so the hierarchy stays intact across pages. The SQL itself stays trivial and fully
 * database-agnostic (a plain SELECT … FROM {local_entities} WHERE …, no recursive CTE and no
 * per-database-family branching), which is why {@see self::query_db()} is overridden. Columns are not
 * sortable (a fixed tree order is mandatory). The name column shows the parent path as a breadcrumb,
 * so a search result keeps its context even though non-matching ancestors are filtered out.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class entities_table extends wunderbyte_table {
    /**
     * Loads the page of rows the database-agnostic way: a plain SELECT/FROM/WHERE (which already
     * carries the search and filter conditions), then orders the result into depth-first tree order
     * in PHP and slices out the requested page. No ORDER BY, recursive CTE or DB-specific SQL.
     *
     * @param int $pagesize
     * @param bool $useinitialsbar
     * @return void
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        global $DB;

        // Total count (database-agnostic COUNT) + initials-bar handling, mirroring the parent.
        if ($this->countsql === null) {
            $this->countsql = 'SELECT COUNT(1) FROM ' . $this->sql->from . ' WHERE ' . $this->sql->where;
            $this->countparams = $this->sql->params;
        }
        if ($useinitialsbar && !$this->is_downloading()) {
            $this->initialbars(true);
        }
        [$wsql, $wparams] = $this->get_sql_where();
        if ($wsql) {
            $this->sql->where .= ' AND ' . $wsql;
            $this->sql->params = array_merge($this->sql->params, $wparams);
            $this->countsql .= ' AND ' . $wsql;
            $this->countparams = array_merge($this->countparams, $wparams);
        }
        $total = $DB->count_records_sql($this->countsql, $this->countparams);
        $this->pagesize($pagesize, $total);

        // Fetch all matching rows (no LIMIT, no ORDER BY) and order them as a tree in PHP.
        $rows = $DB->get_records_sql(
            "SELECT {$this->sql->fields} FROM {$this->sql->from} WHERE {$this->sql->where}",
            $this->sql->params
        );
        $rows = $this->arrange_as_tree($rows);

        $pagestart = (int) $this->get_page_start();
        $pagelen = (int) $this->get_page_size();
        if (!$this->is_downloading() && $pagelen > 0) {
            $rows = array_slice($rows, $pagestart, $pagelen, true);
        }
        $this->rawdata = $rows;
    }

    /**
     * Orders rows into depth-first (parent → children) order and annotates each with its depth and
     * full name path. Works on a filtered/searched subset too: lineage is resolved against the full
     * entity set, so a matching child keeps a correct depth/breadcrumb even when its ancestors were
     * filtered out of the result.
     *
     * @param object[] $rows rows keyed by entity id
     * @return array<int,object> the same rows, ordered, each with ->entitydepth and ->namepath
     */
    public function arrange_as_tree(array $rows): array {
        $map = $this->get_entity_map();

        $keyed = [];
        foreach ($rows as $row) {
            [$depth, , $names] = $this->resolve_lineage((int)$row->id, $map);
            $row->entitydepth = $depth;
            $row->namepath = implode(' / ', $names);
            $keyed[] = [entities::get_tree_sortkey((int)$row->id, $map), $row];
        }

        usort($keyed, static fn($a, $b) => strcmp($a[0], $b[0]));

        $ordered = [];
        foreach ($keyed as [, $row]) {
            $ordered[(int)$row->id] = $row;
        }
        return $ordered;
    }

    /**
     * Lightweight map of all entities, used to resolve lineage for ordering/breadcrumb.
     *
     * Thin delegate to the shared, request-cached {@see entities::get_entity_map()} (the single source
     * of truth for the live hierarchy). Kept protected so any subclass override keeps working.
     *
     * @return array<int,object>
     */
    protected function get_entity_map(): array {
        return entities::get_entity_map();
    }

    /**
     * Walks an entity's parent chain.
     *
     * Thin delegate to the extracted {@see entities::get_ancestor_path()}; behaviour is unchanged.
     *
     * @param int $id
     * @param object[] $map
     * @return array{0:int,1:int[],2:string[]} [depth, ancestor ids root-first incl. self, names root-first incl. self]
     */
    protected function resolve_lineage(int $id, array $map): array {
        return entities::get_ancestor_path($id, $map);
    }

    /**
     * Name column: indentation by depth, a type icon, the name linked to the view page and the
     * parent path as a muted breadcrumb (so search results stay in context).
     *
     * @param stdClass $row
     * @return string
     */
    public function col_name($row): string {
        $depth = (int)($row->entitydepth ?? 0);
        $icon = ($row->entitytype ?? 'location') === 'equipment' ? 'fa-cube' : 'fa-map-marker';

        $link = html_writer::link(
            new moodle_url('/local/entities/view.php', ['id' => $row->id]),
            html_writer::tag('i', '', ['class' => "fa fa-fw $icon me-1 text-muted", 'aria-hidden' => 'true'])
                . format_string($row->name)
        );

        // The namepath is "root / … / self"; the breadcrumb is everything but the entity itself.
        $breadcrumb = '';
        $namepath = (string)($row->namepath ?? '');
        $pos = strrpos($namepath, ' / ');
        if ($pos !== false) {
            $breadcrumb = html_writer::div(substr($namepath, 0, $pos), 'small text-muted');
        }

        return html_writer::div(
            $link . $breadcrumb,
            '',
            ['style' => 'padding-left: ' . ($depth * 1.5) . 'rem;']
        );
    }

    /**
     * Type column: a coloured badge for location vs equipment.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_entitytype($row): string {
        if (($row->entitytype ?? 'location') === 'equipment') {
            return html_writer::span(get_string('entitytype_equipment', 'local_entities'), 'badge text-bg-info');
        }
        return html_writer::span(get_string('entitytype_location', 'local_entities'), 'badge text-bg-secondary');
    }

    /**
     * Usage column: how many booking options reference this entity.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_usecount($row): string {
        $count = (int)($row->usecount ?? 0);
        $class = $count > 0 ? 'badge text-bg-primary' : 'badge text-bg-light';
        // The bare number badge carries meaning by colour and position; give it an accessible label
        // so screen readers announce what the number is about, not just "2".
        return html_writer::span($count, $class, [
            'aria-label' => get_string('usecount', 'local_entities') . ': ' . $count,
        ]);
    }

    /**
     * Actions column: view, edit and (capability permitting) a delete button wired to a shared modal.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_actions($row): string {
        global $OUTPUT;
        $context = context_system::instance();

        $buttons = '';
        $buttons .= html_writer::link(
            new moodle_url('/local/entities/view.php', ['id' => $row->id]),
            html_writer::tag('i', '', ['class' => 'fa fa-search-plus', 'aria-hidden' => 'true']),
            ['class' => 'btn btn-sm btn-outline-secondary me-1', 'title' => get_string('view', 'local_entities')]
        );

        if (has_capability('local/entities:edit', $context)) {
            $buttons .= html_writer::link(
                new moodle_url('/local/entities/edit.php', ['id' => $row->id]),
                html_writer::tag('i', '', ['class' => 'fa fa-edit', 'aria-hidden' => 'true']),
                ['class' => 'btn btn-sm btn-outline-secondary me-1', 'title' => get_string('edit', 'local_entities')]
            );
        }

        if (has_capability('local/entities:delete', $context)) {
            $deletebutton = [[
                'label' => '',
                'class' => 'btn btn-sm btn-outline-danger',
                'href' => '#',
                'iclass' => 'fa fa-trash',
                'id' => $row->id,
                'name' => $row->name,
                'methodname' => 'deleteentity',
                'nomodal' => false,
                'data' => [
                    'id' => $row->id,
                    'titlestring' => 'deleteentity',
                    'bodystring' => 'deleteentityconfirm',
                    'submitbuttonstring' => 'delete',
                    'component' => 'local_entities',
                    'labelcolumn' => 'name',
                ],
            ]];
            table::transform_actionbuttons_array($deletebutton);
            $buttons .= $OUTPUT->render_from_template(
                'local_wunderbyte_table/component_actionbutton',
                ['showactionbuttons' => $deletebutton]
            );
        }

        return html_writer::div($buttons, 'd-flex');
    }

    /**
     * AJAX action handler for the delete button (one shared confirm modal for all rows).
     *
     * @param int $id entity id
     * @param string $data JSON payload from the button
     * @return array
     */
    public function action_deleteentity(int $id, string $data): array {
        $context = context_system::instance();
        require_capability('local/entities:delete', $context);

        $payload = json_decode($data);
        $entityid = (int)($payload->id ?? $id);
        if ($entityid > 0) {
            (new \local_entities\settings_manager($entityid))->delete();
        }

        return [
            'success' => 1,
            'message' => get_string('success'),
        ];
    }
}
