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
 * Builds the entries of the calendar from what is saved and what the user changed since.
 *
 * Saved blocks are shown unless they are about to be removed, new blocks and new series are shown
 * as "not saved", and a changed or deleted series replaces or hides its saved blocks from today on,
 * as the past stays as it was.
 *
 * @module     local_entities/unavailability/event_builder
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {isWholeDays} from 'local_entities/unavailability/range_math';
import {occurrences} from 'local_entities/unavailability/series_preview';

const pad = (n) => String(n).padStart(2, '0');

/**
 * A unix timestamp as a local date and time string, which the calendar reads in the browser timezone.
 *
 * @param {number} seconds
 * @returns {string} e.g. 2026-10-19T08:00:00
 */
const toLocalDateTime = (seconds) => {
    const d = new Date(seconds * 1000);
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}:00`;
};

/**
 * A unix timestamp as a local date string.
 *
 * @param {number} seconds
 * @returns {string} e.g. 2026-10-19
 */
const toLocalDate = (seconds) => toLocalDateTime(seconds).substring(0, 10);

/**
 * One entry of the calendar.
 *
 * @param {string} id
 * @param {Object} range starttime and endtime
 * @param {string} title
 * @param {Array} classNames
 * @param {Object} props what the click handler needs: kind, id, seriesid
 * @returns {Object}
 */
const entry = (id, range, title, classNames, props) => {
    const allDay = isWholeDays(range.starttime, range.endtime);
    return {
        id,
        title,
        allDay,
        start: allDay ? toLocalDate(range.starttime) : toLocalDateTime(range.starttime),
        end: allDay ? toLocalDate(range.endtime) : toLocalDateTime(range.endtime),
        classNames,
        extendedProps: {...props, starttime: range.starttime, endtime: range.endtime},
    };
};

/**
 * Start of today in the browser timezone.
 *
 * @param {Date} now
 * @returns {number} unix timestamp
 */
const startOfDay = (now) => {
    const d = new Date(now);
    d.setHours(0, 0, 0, 0);
    return Math.floor(d.getTime() / 1000);
};

/**
 * The background of a whole-day entry, which fills the column of the day in the week view.
 *
 * An all-day entry only shows up in the all-day row. A background that is not marked as all-day
 * covers the whole column, so it is clear that the day is meant completely.
 *
 * @param {Object} event the whole-day entry
 * @returns {Object} entry for the calendar
 */
const fill = (event) => {
    const pending = event.classNames.includes('entities-unavail-pending');
    return {
        id: `${event.id}-fill`,
        start: toLocalDateTime(event.extendedProps.starttime),
        end: toLocalDateTime(event.extendedProps.endtime),
        allDay: false,
        display: 'background',
        classNames: [pending ? 'entities-unavail-fill-pending' : 'entities-unavail-fill'],
        extendedProps: {kind: 'background'},
    };
};

/**
 * The entry of an assignment of the entity: a booked slot or an appointment, read only.
 *
 * It is never a whole day, so it is shown with its times. It opens the page of what it belongs to, if there is a link.
 *
 * @param {Object} occupation starttime, endtime, name, url
 * @param {number} index position in the list, for the id
 * @returns {Object} entry for the calendar
 */
const occupationEntry = (occupation, index) => ({
    id: `occupation-${index}`,
    title: occupation.name,
    allDay: false,
    start: toLocalDateTime(occupation.starttime),
    end: toLocalDateTime(occupation.endtime),
    classNames: occupation.url ? ['entities-occupation'] : ['entities-occupation', 'entities-occupation-nolink'],
    extendedProps: {
        kind: 'occupation',
        url: occupation.url || '',
        starttime: occupation.starttime,
        endtime: occupation.endtime,
    },
});

/**
 * Builds the entries of the calendar.
 *
 * @param {Object} loaded what the server returned: blocks, series and occupations
 * @param {PendingChanges} pending what the user changed since
 * @param {Object} labels texts: absent, notsaved
 * @param {Date} now the current time
 * @returns {Array} entries for the calendar
 */
export const buildEvents = (loaded, pending, labels, now = new Date()) => {
    const today = startOfDay(now);
    const events = [];

    // Saved blocks, except the ones that are going away.
    for (const block of loaded.blocks) {
        const inFuture = block.endtime > today;
        const hiddenBySeries = block.seriesid && inFuture
            && (pending.isSeriesDeleted(block.seriesid) || pending.updatedSeries.has(block.seriesid));
        if (pending.isBlockRemoved(block.id) || hiddenBySeries) {
            continue;
        }
        const classNames = ['entities-unavail'];
        if (block.seriesid) {
            classNames.push('entities-unavail-series');
        }
        events.push(entry(`block-${block.id}`, block, labels.absent, classNames, {
            kind: 'block',
            id: block.id,
            seriesid: block.seriesid,
        }));
    }

    // Changed series show their new days from today on.
    for (const [seriesid, rule] of pending.updatedSeries) {
        occurrences(rule, today).forEach((range, i) => {
            events.push(entry(`useries-${seriesid}-${i}`, range, labels.notsaved, ['entities-unavail-pending'], {
                kind: 'series-preview',
                id: seriesid,
                seriesid,
            }));
        });
    }

    // New single blocks.
    for (const [tempid, block] of pending.addedBlocks) {
        events.push(entry(`pending-${tempid}`, block, labels.notsaved, ['entities-unavail-pending'], {
            kind: 'pending-block',
            id: tempid,
            seriesid: 0,
        }));
    }

    // New series.
    for (const [tempid, rule] of pending.createdSeries) {
        occurrences(rule).forEach((range, i) => {
            events.push(entry(`pseries-${tempid}-${i}`, range, labels.notsaved, ['entities-unavail-pending'], {
                kind: 'pending-series',
                id: tempid,
                seriesid: 0,
            }));
        });
    }

    // Whole days also fill their column, so it is clear that the day is meant completely.
    const filled = events.concat(events.filter((event) => event.allDay).map(fill));

    // Assignments (booked slots, appointments) are shown as they are. They cannot be changed here.
    return filled.concat((loaded.occupations || []).map((occupation, i) => occupationEntry(occupation, i)));
};
