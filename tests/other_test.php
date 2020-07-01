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
 * PHPUnit generator tests
 *
 * @package mod_attendanceregister
 * @copyright 2016 CINECA
 * @author  Lorenzo Nicora <fad@nicus.it>
 * @author  Renaat Debleu <info@eWallah.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * other tests
 *
 * @package mod_attendanceregister
 * @copyright 2016 CINECA
 * @author  Lorenzo Nicora <fad@nicus.it>
 * @author  Renaat Debleu <info@eWallah.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_attendanceregister_other_testcase extends advanced_testcase {

    /**
     * Test the user capabilites
     */
    public function test_user_capabilites() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/attendanceregister/locallib.php');
        require_once($CFG->dirroot . '/mod/attendanceregister/lib.php');
        $this->resetAfterTest(true);
        $dg = $this->getDataGenerator();
        $course = $dg->create_course();
        $context = \context_course::instance($course->id);
        $uc = new attendanceregister_user_capablities($context);
        $userid = 1;
        $this->AssertFalse($uc->canview($userid));
        $this->AssertFalse($uc->canddeletesession($userid));
        $this->AssertFalse($uc->canaddsession(null, $userid));
        $userid = $dg->create_user()->id;
        $dg->enrol_user($userid, $course->id);
        $this->AssertFalse($uc->canview($userid));
        $this->AssertFalse($uc->canddeletesession($userid));
        $this->AssertFalse($uc->canaddsession(null, $userid));
        $this->setAdminUser();
        $this->AssertFalse($uc->canview($userid));
        $this->AssertFalse($uc->canddeletesession($userid));
        $this->AssertFalse($uc->canaddsession(null, $userid));
        $cm = $dg->create_module('attendanceregister', ['course' => $course->id]);
        $context = \context_module::instance($cm->cmid);
    }

    /**
     * Test the events
     */
    public function test_events() {
        $this->resetAfterTest(true);
        $dg = $this->getDataGenerator();
        $course = $dg->create_course();
        $userid = $dg->create_user()->id;
        $dg->enrol_user($userid, $course->id);
        $ar = $dg->create_module('attendanceregister', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('attendanceregister', $ar->id, $course->id, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $para = ['context' => $context, 'objectid' => $cm->id];
        $event = \mod_attendanceregister\event\course_module_viewed::create($para);
        $event->add_record_snapshot('course_modules', $cm);
        $event->add_record_snapshot('course', $course);
        $event->trigger();
        \mod_attendanceregister\event\mod_attendance_recalculation::create($para)->trigger();
        \mod_attendanceregister\event\participants_attendance_report_viewed::create($para)->trigger();
        \mod_attendanceregister\event\user_attendance_addoffline::create($para)->trigger();
        \mod_attendanceregister\event\user_attendance_deloffline::create($para)->trigger();
        \mod_attendanceregister\event\user_attendance_details_viewed::create($para)->trigger();

        $context = \context_course::instance($course->id);
        \mod_attendanceregister\event\course_module_instance_list_viewed::create(['context' => $context])->trigger();
    }

    /**
     * Test the tasks
     */
    public function test_task() {
        $this->resetAfterTest(true);
        $dg = $this->getDataGenerator();
        $course = $dg->create_course();
        $userid = $dg->create_user()->id;
        $dg->enrol_user($userid, $course->id);
        $dg->create_module('attendanceregister', ['course' => $course->id]);
        $task = new \mod_attendanceregister\task\cron_task();
        $this->AssertEquals('Attendance Cron', $task->get_name());
        $this->AssertEquals(null, $task->execute());
    }

    /**
     * Test several logins
     */
    public function test_logins() {
        global $CFG, $DB;

        $this->resetAfterTest(true);
        $CFG->enablecompletion = 1;
        $dg = $this->getDataGenerator();
        $course = $dg->create_course(['enablecompletion' => 1]);
        $user = $dg->create_user();
        $dg->enrol_user($user->id, $course->id);
        $page = $dg->create_module('page', ['course' => $course->id], ['completion' => 2, 'completionview' => 1]);
        $dg->create_module('attendanceregister', ['course' => $course->id]);
        $context = context_module::instance($page->cmid);
        $cm = get_coursemodule_from_instance('page', $page->id);
        $this->setUser($user);
        $this->assertTrue(isloggedin());

        $i = 0;
        while ($i < 10 ) {
            page_view($page, $course, $cm, $context);
            $event = \mod_page\event\course_module_instance_list_viewed::create(['context' => context_course::instance($course->id)]);
            $event->add_record_snapshot('course', $course);
            $event->trigger();
            $i++;
        }
        $this->setAdminUser();
        $task = new \mod_attendanceregister\task\cron_task();
        $this->AssertEquals('Attendance Cron', $task->get_name());
        $this->AssertEquals(null, $task->execute());
        $task->execute();
        $records = $DB->get_records('attendanceregister');
        foreach ($records as $record) {
            $class1 = new \attendanceregister_tracked_courses($record);
            $this->assertNotEmpty($class1->html_table());
            $usercaps = new \attendanceregister_user_capablities(context_course::instance($course->id));
            $class3 = new \attendanceregister_user_sessions($record, $user->id, $usercaps);
            $this->assertNotEmpty($class3->html_table());
            $class4 = new \attendanceregister_user_aggregates($record, $user->id, $class3);
            $this->assertNotEmpty($class4->html_table());
            $class5 = new \attendanceregister_user_aggregates_summary();
            $this->assertNotEmpty($class5);
        }
    }

    /**
     * Test backup
     */
    public function test_backup() {
        global $CFG, $DB, $USER;
        $this->resetAfterTest(true);
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        $dg = $this->getDataGenerator();
        $courseid = $dg->create_course()->id;
        $userid = $dg->create_user()->id;
        $dg->enrol_user($userid, $courseid);
        $cm = $dg->create_module('attendanceregister', ['course' => $courseid]);
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
        $DB->insert_record('attendanceregister_lock', (object) ['register' => $cm->id, 'userid' => $userid, 'takenon' => time()]);
        $aggregate = new stdClass();
        $aggregate->register = $cm->id;
        $aggregate->userid = $userid;
        $aggregate->onlinesess = 1;
        $aggregate->refcourse = null;
        $aggregate->duration = 30;
        $aggregate->total = 1;
        $aggregate->grandtotal = 0;
        $DB->insert_record('attendanceregister_aggregate', $aggregate);
        $aggregate = new stdClass();
        $aggregate->register = $cm->id;
        $aggregate->userid = $userid;
        $aggregate->onlinesess = 1;
        $aggregate->refcourse = null;
        $aggregate->duration = 300;
        $aggregate->total = 1;
        $aggregate->grandtotal = 1;
        $DB->insert_record('attendanceregister_aggregate', $aggregate);
        $this->setAdminUser();
        $task = new \mod_attendanceregister\task\cron_task();
        $task->execute();
        $bc = new backup_controller(backup::TYPE_1COURSE, $courseid, backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO, backup::MODE_IMPORT, $USER->id);
        $backupid = $bc->get_backupid();
        $bc->execute_plan();
        $bc->destroy();
        unset($bc);
        $courseid = $dg->create_course()->id;
        $rc = new restore_controller($backupid, $courseid, backup::INTERACTIVE_NO,
            backup::MODE_IMPORT, $USER->id, backup::TARGET_CURRENT_ADDING);
        $rc->execute_precheck();
        $rc->execute_plan();
        $rc->destroy();
        unset($rc);
    }
}
