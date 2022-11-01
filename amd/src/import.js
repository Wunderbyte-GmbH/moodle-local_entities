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
 * @package    local_entities
 * @copyright  Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


import DynamicForm from 'core_form/dynamicform';
import Notification from 'core/notification';
import {
    get_string as getString
        }
        from 'core/str';
// ...


export const init = () => {
    // eslint-disable-next-line no-console
    console.log('init import form');

    // Initialize the form - pass the container element and the form class name.
    const dynamicForm = new DynamicForm(document.querySelector('#importformcontainer'), 'local_entities\\form\\import_form');
    // By default the form is removed from the DOM after it is submitted, you may want to change this behavior:
    dynamicForm.addEventListener(dynamicForm.events.FORM_SUBMITTED, (e) => {
        e.preventDefault();
        const response = e.detail;
        // eslint-disable-next-line no-console
        console.log(response);

        let message = 'successfullimport';
        let type = 'success';
        if (e.detail.success != true) {
            message = 'failedimport';
            type = 'error';
        }

        getString(message, 'local_entities').then((localizedmessage) => {

            // We add information about lineerrors.
            if (e.detail.lineerrors) {
                Notification.addNotification({
                    message: e.detail.lineerrors,
                    type: 'info'
                });
            }

            if (e.detail.error) {
                Notification.addNotification({
                    message: e.detail.error,
                    type
                });
            }

            // Success or failure message.
            Notification.addNotification({
                message: localizedmessage,
                type
            });

            return;
        }).catch(e => {
            // eslint-disable-next-line no-console
            console.log(e);
        });


        // It is recommended to reload the form after submission because the elements may change.
        // This will also remove previous submission errors. You will need to pass the same arguments to the form
        // that you passed when you rendered the form on the page.
        dynamicForm.load();
    });
};