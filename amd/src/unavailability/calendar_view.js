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
 * The calendar of the unavailability page.
 *
 * It only shows the entries it is given and reports what the user does: a selected time range, a
 * clicked day and a clicked entry. What that means is decided by the module that uses it.
 *
 * @module     local_entities/unavailability/calendar_view
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {Calendar} from 'local_entities/fullcalendar';

/**
 * A date as a unix timestamp.
 *
 * @param {Date} date
 * @returns {number}
 */
const toSeconds = (date) => Math.floor(date.getTime() / 1000);

// Length of a time slot in the week view. A click on a free slot marks one slot.
const SLOT_MINUTES = 30;

export default class CalendarView {
    /**
     * Constructor.
     *
     * @param {HTMLElement} element where the calendar is drawn
     * @param {Object} options
     * @param {Function} options.provider (fromSeconds, untilSeconds) => Promise of entries
     * @param {Function} options.onSelect (startSeconds, endSeconds) a time range was dragged
     * @param {Function} options.onDayClick (startSeconds, endSeconds) a whole day was clicked
     * @param {Function} options.onEntryClick (extendedProps, element) an entry was clicked
     * @param {Object} options.labels texts: today, week, month, allday, more
     * @param {string} options.locale language code
     * @param {number} options.firstDay first day of the week, 0 = Sunday
     * @param {boolean} options.hour12 12 hour clock instead of 24 hour
     */
    constructor(element, options) {
        this.element = element;
        this.options = options;
        this.calendar = null;
    }

    /**
     * Draws the calendar.
     */
    render() {
        const {provider, onSelect, onDayClick, onEntryClick, labels, locale, firstDay, hour12} = this.options;
        const timeFormat = hour12
            ? {hour: 'numeric', minute: '2-digit', meridiem: 'short', hour12: true}
            : {hour: '2-digit', minute: '2-digit', hour12: false};

        this.calendar = new Calendar(this.element, {
            timeZone: 'local',
            locale,
            firstDay,
            initialView: 'timeGridWeek',
            headerToolbar: {left: 'prev,next today', center: 'title', right: 'timeGridWeek,dayGridMonth'},
            buttonText: {today: labels.today, week: labels.week, month: labels.month},
            allDayText: labels.allday,
            moreLinkText: labels.more,
            eventTimeFormat: timeFormat,
            slotLabelFormat: timeFormat,
            slotMinTime: '00:00:00',
            slotDuration: `00:${SLOT_MINUTES}:00`,
            scrollTime: '07:00:00',
            height: 700,
            nowIndicator: true,
            // The number of a day or of a week opens that week, so one gets from the month to a week.
            weekNumbers: true,
            weekNumberCalculation: 'ISO',
            weekText: labels.week,
            navLinks: true,
            navLinkDayClick: 'timeGridWeek',
            navLinkWeekClick: 'timeGridWeek',
            selectable: true,
            selectMirror: true,
            selectMinDistance: 5,
            unselectAuto: false,
            editable: false,
            eventOverlap: true,
            selectOverlap: true,
            selectAllow: (info) => {
                // Time that is over cannot be marked.
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                return info.end > today;
            },
            dayMaxEvents: true,
            events: (info, success, failure) => {
                provider(toSeconds(info.start), toSeconds(info.end)).then(success).catch(failure);
            },
            select: (info) => {
                onSelect(toSeconds(info.start), toSeconds(info.end));
                this.calendar.unselect();
            },
            dateClick: (info) => {
                // A click on a day, in the month or in the all-day row, marks the whole day. A click on a
                // time slot marks that slot, a longer time range is dragged.
                const start = new Date(info.date);
                const end = new Date(info.date);
                if (info.allDay) {
                    end.setDate(end.getDate() + 1);
                } else {
                    end.setTime(end.getTime() + SLOT_MINUTES * 60 * 1000);
                }
                onDayClick(toSeconds(start), toSeconds(end));
            },
            eventClick: (info) => {
                onEntryClick(info.event.extendedProps, info.el);
            },
        });
        this.calendar.render();
    }

    /**
     * Asks the provider for the entries again and redraws them.
     */
    refresh() {
        if (this.calendar) {
            this.calendar.refetchEvents();
        }
    }

    /**
     * Removes the calendar.
     */
    destroy() {
        if (this.calendar) {
            this.calendar.destroy();
            this.calendar = null;
        }
    }
}
