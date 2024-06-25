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

/**
 * The entitydate class.
 *
 * @package    local_entities
 * @copyright  2022 Georg Maißer Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_entities\local\entities;

use moodle_url;

/**
 * The entitydate class.
 *
 * @copyright  2022 Georg Maißer Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class entitydate {

    /**
     * Item id
     *
     * @var int
     */
    public $itemid;

    /**
     * Component
     *
     * @var string
     */
    public $component;

    /**
     * Area
     *
     * @var string
     */
    public $area;

    /**
     * Name
     *
     * @var string
     */
    public $name;

    /**
     * Starttime as timestamp
     *
     * @var int
     */
    public $starttime;

    /**
     * Endtime as timestamp
     *
     * @var int
     */
    public $endtime;

    /**
     * Status
     *
     * @var int
     */
    public $status;

    /**
     * Link to booking item
     *
     * @var moodle_url
     */
    public $link;

    /**
     * Category of the booking item
     *
     * @var category
     */
    public $bgcolor;

    /**
     * Cunstructor.
     *
     * @param int $itemid
     * @param string $component
     * @param string $area
     * @param string $name
     * @param int $starttime
     * @param int $endtime
     * @param int $status
     * @param ?moodle_url $link
     * @param ?string $bgcolor
     */
    public function __construct(int $itemid,
                                string $component,
                                string $area,
                                string $name,
                                int $starttime,
                                int $endtime,
                                int $status,
                                ?moodle_url $link = null,
                                ?string $bgcolor = null) {
        $this->itemid = $itemid;
        $this->component = $component;
        $this->area = $area;
        $this->name = $name;
        $this->starttime = $starttime;
        $this->endtime = $endtime;
        $this->status = $status;
        $this->link = $link;
        $this->bgcolor = $bgcolor;
    }

    /**
     * Returns all the values as array.
     *
     * @return array
     */
    public function getitem(): array {
        $item = [];
        $item['itemid'] = $this->itemid;
        $item['component'] = $this->component;
        $item['area'] = $this->area;
        $item['name'] = $this->name;
        $item['starttime'] = $this->starttime;
        $item['endtime'] = $this->endtime;
        $item['status'] = $this->status;
        $item['link'] = $this->link;
        $item['bgcolor'] = $this->bgcolor;
        return $item;
    }

    /**
     * To acces private property area.
     *
     * @return string
     */
    public function return_area(): string {
        return $this->area;
    }
}
