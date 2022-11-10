
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
 * @package    local_wunderbyte_table
 * @copyright  Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Gets called from mustache template.
 */
export const init = () => {
    reloadCats();
    addEvents();
};

/**
 * Reload categories.
 */
function reloadCats() {
    let catfieldsets = document.querySelectorAll('[id^=id_categorymeta]');
    let selectvalue = document.getElementById('id_type').value;
    let id = selectvalue.split("_")[0];
    for (var i = 0, len = catfieldsets.length + 0; i < len; i++) {
        catfieldsets[i].hidden = true;
        catfieldsets[i].disabled = true;
        let cats = document.querySelectorAll('[id^=id_categorymeta_' + id + ']');

        cats.forEach(cat => {
            // eslint-disable-next-line no-console
            console.log(cat, id);
            if (typeof (cat) != 'undefined' && cat !== null) {
                cat.hidden = false;
                cat.disabled = false;
            }
        });
    }
}

/**
 * Add events.
 */
function addEvents() {
    let select = document.getElementById('id_type');
    select.addEventListener('change', () => {
        reloadCats();
    });
}
