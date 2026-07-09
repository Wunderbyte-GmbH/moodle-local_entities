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

import ModalSaveCancel from 'core/modal_save_cancel';
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
 * Toggle the "show equipment for the selected location" no-submit button.
 *
 * The button is only relevant when the chosen location actually has equipment. The set of
 * equipment-bearing location ids is resolved server-side and passed in here, so the button can be
 * shown or hidden purely client-side as the location changes - without a no-submit round-trip and
 * without an extra web service call. The whole form row (the .fitem wrapper) is toggled, matching
 * the server-side initial hide done via the element's extra class.
 *
 * @param {Number} index the entity relation index (the option-level entity is index 0)
 * @param {Array} equipmentLocations location entity ids that have equipment
 */
export const initEquipmentToggle = (index, equipmentLocations) => {
    const select = document.getElementById('id_local_entities_entityid_' + index);
    const button = document.getElementById('id_btn_local_entities_equipment_' + index);

    if (!select || !button) {
        return;
    }

    const wrapper = button.closest('.fitem') || button.parentElement;
    const equipmentSet = new Set((equipmentLocations || []).map(String));

    const apply = () => {
        const hasequipment = equipmentSet.has(String(select.value || ''));
        wrapper.classList.toggle('d-none', !hasequipment);
    };

    apply();
    select.addEventListener('change', apply);
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
            ModalSaveCancel.create({
                title: strings[0],
                body: html,
                buttons: {
                    save: strings[1],
                },
                large: true,
            }).then(modal => {
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