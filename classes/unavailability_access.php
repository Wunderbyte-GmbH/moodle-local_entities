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

namespace local_entities;

/**
 * Who may manage the unavailability of which entity.
 *
 * - A person may always manage the own unavailability. Being created as a person is enough, no role
 *   is needed.
 * - The right local/entities:manageunavailability opens every entity: other persons, places and
 *   equipment.
 * - The right local/entities:edit also opens places and equipment, but not persons, as the
 *   unavailability of a person is personal data.
 *
 * The self-editing can be locked per type of entity in the settings. Then only the right
 * local/entities:manageunavailability changes unavailability of that type, everybody else may only look at what they
 * could see before.
 *
 * Guests and users that are not logged in are never allowed.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unavailability_access {
    /** @var string Right to manage the unavailability of every entity. */
    public const CAP_ANY = 'local/entities:manageunavailability';

    /** @var string Right to edit entities, which includes places and equipment. */
    public const CAP_EDIT = 'local/entities:edit';

    /**
     * May the user manage the unavailability of every entity?
     *
     * @param int|null $userid null for the current user
     * @return bool
     */
    public static function can_manage_any(?int $userid = null): bool {
        $userid = self::resolve_userid($userid);
        if ($userid === 0) {
            return false;
        }
        return has_capability(self::CAP_ANY, \context_system::instance(), $userid);
    }

    /**
     * Is the self-editing of the unavailability of a type of entity locked?
     *
     * @param string $entitytype location, equipment or person, empty counts as location
     * @return bool
     */
    public static function is_self_edit_locked(string $entitytype): bool {
        return !empty(get_config('local_entities', 'unavailabilitylock_' . ($entitytype === '' ? 'location' : $entitytype)));
    }

    /**
     * May the user see the unavailability of an entity? The lock does not matter for this.
     *
     * @param \stdClass $entity record of local_entities with entitytype and userid
     * @param int|null $userid null for the current user
     * @return bool
     */
    public static function can_view_entity(\stdClass $entity, ?int $userid = null): bool {
        $userid = self::resolve_userid($userid);
        if ($userid === 0) {
            return false;
        }

        if (self::can_manage_any($userid)) {
            return true;
        }

        if (($entity->entitytype ?? '') === persons::TYPE) {
            return (int)($entity->userid ?? 0) === $userid;
        }
        return has_capability(self::CAP_EDIT, \context_system::instance(), $userid);
    }

    /**
     * May the user manage the unavailability of an entity?
     *
     * Everybody who may see it, unless the type of the entity is locked: then only the right for all entities counts.
     *
     * @param \stdClass $entity record of local_entities with entitytype and userid
     * @param int|null $userid null for the current user
     * @return bool
     */
    public static function can_manage_entity(\stdClass $entity, ?int $userid = null): bool {
        $userid = self::resolve_userid($userid);
        if ($userid === 0) {
            return false;
        }

        if (self::can_manage_any($userid)) {
            return true;
        }

        if (self::is_self_edit_locked((string)($entity->entitytype ?? ''))) {
            return false;
        }

        return self::can_view_entity($entity, $userid);
    }

    /**
     * Throws an exception if the current user may not see the unavailability of the entity.
     *
     * @param \stdClass $entity record of local_entities with entitytype and userid
     * @return void
     * @throws \required_capability_exception
     */
    public static function require_view_entity(\stdClass $entity): void {
        if (self::can_view_entity($entity)) {
            return;
        }
        $capability = ($entity->entitytype ?? '') === persons::TYPE ? self::CAP_ANY : self::CAP_EDIT;
        throw new \required_capability_exception(\context_system::instance(), $capability, 'nopermissions', '');
    }

    /**
     * Throws an exception if the current user may not manage the unavailability of the entity.
     *
     * @param \stdClass $entity record of local_entities with entitytype and userid
     * @return void
     * @throws \required_capability_exception
     */
    public static function require_manage_entity(\stdClass $entity): void {
        if (self::can_manage_entity($entity)) {
            return;
        }
        $type = (string)($entity->entitytype ?? '');
        $capability = $type === persons::TYPE || self::is_self_edit_locked($type) ? self::CAP_ANY : self::CAP_EDIT;
        throw new \required_capability_exception(\context_system::instance(), $capability, 'nopermissions', '');
    }

    /**
     * Is the page offered to the user at all, for example as a link in a menu?
     *
     * That is the case for persons and for everyone who may manage other entities. The rights are
     * checked first, as they come from memory, and only then the database is asked for the person.
     *
     * @param int|null $userid null for the current user
     * @return bool
     */
    public static function can_open_page(?int $userid = null): bool {
        $userid = self::resolve_userid($userid);
        if ($userid === 0) {
            return false;
        }

        $context = \context_system::instance();
        return has_capability(self::CAP_ANY, $context, $userid)
            || has_capability(self::CAP_EDIT, $context, $userid)
            || persons::get_entityid_for_user($userid) !== null;
    }

    /**
     * Resolves the user to check. Returns 0 for guests and users that are not logged in.
     *
     * @param int|null $userid
     * @return int
     */
    public static function resolve_userid(?int $userid): int {
        global $USER;

        $userid = $userid ?? (int)($USER->id ?? 0);
        if ($userid <= 0 || isguestuser($userid)) {
            return 0;
        }
        return $userid;
    }
}
