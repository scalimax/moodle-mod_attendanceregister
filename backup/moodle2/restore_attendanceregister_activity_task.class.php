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
 * Restore activity structure task
 *
 * @package mod_attendanceregister
 * @author  Lorenzo Nicora <fad@nicus.it>
 * @author  Renaat Debleu <info@eWallah.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/attendanceregister/backup/moodle2/restore_attendanceregister_stepslib.php');

/**
 * Restore activity task
 *
 * @package mod_attendanceregister
 * @author  Lorenzo Nicora <fad@nicus.it>
 * @author  Renaat Debleu <info@eWallah.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_attendanceregister_activity_task extends restore_activity_task {
    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        $this->add_step(
           new restore_attendanceregister_activity_structure_step('attendanceregister_structure', 'attendanceregister.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    static public function define_decode_contents() {
        $contents = [];
        $contents[] = new restore_decode_content('attendanceregister', ['intro'], 'attendanceregister');
        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    static public function define_decode_rules() {
        $rules = [];
        $rules[] = new restore_decode_rule('ATTENDANCEREGISTERINDEX',
            '/mod/attendanceregister/index.php?id=$1', 'course');
        $rules[] = new restore_decode_rule('ATTENDANCEREGISTERVIEWBYID',
            '/mod/attendanceregister/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('ATTENDANCEREGISTERVIEWBYREGISTERID',
            '/mod/attendanceregister/view.php?a=$1', 'attendanceregister');
        $rules[] = new restore_decode_rule('ATTENDANCEREGISTERVIEWUSERBYID',
            '/mod/attendanceregister/view.php?id=$1&userid=$2', ['course_module', 'user']);
        $rules[] = new restore_decode_rule('ATTENDANCEREGISTERVIEWUSERBYREGISTERID',
            '/mod/attendanceregister/view.php?a=$1&userid=$2', ['attendanceregister', 'user']);
        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * choice logs. It must return one array
     * of {@link restore_log_rule} objects
     */
    static public function define_restore_log_rules() {
        $rules = [];
        $rules[] = new restore_log_rule('attendanceregister', ATTENDANCEREGISTER_LOGACTION_VIEW,
            'view.php?id={course_module}&userid={user}', '{attendanceregister}');
        $rules[] = new restore_log_rule('attendanceregister', ATTENDANCEREGISTER_LOGACTION_VIEW_ALL,
            'view.php?id={course_module}', '{attendanceregister}');
        $rules[] = new restore_log_rule('attendanceregister', ATTENDANCEREGISTER_LOGACTION_ADD_OFFLINE,
            'view.php?id={course_module}&userid={user}&action='. ATTENDANCEREGISTER_ACTION_SAVE_OFFLINE_SESSION,
            '{attendanceregister}');
        $rules[] = new restore_log_rule('attendanceregister', ATTENDANCEREGISTER_LOGACTION_DELETE_OFFLINE,
            'view.php?id={course_module}&userid={user}&action='. ATTENDANCEREGISTER_ACTION_DELETE_OFFLINE_SESSION,
            '{attendanceregister}');
        $rules[] = new restore_log_rule('attendanceregister', ATTENDANCEREGISTER_LOGACTION_RECALCULTATE,
            'view.php?id={course_module}&action=' . ATTENDANCEREGISTER_ACTION_RECALCULATE,
            '{attendanceregister}');
        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@link restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    static public function define_restore_log_rules_for_course() {
        return [];
    }
}