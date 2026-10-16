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
 * The selection of the entity whose unavailability is shown.
 *
 * With many entities a plain list is hard to use, so the selection can be searched. Choosing an entity
 * opens the page for it. If there are unsaved changes, the browser asks before the page is left.
 *
 * @module     local_entities/unavailability/entity_select
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Autocomplete from 'core/form-autocomplete';

/**
 * Makes the selection searchable and opens the page of the chosen entity.
 *
 * @param {HTMLElement} root the element of the page
 * @param {Object} labels texts: search, choose
 * @returns {Promise|null} null if the page has no selection, as there is nothing to choose
 */
export const init = (root, labels) => {
    const select = root.querySelector('[data-region="unavailability-entity-select"] select');
    if (!select) {
        return null;
    }

    select.addEventListener('change', () => {
        const url = new URL(select.dataset.url, window.location.origin);
        url.searchParams.set('entityid', select.value);
        window.location.href = url.toString();
    });

    return Autocomplete.enhance(`#${select.id}`, false, false, labels.search, false, true, labels.choose, true);
};
