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

namespace local_entities\output;

use local_entities\entities;
use local_entities\unavailability\manageable_entities;
use local_entities\unavailability_access;
use local_entities\planning_period;
use local_entities\daily_limit;

/**
 * Builds the context for the page on which the unavailability of an entity is managed.
 *
 * The page looks the same for every entity, whether it is a room, equipment or a person. The page
 * script checks the rights and hands the entity in. This class only decides what the page shows: the
 * heading and the selection of the entities the user may manage. The calendar is drawn by JavaScript.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unavailability_page {
    /** @var int The entity whose unavailability is shown. */
    private int $entityid;

    /**
     * Constructor.
     *
     * @param int $entityid
     */
    public function __construct(int $entityid) {
        $this->entityid = $entityid;
    }

    /**
     * Exports the context for the template.
     *
     * @param \renderer_base $output
     * @return array heading, readonly (the page only shows, as the user may not change it), entityselect (label, url
     *         and options of the selection, or null)
     */
    public function export_for_template(\renderer_base $output): array {
        global $DB;

        $entity = $DB->get_record('local_entities', ['id' => $this->entityid], '*', MUST_EXIST);

        $data = [
            'heading' => get_string('unavailabilityof', 'local_entities', entities::get_display_name($entity)),
            'readonly' => !unavailability_access::can_manage_entity($entity),
            'entityselect' => null,
        ];

        // Persons only see the days of the planning period. Planners and admins are not limited.
        $period = planning_period::for_user();
        $data['planningperiod'] = $period === null ? null : [
            'start' => $period->starttime,
            'end' => $period->endtime,
            'text' => get_string('unavailability_planningperiod', 'local_entities', planning_period::describe($period)),
        ];

        // The highest number of slots per day. It can be changed also when the type is locked. An empty field uses
        // the default, which is shown as placeholder.
        $default = daily_limit::get_default();
        $data['dailylimit'] = [
            'value' => daily_limit::get_own([$this->entityid])[$this->entityid] ?? null,
            'placeholder' => $default === null
                ? get_string('dailylimit_none', 'local_entities')
                : get_string('dailylimit_default_placeholder', 'local_entities', $default),
            'canchange' => daily_limit::can_change($entity),
            'max' => daily_limit::MAX,
        ];

        // A selection only makes sense if there is something to choose.
        $groups = (new manageable_entities())->find();
        if (array_sum(array_map('count', $groups)) > 1) {
            // One list that can be searched. The type is part of the text, as a search finds both.
            $options = [];
            foreach ($groups as $type => $entities) {
                foreach ($entities as $id => $name) {
                    $options[] = [
                        'id' => (int)$id,
                        'name' => $name . ' (' . $type . ')',
                        'selected' => (int)$id === $this->entityid,
                    ];
                }
            }
            $data['entityselect'] = [
                'label' => get_string('unavailability_entity', 'local_entities'),
                'url' => (new \moodle_url('/local/entities/unavailability.php'))->out(false),
                'options' => $options,
            ];
        }

        return $data;
    }
}
