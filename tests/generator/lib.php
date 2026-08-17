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

use local_entities\form\edit_dynamic_form;

/**
 * Class local_entities_generator for generation of dummy data
 *
 * @package local_entities
 * @category test
 * @copyright 2024 Wunderbyte GmbH <info@wunderbyte.at>
 * @author Andrii Semenets
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_entities_generator extends testing_module_generator {
    /**
     * Create entities.
     *
     * @param array $data
     * @return int
     */
    public function create_entities(array $data): int {

        $data = (object)(array) $data;
        // Force required default values for the entity.
        $data->addresscount = $data->addresscount ?? 0;
        $data->entitytype = $data->entitytype ?? 'location';
        $data->description = $data->description ?? '';
        $data->pricefactor = $data->pricefactor ?? 1.0;
        $data->cfitemid = $data->cfitemid ?? 0;
        $data->daysofweek = json_decode($data->daysofweek ?? "") ?? [];
        $data->starthours = json_decode($data->starthours ?? "") ?? [];
        $data->startminutes = json_decode($data->startminutes ?? "") ?? [];
        $data->endhours = json_decode($data->endhours ?? "") ?? [];
        $data->endminutes = json_decode($data->endminutes ?? "") ?? [];

        // Simulate ajax submission to obtain correct defaults and session key.
        $ajaxargs = json_decode(json_encode($data), true);
        $submitdata = edit_dynamic_form::mock_ajax_submit($ajaxargs);
        // Actuall creation and processing edit_dynamic_form.
        $mform = new edit_dynamic_form(null, null, 'post', '', [], true, $submitdata, true);
        $mform->set_data_for_dynamic_submission();
        $res = $mform->process_dynamic_submission();
        return ($res->id ?? -1);
    }
}
