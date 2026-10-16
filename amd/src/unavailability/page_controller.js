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
 * Connects the parts of the unavailability page: calendar, remembered changes, saving and messages.
 *
 * @module     local_entities/unavailability/page_controller
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Notification from 'core/notification';
import * as Api from 'local_entities/unavailability/api';
import CalendarView from 'local_entities/unavailability/calendar_view';
import {planMark} from 'local_entities/unavailability/mark_planner';
import MessageArea from 'local_entities/unavailability/message_area';
import PendingChanges from 'local_entities/unavailability/pending_changes';
import SeriesEditor from 'local_entities/unavailability/series_editor';
import {buildEvents} from 'local_entities/unavailability/event_builder';
import {clipFrom} from 'local_entities/unavailability/range_math';
import {openEntryDialog} from 'local_entities/unavailability/entry_dialog';

const DAY = 86400;

// What is loaded around the visible days, so moving a little does not ask the server again.
const BUFFER = 7 * DAY;

export class PageController {
    /**
     * Constructor.
     *
     * @param {HTMLElement} root the element of the page
     * @param {Object} config entityid, locale, firstDay, hour12
     * @param {Object} labels texts: today, week, month, allday, absent, notsaved, saved, conflicts
     */
    constructor(root, config, labels) {
        this.root = root;
        this.config = config;
        this.labels = labels;
        this.pending = new PendingChanges();
        this.loaded = null;
        this.busy = false;
        this.entries = [];

        this.saveButton = root.querySelector('[data-action="unavailability-save"]');
        this.discardButton = root.querySelector('[data-action="unavailability-discard"]');
        this.addSeriesButton = root.querySelector('[data-action="unavailability-addseries"]');
        this.dirtyLabel = root.querySelector('[data-region="unavailability-dirty"]');
        this.messages = new MessageArea(root.querySelector('[data-region="unavailability-message"]'), config.locale);
        this.series = new SeriesEditor({
            pending: this.pending,
            labels,
            locale: config.locale,
            startOfToday: () => this.startOfToday(),
            findSeries: (id) => this.findSeries(id),
            onChange: () => this.afterChange(),
        });
        this.view = new CalendarView(root.querySelector('[data-region="unavailability-calendar"]'), {
            provider: (from, until) => this.provideEvents(from, until),
            onSelect: (start, end) => this.addRange(start, end),
            onDayClick: (start, end) => this.addRange(start, end),
            onEntryClick: (props) => this.clickEntry(props),
            labels,
            locale: config.locale,
            firstDay: config.firstDay,
            hour12: config.hour12,
        });
    }

    /**
     * Starts the page.
     */
    start() {
        this.saveButton.addEventListener('click', () => this.save());
        this.discardButton.addEventListener('click', () => this.discard());
        this.addSeriesButton.addEventListener('click', () => this.series.create());
        window.addEventListener('beforeunload', (event) => this.warnAboutUnsavedChanges(event));
        this.view.render();
        this.updateToolbar();
    }

    /**
     * Gives the calendar its entries for the days it shows.
     *
     * @param {number} from unix timestamp
     * @param {number} until unix timestamp
     * @returns {Promise} resolves to the entries
     */
    provideEvents(from, until) {
        return this.load(from, until).then((data) => {
            this.entries = buildEvents(data, this.pending, this.labels);
            return this.entries;
        });
    }

    /**
     * Loads what is saved for the days, unless it is loaded already.
     *
     * @param {number} from unix timestamp
     * @param {number} until unix timestamp
     * @returns {Promise} resolves to {blocks, series}
     */
    load(from, until) {
        if (this.loaded && from >= this.loaded.from && until <= this.loaded.until) {
            return Promise.resolve(this.loaded.data);
        }
        const loadFrom = from - BUFFER;
        const loadUntil = until + BUFFER;
        return Api.load(this.config.entityid, loadFrom, loadUntil).then((data) => {
            this.loaded = {from: loadFrom, until: loadUntil, data};
            return data;
        });
    }

    /**
     * A saved series that is loaded.
     *
     * @param {number} id
     * @returns {Object|undefined} the series, undefined if it is not among the loaded ones
     */
    findSeries(id) {
        return this.loaded ? this.loaded.data.series.find((series) => series.id === Number(id)) : undefined;
    }

    /**
     * The time ranges that are marked already, as shown in the calendar.
     *
     * The background of a whole day has no times of its own and does not count.
     *
     * @returns {Array} objects with starttime and endtime
     */
    takenRanges() {
        return this.entries
            .map((entry) => entry.extendedProps)
            .filter((props) => props.kind !== 'background');
    }

    /**
     * A time range or a day was marked as unavailable.
     *
     * The range is joined with the single blocks it overlaps or touches, so there is one block and not
     * several pieces. The days of a series stay as they are, the range only fills what is free.
     *
     * @param {number} start unix timestamp
     * @param {number} end unix timestamp
     * @param {Object|null} replaced the block that is changed by this, it does not count as taken
     */
    addRange(start, end, replaced = null) {
        const today = this.startOfToday();
        const range = clipFrom({starttime: start, endtime: end}, today);
        if (range) {
            const entries = this.takenRanges()
                .filter((other) => !(replaced && other.kind === replaced.kind && other.id === replaced.id));
            // What began before today and the days of a series cannot be joined.
            const joinable = (props) => props.kind === 'pending-block'
                || (props.kind === 'block' && !props.seriesid && props.starttime >= today);
            const plan = planMark(range, entries.filter(joinable), entries.filter((props) => !joinable(props)));
            plan.remove.forEach((block) => this.pending.removeBlock(block.id));
            plan.add.forEach((part) => this.pending.addBlock(part.starttime, part.endtime));
        }
        this.afterChange();
    }

    /**
     * The times of a block were changed: the block is replaced by the new time range. It may be saved
     * or not yet.
     *
     * The old block does not count as taken, so the new range may overlap it.
     *
     * @param {Object} props kind and id of the block, and its times
     * @param {number} start unix timestamp
     * @param {number} end unix timestamp
     */
    changeBlock(props, start, end) {
        this.pending.removeBlock(props.id);
        this.addRange(start, end, props);
    }

    /**
     * Start of today in the browser timezone. Earlier time cannot be changed.
     *
     * @returns {number} unix timestamp
     */
    startOfToday() {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        return Math.floor(today.getTime() / 1000);
    }

    /**
     * An entry was clicked. A day of a series, saved or not, opens the dialogs of the series. A block
     * opens the dialog to change or delete it.
     *
     * @param {Object} props kind and id of the entry
     */
    clickEntry(props) {
        const today = this.startOfToday();
        const isSeries = props.kind === 'pending-series' || props.kind === 'series-preview'
            || (props.kind === 'block' && props.seriesid);
        // What began before today stays as it was.
        const isOver = props.kind === 'block' && props.starttime < today;
        if (isOver) {
            return;
        }
        if (isSeries) {
            this.series.open(props);
        } else if (props.kind === 'pending-block' || props.kind === 'block') {
            openEntryDialog({
                labels: this.labels,
                entry: props,
                editable: true,
                minStart: today,
                onApply: (start, end) => this.changeBlock(props, start, end),
                onDelete: () => {
                    this.pending.removeBlock(props.id);
                    this.afterChange();
                },
            });
        }
    }

    /**
     * Saves everything that was changed.
     */
    async save() {
        this.busy = true;
        this.updateToolbar();
        try {
            const result = await Api.save(this.config.entityid, this.pending.toPayload());
            if (result.saved) {
                this.pending.reset();
                this.loaded = null;
                this.messages.showSuccess(this.labels.saved);
            } else {
                this.messages.showConflicts(this.labels.conflicts, result.conflicts);
            }
        } catch (error) {
            Notification.exception(error);
        } finally {
            this.busy = false;
            this.view.refresh();
            this.updateToolbar();
        }
    }

    /**
     * Forgets everything that was changed and is not saved.
     */
    discard() {
        this.pending.reset();
        this.afterChange();
    }

    /**
     * Redraws the calendar and the buttons after something changed.
     */
    afterChange() {
        this.messages.clear();
        this.view.refresh();
        this.updateToolbar();
    }

    /**
     * Switches the buttons and the hint according to the state.
     */
    updateToolbar() {
        const dirty = this.pending.isDirty();
        this.saveButton.disabled = !dirty || this.busy;
        this.discardButton.disabled = !dirty || this.busy;
        this.dirtyLabel.classList.toggle('d-none', !dirty);
    }

    /**
     * Asks the browser to warn before the page is left with unsaved changes.
     *
     * @param {Event} event
     */
    warnAboutUnsavedChanges(event) {
        if (this.pending.isDirty()) {
            event.preventDefault();
            event.returnValue = '';
        }
    }
}
