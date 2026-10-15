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

use advanced_testcase;
use local_entities_generator;

/**
 * Tests for the search_entities and get_entity_calendardata external services.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_entities\external\search_entities
 * @covers \local_entities\external\get_entity_calendardata
 */
final class external_search_calendar_test extends advanced_testcase {
    /**
     * Create an entity of a given type and return its id.
     *
     * @param string $name
     * @param string $shortname
     * @param string $entitytype
     * @return int
     */
    private function make_entity(string $name, string $shortname, string $entitytype = 'location'): int {
        /** @var local_entities_generator $gen */
        $gen = $this->getDataGenerator()->get_plugin_generator('local_entities');
        return (int)$gen->create_entities([
            'name' => $name,
            'shortname' => $shortname,
            'entitytype' => $entitytype,
        ]);
    }

    /**
     * search_entities returns matching location entities and excludes equipment + non-matches.
     */
    public function test_search_entities_returns_locations_only(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $room = $this->make_entity('Conference Room', 'confroom', 'location');
        $equipment = $this->make_entity('Conference Beamer', 'confbeamer', 'equipment');
        $other = $this->make_entity('Storage', 'storage', 'location');

        $result = external\search_entities::execute('Conference');

        $this->assertArrayHasKey('list', $result);
        $this->assertArrayHasKey($room, $result['list'], 'Matching location must be returned.');
        $this->assertArrayNotHasKey($equipment, $result['list'], 'Equipment must be excluded from the search.');
        $this->assertArrayNotHasKey($other, $result['list'], 'Non-matching name must not be returned.');
        $this->assertSame('Conference Room', (string)$result['list'][$room]->name);
    }

    /**
     * search_entities enforces local/entities:view.
     */
    public function test_search_entities_requires_capability(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());

        // Capability local/entities:view is granted to all authenticated users by default — prohibit it to assert the gate.
        $userrole = (int)$DB->get_field('role', 'id', ['shortname' => 'user'], MUST_EXIST);
        assign_capability('local/entities:view', CAP_PROHIBIT, $userrole, \context_system::instance()->id, true);
        accesslib_clear_all_caches_for_unit_testing();

        $this->expectException(\required_capability_exception::class);
        external\search_entities::execute('anything');
    }

    /**
     * Function get_entity_calendardata returns valid JSON and an empty error for an entity without dates.
     */
    public function test_get_entity_calendardata_returns_valid_json(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $id = $this->make_entity('Calendar Entity', 'calentity', 'location');
        $result = external\get_entity_calendardata::execute($id);

        $this->assertArrayHasKey('json', $result);
        $this->assertArrayHasKey('error', $result);
        $this->assertSame('', (string)$result['error']);
        $decoded = json_decode((string)$result['json']);
        $this->assertIsArray($decoded, 'Calendar payload must be a JSON array.');
        $this->assertSame([], $decoded, 'A fresh entity has no calendar entries.');
    }

    /**
     * Function get_entity_calendardata must set the page context itself.
     *
     * The date providers push booking option customfields through format_text(), and filters such as
     * filter_emoticon ask $PAGE for the theme. With an unset context that throws a coding exception
     * inside the webservice (AJAX_SCRIPT + developer debugging), which the calendar JS swallows in its
     * empty .fail() handler — the calendar then stays blank with nothing in the browser console.
     *
     * The assertion has to read moodle_page::$_context by reflection: reading $PAGE->context would
     * lazily fall back to the system context, and under CLI (which is how PHPUnit runs) that fallback
     * is silent, so the missing set_context() call would go unnoticed.
     */
    public function test_get_entity_calendardata_sets_page_context(): void {
        global $PAGE;
        $this->resetAfterTest();
        $this->setAdminUser();

        $id = $this->make_entity('Context Entity', 'ctxentity', 'location');

        // A pristine page, so the context can only come from the webservice itself.
        $PAGE = new \moodle_page();
        $context = new \ReflectionProperty(\moodle_page::class, '_context');
        $context->setAccessible(true);
        $this->assertNull($context->getValue($PAGE), 'Precondition: the fresh page has no context.');

        external\get_entity_calendardata::execute($id);

        $this->assertInstanceOf(
            \context_system::class,
            $context->getValue($PAGE),
            'The webservice must set the system context, otherwise format_text() fails in the filter chain.'
        );
    }

    /**
     * get_entity_calendardata is declared with loginrequired = false and the entity detail page is
     * shown to logged out visitors, so the service must not gate itself behind a login. Guards against
     * swapping the set_context() call for validate_context(), which calls require_login() internally.
     */
    public function test_get_entity_calendardata_works_when_logged_out(): void {
        $this->resetAfterTest();
        // It is necessary to have elevated privileges to create an entity.
        $this->setAdminUser();
        $id = $this->make_entity('Public Entity', 'publicentity', 'location');

        // Force user logout.
        require_logout();
        $result = external\get_entity_calendardata::execute($id);

        $this->assertSame('', (string)$result['error']);
        $this->assertSame([], json_decode((string)$result['json']));
    }

    /**
     * filter_dates_for_user keeps visible dates, shows an invisible date only to a user holding
     * the named capability (greyed and prefixed, on a copy), and shows an invisible date without
     * a capability to nobody.
     *
     * @covers \local_entities\entities::filter_dates_for_user
     */
    public function test_filter_dates_for_user(): void {
        $this->resetAfterTest();

        $visible = new local\entities\entitydate(1, 'local_entities', 'test', 'Visible', 100, 200, 0);
        $hidden = new local\entities\entitydate(2, 'local_entities', 'test', 'Hidden', 100, 200, 0, null, '#4285F4');
        $hidden->set_invisible('moodle/site:config', \context_system::instance()->id);
        $hiddenforall = new local\entities\entitydate(3, 'local_entities', 'test', 'Nobody', 100, 200, 0);
        $hiddenforall->invisible = true;
        $dates = [$visible, $hidden, $hiddenforall];

        $this->setAdminUser();
        $foradmin = entities::filter_dates_for_user($dates);
        $this->assertSame([1, 2], array_map(fn($d) => $d->itemid, $foradmin));
        $this->assertSame('[Invisible] Hidden', $foradmin[1]->name);
        $this->assertSame('#808080', $foradmin[1]->bgcolor);
        $this->assertSame('Hidden', $hidden->name, 'The cached object must not be changed.');
        $this->assertSame('#4285F4', $hidden->bgcolor);

        $student = $this->getDataGenerator()->create_user();
        $this->setUser($student);
        $forstudent = entities::filter_dates_for_user($dates);
        $this->assertSame([1], array_map(fn($d) => $d->itemid, $forstudent));

        // An explicit user id works from any session.
        $this->setAdminUser();
        $this->assertSame([1], array_map(fn($d) => $d->itemid, entities::filter_dates_for_user($dates, $student->id)));

        require_logout();
        $this->assertSame([1], array_map(fn($d) => $d->itemid, entities::filter_dates_for_user($dates)));
    }

    /**
     * The calendar of an entity shows the dates of an invisible booking option only to users who
     * may see invisible options, regardless of who filled the entity dates cache: filled by an
     * admin, a student and a logged out visitor still do not get the invisible date; filled by a
     * student, the admin still gets it. Links never carry the id of the user who filled the cache.
     *
     * @covers \local_entities\external\get_entity_calendardata
     * @covers \local_entities\entities::filter_dates_for_user
     */
    public function test_get_entity_calendardata_invisible_options_per_viewer(): void {
        global $CFG;
        if (!\core_component::get_component_directory('mod_booking')) {
            $this->markTestSkipped('mod_booking is the provider of invisible dates.');
        }
        $this->resetAfterTest();
        $this->setAdminUser();
        require_once($CFG->dirroot . '/mod/booking/lib.php');

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $booking = $this->getDataGenerator()->create_module('booking', [
            'course' => $course->id,
            'name' => 'Calendar booking',
            'eventtype' => 'Test event',
            'bookedtext' => ['text' => 'text'],
            'waitingtext' => ['text' => 'text'],
            'notifyemail' => ['text' => 'text'],
            'statuschangetext' => ['text' => 'text'],
            'deletedtext' => ['text' => 'text'],
            'pollurltext' => ['text' => 'text'],
            'pollurlteacherstext' => ['text' => 'text'],
            'notificationtext' => ['text' => 'text'],
            'userleave' => ['text' => 'text'],
            'tags' => '',
            'showviews' => ['mybooking,myoptions,optionsiamresponsiblefor,showall,showactive,myinstitution'],
        ]);
        $entityid = $this->make_entity('Seminar room', 'seminarroom', 'location');

        /** @var \mod_booking_generator $bookinggenerator */
        $bookinggenerator = $this->getDataGenerator()->get_plugin_generator('mod_booking');
        foreach (['Public course' => 0, 'Secret course' => 1] as $text => $invisible) {
            $record = new \stdClass();
            $record->bookingid = $booking->id;
            $record->text = $text;
            $record->invisible = $invisible;
            $record->chooseorcreatecourse = 1;
            $record->courseid = $course->id;
            $record->description = $text;
            $record->optiondateid_1 = "0";
            $record->daystonotify_1 = "0";
            $record->coursestarttime_1 = strtotime('2050-03-01 10:00:00');
            $record->courseendtime_1 = strtotime('2050-03-01 12:00:00');
            $record->local_entities_entityid_0 = $entityid;
            $record->local_entities_entityid_1 = $entityid;
            $record->local_entities_entityarea_1 = "optiondate";
            $record->er_saverelationsforoptiondates = 1;
            $bookinggenerator->create_option($record);
        }
        \mod_booking\singleton_service::destroy_instance();

        $titles = function (array $result): array {
            $this->assertSame('', (string)$result['error']);
            $events = json_decode((string)$result['json']);
            $this->assertIsArray($events);
            $titles = array_map(fn($event) => $event->title, $events);
            sort($titles);
            return $titles;
        };
        $cache = \cache::make('local_entities', 'entitydates');

        // Admin fills the cache.
        $cache->purge();
        $adminevents = json_decode((string)external\get_entity_calendardata::execute($entityid)['json']);
        $this->assertSame(
            ['Public course', '[Invisible] Secret course'],
            $titles(['error' => '', 'json' => json_encode($adminevents)])
        );
        foreach ($adminevents as $event) {
            $this->assertStringNotContainsString('userid=', $event->url, 'The link must not name a user.');
            $this->assertStringContainsString('optionview.php', $event->url);
        }
        $this->assertNotFalse($cache->get($entityid), 'Precondition: the admin filled the cache.');

        $this->setUser($student);
        $this->assertSame(['Public course'], $titles(external\get_entity_calendardata::execute($entityid)));
        require_logout();
        $this->assertSame(['Public course'], $titles(external\get_entity_calendardata::execute($entityid)));

        // Student fills the cache.
        $cache->purge();
        $this->setUser($student);
        $this->assertSame(['Public course'], $titles(external\get_entity_calendardata::execute($entityid)));
        $this->assertNotFalse($cache->get($entityid), 'Precondition: the student filled the cache.');
        $this->setAdminUser();
        $this->assertSame(
            ['Public course', '[Invisible] Secret course'],
            $titles(external\get_entity_calendardata::execute($entityid))
        );
    }
}
