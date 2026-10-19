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

namespace local_entities\people_pool;

/**
 * The stable entry point of other plugins to the pools of persons.
 *
 * Plugins such as mod_booking only need three answers: which pools exist, how a pool is called and
 * which users stand behind it. They never see entities. The methods here contain no logic, they
 * hand the work on, so the classes behind can change without touching the callers.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class people_pool_facade {
    /**
     * Lists all pools, e.g. for a selection in a form.
     *
     * @return array[] ordered by name, each ['id' => int, 'name' => string, 'membercount' => int]
     */
    public static function list_pools(): array {
        $pools = [];
        foreach ((new people_pool_repository())->find_all() as $pool) {
            $pools[] = [
                'id' => (int)$pool->id,
                'name' => (string)$pool->name,
                'membercount' => (int)$pool->membercount,
            ];
        }
        return $pools;
    }

    /**
     * Returns the names of some pools, e.g. to show what an option is configured with.
     *
     * Pools that do not exist any more are missing in the result, so the caller can tell.
     *
     * @param int[] $poolids
     * @return string[] names, keyed by pool id
     */
    public static function get_pool_names(array $poolids): array {
        $wanted = array_flip(array_map('intval', $poolids));
        $names = [];
        foreach ((new people_pool_repository())->find_all() as $pool) {
            if (isset($wanted[(int)$pool->id])) {
                $names[(int)$pool->id] = (string)$pool->name;
            }
        }
        return $names;
    }

    /**
     * Returns the users that stand behind the members of the given pools.
     *
     * @param int[] $poolids
     * @return int[] user ids, ascending, each user once
     */
    public static function get_userids_for_pools(array $poolids): array {
        return (new people_pool_resolver())->get_userids($poolids);
    }
}
