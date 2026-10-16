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
 * Entry point of the unavailability page: loads the texts and starts the controller.
 *
 * @module     local_entities/unavailability/main
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {get_strings as getStrings} from 'core/str';
import {init as entitySelect} from 'local_entities/unavailability/entity_select';

// Texts of the page: name used in the code => string of Moodle core.
const CORE_TEXTS = {
    today: 'today',
    week: 'week',
    month: 'month',
    search: 'search',
    choose: 'choosedots',
};

// Texts of the page: name used in the code => string of this plugin.
const PLUGIN_TEXTS = {
    allday: 'unavailability_allday',
    more: 'unavailability_more',
    absent: 'unavailability_absent',
    notsaved: 'unavailability_notsaved',
    saved: 'unavailability_saved',
    conflicts: 'unavailability_conflicts',
    edit: 'unavailability_edit',
    apply: 'unavailability_apply',
    from: 'unavailability_from',
    until: 'unavailability_until',
    'delete': 'unavailability_delete',
    deleteconfirm: 'unavailability_deleteconfirm',
    invalidrange: 'unavailability_invalidrange',
    series: 'unavailability_series',
    seriesBelongs: 'unavailability_series_belongs',
    seriesWeekdays: 'unavailability_series_weekdays',
    seriesStart: 'unavailability_series_start',
    seriesEnd: 'unavailability_series_end',
    seriesFirstDay: 'unavailability_series_firstday',
    seriesLastDay: 'unavailability_series_lastday',
    seriesThisDay: 'unavailability_series_thisday',
    seriesEdit: 'unavailability_series_edit',
    seriesDelete: 'unavailability_series_delete',
    seriesDeleteConfirm: 'unavailability_series_deleteconfirm',
    seriesNoWeekday: 'unavailability_series_noweekday',
    seriesBadTime: 'unavailability_series_badtime',
    seriesBadPeriod: 'unavailability_series_badperiod',
};

/**
 * Loads the texts of the page with one request.
 *
 * @returns {Promise<Object>} the texts by their name in the code
 */
const loadTexts = async() => {
    const names = [...Object.keys(CORE_TEXTS), ...Object.keys(PLUGIN_TEXTS)];
    const requests = [
        ...Object.values(CORE_TEXTS).map((key) => ({key, component: 'core'})),
        ...Object.values(PLUGIN_TEXTS).map((key) => ({key, component: 'local_entities'})),
    ];
    const texts = await getStrings(requests);
    return Object.fromEntries(names.map((name, index) => [name, texts[index]]));
};

/**
 * Starts the page.
 *
 * @param {number} entityid the entity whose unavailability is shown
 * @param {string} locale language code
 * @param {number|string} firstDay first day of the week, 0 = Sunday
 * @param {number|string} timeFormat 24 or 12
 */
export const init = async(entityid, locale, firstDay, timeFormat) => {
    const root = document.querySelector('[data-region="unavailability-page"]');
    if (!root) {
        return;
    }

    // The path to the calendar library is set by the config module, so it has to be loaded before
    // anything that needs the library is requested.
    await import('local_entities/config');
    const {PageController} = await import('local_entities/unavailability/page_controller');

    const labels = await loadTexts();
    entitySelect(root, labels);

    const parsedFirstDay = parseInt(firstDay, 10);
    const controller = new PageController(
        root,
        {
            entityid: parseInt(entityid, 10),
            locale,
            firstDay: isNaN(parsedFirstDay) ? 1 : parsedFirstDay,
            hour12: parseInt(timeFormat, 10) === 12,
        },
        labels
    );
    controller.start();
};
