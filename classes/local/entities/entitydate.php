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
     * Consumed amount of the entity by this date (capacity mode): participants or units. Default 1.
     *
     * @var int
     */
    public $quantity = 1;

    /**
     * Whether the item is hidden from regular users (e.g. an invisible booking option).
     *
     * The providers return hidden items like any other, because their dates are cached across
     * users and a hidden item still occupies its entity. Whether a viewer gets to see the date is
     * decided per request by entities::filter_dates_for_user() with the capability below.
     *
     * @var bool
     */
    public $invisible = false;

    /**
     * Capability that lets a user see this date although it is invisible, e.g.
     * 'mod/booking:canseeinvisibleoptions'. Without one, an invisible date is shown to nobody.
     *
     * @var ?string
     */
    public $requiredcapability = null;

    /**
     * Id of the context in which $requiredcapability is checked.
     *
     * @var ?int
     */
    public $contextid = null;

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
    public function __construct(
        int $itemid,
        string $component,
        string $area,
        string $name,
        int $starttime,
        int $endtime,
        int $status,
        ?moodle_url $link = null,
        ?string $bgcolor = null
    ) {
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
        $item['quantity'] = $this->quantity;
        $item['invisible'] = $this->invisible;
        $item['requiredcapability'] = $this->requiredcapability;
        $item['contextid'] = $this->contextid;
        return $item;
    }

    /**
     * Marks the date as hidden from regular users and names the capability that reveals it.
     *
     * @param string $capability capability that lets a user see the date, e.g. 'mod/booking:canseeinvisibleoptions'
     * @param int $contextid id of the context the capability is checked in
     * @return void
     */
    public function set_invisible(string $capability, int $contextid): void {
        $this->invisible = true;
        $this->requiredcapability = $capability;
        $this->contextid = $contextid;
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
