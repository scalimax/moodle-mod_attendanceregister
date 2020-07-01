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
 * Classes tests.
 *
 * @package mod_attendanceregister
 * @copyright 2016 CINECA
 * @author  Lorenzo Nicora <fad@nicus.it>
 * @author  Renaat Debleu <info@eWallah.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests classes
 *
 * @package mod_attendanceregister
 * @copyright 2016 CINECA
 * @author  Lorenzo Nicora <fad@nicus.it>
 * @author  Renaat Debleu <info@eWallah.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_attendanceregister_classes_testcase extends advanced_testcase {

    /** @var stdClass context context */
    private $context;

    /** @var int userid */
    private $userid;

    /**
     * Basic setup for these tests.
     */
    public function setUp() {
        global $DB;
        $this->resetAfterTest();
        $dg = $this->getDataGenerator();
        $courseid = $dg->create_course()->id;
        $this->userid = $dg->create_user()->id;
        $dg->enrol_user($this->userid, $courseid);
        $cm = $dg->create_module('attendanceregister', ['course' => $courseid]);
        $dg->create_module('attendanceregister', ['course' => $courseid]);
        $this->context = \context_module::instance($cm->cmid);

        $lock = new stdClass();
        $lock->register = $cm->id;
        $lock->userid = $this->userid;
        $lock->takenon = time();
        $DB->insert_record('attendanceregister_lock', $lock);
        $aggregate = new stdClass();
        $aggregate->register = $cm->id;
        $aggregate->userid = $this->userid;
        $aggregate->onlinesess = 1;
        $aggregate->refcourse = null;
        $aggregate->duration = 0;
        $aggregate->total = 1;
        $aggregate->grandtotal = 0;
        $DB->insert_record('attendanceregister_aggregate', $aggregate);
        $session = new stdClass();
        $session->register = $cm->id;
        $session->userid = $this->userid;
        $session->login = time() - 1000;
        $session->logout = time();
        $session->duration = 1000;
        $session->onlinesess = true;
        $session->refcourse = null;
        $session->comments = null;
        $DB->insert_record('attendanceregister_session', $session);
        $session = new stdClass();
        $session->register = $cm->id;
        $session->userid = $this->userid;
        $session->login = time() - 1000;
        $session->logout = time();
        $session->duration = 1000;
        $session->onlinesess = false;
        $session->refcourse = null;
        $session->comments = null;
        $DB->insert_record('attendanceregister_session', $session);

        $userid = $dg->create_user()->id;
        $dg->enrol_user($userid, $courseid);
        $session = new stdClass();
        $session->register = $cm->id;
        $session->userid = $userid;
        $session->login = time() - 5000;
        $session->logout = time();
        $session->duration = 5000;
        $session->onlinesess = true;
        $session->refcourse = $courseid;
        $session->comments = 'comment';
        $DB->insert_record('attendanceregister_session', $session);
        $session->register = $cm->id;
        $session->onlinesess = false;
        $DB->insert_record('attendanceregister_session', $session);

        $aggregate = new stdClass();
        $aggregate->register = $cm->id;
        $aggregate->userid = $userid;
        $aggregate->onlinesess = 1;
        $aggregate->refcourse = null;
        $aggregate->duration = 20;
        $aggregate->total = 1;
        $aggregate->grandtotal = 0;
        $DB->insert_record('attendanceregister_aggregate', $aggregate);
        $aggregate->register = $cm->id;
        $aggregate->onlinesess = 0;
        $DB->insert_record('attendanceregister_aggregate', $aggregate);
        $lock = new stdClass();
        $lock->register = $cm->id;
        $lock->userid = $userid;
        $lock->takenon = time();
        $DB->insert_record('attendanceregister_lock', $lock);
        $task = new \mod_attendanceregister\task\cron_task();
        $task->execute();
    }

    /**
     * Test tracked courses.
     */
    public function test_tracked_courses() {
        global $DB;
        $this->setAdminUser();
        $records = $DB->get_records('attendanceregister');
        foreach ($records as $record) {
            $class1 = new \attendanceregister_tracked_courses($record);
            $this->assertNotEmpty($class1->html_table());
            $usercaps = new \attendanceregister_user_capablities($this->context);
            $this->assertTrue($usercaps->canview($this->userid));
            $this->assertFalse($usercaps->canddeletesession($this->userid));
            $this->assertFalse($usercaps->canaddsession($record, $this->userid));
            $class2 = new \attendanceregister_tracked_users($record, $usercaps, 0);
            $this->assertNotEmpty($class2->html_table());
            $class3 = new \attendanceregister_user_sessions($record, $this->userid, $usercaps);
            $this->assertNotEmpty($class3->html_table());
            $class4 = new \attendanceregister_user_aggregates($record, $this->userid, $class3);
            $this->assertNotEmpty($class4->html_table());
            $class5 = new \attendanceregister_user_aggregates_summary();
            $this->assertNotEmpty($class5);
        }
        $this->setUser($this->userid);
        foreach ($records as $record) {
            $class1 = new \attendanceregister_tracked_courses($record);
            $this->assertNotEmpty($class1->html_table());
            $usercaps = new \attendanceregister_user_capablities($this->context);
            $this->assertTrue($usercaps->canview($this->userid));
            $this->assertTrue($usercaps->canddeletesession($this->userid));
            $this->assertTrue($usercaps->canaddsession($record, $this->userid));
            $class2 = new \attendanceregister_tracked_users($record, $usercaps, 0);
            $this->assertNotEmpty($class2->html_table());
            $class3 = new \attendanceregister_user_sessions($record, $this->userid, $usercaps);
            $this->assertNotEmpty($class3->html_table());
            $class4 = new \attendanceregister_user_aggregates($record, $this->userid, $class3);
            $this->assertNotEmpty($class4->html_table());
            $class5 = new \attendanceregister_user_aggregates_summary();
            $this->assertNotEmpty($class5);
        }
    }

}