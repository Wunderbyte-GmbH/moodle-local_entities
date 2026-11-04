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

namespace local_entities\hook;

use local_entities\local\entities\entitydate;

/**
 * Asks other plugins at which times an entity is occupied, for example a person who examines at a booked exam.
 *
 * The answer is read live and never cached, so that a new occupation counts at once.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\core\attribute\label('Allows plugins to tell at which times an entity is occupied.')]
#[\core\attribute\tags('local_entities')]
final class collect_occupations {
    /** @var entitydate[] The occupations the plugins told. */
    private array $dates = [];

    /**
     * Constructor.
     *
     * @param int $entityid the entity whose occupations are asked for
     */
    public function __construct(
        /** @var int The entity whose occupations are asked for. */
        public readonly int $entityid,
    ) {
    }

    /**
     * Adds an occupation of the entity.
     *
     * @param entitydate $date
     * @return void
     */
    public function add(entitydate $date): void {
        $this->dates[] = $date;
    }

    /**
     * The occupations the plugins told.
     *
     * @return entitydate[]
     */
    public function get_dates(): array {
        return $this->dates;
    }
}
