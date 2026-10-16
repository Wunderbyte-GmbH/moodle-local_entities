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
 * Calls of the web services that load and save the unavailability of an entity.
 *
 * @module     local_entities/unavailability/api
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';

/**
 * Loads the blocks and series of an entity in a time window.
 *
 * @param {number} entityid
 * @param {number} from start of the window, unix timestamp
 * @param {number} until end of the window, unix timestamp
 * @returns {Promise} resolves to {blocks, series}
 */
export const load = (entityid, from, until) => Ajax.call([{
    methodname: 'local_entities_get_unavailability',
    args: {entityid, from, until},
}])[0];

/**
 * Saves the changes of the user.
 *
 * @param {number} entityid
 * @param {Object} changes as returned by PendingChanges.toPayload()
 * @returns {Promise} resolves to {saved, conflicts}
 */
export const save = (entityid, changes) => Ajax.call([{
    methodname: 'local_entities_save_unavailability',
    args: {entityid, changes},
}])[0];
