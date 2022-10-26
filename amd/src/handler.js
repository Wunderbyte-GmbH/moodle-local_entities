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

import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import Templates from 'core/templates';

import {
    get_strings as getStrings
        }
        from 'core/str';

export const init = () => {
    const element = document.querySelector('#id_openmodal');

    // eslint-disable-next-line no-console
    console.log(element);

    element.addEventListener('click', event => {
        // eslint-disable-next-line no-console
        console.log(event);

        timeTableModal(1);
    });
};

/**
 *
 * @param {entityid} entityid
 */
 function timeTableModal(entityid) {

    getStrings([
        {key: 'timetablemodaltitle', component: 'local_entities'},
        {key: 'timetablemodalbutton', component: 'local_entities'}
    ]
    ).then(strings => {

        const id = entityid;
        const json = {'id': id, 'locale': 'de'};
        Templates.renderForPromise('local_entities/entitiescalendar', json).then(({html, js}) => {

        ModalFactory.create({type: ModalFactory.types.SAVE_CANCEL, large: 'true'}).then(modal => {

            modal.setTitle(strings[0]);
            modal.setBody(html);
            modal.setSaveButtonText(strings[1]);
            modal.getRoot().on(ModalEvents.save, function() {

                // eslint-disable-next-line no-console
                console.log(html);

            });

            modal.show();

            Templates.runTemplateJS(js);
            return modal;
        }).catch(e => {
            // eslint-disable-next-line no-console
            console.log(e);
        });
        return true;
        }).catch(e => {
            // eslint-disable-next-line no-console
            console.log(e);
        });
        return true;
    }).catch(e => {
        // eslint-disable-next-line no-console
        console.log(e);
    });
}