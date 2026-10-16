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
 * The area on the unavailability page that shows the result of saving.
 *
 * Everything that comes from the server is written as text, never as html.
 *
 * @module     local_entities/unavailability/message_area
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export default class MessageArea {
    /**
     * Constructor.
     *
     * @param {HTMLElement} element the area
     * @param {string} locale language code, for the format of times
     */
    constructor(element, locale) {
        this.element = element;
        this.formatter = new Intl.DateTimeFormat(locale || undefined, {dateStyle: 'medium', timeStyle: 'short'});
    }

    /**
     * Empties the area.
     */
    clear() {
        this.element.replaceChildren();
    }

    /**
     * Shows that saving worked.
     *
     * @param {string} text
     */
    showSuccess(text) {
        const box = document.createElement('div');
        box.className = 'alert alert-success';
        box.textContent = text;
        this.element.replaceChildren(box);
    }

    /**
     * Shows the assignments that prevented saving.
     *
     * @param {string} title
     * @param {Array} conflicts list of {starttime, endtime, name, url}
     */
    showConflicts(title, conflicts) {
        const box = document.createElement('div');
        box.className = 'alert alert-danger';

        const heading = document.createElement('p');
        heading.className = 'mb-2 fw-bold';
        heading.textContent = title;
        box.appendChild(heading);

        const list = document.createElement('ul');
        list.className = 'mb-0';
        conflicts.forEach((conflict) => {
            const item = document.createElement('li');
            const when = `${this.formatter.format(new Date(conflict.starttime * 1000))} - `
                + this.formatter.format(new Date(conflict.endtime * 1000));
            if (conflict.url) {
                const link = document.createElement('a');
                link.href = conflict.url;
                link.target = '_blank';
                link.rel = 'noopener';
                link.textContent = conflict.name;
                item.appendChild(link);
            } else {
                item.appendChild(document.createTextNode(conflict.name));
            }
            item.appendChild(document.createTextNode(`: ${when}`));
            list.appendChild(item);
        });
        box.appendChild(list);

        this.element.replaceChildren(box);
    }
}
