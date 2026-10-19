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

use local_entities\persons;

/**
 * Turns pools into the Moodle users behind their members.
 *
 * Other plugins only know users, never entities. The answer does not depend on who asks: whether the
 * asking user may see a person is a question for the pages, not for an assignment that runs in the
 * name of the system.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class people_pool_resolver {
    /**
     * Returns the users that are members of at least one of the given pools.
     *
     * A user who is in several of the pools appears once. Deleted users and pools that do not
     * exist are left out without an error.
     *
     * @param int[] $poolids
     * @return int[] user ids, ascending
     */
    public function get_userids(array $poolids): array {
        global $DB;

        $poolids = array_values(array_unique(array_filter(array_map('intval', $poolids))));
        if (empty($poolids)) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal($poolids, SQL_PARAMS_NAMED, 'pool');
        $params['type'] = persons::TYPE;

        $userids = $DB->get_fieldset_sql(
            "SELECT DISTINCT e.userid
               FROM {local_entities_people_pool_member} m
               JOIN {local_entities} e ON e.id = m.entityid
               JOIN {user} u ON u.id = e.userid
              WHERE m.poolid $insql
                AND e.entitytype = :type
                AND u.deleted = 0
           ORDER BY e.userid",
            $params
        );
        return array_map('intval', $userids);
    }
}
