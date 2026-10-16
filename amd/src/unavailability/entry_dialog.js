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
 * The dialog to change or delete an unavailability.
 *
 * It only asks the user and reports the answer. What a change means is decided by the caller.
 *
 * @module     local_entities/unavailability/entry_dialog
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalEvents from 'core/modal_events';
import ModalSaveCancel from 'core/modal_save_cancel';
import {deleteCancel} from 'core/notification';
import {
    fromDateValue,
    fromInputValue,
    fromLastDayValue,
    isWholeDays,
    toDateValue,
    toInputValue,
    toLastDayValue,
} from 'local_entities/unavailability/range_math';
import {escapeHtml} from 'local_entities/unavailability/html';

/**
 * The values of the two fields for a range. Whole days are shown as dates, where the last day is the
 * last day that is still covered. Everything else is shown as date and time.
 *
 * @param {boolean} allDay
 * @param {Object} range starttime and endtime
 * @returns {Object} start and end, as the fields expect them
 */
const toFieldValues = (allDay, range) => allDay
    ? {start: toDateValue(range.starttime), end: toLastDayValue(range.endtime)}
    : {start: toInputValue(range.starttime), end: toInputValue(range.endtime)};

/**
 * The range for the values of the two fields.
 *
 * @param {boolean} allDay
 * @param {string} start value of the first field
 * @param {string} end value of the second field
 * @returns {Object} starttime and endtime, null where the value is not valid
 */
const fromFieldValues = (allDay, start, end) => allDay
    ? {starttime: fromDateValue(start), endtime: fromLastDayValue(end)}
    : {starttime: fromInputValue(start), endtime: fromInputValue(end)};

/**
 * The content of the dialog.
 *
 * @param {Object} labels texts: allday, from, until, delete
 * @param {Object} entry starttime and endtime
 * @param {boolean} allDay
 * @param {boolean} editable false if only deleting is offered
 * @returns {string} html
 */
const buildBody = (labels, entry, allDay, editable) => {
    const disabled = editable ? '' : ' disabled';
    const values = toFieldValues(allDay, entry);
    const type = allDay ? 'date' : 'datetime-local';
    return `
        <div class="alert alert-danger d-none" role="alert" data-region="entry-dialog-error"></div>
        <div class="form-check mb-3">
            <input type="checkbox" class="form-check-input" id="entities-entry-allday"
                ${allDay ? 'checked' : ''}${disabled}>
            <label class="form-check-label" for="entities-entry-allday">${escapeHtml(labels.allday)}</label>
        </div>
        <div class="form-group">
            <label for="entities-entry-start">${escapeHtml(labels.from)}</label>
            <input type="${type}" class="form-control" id="entities-entry-start"
                value="${values.start}"${disabled}>
        </div>
        <div class="form-group">
            <label for="entities-entry-end">${escapeHtml(labels.until)}</label>
            <input type="${type}" class="form-control" id="entities-entry-end"
                value="${values.end}"${disabled}>
        </div>
        <button type="button" class="btn btn-outline-danger" data-action="entry-dialog-delete">
            ${escapeHtml(labels.delete)}
        </button>`;
};

/**
 * Opens the dialog for an unavailability that can be changed or deleted.
 *
 * @param {Object} options
 * @param {Object} options.labels texts: edit, apply, allday, from, until, delete, deleteconfirm, invalidrange
 * @param {Object} options.entry starttime and endtime (unix timestamps)
 * @param {boolean} options.editable false if the times cannot be changed, only deleted
 * @param {number} options.minStart earliest start that can be chosen (unix timestamp)
 * @param {Function} options.onApply (starttime, endtime) the times were changed
 * @param {Function} options.onDelete the unavailability was deleted and the user confirmed it
 * @returns {Promise}
 */
export const openEntryDialog = async({labels, entry, editable, minStart, onApply, onDelete}) => {
    const modal = await ModalSaveCancel.create({
        title: labels.edit,
        body: buildBody(labels, entry, isWholeDays(entry.starttime, entry.endtime), editable),
        removeOnClose: true,
        show: true,
    });
    modal.setSaveButtonText(labels.apply);

    const root = modal.getRoot()[0];
    const error = root.querySelector('[data-region="entry-dialog-error"]');
    const allDayBox = root.querySelector('#entities-entry-allday');
    const startField = root.querySelector('#entities-entry-start');
    const endField = root.querySelector('#entities-entry-end');

    if (!editable) {
        root.querySelector('[data-action="save"]').classList.add('d-none');
    }

    // Switching between days and times keeps the range that is entered.
    allDayBox.addEventListener('change', () => {
        const allDay = allDayBox.checked;
        const range = fromFieldValues(!allDay, startField.value, endField.value);
        const shown = range.starttime === null || range.endtime === null ? entry : range;
        startField.type = allDay ? 'date' : 'datetime-local';
        endField.type = startField.type;
        const values = toFieldValues(allDay, shown);
        startField.value = values.start;
        endField.value = values.end;
    });

    modal.getRoot().on(ModalEvents.save, (event) => {
        const range = fromFieldValues(allDayBox.checked, startField.value, endField.value);
        if (range.starttime === null || range.endtime === null
            || range.endtime <= range.starttime || range.starttime < minStart) {
            event.preventDefault();
            error.textContent = labels.invalidrange;
            error.classList.remove('d-none');
            return;
        }
        onApply(range.starttime, range.endtime);
    });

    root.querySelector('[data-action="entry-dialog-delete"]').addEventListener('click', () => {
        modal.hide();
        deleteCancel(labels.delete, labels.deleteconfirm, labels.delete, onDelete);
    });
};
