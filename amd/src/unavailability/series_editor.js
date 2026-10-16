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
 * Creating, changing and deleting recurring unavailabilities on the unavailability page.
 *
 * It decides which dialog belongs to a clicked entry and writes the answer into the remembered
 * changes. Nothing is saved before the user presses "Save".
 *
 * @module     local_entities/unavailability/series_editor
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {openSeriesChoiceDialog} from 'local_entities/unavailability/series_choice_dialog';
import {openSeriesDialog} from 'local_entities/unavailability/series_dialog';
import {defaultRule} from 'local_entities/unavailability/series_form';

export default class SeriesEditor {
    /**
     * Constructor.
     *
     * @param {Object} options
     * @param {PendingChanges} options.pending the remembered changes
     * @param {Object} options.labels texts of the dialogs
     * @param {string} options.locale language code
     * @param {Function} options.startOfToday () => unix timestamp, start of today
     * @param {Function} options.findSeries (id) => the saved series, undefined if it is not loaded
     * @param {Function} options.onChange called after the remembered changes changed
     */
    constructor({pending, labels, locale, startOfToday, findSeries, onChange}) {
        this.pending = pending;
        this.labels = labels;
        this.locale = locale;
        this.startOfToday = startOfToday;
        this.findSeries = findSeries;
        this.onChange = onChange;
        this.timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    }

    /**
     * Opens the dialog for a new series.
     */
    create() {
        this.openRule(defaultRule(this.startOfToday(), this.timezone), null, {
            apply: (rule) => this.pending.createSeries(rule),
            remove: null,
        });
    }

    /**
     * Handles a click on an entry that belongs to a series.
     *
     * @param {Object} props kind, id and seriesid of the entry
     */
    open(props) {
        if (props.kind === 'pending-series') {
            // A series that is new: changing replaces it, deleting forgets it.
            this.openRule(this.pending.createdSeries.get(props.id), null, {
                apply: (rule) => this.pending.updateSeries(props.id, rule),
                remove: () => this.pending.deleteSeries(props.id),
            });
        } else if (props.kind === 'series-preview') {
            // A saved series whose change is not saved yet.
            const saved = this.findSeries(props.seriesid);
            this.openRule(this.pending.updatedSeries.get(props.seriesid), saved ? saved.validfrom : null, {
                apply: (rule) => this.pending.updateSeries(props.seriesid, rule),
                remove: () => this.pending.deleteSeries(props.seriesid),
            });
        } else {
            this.openChoice(props);
        }
    }

    /**
     * Asks what to do with a day of a saved series.
     *
     * @param {Object} props kind, id and seriesid of the block
     */
    openChoice(props) {
        const saved = this.findSeries(props.seriesid);
        openSeriesChoiceDialog({
            labels: this.labels,
            canEdit: Boolean(saved),
            onDeleteDay: () => this.done(() => this.pending.removeBlock(props.id)),
            onEdit: () => this.openRule(saved, saved.validfrom, {
                apply: (rule) => this.pending.updateSeries(props.seriesid, rule),
                remove: () => this.pending.deleteSeries(props.seriesid),
            }),
            onDeleteSeries: () => this.done(() => this.pending.deleteSeries(props.seriesid)),
        });
    }

    /**
     * Opens the dialog for a rule.
     *
     * @param {Object} rule the rule to start with
     * @param {number|null} originalFrom first day the series had before, null for a new one
     * @param {Object} actions apply (rule) and remove, null if the series cannot be deleted here
     */
    openRule(rule, originalFrom, actions) {
        openSeriesDialog({
            labels: this.labels,
            locale: this.locale,
            rule,
            timezone: this.timezone,
            minFrom: this.startOfToday(),
            originalFrom,
            onApply: (entered) => this.done(() => actions.apply(entered)),
            onDelete: actions.remove ? () => this.done(actions.remove) : null,
        });
    }

    /**
     * Makes a change and tells that the remembered changes are different now.
     *
     * @param {Function} change
     */
    done(change) {
        change();
        this.onChange();
    }
}
