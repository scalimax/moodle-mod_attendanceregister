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

/**
 * Unit tests privacy
 *
 * @package mod_attendanceregister
 * @author  Lorenzo Nicora <fad@nicus.it>
 * @author  Renaat Debleu <rdebleu@eWallah.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_attendanceregister_privacy_testcase extends provider_testcase {

    /**
     * Test returning metadata.
     */
    public function test_get_metadata() {
        $this->resetAfterTest(true);
        $dg = $this->getDataGenerator();
        $fg = $dg->get_plugin_generator('mod_attendanceregister');

        $c1 = $dg->create_course();
        $c2 = $dg->create_course();
        $cm1 = $dg->create_module('attendanceregister', ['course' => $c1]);
        $co1 = \context_module::instance($cm1->cmid);
        $cm2 = $dg->create_module('attendanceregister', ['course' => $c2]);
        $co2 = \context_module::instance($cm2->cmid);
        $u1 = $dg->create_user();
        $u2 = $dg->create_user();
        $dg->enrol_user($u1->id, $c1->id);
        $dg->enrol_user($u2->id, $c1->id);
        $dg->enrol_user($u1->id, $c2->id);
        
        $collection = new \core_privacy\local\metadata\collection('mod_attendanceregister');
        $collection = mod_attendanceregister\privacy\provider::get_metadata($collection);
        $this->assertNotEmpty($collection);
    }
    /**
     * Test getting the context for the user ID related to this plugin.
     */
    public function test_get_contexts_for_userid() {
        $this->resetAfterTest(true);
        $dg = $this->getDataGenerator();
        $fg = $dg->get_plugin_generator('mod_attendanceregister');

        $c1 = $dg->create_course();
        $c2 = $dg->create_course();
        $cm1 = $dg->create_module('attendanceregister', ['course' => $c1]);
        $co1 = \context_module::instance($cm1->cmid);
        $cm2 = $dg->create_module('attendanceregister', ['course' => $c2]);
        $co2 = \context_module::instance($cm2->cmid);
        $u1 = $dg->create_user();
        $u2 = $dg->create_user();
        $dg->enrol_user($u1->id, $c1->id);
        $dg->enrol_user($u2->id, $c1->id);
        $dg->enrol_user($u1->id, $c2->id);
        
        $contextlist = \mod_attendanceregister\privacy\provider::get_contexts_for_userid($u1->id);
        $this->assertNotEmpty($contextlist);
        $contextlist = \mod_attendanceregister\privacy\provider::get_contexts_for_userid($u2->id);
        $this->assertNotEmpty($contextlist);
    }

    /**
     * Check the exporting of sessions for a user.
     */
    public function test_export_sessions() {
        $this->resetAfterTest(true);
        $dg = $this->getDataGenerator();
        $fg = $dg->get_plugin_generator('mod_attendanceregister');

        $c1 = $dg->create_course();
        $c2 = $dg->create_course();
        $cm1 = $dg->create_module('attendanceregister', ['course' => $c1]);
        $co1 = \context_module::instance($cm1->cmid);
        $cm2 = $dg->create_module('attendanceregister', ['course' => $c2]);
        $co2 = \context_module::instance($cm2->cmid);
        $u1 = $dg->create_user();
        $u2 = $dg->create_user();
        $dg->enrol_user($u1->id, $c1->id);
        $dg->enrol_user($u2->id, $c1->id);
        $dg->enrol_user($u1->id, $c2->id);
        $this->export_context_data_for_user($u1->id, $co1, 'mod_attendanceregister');
        $writer = \core_privacy\local\request\writer::with_context($co1);
        $this->assertTrue($writer->has_any_data());
        $this->export_context_data_for_user($u2->id, $co1, 'mod_attendanceregister');
        $writer = \core_privacy\local\request\writer::with_context($co1);
        $this->assertTrue($writer->has_any_data());
        $this->export_context_data_for_user($u2->id, $co2, 'mod_attendanceregister');
        $writer = \core_privacy\local\request\writer::with_context($co2);
        $this->assertTrue($writer->has_any_data());
    }

    /**
     * Tests the deletion of all sessions.
     */
    public function test_delete_sessions_for_all_users_in_context() {
        $this->resetAfterTest(true);
        $dg = $this->getDataGenerator();
        $fg = $dg->get_plugin_generator('mod_attendanceregister');

        $c1 = $dg->create_course();
        $c2 = $dg->create_course();
        $cm1 = $dg->create_module('attendanceregister', ['course' => $c1]);
        $co1 = \context_module::instance($cm1->cmid);
        $cm2 = $dg->create_module('attendanceregister', ['course' => $c2]);
        $co2 = \context_module::instance($cm2->cmid);
        $u1 = $dg->create_user();
        $u2 = $dg->create_user();
        $dg->enrol_user($u1->id, $c1->id);
        $dg->enrol_user($u2->id, $c1->id);
        $dg->enrol_user($u1->id, $c2->id);
        \mod_attendanceregister\privacy\provider::delete_data_for_all_users_in_context($co1);
        $list1 = new core_privacy\tests\request\approved_contextlist($u1, 'mod_attendanceregister', []);
        $list2 = new core_privacy\tests\request\approved_contextlist($u2, 'mod_attendanceregister', []);
        $this->assertEmpty($list1);
        $this->assertEmpty($list2);
    }

    /**
     * Tests deletion of sessions for a specified user.
     */
    public function test_delete_sessions_for_user() {
        $dg = $this->getDataGenerator();
        $c1 = $dg->create_course();
        $cm1 = $dg->create_module('attendanceregister', ['course' => $c1]);
        $co1 = \context_module::instance($cm1->cmid);
        $u1 = $dg->create_user();
        $dg->enrol_user($u1->id, $c1->id);
        $list = new core_privacy\tests\request\approved_contextlist($u1, 'mod_attendanceregister', []);
        \mod_attendanceregister\privacy\provider::delete_data_for_user($list);
        $this->export_context_data_for_user($u1->id, $co1, 'mod_attendanceregister');
        $writer = \core_privacy\local\request\writer::with_context($co1);
        $this->assertTrue($writer->has_any_data());
    }
}