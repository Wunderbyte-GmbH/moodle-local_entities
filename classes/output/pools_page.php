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

use local_entities\pool\pool_repository;
use local_entities\pool\pool_types;

/**
 * Builds the context for the page on which the pools are managed.
 *
 * The page is built like the list of entities: buttons to add a pool of each type, a filter by type
 * and a table. The page script checks the rights. This class only turns the pools and the enabled types
 * into buttons, filters and rows. Adding and editing happen in a dialog that JavaScript opens.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pools_page {
    /** @var string Type the list is filtered by, empty for all. */
    private string $typefilter;

    /**
     * Constructor.
     *
     * @param string $typefilter type to filter by, empty for all, a type that is not enabled counts as empty
     */
    public function __construct(string $typefilter = '') {
        $this->typefilter = pool_types::exists($typefilter) ? $typefilter : '';
    }

    /**
     * Exports the context for the template.
     *
     * @param \renderer_base $output
     * @return array addbuttons, filters, haspools and pools
     */
    public function export_for_template(\renderer_base $output): array {
        $types = pool_types::all();

        $addbuttons = [];
        $filters = [['label' => get_string('all'), 'url' => $this->url(''), 'active' => $this->typefilter === '']];
        foreach ($types as $type => $label) {
            $addbuttons[] = [
                'type' => $type,
                'label' => get_string('pool_add_' . $type, 'local_entities'),
                'icon' => pool_types::icon($type),
            ];
            $filters[] = ['label' => $label, 'url' => $this->url($type), 'active' => $this->typefilter === $type];
        }

        $pools = [];
        foreach ((new pool_repository())->find_all($this->typefilter) as $pool) {
            $type = (string)$pool->pooltype;
            $pools[] = [
                'id' => (int)$pool->id,
                'name' => (string)$pool->name,
                'description' => (string)$pool->description,
                'typelabel' => $types[$type] ?? $type,
                'icon' => pool_types::icon($type),
                'membercount' => (int)$pool->membercount,
            ];
        }

        return [
            'addbuttons' => $addbuttons,
            'filters' => $filters,
            'haspools' => !empty($pools),
            'pools' => $pools,
        ];
    }

    /**
     * The address of the page with a filter.
     *
     * @param string $type type to filter by, empty for all
     * @return string
     */
    private function url(string $type): string {
        $params = $type === '' ? [] : ['type' => $type];
        return (new \moodle_url('/local/entities/pools.php', $params))->out(false);
    }
}
