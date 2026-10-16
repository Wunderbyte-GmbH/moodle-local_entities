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
 * The form of a recurring unavailability: weekdays, times and the period it applies to.
 *
 * It converts between the values of the form fields and the rule the server saves, and checks a rule.
 * Days and times are those of the browser, which is also the timezone the rule is saved with.
 *
 * @module     local_entities/unavailability/series_form
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {fromDateValue, toDateValue} from 'local_entities/unavailability/range_math';

const DAY = 86400;

// A series can last this long, which is the limit of the server.
const MAX_DAYS = 366;

const pad = (n) => String(n).padStart(2, '0');

/**
 * Seconds since midnight as the value of a time input. The input cannot show 24:00, so the end of the
 * day is shown as 23:59.
 *
 * @param {number} seconds
 * @returns {string} e.g. 08:30
 */
export const toTimeValue = (seconds) => {
    const shown = Math.min(Math.max(seconds, 0), DAY - 60);
    return `${pad(Math.floor(shown / 3600))}:${pad(Math.floor((shown % 3600) / 60))}`;
};

/**
 * The value of a time input as seconds since midnight.
 *
 * @param {string} value e.g. 08:30
 * @returns {number|null} null if the value is empty or not a time
 */
export const fromTimeValue = (value) => {
    const match = /^(\d{2}):(\d{2})$/.exec(value);
    if (!match || Number(match[1]) > 23 || Number(match[2]) > 59) {
        return null;
    }
    return Number(match[1]) * 3600 + Number(match[2]) * 60;
};

/**
 * The short names of the weekdays in a language, Monday first.
 *
 * @param {string} locale language code
 * @returns {string[]} seven names
 */
export const weekdayNames = (locale) => {
    const format = new Intl.DateTimeFormat(locale, {weekday: 'short'});
    // The 1st of January 2024 was a Monday.
    return [1, 2, 3, 4, 5, 6, 7].map((day) => format.format(new Date(2024, 0, day)));
};

// A new series lasts about three months, unless the user chooses otherwise.
const DEFAULT_DAYS = 90;

/**
 * The rule that a new series starts with: whole days, from today for about three months.
 *
 * @param {number} today start of today (unix timestamp)
 * @param {string} timezone
 * @returns {Object} rule, without weekdays
 */
export const defaultRule = (today, timezone) => {
    const last = new Date(today * 1000);
    last.setDate(last.getDate() + DEFAULT_DAYS);
    return {
        weekdays: [],
        allday: true,
        daystart: 8 * 3600,
        dayend: 16 * 3600,
        validfrom: today,
        validuntil: Math.floor(last.getTime() / 1000),
        timezone,
        reason: '',
    };
};

/**
 * Builds a rule from the values of the form.
 *
 * @param {Object} form weekdays, allday, start, end (time values), from, until (date values)
 * @param {string} timezone
 * @returns {Object} rule, values that are not valid are null
 */
export const ruleFromForm = (form, timezone) => ({
    weekdays: [...form.weekdays].sort((a, b) => a - b),
    allday: form.allday,
    daystart: form.allday ? 0 : fromTimeValue(form.start),
    dayend: form.allday ? 0 : fromTimeValue(form.end),
    validfrom: fromDateValue(form.from),
    validuntil: fromDateValue(form.until),
    timezone,
    reason: '',
});

/**
 * The values of the form for a rule.
 *
 * @param {Object} rule
 * @returns {Object} weekdays, allday, start, end (time values), from, until (date values)
 */
export const formFromRule = (rule) => ({
    weekdays: [...rule.weekdays],
    allday: rule.allday,
    start: toTimeValue(rule.allday ? 8 * 3600 : rule.daystart),
    end: toTimeValue(rule.allday ? 16 * 3600 : rule.dayend),
    from: toDateValue(rule.validfrom),
    until: toDateValue(rule.validuntil),
});

/**
 * Checks a rule.
 *
 * @param {Object} rule as built by ruleFromForm
 * @param {number} minFrom earliest first day, normally the start of today
 * @param {number|null} originalFrom the first day the series had before, it may stay even if it is over
 * @returns {string|null} 'noweekday', 'badtime', 'badperiod', or null if the rule is fine
 */
export const checkRule = (rule, minFrom, originalFrom = null) => {
    if (rule.weekdays.length === 0) {
        return 'noweekday';
    }
    if (!rule.allday && (rule.daystart === null || rule.dayend === null || rule.dayend <= rule.daystart)) {
        return 'badtime';
    }
    if (rule.validfrom === null || rule.validuntil === null || rule.validuntil < rule.validfrom) {
        return 'badperiod';
    }
    if (rule.validfrom < minFrom && rule.validfrom !== originalFrom) {
        return 'badperiod';
    }
    if (Math.round((rule.validuntil - rule.validfrom) / DAY) > MAX_DAYS) {
        return 'badperiod';
    }
    return null;
};
