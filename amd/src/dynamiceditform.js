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
 * @package    local_
 * @author     Thomas Winkler
 * @copyright  Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Dynamic dynamicchangesemesterform.
 *
 * @module     rk_manager/dynamicoptinform
 * @copyright  2022 Wunderbyte GmbH
 * @author     Thomas Winkler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 import DynamicForm from 'core_form/dynamicform';

 export const init = (selector, formClass) => {

    const formContainer = document.querySelector(selector);
    const returnurl = formContainer.dataset.returnurl;
    const editform = new DynamicForm(formContainer, formClass);

    formContainer.addEventListener('change', (e) => {
        if (!e.target.name) {
            return;
        }
        if (e.target.name == 'cfitemid') {
            window.skipClientValidation = true;
            let button = document.querySelector('[name="btn_cfcategoryid"]');
            editform.processNoSubmitButton(button);
        }
        if (e.target.name == 'entitytype') {
            // Snap the field template to the one matching the chosen entity type, then reload the form
            // through the existing category no-submit so the right fields appear automatically.
            const cfselect = formContainer.querySelector('[name="cfitemid"]');
            if (cfselect) {
                const target = e.target.value == 'equipment'
                    ? cfselect.dataset.templateEquipment
                    : cfselect.dataset.templateLocation;
                if (target !== undefined && target !== '' && target !== '0') {
                    cfselect.value = target;
                }
                window.skipClientValidation = true;
                editform.processNoSubmitButton(document.querySelector('[name="btn_cfcategoryid"]'));
            }
        }
    });
    editform.addEventListener(editform.events.FORM_SUBMITTED, (e) => {
        e.preventDefault();
        window.location.href = e.detail.returnurl;
    });

    // Cancel button does not make much sense in such forms but since it's there we'll just reload.
    editform.addEventListener(editform.events.FORM_CANCELLED, (e) => {
        e.preventDefault();
        window.location.href = returnurl;
    });
 };
