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

namespace local_entities\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use context_system;
use local_entities\local\views\entity_types;
use local_entities\local\views\view_templates;

/**
 * Saves the global active detail-view template (manager action behind the change capability).
 *
 * @package     local_entities
 * @category    external
 * @copyright   2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class set_active_view_template extends external_api {

    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'template' => new external_value(PARAM_ALPHANUMEXT, 'The view template key to activate'),
            'entitytype' => new external_value(PARAM_ALPHANUMEXT,
                'Apply to all entities of this type; empty sets the global fallback', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Persists the chosen template as the active view template for an entity type
     * (or as the global fallback when no type is given).
     *
     * @param string $template
     * @param string $entitytype
     * @return array
     */
    public static function execute(string $template, string $entitytype = ''): array {
        $params = self::validate_parameters(self::execute_parameters(),
            ['template' => $template, 'entitytype' => $entitytype]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/entities:changeviewtemplate', $context);

        if (!view_templates::exists($params['template'])) {
            throw new \invalid_parameter_exception('Unknown view template: ' . $params['template']);
        }

        if ($params['entitytype'] !== '') {
            if (!entity_types::exists($params['entitytype'])) {
                throw new \invalid_parameter_exception('Unknown entity type: ' . $params['entitytype']);
            }
            set_config('activeviewtemplate_' . $params['entitytype'], $params['template'], 'local_entities');
        } else {
            set_config('activeviewtemplate', $params['template'], 'local_entities');
        }

        return ['success' => true, 'template' => $params['template']];
    }

    /**
     * Describes the return value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the template was saved'),
            'template' => new external_value(PARAM_ALPHANUMEXT, 'The saved template key'),
        ]);
    }
}
