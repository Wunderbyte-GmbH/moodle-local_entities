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
 * local entities lib
 * @package    local_entities
 * @copyright  2021 Wunderbyte GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * This function extends the navigation with the report items
 *
 * @param navigation_node $navigation The navigation node to extend
 */
function local_entities_extend_navigation($navigation) {
    $context = \context_system::instance();
    if (has_capability('local/entities:canedit', $context)) {
        $nodehome = $navigation->get('home');
        if (empty($nodehome)) {
            $nodehome = $navigation;
        }
        $pluginname = get_string('pluginname', 'local_entities');
        $link = new moodle_url('/local/entities/entities.php', array());
        $icon = new pix_icon('tennis-ball', $pluginname, 'local_entities');
        $nodecreatecourse = $nodehome->add($pluginname, $link, navigation_node::NODETYPE_LEAF, $pluginname, 'entities', $icon);
        $nodecreatecourse->showinflatnavigation = true;
    }
}

/**
 *
 * Get saved files for the page
 *
 * @param mixed $course
 * @param mixed $birecordorcm
 * @param mixed $context
 * @param mixed $filearea
 * @param mixed $args
 * @param bool $forcedownload
 * @param array $options
 */
function local_entities_pluginfile($course, $birecordorcm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    $fs = get_file_storage();

    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    if ($filearea === 'description') {
        if (!$file = $fs->get_file($context->id, 'local_entities', 'entitycontent', 0, $filepath, $filename)
        || $file->is_directory()) {
            send_file_not_found();
        }
    } else if ($filearea === 'image') {
        $itemid = array_pop($args);
        $file = $fs->get_file($context->id, 'local_entities', $filearea, $itemid, '/', $filename);
        // Todo: Maybe put in fall back image.
    }

    \core\session\manager::write_close();
    send_stored_file($file, null, 0, $forcedownload, $options);
}
