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
 * Calculations with time ranges for the unavailability page.
 *
 * @module     local_entities/unavailability/range_math
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Cuts off the part of a range that lies before a point in time.
 *
 * @param {Object} range starttime and endtime (unix timestamps)
 * @param {number} from unix timestamp
 * @returns {Object|null} the remaining range, null if nothing remains
 */
export const clipFrom = (range, from) => {
    const starttime = Math.max(range.starttime, from);
    return range.endtime > starttime ? {starttime, endtime: range.endtime} : null;
};

/**
 * The parts of a range that are not covered by other ranges.
 *
 * @param {Object} range starttime and endtime (unix timestamps)
 * @param {Array} covered ranges that are taken already, in any order and possibly overlapping
 * @returns {Array} the free parts, in order, without empty ones
 */
export const subtractRanges = (range, covered) => {
    const sorted = [...covered].sort((a, b) => a.starttime - b.starttime);
    const free = [];
    let position = range.starttime;
    for (const other of sorted) {
        if (other.endtime <= position) {
            continue;
        }
        if (other.starttime >= range.endtime) {
            break;
        }
        if (other.starttime > position) {
            free.push({starttime: position, endtime: other.starttime});
        }
        position = other.endtime;
    }
    if (position < range.endtime) {
        free.push({starttime: position, endtime: range.endtime});
    }
    return free;
};

const pad = (n) => String(n).padStart(2, '0');

/**
 * A unix timestamp as the value of a datetime-local input, in the browser timezone.
 *
 * @param {number} seconds
 * @returns {string} e.g. 2026-10-19T08:00
 */
export const toInputValue = (seconds) => {
    const d = new Date(seconds * 1000);
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
};

/**
 * The value of a datetime-local input as a unix timestamp, in the browser timezone.
 *
 * @param {string} value e.g. 2026-10-19T08:00
 * @returns {number|null} null if the value is empty or not a date
 */
export const fromInputValue = (value) => {
    const time = new Date(value).getTime();
    return value && !isNaN(time) ? Math.floor(time / 1000) : null;
};


/**
 * Is the range made of whole local days?
 *
 * @param {number} starttime unix timestamp
 * @param {number} endtime unix timestamp
 * @returns {boolean}
 */
export const isWholeDays = (starttime, endtime) => {
    const start = new Date(starttime * 1000);
    const end = new Date(endtime * 1000);
    return start.getHours() === 0 && start.getMinutes() === 0 && end.getHours() === 0 && end.getMinutes() === 0;
};

/**
 * A unix timestamp as the value of a date input, in the browser timezone.
 *
 * @param {number} seconds
 * @returns {string} e.g. 2026-10-19
 */
export const toDateValue = (seconds) => toInputValue(seconds).substring(0, 10);

/**
 * The day of the last moment of a range, which is the last day it covers. A range that ends at
 * midnight ends on the day before.
 *
 * @param {number} endtime unix timestamp, the end of the range
 * @returns {string} e.g. 2026-10-22
 */
export const toLastDayValue = (endtime) => toDateValue(endtime - 1);

/**
 * The start of a day, from the value of a date input, in the browser timezone.
 *
 * @param {string} value e.g. 2026-10-19
 * @returns {number|null} unix timestamp, null if the value is empty or not a date
 */
export const fromDateValue = (value) => /^\d{4}-\d{2}-\d{2}$/.test(value) ? fromInputValue(`${value}T00:00`) : null;

/**
 * The end of a range that lasts until the end of a day, from the value of a date input.
 *
 * @param {string} value e.g. 2026-10-22
 * @returns {number|null} the start of the next day, null if the value is empty or not a date
 */
export const fromLastDayValue = (value) => {
    const start = fromDateValue(value);
    if (start === null) {
        return null;
    }
    const next = new Date(start * 1000);
    next.setDate(next.getDate() + 1);
    return Math.floor(next.getTime() / 1000);
};
