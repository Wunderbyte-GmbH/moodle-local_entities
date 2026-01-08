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
 * Local entities Renderer
 *
 * @package     local_entities
 * @author      Thomas Winkler
 * @copyright   2021 Wunderbyte GmbH
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 *
 * Class local_entities_renderer
 *
 * @copyright   2021 Wunderbyte GmbH
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_entities_renderer extends plugin_renderer_base {
    /**
     * Undocumented function
     *
     * @param int $parent
     * @param string $name
     * @return string htmlcode
     */
    public function get_submenuitem(int $parent, string $name): string {
        global $CFG, $USER;
        $html = '';
        $records = \local_entities\entities::list_all_subentities($parent);
        if ($records) {
            $html .= "<li class='list-group-item'>";
            $html .=
                // View.
                '<a href="' . new moodle_url(
                    $CFG->wwwroot . '/local/entities/view.php',
                    ['id' => $parent]
                ) . '" class="btn btn--plain btn--smaller btn--primary btn_edit">' .
                    '<i class="fa fa-search-plus"></i>&nbsp;' .
                get_string('view', 'local_entities') . '</a> | ' .
                // Edit.
                '<a href="' . new moodle_url(
                    $CFG->wwwroot . '/local/entities/edit.php',
                    ['id' => $parent]
                ) . '" class="btn btn--plain btn--smaller btn--primary btn_edit">' .
                    '<i class="fa fa-edit"></i>&nbsp;' .
                get_string('edit', 'local_entities') . '</a> | ';

            // Delete button.
            $html .=
                '<button class="btn btn--plain btn--smaller btn--primary btn_edit"
                    title="' . get_string('delete', 'local_entities') .
                    '" rel="nofollow" data-method="delete"
                    data-target="#deleteModal-' . $parent . '" data-bs-target="#deleteModal-' . $parent . '"
                    data-toggle="modal" data-bs-toggle="modal">
                        <i class="fa fa-trash"></i>&nbsp;' . get_string('delete', 'local_entities') .
                '</button>';

            // Delete modal.
            $html .=
                '<div class="modal fade" id="deleteModal-' . $parent . '">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLabel">' .
                                get_string('deleteentity', 'local_entities') . '</h5>
                                <button type="button"
                                    class="btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p>' . get_string('deleteentityconfirm', 'local_entities') . '</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal"
                                        id="close-modal">' . get_string('cancel') . '</button>
                                <a href="' . new moodle_url(
                                            $CFG->wwwroot . '/local/entities/entities.php',
                                            [
                                            'del' => $parent,
                                            'sesskey' => $USER->sesskey,
                                            ]
                                        ) . '" rel="nofollow" class="btn btn-danger">' .
                                get_string('delete') . '</a>
                            </div>
                        </div>
                    </div>
                </div>';

            $html .= "<h4>" . $name . "</h4>";
            $html .= "<ul class='ps-4 border-0'>";
            foreach ($records as $entity) {
                $html .= $this->get_submenuitem($entity->id, $entity->name);
            }
            $html .= "</ul>";
            $html .= "</li>";
        } else {
            $html .= "<li class='list-group-item'>";
            $html .=
                '<a href="' . new moodle_url(
                    $CFG->wwwroot . '/local/entities/view.php',
                    ['id' => $parent]
                ) . '" class="btn btn--plain btn--smaller btn--primary btn_edit">' .
                    '<i class="fa fa fa-search-plus"></i>&nbsp;' .
                get_string('view', 'local_entities') . '</a> | ' .
                '<a href="' . new moodle_url(
                    $CFG->wwwroot . '/local/entities/edit.php',
                    ['id' => $parent]
                ) . '" class="btn btn--plain btn--smaller btn--primary btn_edit">' .
                    '<i class="fa fa fa-edit"></i>&nbsp;' .
                get_string('edit', 'local_entities') . '</a> | ';

            // Delete button.
            $html .=
                '<button class="btn btn--plain btn--smaller btn--primary btn_edit"
                    title="' . get_string('delete', 'local_entities') .
                    '" rel="nofollow" data-method="delete"
                    data-target="#deleteModal-' . $parent . '" data-bs-target="#deleteModal-' . $parent . '"
                    data-toggle="modal" data-bs-toggle="modal">
                        <i class="fa fa-trash"></i>&nbsp;' . get_string('delete', 'local_entities') .
                '</button>';

            // Delete modal.
            $html .=
                '<div class="modal fade" id="deleteModal-' . $parent . '">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLabel">' .
                                get_string('deleteentity', 'local_entities') . '</h5>
                                <button type="button"
                                    class="btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p>' . get_string('deleteentityconfirm', 'local_entities') . '</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal"
                                        id="close-modal">' . get_string('cancel') . '</button>
                                <a href="' . new moodle_url(
                                            $CFG->wwwroot . '/local/entities/entities.php',
                                            [
                                            'del' => $parent,
                                            'sesskey' => $USER->sesskey,
                                            ]
                                        ) . '" rel="nofollow" class="btn btn-danger">' .
                                get_string('delete') . '</a>
                            </div>
                        </div>
                    </div>
                </div>';

            $html .= "<h4 class=''>" . $name . "</h4>";
            $html .= "</li>";
        }
        return $html;
    }

    /**
     * List entities.
     *
     * @return string
     */
    public function list_entities(): string {
        global $CFG;

        $html = '<ul class="list-group mb-4">';
        $html .= '<li class="list-group-item bg-light"><h4>' . get_string("entitylist", "local_entities") . '</h4></li>';
        $html .= "<li class='list-group-item'><a href='"
        . new moodle_url($CFG->wwwroot . '/local/entities/edit.php') .
           "' class='btn btn-smaller btn-primary pull-right mx-2'>" .
           '<i class="fa fa-plus"></i> ' .
            get_string("addentity", "local_entities") . "</a><a href='"
            . new moodle_url($CFG->wwwroot . '/local/entities/customfield.php') .
               "' class='btn btn-smaller btn-primary pull-right'>" .
               '<i class="fa fa-plus"></i> ' .
                get_string("addcategory", "local_entities") . "</a></li>";
        $records = \local_entities\entities::list_all_parent_entities();
        foreach ($records as $entity) {
            $html .= $this->get_submenuitem($entity->id, $entity->name);
        }

        $html .= "<li class='list-group-item'><a href='"
         . new moodle_url($CFG->wwwroot . '/local/entities/edit.php') .
            "' class='btn btn-smaller btn-primary pull-right mx-2'>" .
            '<i class="fa fa-plus"></i> ' .
             get_string("addentity", "local_entities") . "</a><a href='"
             . new moodle_url($CFG->wwwroot . '/local/entities/customfield.php') .
                "' class='btn btn-smaller btn-primary pull-right'>" .
                '<i class="fa fa-plus"></i> ' .
                get_string("addcategory", "local_entities") . "</a></li>";

        $html .= "</ul>";
        return $html;
    }


    /**
     * Get submenu select
     *
     * @param int $parent
     * @param string $name
     * @return string htmlcode
     */
    public function get_submenuitem_select(int $parent, string $name) {
        $html = '';
        $records = \local_entities\entities::list_all_subentities($parent);
        if ($records) {
            $html .= "<li  class='list-group-item p-0 ps-2'>";

            $html .= "<span class='' href='#parent-" . $parent
            . "' data-toggle='collapse' data-bs-toggle='collapse' aria-expanded='false'>" . $name . "</span>";
            $html .= "<div class='pull-right'><span class='btn btn-primary py-0 fa-plus fa' data-action='addentity'
            data-entityname='" . $name . "'  data-entityid='" . $parent . "'></span></div>";

            $html .= "<ul class='ps-4 border-0 collapse' id='parent-" . $parent . "'>";
            foreach ($records as $entity) {
                $html .= $this->get_submenuitem_select($entity->id, $entity->name);
            }
            $html .= "</ul>";
            $html .= "</li>";
        } else {
            $html .= "<li class='list-group-item p-0 ps-2'>";
            $html .= "<span class=''>" . $name . "</span>";
            $html .= "<div class='pull-right'><span class='btn btn-primary py-0 fa-plus fa' data-action='addentity'
            data-entityname='" . $name . "'  data-entityid='" . $parent . "'></span></div>";
            $html .= "</li>";
        }
        return $html;
    }

    /**
     * A custom Select for js select addentity
     *
     * @return string
     */
    public function list_entities_select(): string {
        $html = '<ul class="list-group group-root my-4">';
        $html .= "<li  class='list-group-item p-0 ps-2'><span class='btn btn-primary py-0' data-action='addentity'
        data-entityname='none'  data-entityid='-1'>No entity</span></li>";
        $records = \local_entities\entities::list_all_parent_entities();
        foreach ($records as $entity) {
            $html .= $this->get_submenuitem_select($entity->id, $entity->name);
        }
        $html .= "</ul>";
        return $html;
    }
}
