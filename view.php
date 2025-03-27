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
 * View attendance
 *
 * @package mod_attendanceregister
 * @copyright 2016 CINECA
 * @author  Lorenzo Nicora <fad@nicus.it>
 * @author  Renaat Debleu <info@eWallah.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_OUTPUT_BUFFERING', true);

require('../../config.php');
require_once('lib.php');
require_once($CFG->libdir . '/completionlib.php');

abstract class ViewState {

    protected $OUTPUT;

    protected $view_helper;

    function __construct($OUTPUT, $view_helper) {
        $this->OUTPUT = $OUTPUT;
        $this->view_helper = $view_helper;
    }

    abstract function prepare_offline_session_form();

    abstract function display_offline_session_form();

    abstract function display_footer();

    abstract function display_groups_menu();

    abstract function notify_recalc_scheduled();

    abstract function set_pagelayout($page);
}

class Printable extends ViewState {

    function prepare_offline_session_form() {}

    function display_offline_session_form() {}

    function display_footer() {}

    function display_groups_menu() {}

    function notify_recalc_scheduled() {}

    function set_pagelayout($page) {
        $page->set_pagelayout('print');
    }
}

class DisplayOnScreen extends ViewState {
    
    function set_pagelayout($PAGE) {}

    function prepare_offline_session_form() {
        $this->view_helper->prepare_show_offline_session_form();
    }

    function display_offline_session_form() {
        if ($this->view_helper->mform && $this->view_helper->register->offlinesessions) {
            echo $this->OUTPUT->box_start('generalbox attendanceregister_offlinesessionform');
            $this->view_helper->mform->display();
            echo $this->OUTPUT->box_end();
        }
    }

    function display_footer() {
        echo $this->OUTPUT->footer();
    }

    function display_groups_menu() {
        if ($this->view_helper->usercaps->canrecalc) {
            echo groups_allgroups_course_menu($this->view_helper->course, $url, true, $groupid);
        }
    }

    function notify_recalc_scheduled() {
        if ($this->view_helper->register->pendingrecalc && $this->view_helper->usercaps->canrecalc) {
            echo $this->OUTPUT->notification(get_string('recalc_scheduled_on_next_cron', 'attendanceregister'));
        }
    }
}

// Main parameters.

$groupid = optional_param('group', 0, PARAM_INT);

$view_helper = \mod_attendanceregister\view\view_helper::make_view_helper($OUTPUT);

$view_helper->active_user->can_view_some_registers($view_helper->context);
$state = new DisplayOnScreen($OUTPUT, $view_helper);
$command = function () {return true;};
$inputaction = optional_param('action', '', PARAM_ALPHA);
switch ($inputaction) {
    case ATTENDANCEREGISTER_ACTION_RECALCULATE:
        $command = $view_helper->can_do_recalc();
        break;
    case ATTENDANCEREGISTER_ACTION_SCHEDULERECALC:
        $command = $view_helper->can_do_sched_recalc();
        break;
    case ATTENDANCEREGISTER_ACTION_PRINTABLE:
        $state = new Printable($OUTPUT, $view_helper);
        break;
    case ATTENDANCEREGISTER_ACTION_SAVE_OFFLINE_SESSION:
        $command = $view_helper->prepare_show_offline_session_form();
        break;
    case ATTENDANCEREGISTER_ACTION_DELETE_OFFLINE_SESSION:
        $command = $view_helper->prepare_delete_offline_session();
        break;
    default:

        
}

$state->prepare_offline_session_form();

// Retrieve Course Completion info object.
$completion = new completion_info($view_helper->course);

$view_helper->choose_view_type();

$url = attendanceregister_makeurl($view_helper->register, $view_helper->userid(), $groupid, $inputaction);
$PAGE->set_url($url->out());
$PAGE->set_context($view_helper->context);
$PAGE->set_title(format_string($view_helper->course->shortname . ': ' . $view_helper->register->name . $view_helper->active_user->fullname()));

$PAGE->set_heading($view_helper->course->fullname);
$state->set_pagelayout($PAGE);

$view_helper->trigger_event();

if ($view_helper->userid() == $USER->id && $completion->is_enabled($view_helper->cm)) {
    $completion->set_module_viewed($view_helper->cm, $view_helper->userid());
}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($view_helper->register->name . $view_helper->active_user->fullname()));

$doshowcontents = $command();

if ($doshowcontents) {
    if ($view_helper->userid()) {
        $view_helper->display_log_button();
        echo '<br />';
        $state->display_offline_session_form();
        $view_helper->display_usersessions();
    } else {
        $state->display_groups_menu($groupid);
        $state->notify_recalc_scheduled();
        $view_helper->display_show_my_sessions_button();
        $view_helper->display_trackedusers();
    }
}

$state->display_footer();
