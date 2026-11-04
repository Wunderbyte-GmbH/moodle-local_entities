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
 * The field for the highest number of slots per day. It is saved on its own, also when the page is locked.
 *
 * @module     local_entities/unavailability/daily_limit
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Notification from 'core/notification';
import {get_string as getString} from 'core/str';
import {saveDailyLimit} from 'local_entities/unavailability/api';
import {parseLimit} from 'local_entities/unavailability/limit_value';

/**
 * Connects the field, if the page has one that can be changed.
 *
 * @param {HTMLElement} root the element of the page
 * @param {number} entityid
 */
export const init = (root, entityid) => {
    const form = root.querySelector('[data-region="unavailability-dailylimit"]');
    if (!form) {
        return;
    }
    const field = form.querySelector('input');
    const button = form.querySelector('button[type="submit"]');
    const status = form.querySelector('[data-region="unavailability-dailylimit-status"]');
    if (!button) {
        return;
    }
    const max = parseInt(form.dataset.max, 10);

    const show = (text, ok) => {
        status.textContent = text;
        status.classList.toggle('text-success', ok);
        status.classList.toggle('text-danger', !ok);
    };

    field.addEventListener('input', () => {
        status.textContent = '';
    });

    form.addEventListener('submit', async(event) => {
        event.preventDefault();
        const limit = parseLimit(field.value, max);
        if (limit === null) {
            show(await getString('dailylimit_invalid', 'local_entities', max), false);
            field.focus();
            return;
        }
        button.disabled = true;
        try {
            const result = await saveDailyLimit(entityid, limit);
            field.value = result.limit === 0 ? '' : String(result.limit);
            show(await getString('dailylimit_saved', 'local_entities'), true);
        } catch (error) {
            Notification.exception(error);
        } finally {
            button.disabled = false;
        }
    });
};
