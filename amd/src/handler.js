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

import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import Templates from 'core/templates';
import {get_strings as getStrings} from 'core/str';

export const init = () => {
    const element = document.querySelector('#id_openmodal');

    if (!element) {
        return;
    }

    element.addEventListener('click', () => {

        const selectedelement = document.querySelector('#id_entitiesrelationcontainer div.form-autocomplete-selection span');

        if (selectedelement) {
            const entityid = selectedelement.dataset.value;

            if (entityid > 0) {
                timeTableModal(entityid);
            }
        }
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
        // eslint-disable-next-line promise/no-nesting
        Templates.renderForPromise('local_entities/entitiescalendar', json).then(({html, js}) => {
            // eslint-disable-next-line promise/no-nesting
            ModalFactory.create({
                type: ModalFactory.types.SAVE_CANCEL,
                large: 'true'
            }).then(modal => {
                modal.setTitle(strings[0]);
                modal.setBody(html);
                modal.setSaveButtonText(strings[1]);
                modal.getRoot().on(ModalEvents.save + " "
                + ModalEvents.outsideClick + " "
                + ModalEvents.hidden, function() {
                    /* Destroy the modal so calendar gets reloaded on further openings. */
                    modal.destroy();
                });
                modal.setRemoveOnClose(true);
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