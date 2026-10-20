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

namespace local_entities\pool;

use local_entities\unavailability_access;

/**
 * Who may see and change the pools of persons.
 *
 * Pools list persons, so the same people may use them who may manage the unavailability of every
 * person (right local/entities:manageunavailability). The rule itself stays in
 * {@see unavailability_access}, this class only gives it the name that fits pools. Guests and users
 * that are not logged in are never allowed.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pool_access {
    /**
     * May the user see and change pools?
     *
     * @param int|null $userid null for the current user
     * @return bool
     */
    public static function can_manage(?int $userid = null): bool {
        return unavailability_access::can_manage_any($userid);
    }

    /**
     * Stops the request if the user may not see and change pools.
     *
     * @param int|null $userid null for the current user
     * @return void
     * @throws \required_capability_exception
     */
    public static function require_manage(?int $userid = null): void {
        if (!self::can_manage($userid)) {
            throw new \required_capability_exception(
                \context_system::instance(),
                unavailability_access::CAP_ANY,
                'nopermissions',
                ''
            );
        }
    }
}
