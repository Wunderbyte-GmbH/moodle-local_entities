  
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
 * @param {string} catid
 * @param {string} encodedtable
 */
export const init = () => {
    reloadCats();
    addEvents();
};

function reloadCats() {
    catfieldsets = document.querySelectorAll('[id^=id_category]');
    select = document.getElementById('id_type').value;
    for (var i = 0, len = catfieldsets.length + 0; i < len; i++) {
        if (i + 1 == select) { 
            catfieldsets[i].hidden = false;
            catfieldsets[i].disabled = false;
        } else {         
        catfieldsets[i].hidden = true;
        catfieldsets[i].disabled = true;
        }
    }
}

function addEvents() {
    select = document.getElementById('id_type');
    select.addEventListener('change', () => {
        loadCategories();
    });
}

function fillform() {
    length = 6;
    digits = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    
    let str = '';
    for( i = 0; i < length; i++ ) str += digits.charAt( Math.floor( Math.random() * digits.length ) );
  
    let fields = document.querySelectorAll('form input:not([type="submit"]');

    for (i = 0; i<fields.length-2;i++) {
        fields[i].value = str;
    }
    document.querySelector('#id_descriptioneditable').innerHTML  = str;
}
