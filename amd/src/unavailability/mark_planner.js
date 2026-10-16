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
 * Works out what marking a time range as unavailable means for the blocks that exist.
 *
 * The new range is joined with every block it overlaps or touches, so the result is one block and not
 * several pieces. Entries that cannot be joined, like the days of a series, stay as they are, and the
 * new range only fills what they leave free.
 *
 * @module     local_entities/unavailability/mark_planner
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {subtractRanges} from 'local_entities/unavailability/range_math';

/**
 * Do two ranges overlap or touch?
 *
 * @param {Object} a starttime and endtime
 * @param {Object} b starttime and endtime
 * @returns {boolean}
 */
const meets = (a, b) => a.starttime <= b.endtime && b.starttime <= a.endtime;

/**
 * The blocks that overlap or touch a range.
 *
 * @param {Object} range starttime and endtime
 * @param {Array} blocks
 * @returns {Array}
 */
const touching = (range, blocks) => blocks.filter((block) => meets(range, block));

/**
 * The blocks that are not in a list.
 *
 * @param {Array} blocks
 * @param {Array} taken
 * @returns {Array}
 */
const without = (blocks, taken) => blocks.filter((block) => !taken.includes(block));

/**
 * The range that covers a range and some blocks.
 *
 * @param {Object} range starttime and endtime
 * @param {Array} blocks
 * @returns {Object} starttime and endtime
 */
const spanOf = (range, blocks) => ({
    starttime: Math.min(range.starttime, ...blocks.map((block) => block.starttime)),
    endtime: Math.max(range.endtime, ...blocks.map((block) => block.endtime)),
});

/**
 * Joins a range with the blocks it meets, and with the blocks those meet after they are joined.
 *
 * @param {Object} part starttime and endtime
 * @param {Array} blocks
 * @returns {Object} joined (the resulting range) and absorbed (the blocks that went into it)
 */
const join = (part, blocks) => {
    let joined = {starttime: part.starttime, endtime: part.endtime};
    let absorbed = [];
    let found = touching(joined, blocks);
    while (found.length > 0) {
        absorbed = absorbed.concat(found);
        joined = spanOf(joined, found);
        found = touching(joined, without(blocks, absorbed));
    }
    return {joined, absorbed};
};

/**
 * Plans the marking of a time range.
 *
 * @param {Object} range starttime and endtime of what the user marked
 * @param {Array} blocks single blocks that can be joined, each with kind, id, starttime and endtime
 * @param {Array} fixed ranges that cannot be joined and are not changed
 * @returns {Object} remove (the blocks that are joined and go away, each with kind and id) and add
 *                   (the ranges to add)
 */
export const planMark = (range, blocks, fixed) => {
    const remove = [];
    const add = [];
    let rest = blocks;

    for (const part of subtractRanges(range, fixed)) {
        const {joined, absorbed} = join(part, rest);
        rest = without(rest, absorbed);
        const unchanged = absorbed.length === 1
            && absorbed[0].starttime === joined.starttime && absorbed[0].endtime === joined.endtime;
        if (!unchanged) {
            absorbed.forEach((block) => remove.push({kind: block.kind, id: block.id}));
            add.push(joined);
        }
    }
    return {remove, add};
};
