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

namespace local_entities\unavailability;

/**
 * The rule of a series: on which weekdays, at which times and between which days a block repeats.
 *
 * The rule is checked and normalised when it is created from user input. It calculates the single
 * time ranges it describes, in its own timezone, so times stay the same across a change of daylight
 * saving time. It does not touch the database.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class series_rule {
    /** @var int Longest allowed series in days, so a wrong end date can not create thousands of blocks. */
    public const MAX_DAYS = 366;

    /**
     * Constructor, use the named constructors instead.
     *
     * @param int[] $weekdays 1 (Monday) to 7 (Sunday), unique and sorted
     * @param bool $allday whole days instead of a time of day
     * @param int $daystart seconds since midnight, 0 for all-day rules
     * @param int $dayend seconds since midnight, 0 for all-day rules
     * @param int $validfrom midnight of the first day in the timezone of the rule
     * @param int $validuntil midnight of the last day in the timezone of the rule
     * @param string $timezone
     * @param string $reason
     */
    private function __construct(
        public readonly array $weekdays,
        public readonly bool $allday,
        public readonly int $daystart,
        public readonly int $dayend,
        public readonly int $validfrom,
        public readonly int $validuntil,
        public readonly string $timezone,
        public readonly string $reason,
    ) {
    }

    /**
     * Checks user input and builds a normalised rule.
     *
     * @param array $rule weekdays, allday, daystart and dayend (seconds since midnight, only if not
     *                    allday), validfrom and validuntil (any time on the first and the last day),
     *                    timezone, reason
     * @return self
     * @throws \invalid_parameter_exception if the rule is not valid
     */
    public static function from_array(array $rule): self {
        $weekdays = array_values(array_unique(array_map('intval', (array)($rule['weekdays'] ?? []))));
        sort($weekdays);
        if (empty($weekdays) || min($weekdays) < 1 || max($weekdays) > 7) {
            throw new \invalid_parameter_exception('A series needs weekdays between 1 (Monday) and 7 (Sunday).');
        }

        $allday = !empty($rule['allday']);
        $daystart = (int)($rule['daystart'] ?? 0);
        $dayend = (int)($rule['dayend'] ?? 0);
        if (!$allday && ($daystart < 0 || $dayend > DAYSECS || $dayend <= $daystart)) {
            throw new \invalid_parameter_exception('The time of a series must end after it starts, on the same day.');
        }

        $timezone = \core_date::normalise_timezone($rule['timezone'] ?? '99');
        $tz = new \DateTimeZone($timezone);
        $first = (new \DateTimeImmutable('@' . (int)($rule['validfrom'] ?? 0)))->setTimezone($tz)->setTime(0, 0);
        $last = (new \DateTimeImmutable('@' . (int)($rule['validuntil'] ?? 0)))->setTimezone($tz)->setTime(0, 0);
        if ((int)($rule['validfrom'] ?? 0) <= 0 || $last < $first) {
            throw new \invalid_parameter_exception('A series needs a first day and a last day that is not before it.');
        }
        if ($first->diff($last)->days > self::MAX_DAYS) {
            throw new \invalid_parameter_exception('A series can not be longer than ' . self::MAX_DAYS . ' days.');
        }

        return new self(
            $weekdays,
            $allday,
            $allday ? 0 : $daystart,
            $allday ? 0 : $dayend,
            $first->getTimestamp(),
            $last->getTimestamp(),
            $timezone,
            \core_text::substr(trim((string)($rule['reason'] ?? '')), 0, 255)
        );
    }

    /**
     * Builds a rule from a stored record of the series table. The record is trusted, as it was
     * checked when it was stored.
     *
     * @param \stdClass $record
     * @return self
     */
    public static function from_record(\stdClass $record): self {
        return new self(
            array_map('intval', explode(',', (string)$record->weekdays)),
            !empty($record->allday),
            (int)($record->daystart ?? 0),
            (int)($record->dayend ?? 0),
            (int)$record->validfrom,
            (int)$record->validuntil,
            (string)$record->timezone,
            (string)($record->reason ?? '')
        );
    }

    /**
     * The fields of the rule as they are stored in the series table.
     *
     * @return array weekdays, allday, daystart, dayend, validfrom, validuntil, timezone, reason
     */
    public function to_record_fields(): array {
        return [
            'weekdays' => implode(',', $this->weekdays),
            'allday' => $this->allday ? 1 : 0,
            'daystart' => $this->allday ? null : $this->daystart,
            'dayend' => $this->allday ? null : $this->dayend,
            'validfrom' => $this->validfrom,
            'validuntil' => $this->validuntil,
            'timezone' => $this->timezone,
            'reason' => $this->reason,
        ];
    }

    /**
     * The time ranges the rule describes, one per matching day.
     *
     * @param int $notbefore days before the day of this timestamp are left out, 0 for all days
     * @param int $seriesid id of the series the ranges are given
     * @return time_range[] ordered by start time
     */
    public function occurrences(int $notbefore = 0, int $seriesid = 0): array {
        $tz = new \DateTimeZone($this->timezone);
        $day = (new \DateTimeImmutable('@' . max($this->validfrom, $notbefore)))->setTimezone($tz)->setTime(0, 0);
        $last = (new \DateTimeImmutable('@' . $this->validuntil))->setTimezone($tz)->setTime(0, 0);
        $weekdays = array_flip($this->weekdays);

        $ranges = [];
        while ($day <= $last) {
            if (isset($weekdays[(int)$day->format('N')])) {
                if ($this->allday) {
                    $start = $day;
                    $end = $day->modify('+1 day');
                } else {
                    $start = $day->setTime(intdiv($this->daystart, 3600), intdiv($this->daystart % 3600, 60));
                    $end = $day->setTime(intdiv($this->dayend, 3600), intdiv($this->dayend % 3600, 60));
                }
                $ranges[] = new time_range($start->getTimestamp(), $end->getTimestamp(), $this->reason, $seriesid);
            }
            $day = $day->modify('+1 day');
        }

        return $ranges;
    }
}
