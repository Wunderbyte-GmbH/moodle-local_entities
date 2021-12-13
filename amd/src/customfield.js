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

export const init = () => {
    reloadCats();
    addEvents();
};

function reloadCats() {
    let catfieldsets = document.querySelectorAll('[id^=id_category]');
    let select = document.getElementById('id_type').value;
    let selectsplit = select.split('_');
    let catid = selectsplit[0];
    for (var i = 0, len = catfieldsets.length + 0; i < len; i++) {
        if (i + 1 == catid) { 
            catfieldsets[i].hidden = false;
            catfieldsets[i].disabled = false;
        } else {     
        catfieldsets[i].disabled = true;    
        catfieldsets[i].hidden = true;
        }
    }
}

function addEvents() {
    let select = document.getElementById('id_type');
    select.addEventListener('change', () => {
        reloadCats();
    });
    let form  = document.querySelector('.mform');
    form.addEventListener('submit', () => {
        removeInputs();
        alert("remove");
    });
}


// TODO disabled fields get sent WHYYYYYYY?
function removeInputs() {
    let catfieldsets = document.querySelectorAll('[id^=id_category]');
    for (var i = 0, len = catfieldsets.length + 0; i < len; i++) {
        if (catfieldsets[i].hidden == true) { 
            catfieldsets[i].remove();
        } 
    }
}

function addAddress() {

}

function addContacts() {
    
}