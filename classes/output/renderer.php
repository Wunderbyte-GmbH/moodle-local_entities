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
 * Plugin event observers are registered here.
 *
 * @package local_entities
 * @copyright 2021 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_entities\output;

use context_system;
use plugin_renderer_base;
use templatable;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderer class.
 * @package local_entities
 */
class renderer extends plugin_renderer_base {

    /**
     * Render a mooduell view page.
     *
     * @param templatable $viewpage
     * @return string|boolean
     */
    public function render_viewpage(templatable $viewpage) {
        $data = $viewpage->export_for_template($this);
        return $this->render_from_template('local_entities/view', $data);
    }

    /**
     * Get Submenu item.
     *
     * @param int $parent
     * @param string $name
     * @return void
     */
    public function get_submenuitem($parent, $name) {
        global $DB, $CFG, $USER;
        $html = '';
        $records = \local_entities\entities::list_all_subentities($parent);
        if ($records) {
            $html .= "<li class='list-group-item'>";
            $html .= '<div class="pull-right">' .
                '<a href="' . new moodle_url($CFG->wwwroot . '/local/entities/view.php',
                    array('id' => $parent)) . '" class="btn">' .
                    '<i class="fa fa-edit"></i>' .
                get_string('view', 'local_entities') . '</a> | ' .
                '<a href="' . new moodle_url($CFG->wwwroot . '/local/entities/edit.php',
                    array('id' => $parent)) . '" class="btn">' .
                    '<i class="fa fa-edit"></i>' .
                get_string('edit', 'local_entities') . '</a> | ' .
                '<a href="' . new moodle_url($CFG->wwwroot . '/local/entities/entities.php',
                    array('del' => $parent, 'sesskey' => $USER->sesskey)) .
                    '" class="btn">' .
                    '<i class="fa fa fa-trash"></i>' .
                    get_string('delete', 'local_entities') . ' </a></div>';
            $html .= "<h4 class=''>" . $name . "</h4>";
            $html .= "<ul class='pl-4 border-0'>";
            foreach ($records as $entity) {
                $html .= $this->get_submenuitem($entity->id, $entity->name);
            }
            $html .= "</ul>";
            $html .= "</li>";
        } else {
            $html .= "<li class='list-group-item'>";
            $html .= '<div class="pull-right">' .
                '<a href="' . new moodle_url($CFG->wwwroot . '/local/entities/view.php',
                    array('id' => $parent)) . '" class="btn">' .
                    '<i class="fa fa fa-edit"></i>' .
                get_string('view', 'local_entities') . '</a> | ' .
                '<a href="' . new moodle_url($CFG->wwwroot . '/local/entities/edit.php',
                    array('id' => $parent)) . '" class="btn">' .
                    '<i class="fa fa fa-edit"></i>' .
                get_string('edit', 'local_entities') . '</a> | ' .
                '<a href="' . new moodle_url($CFG->wwwroot . '/local/entities/entities.php',
                    array('del' => $parent, 'sesskey' => $USER->sesskey)) .
                    '" class="btn">' .
                    '<i class="fa fa fa-trash"></i>' .
                get_string('delete', 'local_entities') . ' </a></div>';
            $html .= "<h4 class=''>" . $name . "</h4>";
            $html .= "</li>";
        }
        return $html;
    }

    /**
     * List entities.
     *
     * @return void
     */
    public function list_entities() {
        global $DB, $CFG;

        $html = '<ul class="list-group mb-4">';
        $html .= '<li class="list-group-item bg-light"><h4>Entity List</h4></li>';
        $records = \local_entities\entities::list_all_entities();
        foreach ($records as $entity) {
            $html .= $this->get_submenuitem($entity->id, $entity->name);
        }

        $html .= "<li class='list-group-item'><a href='"
         . new moodle_url($CFG->wwwroot . '/local/entities/edit.php') .
            "' class='btn btn-smaller btn-primary pull-right'>" .
            '<i class="fa fa-plus"></i> ' .
             get_string("addentity", "local_entities") . "</a></li>";

        $html .= "</ul>";
        return $html;
    }


    /**
     *
     * Gets all the menu items
     *
     * @param mixed $parent
     * @param string $name
     * @param string $url
     * @return string
     */
    public function get_menuitem($parent, $name, $url) {
        global $DB, $CFG;
        $context = context_system::instance();
        $html = '';
        $urllocation = new moodle_url($CFG->wwwroot . '/local/entities/', array('id' => $parent));
        if (get_config('local_entities', 'cleanurl_enabled')) {
            $urllocation = new moodle_url($CFG->wwwroot . '/local/entities/' . $url);
        }
        $today = date('U');
        $records = $DB->get_records_sql("SELECT * FROM {local_entities} WHERE deleted=0 AND onmenu=1 " .
            "AND entitytype='entity' AND entityparent=? AND entitydate <=? " .
            "ORDER BY entityorder", array($parent, $today));
        if ($records) {
            $html .= "<li class='customentities_item'><a href='" . $urllocation . "'>" . $name . "</a>";
            $html .= "<ul class='customentities_submenu'>";
            $canaccess = true;
            foreach ($records as $entity) {
                if (isset($entity->accesslevel) && stripos($entity->accesslevel, ":") !== false) {
                    $canaccess = false;
                    $levels = explode(",", $entity->accesslevel);
                    foreach ($levels as $level) {
                        if ($canaccess != true) {
                            if (stripos($level, "!") !== false) {
                                $level = str_replace("!", "", $level);
                                $canaccess = has_capability(trim($level), $context) ? false : true;
                            } else {
                                $canaccess = has_capability(trim($level), $context) ? true : false;
                            }
                        }
                    }
                }
                if ($canaccess) {
                    $html .= $this->get_menuitem($entity->id, $entity->entityname, $entity->menuname);
                }
            }
            $html .= "</ul>";
            $html .= "</li>";
        } else {
            $html .= "<li class='customentities_item'><a href='" . $urllocation . "'>" . $name . "</a></li>";
        }
        return $html;
    }

    /**
     *
     * Builds the menu for the entity
     *
     * @return string
     */
    public function build_menu() {
        global $DB;
        $context = context_system::instance();
        $dbman = $DB->get_manager();
        $html = '';
        if ($dbman->table_exists('local_entities')) {
            $html = '<ul class="customentities_nav">';
            $today = date('U');
            $records = $DB->get_records_sql("SELECT * FROM {local_entities} WHERE deleted=0 AND onmenu=1 " .
                "AND entitytype='entity' AND entityparent=0 AND entitydate <= ? ORDER BY entityorder", array($today));
            $canaccess = true;
            foreach ($records as $entity) {
                if (isset($entity->accesslevel) && stripos($entity->accesslevel, ":") !== false) {
                    $canaccess = false;
                    $levels = explode(",", $entity->accesslevel);
                    foreach ($levels as $key => $level) {
                        if ($canaccess != true) {
                            if (stripos($level, "!") !== false) {
                                $level = str_replace("!", "", $level);
                                $canaccess = has_capability(trim($level), $context) ? false : true;
                            } else {
                                $canaccess = has_capability(trim($level), $context) ? true : false;
                            }
                        }
                    }
                }
                if ($canaccess) {
                    $html .= $this->get_menuitem($entity->id, $entity->entityname, $entity->menuname);
                }
            }
            $html .= "</ul>";
        }
        return $html;
    }
}
