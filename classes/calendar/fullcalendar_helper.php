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
 * local entities
 *
 * @package local_entities
 * @author Thomas Winkler
 * @copyright 2022 Wunderbyte GmbH
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_entities\calendar;

use DateTime;
use stdClass;
defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once("$CFG->libdir/externallib.php");

/**
 * Class fullcalendar_helper
 *
 * @author Thomas Winkler
 * @copyright 2022 Wunderbyte GmbH
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fullcalendar_helper {

    /**
     * entities constructor.
     */
    public function __construct() {
        // Empty?
    }

    /**
     * Get lang strings of days of week for select
     *
     * @return array
     */
    public static function get_weekdays() :array {
        $daysofweekarray = [
            1 => get_string('monday', 'calendar'),
            2 => get_string('tuesday', 'calendar'),
            3 => get_string('wednesday', 'calendar'),
            4 => get_string('thursday', 'calendar'),
            5 => get_string('friday', 'calendar'),
            6 => get_string('saturday', 'calendar'),
            7 => get_string('sunday', 'calendar')];
        return $daysofweekarray;
    }

    /**
     * Get hours of day for select
     *
     * @return array
     */
    public static function get_hours_select() :array {
        for ($i = 0; $i <= 23; $i++) {
            $hours[$i] = sprintf("%02d", $i);
        }
        return $hours;
    }

    /**
     * Get minutes for select
     *
     * @return array
     */
    public static function get_minutes_select() :array {
        for ($i = 0; $i < 60; $i++) {
            $minutes[$i] = "  " .  sprintf("%02d", $i);
        }
        return $minutes;
    }
}
