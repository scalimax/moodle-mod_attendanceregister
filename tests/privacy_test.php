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
 * Privacy tests.
 *
 * @package mod_attendanceregister
 * @author  Lorenzo Nicora <fad@nicus.it>
 * @author  Renaat Debleu <rdebleu@eWallah.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \core_privacy\tests\provider_testcase;
use \mod_attendanceregister\privacy\provider;

/**
 * Unit tests privacy
 *
 * @package mod_attendanceregister
 * @author  Lorenzo Nicora <fad@nicus.it>
 * @author  Renaat Debleu <rdebleu@eWallah.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_attendanceregister_privacy_testcase extends provider_testcase {

    /** stdClass context context */
    private $context;

    /** stdClass user user */
    private $user;

    /**
     * Basic setup for these tests.
     */
    public function setUp() {
        global $DB;
        $this->resetAfterTest();
        $dg = $this->getDataGenerator();
        $course = $dg->create_course();
        $cm = $dg->create_module('attendanceregister', ['course' => $course->id]);
        $cm2 = $dg->create_module('attendanceregister', ['course' => $course->id]);
        $this->context = \context_module::instance($cm->cmid);
        $this->user = $dg->create_user();
        $dg->enrol_user($this->user->id, $course->id);

        $session = new stdClass();
        $session->register = $cm->id;
        $session->userid = $this->user->id;
        $session->login = time() - 1000;
        $session->logout = time();
        $session->duration = 1000;
        $session->onlinesess = true;
        $session->refcourse = null;
        $session->comments = null;
        $DB->insert_record('attendanceregister_session', $session);

        $aggregate = new stdClass();
        $aggregate->register = $cm->id;
        $aggregate->userid = $this->user->id;
        $aggregate->onlinesess = 1;
        $aggregate->refcourse = null;
        $aggregate->duration = 0;
        $aggregate->total = 1;
        $aggregate->grandtotal = 0;
        $DB->insert_record('attendanceregister_aggregate', $aggregate);

        $lock = new stdClass();
        $lock->register = $cm->id;
        $lock->userid = $this->user->id;
        $lock->takenon = time();
        $DB->insert_record('attendanceregister_lock', $lock);

        $user = $dg->create_user();
        $dg->enrol_user($user->id, $course->id);
        $session = new stdClass();
        $session->register = $cm->id;
        $session->userid = $user->id;
        $session->login = time() - 5000;
        $session->logout = time();
        $session->duration = 5000;
        $session->onlinesess = true;
        $session->refcourse = null;
        $session->comments = null;
        $DB->insert_record('attendanceregister_session', $session);
        $session->register = $cm2->id;
        $DB->insert_record('attendanceregister_session', $session);

        $aggregate = new stdClass();
        $aggregate->register = $cm->id;
        $aggregate->userid = $user->id;
        $aggregate->onlinesess = 1;
        $aggregate->refcourse = null;
        $aggregate->duration = 20;
        $aggregate->total = 1;
        $aggregate->grandtotal = 0;
        $DB->insert_record('attendanceregister_aggregate', $aggregate);
        $aggregate->register = $cm2->id;
        $DB->insert_record('attendanceregister_aggregate', $aggregate);
        $lock = new stdClass();
        $lock->register = $cm->id;
        $lock->userid = $user->id;
        $lock->takenon = time();
        $DB->insert_record('attendanceregister_lock', $lock);
    }

    /**
     * Test returning metadata.
     */
    public function test_get_metadata() {
        $collection = new \core_privacy\local\metadata\collection('mod_attendanceregister');
        $collection = mod_attendanceregister\privacy\provider::get_metadata($collection);
        $this->assertNotEmpty($collection);
    }
    /**
     * Test getting the context for the user ID related to this plugin.
     */
    public function test_get_contexts_for_userid() {
        $contextlist = \mod_attendanceregister\privacy\provider::get_contexts_for_userid($this->user->id);
        $this->assertNotEmpty($contextlist);
    }

    /**
     * Check the exporting of sessions for a user.
     */
    public function test_export_sessions() {
        $this->export_context_data_for_user($this->user->id, $this->context, 'mod_attendanceregister');
        $writer = \core_privacy\local\request\writer::with_context($this->context);
        $this->assertTrue($writer->has_any_data());
    }

    /**
     * Tests the deletion of all sessions.
     */
    public function test_delete_sessions_for_all_users_in_context() {
        global $DB;
        $this->assertEquals(3, $DB->count_records('attendanceregister_session'));
        \mod_attendanceregister\privacy\provider::delete_data_for_all_users_in_context($this->context);
        $list = new core_privacy\tests\request\approved_contextlist($this->user, 'mod_attendanceregister', []);
        $this->assertEmpty($list);
        $this->assertEquals(1, $DB->count_records('attendanceregister_session'));
        $this->assertEquals(1, $DB->count_records('attendanceregister_aggregate'));
        $this->assertEquals(0, $DB->count_records('attendanceregister_lock'));
    }

    /**
     * Tests deletion of sessions for a specified user.
     */
    public function test_delete_sessions_for_user() {
        global $DB;
        $list = new core_privacy\tests\request\approved_contextlist($this->user, 'mod_attendanceregister', [$this->context->id]);
        $this->assertNotEmpty($list);
        \mod_attendanceregister\privacy\provider::delete_data_for_user($list);
        $this->export_context_data_for_user($this->user->id, $this->context, 'mod_attendanceregister');
        $writer = \core_privacy\local\request\writer::with_context($this->context);
        $this->assertTrue($writer->has_any_data());
        $this->assertEquals(2, $DB->count_records('attendanceregister_session'));
        $this->assertEquals(2, $DB->count_records('attendanceregister_aggregate'));
        $this->assertEquals(1, $DB->count_records('attendanceregister_lock'));
    }

    /**
     * Tests get users in context.
     */
    public function test_get_users_in_context() {
        $userlist = new \core_privacy\local\request\userlist($this->context, 'mod_attendanceregister');
        provider::get_users_in_context($userlist);
        $this->assertCount(2, $userlist);
    }

    /**
     * Tests delete data for users.
     */
    public function test_delete_data_for_users_in_context() {
        $approved = new \core_privacy\local\request\approved_userlist($this->context, 'mod_attendanceregister', [$this->user->id]);
        provider::delete_data_for_users($approved);
        $userlist = new \core_privacy\local\request\userlist($this->context, 'mod_attendanceregister');
        provider::get_users_in_context($userlist);
        $this->assertCount(1, $userlist);
    }
}