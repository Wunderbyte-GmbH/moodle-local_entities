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
 * Reads the value of the field for the highest number of slots per day. Needs nothing of Moodle, so it can be tested.
 *
 * @module     local_entities/unavailability/limit_value
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Reads the field: 0 for no limit, null if the value is not allowed.
 *
 * @param {string} value what was entered
 * @param {number} max highest limit
 * @returns {number|null}
 */
export const parseLimit = (value, max) => {
    const text = String(value).trim();
    if (text === '') {
        return 0;
    }
    if (!/^\d+$/.test(text)) {
        return null;
    }
    const limit = parseInt(text, 10);
    return limit >= 1 && limit <= max ? limit : null;
};
