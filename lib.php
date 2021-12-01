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
 * @package    local_edusupport
 * @copyright  2020 Center for Learningmanagement (www.lernmanagement.at)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

defined('MOODLE_INTERNAL') || die;

/**
 * Adds module specific settings to the settings block
 *
 * @param settings_navigation $settings The settings navigation object
 * @param navigation_node $modnode The node to add module settings to
 *
 * $settings is unused, but API requires it. Suppress PHPMD warning.
 *
 */
function local_entities_extend_navigation($navigation) {
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
//TODO add pluginfile
