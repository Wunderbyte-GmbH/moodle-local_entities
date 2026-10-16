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
 * The dialog that asks what to do with a day that belongs to a recurring unavailability.
 *
 * It only asks the user and reports the choice. What happens is decided by the caller.
 *
 * @module     local_entities/unavailability/series_choice_dialog
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalCancel from 'core/modal_cancel';
import {deleteCancel} from 'core/notification';
import {escapeHtml} from 'local_entities/unavailability/html';

/**
 * Opens the dialog for a day of a series.
 *
 * @param {Object} options
 * @param {Object} options.labels texts: series, seriesBelongs, seriesThisDay, seriesEdit, seriesDelete,
 *                                seriesDeleteConfirm, deleteconfirm, delete
 * @param {boolean} options.canEdit false if the series cannot be changed, e.g. it is not loaded
 * @param {Function} options.onDeleteDay only this day is deleted and the user confirmed it
 * @param {Function} options.onEdit the user wants to change the series
 * @param {Function} options.onDeleteSeries the series is deleted from today on and the user confirmed it
 * @returns {Promise}
 */
export const openSeriesChoiceDialog = async({labels, canEdit, onDeleteDay, onEdit, onDeleteSeries}) => {
    const modal = await ModalCancel.create({
        title: labels.series,
        body: `
            <p>${escapeHtml(labels.seriesBelongs)}</p>
            <div class="d-grid gap-2">
                <button type="button" class="btn btn-outline-danger mb-2" data-action="series-choice-day">
                    ${escapeHtml(labels.seriesThisDay)}
                </button>
                <button type="button" class="btn btn-outline-primary mb-2 ${canEdit ? '' : 'd-none'}"
                    data-action="series-choice-edit">${escapeHtml(labels.seriesEdit)}</button>
                <button type="button" class="btn btn-outline-danger" data-action="series-choice-series">
                    ${escapeHtml(labels.seriesDelete)}
                </button>
            </div>`,
        removeOnClose: true,
        show: true,
    });

    const root = modal.getRoot()[0];
    const on = (action, handler) => root.querySelector(`[data-action="${action}"]`).addEventListener('click', handler);

    on('series-choice-day', () => {
        modal.hide();
        deleteCancel(labels.seriesThisDay, labels.deleteconfirm, labels.delete, onDeleteDay);
    });
    on('series-choice-edit', () => {
        modal.hide();
        onEdit();
    });
    on('series-choice-series', () => {
        modal.hide();
        deleteCancel(labels.seriesDelete, labels.seriesDeleteConfirm, labels.seriesDelete, onDeleteSeries);
    });
};
