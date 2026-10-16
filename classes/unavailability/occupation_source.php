<?php
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

namespace local_entities\unavailability;

/**
 * Where the occupations of a person come from, e.g. the bookings of the booking plugin.
 *
 * The conflict detector only asks this interface, so it does not need to know any plugin, and tests
 * can replace the source by a fixed list.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface occupation_source {
    /**
     * Returns the occupations of an entity.
     *
     * @param int $entityid
     * @return \local_entities\local\entities\entitydate[] objects with starttime, endtime, name, link,
     *         component, area, itemid and status (1 = cancelled)
     */
    public function get_occupations(int $entityid): array;
}
