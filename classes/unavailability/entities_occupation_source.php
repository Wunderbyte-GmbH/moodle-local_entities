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

use local_entities\entities;

/**
 * The occupations of an entity as the connected plugins deliver them through the service providers.
 *
 * The occupations are read live and not from the cache, because the answer is used to refuse a
 * write and a stale cache must not hide an assignment.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class entities_occupation_source implements occupation_source {
    /**
     * Returns the occupations of an entity, read live.
     *
     * @param int $entityid
     * @return \local_entities\local\entities\entitydate[]
     */
    public function get_occupations(int $entityid): array {
        return entities::get_all_dates_for_entity($entityid, true);
    }
}
