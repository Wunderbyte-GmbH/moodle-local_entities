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
 * local entities reoccuringevent
 *
 * @package local_entities
 * @author Thomas Winkler
 * @copyright 2022 Wunderbyte GmbH
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_entities\calendar;

use stdClass;
use DateTime;
use DateTimeZone;
use local_entities\local\entities\entitydate;

/**
 * Class reoccuringevent for fullcalendar.js
 *
 * @author Thomas Winkler
 * @copyright 2022 Wunderbyte GmbH
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reoccuringevent {
    /**
     * @var string
     */
    public $title;
    /**
     * @var string
     */
    public $daysofweek;
    /**
     * @var int
     */
    public $starttime;
    /**
     * @var int
     */
    public $endtime;
    /**
     * @var array
     */
    public $properties = [];

    /**
     * reoccuringevent constructor.
     *
     * @param array $eventarray
     */
    public function __construct(array $eventarray) {
        $this->title = $eventarray['title'];
        $this->daysofweek = $eventarray['daysofweek'];
        if (isset($eventarray['starttime'])) {
            $this->starttime = $eventarray['starttime'];
        } else {
            $this->starttime = $eventarray['starthours'] . ':' . $eventarray['startminutes'];
        }
        if (isset($eventarray['endtime'])) {
            $this->endtime = $eventarray['endtime'];
        } else {
            $this->endtime = $eventarray['endhours'] . ':' . $eventarray['endminutes'];
        }
        // Record misc properties.
        foreach ($eventarray as $name => $value) {
            if (!in_array($name, ['title', 'daysofweek', 'starthours', 'startminutes', 'endhours', 'endminutes'])) {
                $this->properties[$name] = $value;
            }
        }
    }

    /**
     * Converts this entities event into an array
     *
     * @return array
     */
    public function toarray(): array {
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
    public static function events_to_json(array $events): string {
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
    public static function json_to_form(string $eventsjson): stdClass {
        if (!$events = json_decode($eventsjson)) {
            return (object)[];
        }
        $i = 0;
        $formevent = new stdClass();
        foreach ($events as $event) {
            $formevent->daysofweek[$i] = explode(',', $event->daysOfWeek);
            $start = explode(':', $event->startTime);
            $formevent->starthours[$i] = $start[0];
            $formevent->startminutes[$i] = $start[1];
            $end = explode(':', $event->endTime);
            $formevent->endhours[$i] = $end[0];
            $formevent->endminutes[$i] = $end[1];
            $i++;
        }
        $formevent->count = $i;
        return $formevent;
    }

    /**
     * Converts JSON from DB to reoccuring event class array
     *
     * @param string $eventsjson
     * @return array
     */
    public static function json_to_events(string $eventsjson): array {
        $events = json_decode($eventsjson);
        $reoccuringevents = [];
        foreach ($events as $event) {
            $reoccuringevent['title'] = $event->title;
            $reoccuringevent['daysofweek'] = $event->daysOfWeek;
            $reoccuringevent['starttime'] = $event->startTime;
            $reoccuringevent['endtime'] = $event->endTime;
            $reoccuringevents[] = new self($reoccuringevent);
        }
        return $reoccuringevents;
    }

    /**
     * Checks if an event is within openinghours
     * @param array $reoccuringevents
     * @param entitydate $eventtobook
     * @return boolean
     */
    public static function date_within_openinghours(array $reoccuringevents, entitydate $eventtobook): bool {
        if (empty($eventtobook->starttime) || empty($eventtobook->endtime)) {
            return false;
        }
        $startweekday = date('N', $eventtobook->starttime);
        $endweekday = date('N', $eventtobook->endtime);
        // TODO: Maybe allow overlapping?
        if ($startweekday != $endweekday) {
            return false;
        }

        $eventswithweekday = self::has_weekday($reoccuringevents, $startweekday);
        $inopeninghours = false;
        foreach ($eventswithweekday as $event) {
            $startopeninghourstime = (int)str_replace(':', '', $event->starttime);
            $dtstarteventtobooktime = new DateTime();
            $dtstarteventtobooktime->setTimezone(new DateTimeZone('Europe/Vienna'));
            $dtstarteventtobooktime->setTimestamp($eventtobook->starttime);
            $starteventtobooktime = (int)$dtstarteventtobooktime->format('Hi');
            $endopeninghourstime = (int)str_replace(':', '', $event->endtime);
            $dtendeventtobooktime = new DateTime();
            $dtendeventtobooktime->setTimezone(new DateTimeZone('Europe/Vienna'));
            $dtendeventtobooktime->setTimestamp($eventtobook->endtime);
            $endeventtobooktime = (int)$dtendeventtobooktime->format('Hi');
            if ($starteventtobooktime >= $startopeninghourstime && $endeventtobooktime <= $endopeninghourstime) {
                $inopeninghours = true;
            }
        }
        return $inopeninghours;
    }

    /**
     * Gives back events with the given weekday
     *
     * @param array $reoccuringevents array of events
     * @param int $weekday
     * @return array
     */
    private static function has_weekday(array $reoccuringevents, int $weekday): array {
        $eventswithweekday = [];
        foreach ($reoccuringevents as $reoccuringevent) {
            if (strstr($reoccuringevent->daysofweek, (string)$weekday)) {
                $eventswithweekday[] = $reoccuringevent;
            }
        }
        return $eventswithweekday;
    }
}
