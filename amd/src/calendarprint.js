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
 * Print Canvas
 * @package    local_entities
 * @copyright  Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export const init = () => {
    const element = document.querySelector('[data-action="print"]');

    element.addEventListener('click', () => {
        window.html2canvas(document.querySelector('.fc-view-harness-active'),
        {

        }).then(function (canvas) {
        var a = document.createElement("a");
        a.download = "chart.png";
        a.href = canvas.toDataURL("image/png");
        document.body.appendChild(a);
        a.click();
        return;
      }).catch((e) => {
        // eslint-disable-next-line no-console
        console.log(e);
      });
    });
};

