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
 * The pool page: opens the dialog to add or edit a pool and deletes a pool after a question.
 *
 * The table is drawn by the server, so after every change the page is loaded again.
 *
 * @module     local_entities/pools
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalForm from 'core_form/modalform';
import {call as fetchMany} from 'core/ajax';
import {deleteCancel, exception as displayException} from 'core/notification';
import {get_string as getString, get_strings as getStrings} from 'core/str';

const FORM_CLASS = 'local_entities\\form\\pool_dynamic_form';

/**
 * Opens the dialog for a new pool (poolid 0, with the type of the pool) or an existing one.
 *
 * @param {number} poolid
 * @param {string} title
 * @param {string} pooltype type of a new pool, an existing pool keeps its type
 */
const openForm = (poolid, title, pooltype) => {
    const form = new ModalForm({
        formClass: FORM_CLASS,
        args: {id: poolid, pooltype},
        modalConfig: {title},
        returnFocus: document.activeElement,
    });
    form.addEventListener(form.events.FORM_SUBMITTED, () => window.location.reload());
    form.show();
};

/**
 * Asks for confirmation and deletes a pool.
 *
 * @param {number} poolid
 * @param {string} name
 * @return {Promise}
 */
const deletePool = async(poolid, name) => {
    const [title, question] = await getStrings([
        {key: 'delete', component: 'core'},
        {key: 'pool_deleteconfirm', component: 'local_entities', param: name},
    ]);
    deleteCancel(title, question, title, () => {
        fetchMany([{methodname: 'local_entities_delete_pool', args: {poolid}}])[0]
            .then(() => window.location.reload())
            .catch(displayException);
    });
};

/**
 * Starts the page: one listener for all buttons of the table.
 */
export const init = () => {
    const region = document.querySelector('[data-region="pools"]');
    if (!region) {
        return;
    }

    region.addEventListener('click', event => {
        const button = event.target.closest('[data-action]');
        if (!button || !region.contains(button)) {
            return;
        }
        const poolid = parseInt(button.dataset.id || '0', 10);

        switch (button.dataset.action) {
            case 'pool-add': {
                const pooltype = button.dataset.pooltype;
                getString('pool_add_' + pooltype, 'local_entities')
                    .then(title => openForm(0, title, pooltype))
                    .catch(displayException);
                break;
            }
            case 'pool-edit':
                getString('pool_edit', 'local_entities').then(title => openForm(poolid, title, '')).catch(displayException);
                break;
            case 'pool-delete':
                deletePool(poolid, button.dataset.name || '').catch(displayException);
                break;
        }
    });
};
