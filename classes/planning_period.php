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

namespace local_entities;

use local_entities\unavailability\time_range;

/**
 * The planning period: the days in which unavailability is entered and exams are planned.
 *
 * It is set once for the whole site, as a first and a last day. The days are read in the time zone of the
 * server. Without both days, or with the last day before the first, there is no planning period and
 * nothing is limited. Planners and admins are never limited.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class planning_period {
    /** @var string Setting of the first day, as 2026-12-01. */
    public const CONFIG_START = 'planningperiod_start';

    /** @var string Setting of the last day, as 2026-12-31. */
    public const CONFIG_END = 'planningperiod_end';

    /**
     * The planning period of the site.
     *
     * @return time_range|null from the start of the first day until the end of the last day, null if none is set
     */
    public static function get(): ?time_range {
        $first = self::parse_day((string)get_config('local_entities', self::CONFIG_START));
        $last = self::parse_day((string)get_config('local_entities', self::CONFIG_END));
        if ($first === null || $last === null || $last < $first) {
            return null;
        }
        return new time_range($first->getTimestamp(), $last->modify('+1 day')->getTimestamp());
    }

    /**
     * The planning period that limits a user. Planners and admins are not limited.
     *
     * @param int|null $userid null for the current user
     * @return time_range|null null if the user is not limited
     */
    public static function for_user(?int $userid = null): ?time_range {
        return unavailability_access::can_manage_any($userid) ? null : self::get();
    }

    /**
     * Does the period hold the whole time range?
     *
     * @param time_range $period
     * @param int $starttime
     * @param int $endtime
     * @return bool
     */
    public static function contains(time_range $period, int $starttime, int $endtime): bool {
        return $starttime >= $period->starttime && $endtime <= $period->endtime;
    }

    /**
     * The period as text for messages, for example "01.12.2026 – 31.12.2026".
     *
     * @param time_range $period
     * @return string
     */
    public static function describe(time_range $period): string {
        $format = get_string('strftimedatefullshort', 'langconfig');
        $timezone = \core_date::get_server_timezone();
        return userdate($period->starttime, $format, $timezone) . ' – ' . userdate($period->endtime - 1, $format, $timezone);
    }

    /**
     * Reads a day as 2026-12-01 at midnight in the time zone of the server.
     *
     * @param string $value
     * @return \DateTimeImmutable|null null if the value is empty or not a real day
     */
    public static function parse_day(string $value): ?\DateTimeImmutable {
        $value = trim($value);
        $day = \DateTimeImmutable::createFromFormat('!Y-m-d', $value, \core_date::get_server_timezone_object());
        if ($day === false || $day->format('Y-m-d') !== $value) {
            return null;
        }
        return $day;
    }
}
