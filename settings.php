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
                $componentname . '/categories',
                get_string('categories', $componentname),
                get_string('categories:description', $componentname),
                [],
                $categories
            )
        );
    }

    $settings->add(
        new admin_setting_configcheckbox(
            $componentname . '/fallback_image_parent',
            get_string('fallback_image_parent', $componentname),
            get_string('fallback_image_parent:description', $componentname),
            1
        )
    );

    $settings->add(
        new admin_setting_configcheckbox(
            $componentname . '/fallback_address_parent',
            get_string('fallback_address_parent', $componentname),
            get_string('fallback_address_parent:description', $componentname),
            1
        )
    );

    $settings->add(
        new admin_setting_configcheckbox(
            $componentname . '/fallback_contacts_parent',
            get_string('fallback_contacts_parent', $componentname),
            get_string('fallback_contacts_parent:description', $componentname),
            1
        )
    );

    // Global fallback detail-view template (used for any type without its own choice). Managers can
    // also switch and save it directly on a detail page.
    $settings->add(
        new admin_setting_configselect(
            $componentname . '/activeviewtemplate',
            get_string('activeviewtemplate', $componentname),
            get_string('activeviewtemplate:description', $componentname),
            \local_entities\local\views\view_templates::DEFAULT_TEMPLATE,
            \local_entities\local\views\view_templates::menu()
        )
    );

    // Per-entity-type overrides: each type may use its own template; empty inherits the global fallback.
    $typechoices = ['' => get_string('inheritglobal', $componentname)]
        + \local_entities\local\views\view_templates::menu();
    foreach (\local_entities\local\views\entity_types::all() as $type => $typename) {
        $settings->add(
            new admin_setting_configselect(
                $componentname . '/activeviewtemplate_' . $type,
                get_string('activeviewtemplatefortype', $componentname, $typename),
                get_string('activeviewtemplatefortype:description', $componentname, $typename),
                '',
                $typechoices
            )
        );
    }

    $settings->add(
        new admin_setting_configcheckbox(
            $componentname . '/usesubentitynamesforfilter',
            get_string('usesubentitynamesforfilter', $componentname),
            get_string('usesubentitynamesforfilter:description', $componentname),
            0
        )
    );
}
