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


export const searchInput = () => {
    let input, filter, li, a, i, txtValue;
    input = document.getElementById("entitysearch");
    filter = input.value.toUpperCase();
    li = document.querySelectorAll("#entitiesrelation-form .group-root>li");
    for (i = 0; i < li.length; i++) {
        a = li[i].querySelector('span');
        txtValue = a.textContent || a.innerText;
        console.log(txtValue);
        if (txtValue.toUpperCase().indexOf(filter) > -1) {
            li[i].style.display = "";
        } else {
            li[i].style.display = "none";
        }
    }
};

function addEvents() {
    document.querySelector("#entitiesrelation-form").addEventListener('click', function(e) {
        if (e.target.dataset.action == "addentity") {
            e.preventDefault();
            e.stopPropagation();
            let entityidfield = document.querySelector("[name='local_entities_entityid']");
            entityidfield.value = e.target.dataset.entityid;
            let entitynamefield = document.querySelector("[name='local_entities_entityname']");
            entitynamefield.value = e.target.dataset.entityname;
        }
    });
    document.getElementById("entitysearch").addEventListener('keyup', function () {searchInput();});
}

export const init = (cmid, module, optionid) => {
    addEvents();
};
