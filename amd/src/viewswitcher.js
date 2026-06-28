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
 * Saves the previewed detail-view template as the global active one.
 *
 * Template switching itself is preview-only (plain links carrying ?template=); only the explicit
 * save button persists the choice for everyone via the external function.
 *
 * @module     local_entities/viewswitcher
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import Ajax from 'core/ajax';

export const init = () => {
    const root = document.querySelector('.local-entities-viewswitcher');
    if (!root) {
        return;
    }
    const savebutton = root.querySelector('[data-action="save-view-template"]');
    if (!savebutton) {
        return;
    }

    savebutton.addEventListener('click', () => {
        savebutton.disabled = true;
        Ajax.call([{
            methodname: 'local_entities_set_active_view_template',
            args: {
                template: savebutton.dataset.template,
                entitytype: savebutton.dataset.entitytype || '',
            },
        }])[0].then(() => {
            // Reload without the preview parameter so the now-saved template is shown as active.
            window.location.href = root.dataset.baseurl;
            return true;
        }).catch(() => {
            savebutton.disabled = false;
        });
    });
};
