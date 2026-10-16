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
 * Calculates the days a series rule describes, as a preview before the series is saved.
 *
 * The calculation uses the timezone of the browser, which is also the timezone the calendar shows
 * and the rule is saved with. What counts in the end is the result of the server after saving.
 *
 * @module     local_entities/unavailability/series_preview
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const MAX_DAYS = 400;

/**
 * Sets the time of a date from seconds since midnight. 86400 gives midnight of the next day.
 *
 * @param {Date} day midnight of a day
 * @param {number} seconds seconds since midnight
 * @returns {Date}
 */
const atSeconds = (day, seconds) => {
    const result = new Date(day);
    result.setHours(Math.floor(seconds / 3600), Math.floor((seconds % 3600) / 60), 0, 0);
    return result;
};

/**
 * Midnight of the next day. Built from the date parts, so it is right across a change of daylight
 * saving time.
 *
 * @param {Date} day midnight of a day
 * @returns {Date}
 */
const nextDay = (day) => new Date(day.getFullYear(), day.getMonth(), day.getDate() + 1);

/**
 * The time ranges a rule describes, one per matching day.
 *
 * @param {Object} rule weekdays (1 = Monday to 7 = Sunday), allday, daystart, dayend (seconds since
 *                      midnight), validfrom and validuntil (unix timestamps, any time on that day)
 * @param {number} notBefore days before the day of this unix timestamp are left out, 0 for all days
 * @returns {Array} list of {starttime, endtime} as unix timestamps, ordered by start
 */
export const occurrences = (rule, notBefore = 0) => {
    const first = new Date(Math.max(rule.validfrom, notBefore) * 1000);
    first.setHours(0, 0, 0, 0);
    const last = new Date(rule.validuntil * 1000);
    last.setHours(0, 0, 0, 0);

    const result = [];
    for (let day = new Date(first), i = 0; i < MAX_DAYS && day <= last; i++, day = nextDay(day)) {
        const weekday = day.getDay() === 0 ? 7 : day.getDay();
        if (rule.weekdays.includes(weekday)) {
            const start = rule.allday ? new Date(day) : atSeconds(day, rule.daystart);
            const end = rule.allday ? atSeconds(day, 24 * 3600) : atSeconds(day, rule.dayend);
            result.push({
                starttime: Math.floor(start.getTime() / 1000),
                endtime: Math.floor(end.getTime() / 1000),
            });
        }
    }
    return result;
};
