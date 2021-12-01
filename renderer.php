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
 * @author      Kevin Dibble
 * @copyright   2017 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/entities/forms/edit.php');

/**
 *
 * Class local_entities_renderer
 *
 * @copyright   2017 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_entities_renderer extends plugin_renderer_base
{

    /**
     * @var array
     */
    public $errorfields = array();

    /**
     *
     * List the entities for the user to view
     *
     * @return string
     */
    public function list_entities() {
        global $DB, $CFG;
        $html = '<ul class="customentities-list">';
        $records = $DB->get_records_sql("SELECT * FROM {local_entities}");
        foreach ($records as $entity) {
            $html .= $this->get_submenuitem($entity->id, $entity->entityname);
        }

        $html .= "<li class='customentities-list-element'>
                	<a href='" . new moodle_url($CFG->wwwroot . '/local/entities/edit.php') .
            "' class='customentities-add'>" . get_string("addentity", "local_entities") . "</a></li>";

        $html .= "</ul>";
        return $html;
    }

    /**
     *
     * Process the submitted form up update entity data
     *
     * @param mixed $entity
     */
    public function processform($entity) {
        global $DB;
        $touser = get_admin();
        $fromuser = clone $touser;

        $touser->email = isset($entity->emailto) && trim($entity->emailto) ? $entity->emailto : $touser->email;
        $touser->emailstop = 0;

        $messagetext = '';
        $fields = array();
        $records = json_decode($entity->entitydata);
        $outarray = array();
        foreach ((array)$records as $key => $value) {
            if ($value->type != "HTML") {
                $outarray[] = "{" . $value->name . "}";

                $tmpparam = str_replace(" ", "_", $value->name);
                $tmpparam = optional_param($tmpparam, '', PARAM_RAW);
                $tmpparam = $this->cleanme($tmpparam, $value->type);
                $fields[$value->name] = $tmpparam;
                $messagetext .= ucfirst($value->name) . ": " . $tmpparam . "\r\n";
                $field = strtolower(str_replace(" ", "", $value->name));
                $fromuser->$field = $tmpparam;
            }
        }

        $messagehtml = nl2br($messagetext);
        $subject = $entity->entityname;

        $data = new stdClass();
        $data->formdate = date('U');
        $data->formcontent = json_encode($fields);
        $data->formname = $entity->id;
        $DB->insert_record('local_entitieslogging', $data);

        email_to_user($touser, $fromuser, $subject, $messagetext, $messagehtml, '', '', true);

        $outarray[] = "{table}";
        $fields[] = $messagetext;

        $messageforuser = str_replace($outarray, $fields, get_config('local_entities', 'message_copy'));
        $messagetext = strip_tags(str_replace(array("</p>", "<br>", "&nbsp;"), array("</p>\r\n", "<br>\r\n", ""),
            $messageforuser));

        // Emails a copy to the user.
        if (get_config('local_entities', 'user_copy') == 1) {
            email_to_user($fromuser, $touser, $subject, $messagetext, $messageforuser, '', '', true);
        }
    }

    /**
     *
     * Save the entity to the database and redirect the user
     *
     * @param bool $entity
     */
    public function save_entity($entity = false) {
        global $CFG;
        $mform = new entities_form($entity);
        if ($mform->is_cancelled()) {
            redirect(new moodle_url($CFG->wwwroot . '/local/entities/entities.php'));
        } else if ($data = $mform->get_data()) {
            require_once($CFG->libdir . '/formslib.php');
            $context = context_system::instance();
            $data->entitycontent['text'] = file_save_draft_area_files($data->entitycontent['itemid'], $context->id,
                'local_entities', 'entitycontent',
                0, array('subdirs' => true), $data->entitycontent['text']);

            $data->entitydata = '';
            $recordentity = new stdClass();
            $recordentity->id = $data->id;
            $recordentity->name = $data->name;
            $recordentity->sortorder = intval($data->sortorder);      
            $recordentity->type = $data->type;
            $recordentity->parentid = intval($data->parentid);
            $recordentity->description = $data->description['text'];
            $result = $entity->update($recordentity);
            if ($result && $result > 0) {
                $options = array('subdirs' => 0, 'maxbytes' => 204800, 'maxfiles' => 1, 'accepted_types' => '*');
                if (isset($data->ogimage_filemanager)) {
                    file_postupdate_standard_filemanager($data, 'ogimage', $options, $context, 'local_entities', 'ogimage', $result);
                }
                redirect(new moodle_url($CFG->wwwroot . '/local/entities/edit.php', array('id' => $result)));
            }
        }
    }

    /**
     *
     * Show the entity information to edit
     *
     * @param bool $entity
     */
    public function edit_entity($entity = false) {
        $mform = new entities_form($entity);
        $forform = new stdClass();
        $forform->description['text'] = $entity->description;
        $forform->name = $entity->name;
        $forform->id = $entity->id;
        $forform->parentid = $entity->parentid;
        $forform->sortorder  = $entity->sortorder;
        $mform->set_data($forform);
        $mform->display();
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
                    $canaccess = false;        // entity Has level Requirements - check rights.
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
