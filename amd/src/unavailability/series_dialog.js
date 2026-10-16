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
 * The dialog to create or change a recurring unavailability.
 *
 * It only asks the user and reports the rule. What happens with it is decided by the caller.
 *
 * @module     local_entities/unavailability/series_dialog
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalEvents from 'core/modal_events';
import ModalSaveCancel from 'core/modal_save_cancel';
import {deleteCancel} from 'core/notification';
import {escapeHtml} from 'local_entities/unavailability/html';
import {checkRule, formFromRule, ruleFromForm, weekdayNames} from 'local_entities/unavailability/series_form';

/**
 * The checkboxes of the weekdays.
 *
 * @param {string[]} names seven names, Monday first
 * @param {number[]} selected weekdays that are chosen, 1 = Monday
 * @returns {string} html
 */
const weekdayBoxes = (names, selected) => names.map((name, index) => {
    const day = index + 1;
    return `
        <div class="form-check form-check-inline">
            <input type="checkbox" class="form-check-input" id="entities-series-day-${day}" value="${day}"
                ${selected.includes(day) ? 'checked' : ''}>
            <label class="form-check-label" for="entities-series-day-${day}">${escapeHtml(name)}</label>
        </div>`;
}).join('');

/**
 * The content of the dialog.
 *
 * @param {Object} labels texts of the dialog
 * @param {string} locale language code
 * @param {Object} form values of the form, see formFromRule
 * @param {boolean} deletable is a button to delete the series offered?
 * @returns {string} html
 */
const buildBody = (labels, locale, form, deletable) => `
    <div class="alert alert-danger d-none" role="alert" data-region="series-dialog-error"></div>
    <div class="form-group">
        <div class="mb-1">${escapeHtml(labels.seriesWeekdays)}</div>
        ${weekdayBoxes(weekdayNames(locale), form.weekdays)}
    </div>
    <div class="form-check mb-3">
        <input type="checkbox" class="form-check-input" id="entities-series-allday" ${form.allday ? 'checked' : ''}>
        <label class="form-check-label" for="entities-series-allday">${escapeHtml(labels.allday)}</label>
    </div>
    <div class="form-row ${form.allday ? 'd-none' : ''}" data-region="series-dialog-times">
        <div class="form-group col-6">
            <label for="entities-series-start">${escapeHtml(labels.seriesStart)}</label>
            <input type="time" class="form-control" id="entities-series-start" value="${form.start}">
        </div>
        <div class="form-group col-6">
            <label for="entities-series-end">${escapeHtml(labels.seriesEnd)}</label>
            <input type="time" class="form-control" id="entities-series-end" value="${form.end}">
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-6">
            <label for="entities-series-from">${escapeHtml(labels.seriesFirstDay)}</label>
            <input type="date" class="form-control" id="entities-series-from" value="${form.from}">
        </div>
        <div class="form-group col-6">
            <label for="entities-series-until">${escapeHtml(labels.seriesLastDay)}</label>
            <input type="date" class="form-control" id="entities-series-until" value="${form.until}">
        </div>
    </div>
    ${deletable ? `
    <button type="button" class="btn btn-outline-danger" data-action="series-dialog-delete">
        ${escapeHtml(labels.seriesDelete)}
    </button>` : ''}`;

/**
 * Opens the dialog for a recurring unavailability.
 *
 * @param {Object} options
 * @param {Object} options.labels texts of the dialog
 * @param {string} options.locale language code
 * @param {Object} options.rule the rule to start with
 * @param {string} options.timezone timezone the rule is saved with
 * @param {number} options.minFrom earliest first day that can be chosen (unix timestamp)
 * @param {number|null} options.originalFrom first day of the series before, it may stay as it is
 * @param {Function} options.onApply (rule) the rule was entered
 * @param {Function|null} options.onDelete the series was deleted and the user confirmed it, or null
 *                                         if the series cannot be deleted here
 * @returns {Promise}
 */
export const openSeriesDialog = async({labels, locale, rule, timezone, minFrom, originalFrom, onApply, onDelete}) => {
    const modal = await ModalSaveCancel.create({
        title: labels.series,
        body: buildBody(labels, locale, formFromRule(rule), onDelete !== null),
        removeOnClose: true,
        show: true,
    });
    modal.setSaveButtonText(labels.apply);

    const root = modal.getRoot()[0];
    const error = root.querySelector('[data-region="series-dialog-error"]');
    const field = (id) => root.querySelector(`#entities-series-${id}`);
    const messages = {
        noweekday: labels.seriesNoWeekday,
        badtime: labels.seriesBadTime,
        badperiod: labels.seriesBadPeriod,
    };

    field('allday').addEventListener('change', () => {
        root.querySelector('[data-region="series-dialog-times"]').classList.toggle('d-none', field('allday').checked);
    });

    modal.getRoot().on(ModalEvents.save, (event) => {
        const weekdays = [...root.querySelectorAll('[id^="entities-series-day-"]:checked')].map((box) => Number(box.value));
        const entered = ruleFromForm({
            weekdays,
            allday: field('allday').checked,
            start: field('start').value,
            end: field('end').value,
            from: field('from').value,
            until: field('until').value,
        }, timezone);
        const problem = checkRule(entered, minFrom, originalFrom);
        if (problem !== null) {
            event.preventDefault();
            error.textContent = messages[problem];
            error.classList.remove('d-none');
            return;
        }
        onApply(entered);
    });

    const deleteButton = root.querySelector('[data-action="series-dialog-delete"]');
    if (deleteButton) {
        deleteButton.addEventListener('click', () => {
            modal.hide();
            deleteCancel(labels.seriesDelete, labels.seriesDeleteConfirm, labels.seriesDelete, onDelete);
        });
    }
};
