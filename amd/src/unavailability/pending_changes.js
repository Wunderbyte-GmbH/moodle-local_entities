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
 * Remembers what the user changed on the unavailability page until it is saved.
 *
 * Only the differences to the saved state are kept: new single blocks, blocks to remove, and series
 * to create, change or delete. The result is exactly the list the save web service expects.
 *
 * @module     local_entities/unavailability/pending_changes
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export default class PendingChanges {
    /**
     * Constructor.
     */
    constructor() {
        this.reset();
    }

    /**
     * Forgets all changes.
     */
    reset() {
        this.addedBlocks = new Map();
        this.removedBlockIds = new Set();
        this.createdSeries = new Map();
        this.updatedSeries = new Map();
        this.deletedSeriesIds = new Set();
        this.counter = 0;
    }

    /**
     * Adds a new single block.
     *
     * @param {number} starttime unix timestamp
     * @param {number} endtime unix timestamp
     * @returns {string|null} temporary id of the block, null if the block has no length
     */
    addBlock(starttime, endtime) {
        if (!(endtime > starttime)) {
            return null;
        }
        const id = `new-${++this.counter}`;
        this.addedBlocks.set(id, {starttime, endtime, reason: ''});
        return id;
    }

    /**
     * Removes a block. A block that was only added here is simply forgotten.
     *
     * @param {number|string} id stored id, or temporary id of a block added here
     */
    removeBlock(id) {
        if (typeof id === 'string' && this.addedBlocks.has(id)) {
            this.addedBlocks.delete(id);
            return;
        }
        this.removedBlockIds.add(Number(id));
    }

    /**
     * Is a stored block going to be removed?
     *
     * @param {number} id
     * @returns {boolean}
     */
    isBlockRemoved(id) {
        return this.removedBlockIds.has(Number(id));
    }

    /**
     * Adds a new series.
     *
     * @param {Object} rule weekdays, allday, daystart, dayend, validfrom, validuntil, timezone, reason
     * @returns {string} temporary id of the series
     */
    createSeries(rule) {
        const id = `newseries-${++this.counter}`;
        this.createdSeries.set(id, rule);
        return id;
    }

    /**
     * Changes the rule of a series. For a series created here the rule is replaced.
     *
     * @param {number|string} id stored id, or temporary id of a series created here
     * @param {Object} rule
     */
    updateSeries(id, rule) {
        if (typeof id === 'string' && this.createdSeries.has(id)) {
            this.createdSeries.set(id, rule);
            return;
        }
        this.updatedSeries.set(Number(id), rule);
    }

    /**
     * Deletes a series. A series that was only created here is simply forgotten.
     *
     * @param {number|string} id stored id, or temporary id of a series created here
     */
    deleteSeries(id) {
        if (typeof id === 'string' && this.createdSeries.has(id)) {
            this.createdSeries.delete(id);
            return;
        }
        this.updatedSeries.delete(Number(id));
        this.deletedSeriesIds.add(Number(id));
    }

    /**
     * Is a stored series going to be deleted?
     *
     * @param {number} id
     * @returns {boolean}
     */
    isSeriesDeleted(id) {
        return this.deletedSeriesIds.has(Number(id));
    }

    /**
     * Is there anything to save?
     *
     * @returns {boolean}
     */
    isDirty() {
        return this.addedBlocks.size > 0
            || this.removedBlockIds.size > 0
            || this.createdSeries.size > 0
            || this.updatedSeries.size > 0
            || this.deletedSeriesIds.size > 0;
    }

    /**
     * The changes in the form the save web service expects.
     *
     * @returns {Object} addblocks, removeblockids, createseries, updateseries, deleteseries
     */
    toPayload() {
        return {
            addblocks: [...this.addedBlocks.values()],
            removeblockids: [...this.removedBlockIds],
            createseries: [...this.createdSeries.values()],
            updateseries: [...this.updatedSeries.entries()].map(([id, rule]) => ({id, rule})),
            deleteseries: [...this.deletedSeriesIds],
        };
    }
}
