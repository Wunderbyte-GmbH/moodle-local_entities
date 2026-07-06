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

namespace local_entities\local\templates;

use core_customfield\category_controller;
use core_customfield\field_controller;
use local_entities\customfield\entities_cf_helper;
use local_entities\customfield\entities_handler;

/**
 * Seeds the default, ready-to-use entity field templates (location & equipment).
 *
 * These are ordinary custom field categories created via the core customfield API, so they can be
 * edited, extended or removed by an admin afterwards. Seeding is idempotent (guarded by a config
 * flag) and never touches existing entities or their stored data.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class template_seeder {
    /** @var string Config flag remembering that the defaults were seeded once (so re-upgrade is a no-op). */
    const SEEDED_FLAG = 'defaulttemplatesseeded';

    /** @var int Field visibility: shown to everybody (mirrors entities_handler::VISIBLETOALL). */
    const VISIBLETOALL = 2;

    /**
     * Creates the two default templates once. Safe to call repeatedly (no-op after the first run).
     *
     * @return void
     */
    public static function seed_default_templates(): void {
        // Idempotent: only ever seed once. An admin who deletes/edits the templates is respected.
        if (get_config('local_entities', self::SEEDED_FLAG)) {
            return;
        }

        $locitemid = self::create_template(self::location_definition());
        $equipitemid = self::create_template(self::equipment_definition());

        // Persist the resulting itemids so later features (type-based auto-binding) can reference
        // them; itemid is a global running number and therefore not safe to hard-code.
        set_config('template_location_itemid', $locitemid, 'local_entities');
        set_config('template_equipment_itemid', $equipitemid, 'local_entities');
        set_config(self::SEEDED_FLAG, 1, 'local_entities');
    }

    /**
     * Creates one category and its fields from a definition, returning the category itemid.
     *
     * @param array $definition ['name' => string, 'fields' => array]
     * @return int the itemid of the created category
     */
    private static function create_template(array $definition): int {
        $itemid = entities_cf_helper::get_next_itemid();
        $handler = entities_handler::create($itemid);

        $categoryid = $handler->create_category($definition['name']);
        $category = category_controller::create($categoryid);

        foreach ($definition['fields'] as $fielddef) {
            $field = field_controller::create(0, (object)['type' => $fielddef['type']], $category);
            $handler->save_field_configuration($field, (object)[
                'name' => $fielddef['name'],
                'shortname' => $fielddef['shortname'],
                'type' => $fielddef['type'],
                'description' => '',
                'descriptionformat' => FORMAT_HTML,
                'configdata' => ($fielddef['configdata'] ?? [])
                    + self::type_default_configdata($fielddef['type'])
                    + [
                        'required' => 0,
                        'uniquevalues' => 0,
                        'locked' => 0,
                        'visibility' => self::VISIBLETOALL,
                    ],
            ]);
        }

        return $itemid;
    }

    /**
     * Per-field-type default configdata, so each core field type gets the keys its data controller
     * expects (the text type reads several keys as plain array access and warns if they are missing).
     *
     * @param string $type custom field type
     * @return array
     */
    private static function type_default_configdata(string $type): array {
        switch ($type) {
            case 'text':
                return ['defaultvalue' => '', 'displaysize' => 50, 'maxlength' => 1333,
                    'ispassword' => 0, 'link' => '', 'linktarget' => ''];
            case 'textarea':
                return ['defaultvalue' => '', 'defaultvalueformat' => FORMAT_HTML];
            case 'select':
                return ['defaultvalue' => ''];
            case 'date':
                return ['includetime' => 0, 'mindate' => 0, 'maxdate' => 0];
            case 'checkbox':
                return ['checkbydefault' => 0];
            default:
                return [];
        }
    }

    /**
     * Shorthand for a plugin language string (resolved to the site language at seed time).
     *
     * @param string $key
     * @return string
     */
    private static function str(string $key): string {
        return get_string($key, 'local_entities');
    }

    /**
     * Definition of the "Location" template. Names come from plain Moodle language strings, so they
     * are stored in the site's language at seed time (German on a German platform, English otherwise).
     *
     * @return array
     */
    private static function location_definition(): array {
        return [
            'name' => self::str('template_location'),
            'fields' => [
                ['shortname' => 'loc_building', 'type' => 'text', 'name' => self::str('tplfield_building')],
                ['shortname' => 'loc_roomnumber', 'type' => 'text', 'name' => self::str('tplfield_roomnumber')],
                ['shortname' => 'loc_area', 'type' => 'text', 'name' => self::str('tplfield_area')],
                ['shortname' => 'loc_seats', 'type' => 'text', 'name' => self::str('tplfield_seats')],
                ['shortname' => 'loc_amenities', 'type' => 'textarea', 'name' => self::str('tplfield_amenities')],
                ['shortname' => 'loc_accessible', 'type' => 'checkbox', 'name' => self::str('tplfield_accessible'),
                    'configdata' => ['checkbydefault' => 0]],
                ['shortname' => 'loc_notes', 'type' => 'textarea', 'name' => self::str('tplfield_notes')],
            ],
        ];
    }

    /**
     * Definition of the "Equipment" template.
     *
     * @return array
     */
    private static function equipment_definition(): array {
        $conditionoptions = implode("\n", [
            self::str('tplcond_new'),
            self::str('tplcond_good'),
            self::str('tplcond_used'),
            self::str('tplcond_defective'),
        ]);

        return [
            'name' => self::str('template_equipment'),
            'fields' => [
                ['shortname' => 'eq_inventoryno', 'type' => 'text', 'name' => self::str('tplfield_inventoryno'),
                    'configdata' => ['uniquevalues' => 1]],
                ['shortname' => 'eq_manufacturer', 'type' => 'text', 'name' => self::str('tplfield_manufacturer')],
                ['shortname' => 'eq_model', 'type' => 'text', 'name' => self::str('tplfield_model')],
                ['shortname' => 'eq_serial', 'type' => 'text', 'name' => self::str('tplfield_serial')],
                ['shortname' => 'eq_purchasedate', 'type' => 'date', 'name' => self::str('tplfield_purchasedate')],
                ['shortname' => 'eq_condition', 'type' => 'select', 'name' => self::str('tplfield_condition'),
                    'configdata' => ['options' => $conditionoptions]],
                ['shortname' => 'eq_responsible', 'type' => 'text', 'name' => self::str('tplfield_responsible')],
                ['shortname' => 'eq_notes', 'type' => 'textarea', 'name' => self::str('tplfield_notes')],
            ],
        ];
    }
}
