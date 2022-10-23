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
 * This file contains the local_entities\local\callback\service_provider interface.
 *
 * Plugins should implement this if they use entities subsystem.
 *
 * @package local_entities
 * @copyright 2022 Georg Maißer Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_entities\local\callback;

/**
 * The service_provider interface for plugins to provide callbacks which are needed by the entities subsystem.
 *
 * @copyright  2022 Georg Maißer Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface service_provider {

    /**
     * This function takes a list of ids and returns a list of dates in the entitydate format.
     * As one plugin can use multiple different handlers...
     * ... we use $area to specify the handler of the component.
     * Every area in the $areas array holds its on list of ids.
     * @param array $areas
     * @return array
     */
    public static function return_array_of_dates(array $areas):array;
}
