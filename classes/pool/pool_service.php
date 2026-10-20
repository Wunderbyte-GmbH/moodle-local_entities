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

use local_entities\persons;

/**
 * The actions of the pool page: save a pool together with its members, and delete a pool.
 *
 * The form and the web services only call this class. It does not check rights, the callers do that
 * with {@see pool_access}. The members are given as entities of the type of the pool.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pool_service {
    /** @var pool_repository */
    private pool_repository $repository;

    /**
     * Constructor.
     *
     * @param pool_repository|null $repository null for the standard storage
     */
    public function __construct(?pool_repository $repository = null) {
        $this->repository = $repository ?? new pool_repository();
    }

    /**
     * Creates a pool or changes it, and makes its members exactly the given entities.
     *
     * Everything happens in one transaction: if the name is not allowed or one entity does not have
     * the type of the pool, nothing is changed. The type is only used for a new pool, a pool keeps
     * its type.
     *
     * @param int $id id of the pool, 0 for a new pool
     * @param string $name
     * @param string $description
     * @param int[] $entityids the entities that are to be members
     * @param int $usermodified user who saves the pool
     * @param string $pooltype type of a new pool
     * @return int id of the pool
     * @throws \moodle_exception
     */
    public function save_pool(
        int $id,
        string $name,
        string $description,
        array $entityids,
        int $usermodified,
        string $pooltype = persons::TYPE
    ): int {
        global $DB;

        $entityids = array_values(array_unique(array_map('intval', $entityids)));

        $transaction = $DB->start_delegated_transaction();
        try {
            if ($id > 0) {
                $this->repository->update($id, $name, $description, $usermodified);
                $poolid = $id;
            } else {
                $poolid = $this->repository->create($name, $description, $usermodified, $pooltype);
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
     * Makes an entity a member of exactly the given pools.
     *
     * It is the other way round than {@see self::save_pool()}: the person page sets the pools of one
     * person. Everything happens in one transaction. A pool that does not exist or has another type
     * than the entity makes the whole call fail and changes nothing.
     *
     * @param int $entityid
     * @param int[] $poolids the pools the entity is to belong to
     * @param int $usermodified user who saves
     * @return void
     * @throws \moodle_exception
     */
    public function set_pools_of_entity(int $entityid, array $poolids, int $usermodified): void {
        global $DB;

        $wanted = array_values(array_unique(array_map('intval', $poolids)));
        $current = $this->repository->find_poolids_of_entity($entityid);

        $transaction = $DB->start_delegated_transaction();
        try {
            foreach (array_diff($current, $wanted) as $poolid) {
                $this->repository->remove_members($poolid, [$entityid]);
            }
            foreach (array_diff($wanted, $current) as $poolid) {
                $this->repository->add_members($poolid, [$entityid], $usermodified);
            }
        } catch (\Throwable $e) {
            $transaction->rollback($e);
        }
        $transaction->allow_commit();
    }

    /**
     * Deletes a pool with its memberships. The members themselves stay.
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
}
