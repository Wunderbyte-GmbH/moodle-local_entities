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
 * The actions of the pool page: save a pool together with its members, and delete a pool.
 *
 * The form and the web services only call this class. It does not check rights, the callers do that
 * with {@see people_pool_access}. The members are given as Moodle users, as the page shows them,
 * and are turned into person entities here.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class people_pool_service {
    /** @var people_pool_repository */
    private people_pool_repository $repository;

    /**
     * Constructor.
     *
     * @param people_pool_repository|null $repository null for the standard storage
     */
    public function __construct(?people_pool_repository $repository = null) {
        $this->repository = $repository ?? new people_pool_repository();
    }

    /**
     * Creates a pool or changes it, and makes its members exactly the given users.
     *
     * Everything happens in one transaction: if one user is no person or the name is not allowed,
     * nothing is changed.
     *
     * @param int $id id of the pool, 0 for a new pool
     * @param string $name
     * @param string $description
     * @param int[] $userids the users that are to be members, all of them must be persons
     * @param int $usermodified user who saves the pool
     * @return int id of the pool
     * @throws \moodle_exception
     */
    public function save_pool(int $id, string $name, string $description, array $userids, int $usermodified): int {
        global $DB;

        $entityids = $this->entityids_of_users($userids);

        $transaction = $DB->start_delegated_transaction();
        try {
            if ($id > 0) {
                $this->repository->update($id, $name, $description, $usermodified);
                $poolid = $id;
            } else {
                $poolid = $this->repository->create($name, $description, $usermodified);
            }

            $current = $this->repository->get_member_entityids($poolid);
            $this->repository->remove_members($poolid, array_diff($current, $entityids));
            $this->repository->add_members($poolid, $entityids, $usermodified);
        } catch (\Throwable $e) {
            $transaction->rollback($e);
        }
        $transaction->allow_commit();

        return $poolid;
    }

    /**
     * Deletes a pool with its memberships. The persons stay.
     *
     * @param int $id
     * @return void
     * @throws \moodle_exception if the pool does not exist
     */
    public function delete_pool(int $id): void {
        if ($this->repository->find($id) === null) {
            throw new \moodle_exception('error:poolnotfound', 'local_entities');
        }
        $this->repository->delete($id);
    }

    /**
     * Turns users into the entities of their persons.
     *
     * @param int[] $userids
     * @return int[] entity ids, each once
     * @throws \moodle_exception if a user is no person
     */
    private function entityids_of_users(array $userids): array {
        $entityids = [];
        foreach (array_unique(array_map('intval', $userids)) as $userid) {
            $entityid = persons::get_entityid_for_user($userid);
            if ($entityid === null) {
                throw new \moodle_exception('error:poolmembernotperson', 'local_entities');
            }
            $entityids[] = $entityid;
        }
        return $entityids;
    }
}
