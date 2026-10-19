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
 * Storage of the pools of persons and of their members.
 *
 * It reads and writes pools and members and guards the two rules of the data: a pool name is unique
 * and only persons can be members. Who may change pools and how pools are turned into users is
 * handled elsewhere.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class people_pool_repository {
    /** @var string Table of the pools. */
    private const POOLS = 'local_entities_people_pool';

    /** @var string Table of the members. */
    private const MEMBERS = 'local_entities_people_pool_member';

    /**
     * Returns all pools with the number of their members.
     *
     * @return \stdClass[] pools ordered by name, keyed by id, with the extra field membercount
     */
    public function find_all(): array {
        global $DB;

        return $DB->get_records_sql(
            "SELECT p.*, (SELECT COUNT(1) FROM {" . self::MEMBERS . "} m WHERE m.poolid = p.id) AS membercount
               FROM {" . self::POOLS . "} p
           ORDER BY p.name, p.id"
        );
    }

    /**
     * Returns one pool.
     *
     * @param int $id
     * @return \stdClass|null null if the pool does not exist
     */
    public function find(int $id): ?\stdClass {
        global $DB;

        $pool = $DB->get_record(self::POOLS, ['id' => $id]);
        return $pool ?: null;
    }

    /**
     * Is a pool name already used by another pool? The comparison ignores case.
     *
     * @param string $name
     * @param int $ignoreid pool that may keep its name (the one being changed), 0 for a new pool
     * @return bool
     */
    public function is_name_taken(string $name, int $ignoreid = 0): bool {
        global $DB;

        $select = $DB->sql_equal('name', ':name', false) . ' AND id <> :ignoreid';
        return $DB->record_exists_select(self::POOLS, $select, ['name' => trim($name), 'ignoreid' => $ignoreid]);
    }

    /**
     * Creates a pool.
     *
     * @param string $name unique name, without case
     * @param string $description
     * @param int $usermodified user who creates the pool
     * @return int id of the new pool
     * @throws \moodle_exception if the name is empty, too long or already used
     */
    public function create(string $name, string $description, int $usermodified): int {
        global $DB;

        $now = time();
        return (int)$DB->insert_record(self::POOLS, (object)[
            'name' => $this->checked_name($name),
            'description' => $description,
            'timecreated' => $now,
            'timemodified' => $now,
            'usermodified' => $usermodified,
        ]);
    }

    /**
     * Changes name and description of a pool.
     *
     * @param int $id
     * @param string $name unique name, without case
     * @param string $description
     * @param int $usermodified user who changes the pool
     * @return void
     * @throws \moodle_exception if the pool does not exist or the name is empty, too long or already used
     */
    public function update(int $id, string $name, string $description, int $usermodified): void {
        global $DB;

        $this->must_exist($id);
        $DB->update_record(self::POOLS, (object)[
            'id' => $id,
            'name' => $this->checked_name($name, $id),
            'description' => $description,
            'timemodified' => time(),
            'usermodified' => $usermodified,
        ]);
    }

    /**
     * Deletes a pool with all its memberships. The persons themselves stay.
     *
     * @param int $id
     * @return void
     */
    public function delete(int $id): void {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records(self::MEMBERS, ['poolid' => $id]);
        $DB->delete_records(self::POOLS, ['id' => $id]);
        $transaction->allow_commit();
    }

    /**
     * Returns the entities that are members of a pool.
     *
     * @param int $poolid
     * @return int[] entity ids of the persons
     */
    public function get_member_entityids(int $poolid): array {
        global $DB;

        $ids = $DB->get_fieldset_select(self::MEMBERS, 'entityid', 'poolid = :poolid ORDER BY id', ['poolid' => $poolid]);
        return array_map('intval', $ids);
    }

    /**
     * Returns the pools a person belongs to.
     *
     * @param int $entityid
     * @return int[] pool ids
     */
    public function find_poolids_of_entity(int $entityid): array {
        global $DB;

        $ids = $DB->get_fieldset_select(self::MEMBERS, 'poolid', 'entityid = :entityid ORDER BY poolid', ['entityid' => $entityid]);
        return array_map('intval', $ids);
    }

    /**
     * Adds persons to a pool. Persons that are members already are left as they are.
     *
     * @param int $poolid
     * @param int[] $entityids entities of type person
     * @param int $usermodified user who adds them
     * @return int number of persons that were added
     * @throws \moodle_exception if the pool does not exist or one of the entities is no person
     */
    public function add_members(int $poolid, array $entityids, int $usermodified): int {
        global $DB;

        $this->must_exist($poolid);
        $entityids = array_values(array_unique(array_map('intval', $entityids)));
        if (empty($entityids)) {
            return 0;
        }
        $this->must_be_persons($entityids);

        $new = array_diff($entityids, $this->get_member_entityids($poolid));
        $now = time();
        $transaction = $DB->start_delegated_transaction();
        foreach ($new as $entityid) {
            $DB->insert_record(self::MEMBERS, (object)[
                'poolid' => $poolid,
                'entityid' => $entityid,
                'timecreated' => $now,
                'usermodified' => $usermodified,
            ]);
        }
        $transaction->allow_commit();

        return count($new);
    }

    /**
     * Removes persons from a pool.
     *
     * @param int $poolid
     * @param int[] $entityids
     * @return void
     */
    public function remove_members(int $poolid, array $entityids): void {
        global $DB;

        $entityids = array_values(array_unique(array_map('intval', $entityids)));
        if (empty($entityids)) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($entityids, SQL_PARAMS_NAMED, 'ent');
        $DB->delete_records_select(self::MEMBERS, "poolid = :poolid AND entityid $insql", ['poolid' => $poolid] + $params);
    }

    /**
     * Removes a person from all pools, e.g. because the person is deleted.
     *
     * @param int $entityid
     * @return void
     */
    public function remove_entity_everywhere(int $entityid): void {
        global $DB;

        $DB->delete_records(self::MEMBERS, ['entityid' => $entityid]);
    }

    /**
     * Makes sure a pool exists.
     *
     * @param int $id
     * @return void
     * @throws \moodle_exception
     */
    private function must_exist(int $id): void {
        global $DB;

        if (!$DB->record_exists(self::POOLS, ['id' => $id])) {
            throw new \moodle_exception('error:poolnotfound', 'local_entities');
        }
    }

    /**
     * Makes sure every given entity exists and is a person.
     *
     * @param int[] $entityids not empty, without duplicates
     * @return void
     * @throws \moodle_exception
     */
    private function must_be_persons(array $entityids): void {
        global $DB;

        [$insql, $params] = $DB->get_in_or_equal($entityids, SQL_PARAMS_NAMED, 'ent');
        $select = "id $insql AND entitytype = :type";
        $found = $DB->count_records_select('local_entities', $select, $params + ['type' => persons::TYPE]);
        if ($found !== count($entityids)) {
            throw new \moodle_exception('error:poolmembernotperson', 'local_entities');
        }
    }

    /**
     * Trims a pool name and checks that it is usable.
     *
     * @param string $name
     * @param int $ignoreid pool that may keep its name (the one being changed), 0 for a new pool
     * @return string the trimmed name
     * @throws \moodle_exception if the name is empty, too long or already used by another pool
     */
    private function checked_name(string $name, int $ignoreid = 0): string {
        $name = trim($name);
        if ($name === '' || \core_text::strlen($name) > 255) {
            throw new \moodle_exception('error:poolnameinvalid', 'local_entities');
        }
        if ($this->is_name_taken($name, $ignoreid)) {
            throw new \moodle_exception('error:poolnameexists', 'local_entities');
        }
        return $name;
    }
}
