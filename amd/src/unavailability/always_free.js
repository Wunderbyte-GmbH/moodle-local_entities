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
 * The checkbox with which a person confirms to be available the whole planning period. It is saved at once.
 *
 * @module     local_entities/unavailability/always_free
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Notification from 'core/notification';
import {get_string as getString} from 'core/str';
import {saveAlwaysFree} from 'local_entities/unavailability/api';

/**
 * Connects the checkbox, if the page has one that can be changed.
 *
 * @param {HTMLElement} root the element of the page
 * @param {number} entityid
 */
export const init = (root, entityid) => {
    const region = root.querySelector('[data-region="unavailability-alwaysfree"]');
    const box = region ? region.querySelector('input[type="checkbox"]') : null;
    if (!box || box.disabled) {
        return;
    }
    const status = region.querySelector('[data-region="unavailability-alwaysfree-status"]');

    box.addEventListener('change', async() => {
        const wanted = box.checked;
        box.disabled = true;
        status.textContent = '';
        try {
            const result = await saveAlwaysFree(entityid, wanted);
            box.checked = result.confirmed;
            status.textContent = await getString(result.confirmed ? 'alwaysfree_saved' : 'alwaysfree_removed', 'local_entities');
            status.classList.add('text-success');
        } catch (error) {
            box.checked = !wanted;
            Notification.exception(error);
        } finally {
            box.disabled = false;
        }
    });
};
