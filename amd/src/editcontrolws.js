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

import Ajax from 'core/ajax';

/**
 * Deletes Entity
 * @param {*} id
 */
 export function deletEntity(id) {
    Ajax.call([{
        methodname: "local_entities_delete_entry",
        args: {
            'id': id
        },
        done: function(result) {

            // eslint-disable-next-line no-console
            console.log('deleted', result, id);
            return;
        },
    }]);
}

/**
 * Toggles Visibility from Entity
 * @param {*} id
 */
 export function toggleVisibility(id) {
    Ajax.call([{
        methodname: "local_entities_visibility",
        args: {
            'id': id
        },
        done: function(result) {

            // eslint-disable-next-line no-console
            console.log('toggled', result, id);
            return;
        },
    }]);
}