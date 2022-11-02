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

use stdClass;

/**
 * Class event
 *
 * @author Thomas Winkler
 * @copyright 2022 Wunderbyte GmbH
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reoccuringevent {

    public $title;
    public $daysofweek;
    public $starttime;
    public $endtime;
    public $properties = array();

    /**
     * event constructor.
     */
    public function __construct(array $eventarray) {
        $this->title = $eventarray['title'];
        $this->daysofweek = $eventarray['daysofweek'];
        $this->starttime = $eventarray['starthours']. ':' .$eventarray['startminutes'];
        $this->endtime = $eventarray['endhours']. ':' .$eventarray['endminutes'];
        // Record misc properties.
        foreach ($eventarray as $name => $value) {
            if (!in_array($name, array('title', 'daysofweek', 'starthours', 'startminutes', 'endhours', 'endminutes'))) {
                $this->properties[$name] = $value;
            }
        }
    }

    /**
     * Converts this entities event into an array
     *
     * @return array
     */
    public function toarray() :array {
        $eventarray = $this->properties;
        $eventarray['title'] = $this->title;
        $eventarray['daysOfWeek'] = $this->daysofweek;
        $eventarray['startTime'] = $this->starttime;
        $eventarray['endTime'] = $this->endtime;
        return $eventarray;
    }

    /**
     * Returns json of events for fullcalendar.js
     *
     * @param array $events
     * @return string
     */
    public static function events_to_json(array $events) :string {
        $allevents = [];
        foreach ($events as $event) {
            $allevents[] = $event->toarray();
        }
        return json_encode($allevents);
    }

    /**
     * Returns data for dynamic form
     *
     * @param string $eventsjson
     * @return stdClass
     */
    public static function json_to_form(string $eventsjson) :stdClass {
        $events = json_decode($eventsjson);
        $i = 0;
        $formevent = new stdClass();
        foreach ($events as $event) {
            $formevent->daysofweek[$i] = explode(',', $event->daysOfWeek);
            $start = explode(':' , $event->startTime);
            $formevent->starthours[$i] = $start[0];
            $formevent->startminutes[$i] = $start[1];
            $end = explode(':' , $event->endTime);
            $formevent->endhours[$i] = $end[0];
            $formevent->endminutes[$i] = $end[1];
            $i++;
        }
        $formevent->count = $i + 1;
        return $formevent;
    }
}
