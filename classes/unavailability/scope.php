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
 * The scope an unavailability applies to: everywhere, or one object of a plugin (e.g. a booking
 * instance or a booking option).
 *
 * A scope without component means "everywhere". It is immutable, so it can be passed around safely.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class scope {
    /**
     * Constructor, use the named constructors instead.
     *
     * @param string $component
     * @param string $area
     * @param int $instanceid
     */
    private function __construct(
        /** @var string Component, empty for everywhere. */
        public readonly string $component,
        /** @var string Area within the component, empty for everywhere. */
        public readonly string $area,
        /** @var int Id of the object, 0 for everywhere. */
        public readonly int $instanceid,
    ) {
    }

    /**
     * The scope that applies everywhere.
     *
     * @return self
     */
    public static function everywhere(): self {
        return new self('', '', 0);
    }

    /**
     * A scope for one object. Without a component this is the scope "everywhere".
     *
     * @param string $component
     * @param string $area
     * @param int $instanceid
     * @return self
     */
    public static function of(string $component, string $area, int $instanceid): self {
        if ($component === '') {
            return self::everywhere();
        }
        return new self($component, $area, $instanceid);
    }

    /**
     * Builds a scope from the array form callers use.
     *
     * @param array $scope keys component, area, instanceid; missing keys count as empty
     * @return self
     */
    public static function from_array(array $scope): self {
        return self::of(
            (string)($scope['component'] ?? ''),
            (string)($scope['area'] ?? ''),
            (int)($scope['instanceid'] ?? 0)
        );
    }

    /**
     * Does the scope apply everywhere?
     *
     * @return bool
     */
    public function is_everywhere(): bool {
        return $this->component === '';
    }

    /**
     * Is this the same scope as another one?
     *
     * @param self $other
     * @return bool
     */
    public function equals(self $other): bool {
        return $this->component === $other->component
            && $this->area === $other->area
            && $this->instanceid === $other->instanceid;
    }

    /**
     * The array form, as it is stored.
     *
     * @return array component, area, instanceid
     */
    public function to_array(): array {
        return ['component' => $this->component, 'area' => $this->area, 'instanceid' => $this->instanceid];
    }
}
