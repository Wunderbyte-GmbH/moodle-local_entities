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
 * Contains class mod_questionnaire\output\indexpage
 *
 * @package    local_entities
 * @copyright  2021 Wunderbyte Gmbh <info@wunderbyte.at>
 * @author     Thomas Winkler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

namespace local_entities\output;

use renderable;
use renderer_base;
use templatable;
use local_entities\settings_manager;
use local_entities\customfield\entities_handler;
use stdClass;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * viewpage class to display view.php
 * @package mod_mooduell
 *
 */
class viewpage implements renderable, templatable {


    private $data;
    /**
     * Constructor.
     * @param settings_manager $data
     */
    public function __construct(int $id) {
        global $USER;
        $data = settings_manager::get_settings($id);
        $context = \context_system::instance();
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'local_entities', 'image', $data->id);
        foreach ($files as $file) {
            $filename = $file->get_filename();
            if ($file->get_filesize() > 0) {
                $url = moodle_url::make_file_url('/pluginfile.php', '/1/local_entities/image/' . $data->id . '/' . $filename);
            }
        }

        $handler = entities_handler::create($id);
        $customfields = $handler->get_instance_data($id);
        $customdata = '';
        foreach ($customfields as $customfield) {
            if (empty($customfield->get_value())) {
                continue;
            }
            $cat = $customfield->get_field()->get_category()->get('name');
            $metakey = $customfield->get_field()->get('name');
            $customdata .= '<span><b>' . $metakey . '</b>: ' . $customfield->get_value() .'</span></br>';
        }
        $data->customdata = $customdata;
        $data->url = $url;
        $data->description = file_rewrite_pluginfile_urls($data->description, 'pluginfile.php',
        $context->id, 'local_entity', 'description', null);

        $data->isopen = $data->open ? 'checked' : '';
        $data->picture = isset($url) ? $url : null;
        $data->hasaddress = isset($data->address);
        $data->hascontacts = isset($data->contacts);
        $data->haspicture = isset($data->picture);
        if ($data->hasaddress) {
            $data->addresscleaned = array_values($data->address);
        }
        if ($data->hascontacts) {
            $data->contactscleaned = array_values($data->contacts);
        }
        if (isset($data->type)) {
            $type = explode('_', $data->type, 2);
            $data->type = $type[1];
        }
        $data->editurl = new moodle_url('/local/entities/edit.php', array( 'id' => $data->id));
        $data->delurl = new moodle_url('/local/entities/entities.php', array( 'del' => $data->id , 'sesskey' => $USER->sesskey));
        $data->description = format_text($data->description, FORMAT_HTML);
        $this->data = $data;
    }

    /**
     * Prepare data for use in a template
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        $data = $this->data;
        return $data;
    }



}
