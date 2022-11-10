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
 * Class dates.
 * @package local_entities
 * @author Bernhard Fischer
 * @copyright 2022 Wunderbyte GmbH
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_entities;

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Class dates.
 *
 * @author Bernhard Fischer
 * @copyright 2022 Wunderbyte GmbH
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dates {

    /**
     * Helper function to format option dates. If they are on the same day, show date only once.
     * Else show both dates.
     * @param int $starttimestamp
     * @param int $endtimestamp
     * @param string $lang optional language parameter
     * @param bool $showweekdays if true, weekdays will be shown
     * @return string the prettified string from start to end date
     */
    public static function prettify_dates_start_end(int $starttimestamp, int $endtimestamp,
        string $lang = 'en', bool $showweekdays = true): string {

        $prettifiedstring = '';

        // Only show weekdays, if they haven't been turned off.
        if ($showweekdays) {
            $weekdayformat = 'D, ';
        } else {
            $weekdayformat = '';
        }

        switch($lang) {
            case 'de':
                $stringstartdate = date($weekdayformat . 'd.m.Y', $starttimestamp);
                $stringenddate = date($weekdayformat . 'd.m.Y', $endtimestamp);
                break;
            case 'en':
            default:
                $stringstartdate = date($weekdayformat . 'Y-m-d', $starttimestamp);
                $stringenddate = date($weekdayformat . 'Y-m-d', $endtimestamp);
                break;
        }

        $stringstarttime = date('H:i', $starttimestamp);
        $stringendtime = date('H:i', $endtimestamp);

        if ($stringstartdate === $stringenddate) {
            // If they are one the same day, show date only once.
            $prettifiedstring = $stringstartdate . ' | ' . $stringstarttime . '-' . $stringendtime;
        } else {
            // Else show both dates.
            $prettifiedstring = $stringstartdate . ' | ' . $stringstarttime . ' - ' . $stringenddate . ' | ' . $stringendtime;
        }

        // Little hack that is necessary because date does not support appropriate internationalization.
        if ($showweekdays) {
            if ($lang == 'de') {
                // Note: If we want to support further languages, this should be moved to a separate function...
                // ...and be implemented with switch.
                $weekdaysenglishpatterns = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                $weekdaysreplacements = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];
                for ($i = 0; $i < 7; $i++) {
                    $prettifiedstring = str_replace($weekdaysenglishpatterns[$i], $weekdaysreplacements[$i], $prettifiedstring);
                }
            }
        }

        return $prettifiedstring;
    }
}
