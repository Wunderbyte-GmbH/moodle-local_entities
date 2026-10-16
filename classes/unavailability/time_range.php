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
 * A time range of an unavailability, with its reason and the series it belongs to.
 *
 * It is immutable. Ranges of the same reason and the same series can be merged, ranges of different
 * reasons or series never are.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class time_range {
    /**
     * Constructor.
     *
     * @param int $starttime
     * @param int $endtime
     * @param string $reason
     * @param int $seriesid 0 for a single block that belongs to no series
     */
    public function __construct(
        /** @var int Start (unix timestamp). */
        public readonly int $starttime,
        /** @var int End (unix timestamp). */
        public readonly int $endtime,
        /** @var string Reason, may be empty. */
        public readonly string $reason = '',
        /** @var int Id of the series, 0 for none. */
        public readonly int $seriesid = 0,
    ) {
    }

    /**
     * Does the range have a length?
     *
     * @return bool
     */
    public function has_length(): bool {
        return $this->endtime > $this->starttime;
    }

    /**
     * Key of the group of ranges that may be merged: same reason and same series.
     *
     * @return string
     */
    public function group_key(): string {
        return json_encode([$this->reason, $this->seriesid]);
    }

    /**
     * Returns the same range with another end.
     *
     * @param int $endtime
     * @return self
     */
    public function with_end(int $endtime): self {
        return new self($this->starttime, $endtime, $this->reason, $this->seriesid);
    }

    /**
     * Key that is the same for two ranges only if start, end, reason and series are all the same.
     *
     * @return string
     */
    public function identity(): string {
        return json_encode([$this->starttime, $this->endtime, $this->reason, $this->seriesid]);
    }
}
