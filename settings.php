<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin administration entities are defined here.
 *
 * @package     local_entities
 * @category    admin
 * @copyright   2021 Wunderbyte GmbH<info@wunderbyte.at>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$componentname = 'local_entities';

// Default for users that have site config.
if ($hassiteconfig) {
    // Add the category to the local plugin branch.
    $settings = new admin_settingpage($componentname . '_settings', '');
    $ADMIN->add('localplugins', new admin_category($componentname, get_string('pluginname', $componentname)));
    $ADMIN->add($componentname, $settings);

    // Select Standard Categories from custom categories.
    $categories = \local_entities\customfield\entities_cf_helper::get_all_cf_categories();

    if (!empty($categories)) {
        $settings->add(
            new admin_setting_configmultiselect(
            $componentname .'/categories',
            get_string('categories', $componentname),
            get_string('categories:description', $componentname),
            array(),
            $categories
            )
        );
    }
}
