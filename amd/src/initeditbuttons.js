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

/*
 * @package    mod_booking
 * @copyright  Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalForm from 'core_form/modalform';
import Notification from 'core/notification';
import Pending from 'core/pending';
import {
    get_string as getString,
    get_strings as getStrings,
} from 'core/str';
import { deletEntity, toggleVisibility } from './editcontrolws';


/**
 * Init
 * @param {*} containerSelector
 *
 */
export const init = (containerSelector) => {
    const rootNode = document.querySelector(containerSelector);

    rootNode.addEventListener('click', e => {
        const actonHandler = e.target.closest('[data-action]');
        if (!actonHandler) {
            return;
        }
        if (actonHandler.dataset.action === 'local_entities_delete-entity') {
            e.preventDefault();
            confirmDelete(actonHandler.dataset.id);
            return;
        }
        if (actonHandler.dataset.role === 'local_entities_togglevisibility-entity') {
            e.preventDefault();
            return;
        }
        if (actonHandler.dataset.action === 'local_entities_deletecfcategory') {
            e.preventDefault();
            confirmDelete(actonHandler.dataset.id);
            return;
        }
    });
}


/**
 * Display confirmation dialogue
 *
 * @param {Number} id
 * @param {String} type
 * @param {String} component
 * @param {String} area
 * @param {Number} itemid
 */
 const confirmDelete = (id, type, component, area, itemid) => {
    const pendingPromise = new Pending('core_customfield/form:confirmDelete');

    getStrings([
        {'key': 'confirm'},
        {'key': 'confirmdelete' + type, component: 'core_customfield'},
        {'key': 'yes'},
        {'key': 'no'},
    ])
    .then(strings => {
        return Notification.confirm(strings[0], strings[1], strings[2], strings[3], function() {
            const pendingDeletePromise = new Pending('core_customfield/form:confirmDelete');

        });
    })
    .then(pendingPromise.resolve)
    .catch(Notification.exception);
};

