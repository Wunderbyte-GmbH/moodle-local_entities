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

namespace local_entities\admin;

use local_entities\planning_period;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/adminlib.php');

/**
 * Admin setting for one day, chosen with the date picker of the browser and stored as 2026-12-01.
 *
 * An empty value is allowed and means that no day is set.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setting_date extends \admin_setting {
    /**
     * The stored day.
     *
     * @return string|null
     */
    public function get_setting() {
        return $this->config_read($this->name);
    }

    /**
     * Stores the day, or nothing if the field is empty.
     *
     * @param mixed $data
     * @return string empty if saved, otherwise the error
     */
    public function write_setting($data) {
        $value = trim((string)$data);
        if ($value !== '' && planning_period::parse_day($value) === null) {
            return get_string('planningperiod_invaliddate', 'local_entities');
        }
        return $this->config_write($this->name, $value) ? '' : get_string('errorsetting', 'admin');
    }

    /**
     * The date field.
     *
     * @param mixed $data the stored day
     * @param string $query
     * @return string
     */
    public function output_html($data, $query = '') {
        $element = \html_writer::empty_tag('input', [
            'type' => 'date',
            'id' => $this->get_id(),
            'name' => $this->get_full_name(),
            'value' => (string)$data,
            'class' => 'form-control w-auto',
        ]);
        return format_admin_setting($this, $this->visiblename, $element, $this->description, true, '', null, $query);
    }
}
