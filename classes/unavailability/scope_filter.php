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

namespace local_entities\unavailability;

/**
 * Selects blocks and series by their scope and turns that selection into an SQL condition.
 *
 * Three kinds of selection exist:
 * - unrestricted: all blocks, whatever their scope,
 * - global and listed scopes: the blocks that apply everywhere plus those of the listed scopes,
 *   an empty list therefore selects only the blocks that apply everywhere,
 * - exactly one scope: only the blocks of that scope.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class scope_filter {
    /**
     * Constructor, use the named constructors instead.
     *
     * @param bool $unrestricted no condition at all
     * @param bool $includeglobal blocks that apply everywhere are selected
     * @param scope[] $scopes scopes whose blocks are selected
     */
    private function __construct(
        /** @var bool No condition at all */
        private readonly bool $unrestricted,
        /** @var bool Blocks that apply everywhere are selected */
        private readonly bool $includeglobal,
        /** @var scope[] Scopes whose blocks are selected */
        private readonly array $scopes,
    ) {
    }

    /**
     * Selects all blocks.
     *
     * @return self
     */
    public static function unrestricted(): self {
        return new self(true, false, []);
    }

    /**
     * Selects the blocks that apply everywhere and those of the given scopes.
     *
     * @param scope[] $scopes
     * @return self
     */
    public static function global_and(array $scopes = []): self {
        $scopes = array_values(array_filter($scopes, static fn(scope $scope) => !$scope->is_everywhere()));
        return new self(false, true, $scopes);
    }

    /**
     * Selects only the blocks of exactly one scope. For "everywhere" these are the global blocks.
     *
     * @param scope $scope
     * @return self
     */
    public static function exactly(scope $scope): self {
        if ($scope->is_everywhere()) {
            return new self(false, true, []);
        }
        return new self(false, false, [$scope]);
    }

    /**
     * Builds the selection from the form callers pass: null for all blocks, otherwise a list of
     * scopes (as objects or as arrays) next to the global blocks.
     *
     * @param array|null $scopes
     * @return self
     */
    public static function from_nullable(?array $scopes): self {
        if ($scopes === null) {
            return self::unrestricted();
        }
        return self::global_and(array_map(
            static fn($scope) => $scope instanceof scope ? $scope : scope::from_array((array)$scope),
            array_values($scopes)
        ));
    }

    /**
     * Builds the SQL condition.
     *
     * @param string $alias table alias of the unavailability or series table, empty for none
     * @param string $prefix prefix for the parameter names, so several filters can share a query
     * @return array [sql, params]
     */
    public function to_sql(string $alias, string $prefix): array {
        if ($this->unrestricted) {
            return ['1 = 1', []];
        }

        $column = static fn(string $name): string => $alias === '' ? $name : "{$alias}.{$name}";
        $clauses = [];
        $params = [];

        if ($this->includeglobal) {
            // Some databases store an empty string as NULL.
            $clauses[] = '(' . $column('component') . ' IS NULL OR ' . $column('component') . " = '')";
        }
        foreach ($this->scopes as $i => $scope) {
            $clauses[] = '(' . $column('component') . " = :{$prefix}c{$i}"
                . ' AND ' . $column('area') . " = :{$prefix}a{$i}"
                . ' AND ' . $column('instanceid') . " = :{$prefix}i{$i})";
            $params["{$prefix}c{$i}"] = $scope->component;
            $params["{$prefix}a{$i}"] = $scope->area;
            $params["{$prefix}i{$i}"] = $scope->instanceid;
        }

        return ['(' . implode(' OR ', $clauses) . ')', $params];
    }
}
