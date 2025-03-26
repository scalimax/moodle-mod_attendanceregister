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







class ViewState {

    protected $OUTPUT;

    function __construct($OUTPUT) {
        $this->OUTPUT = $OUTPUT;
    }

    function should_save_offline_sessions($userid, $context, $view_helper, $inputaction) {

    }

    function show_offline_sessions_form($userid, $usersessions, $view_helper) {
        return true;
    }
}

class Printable extends ViewState {

}

class DisplayOnScreen extends ViewState {

    public $doshowofflinesessionform;

    public $dosaveofflinesession;

    public $mform;

    // function __construct($OUTPUT) {
    //     parent::__construct($OUTPUT);
    // }

    function should_save_offline_sessions($userid, $context, $view_helper, $inputaction) {
        if ($view_helper->register->offlinesessions) {
            // Only if User is NOT logged-in-as, or ATTENDANCEREGISTER_ALLOW_LOGINAS_OFFLINE_SESSIONS is enabled.
            if (!\core\session\manager::is_loggedinas() || ATTENDANCEREGISTER_ALLOW_LOGINAS_OFFLINE_SESSIONS) {
        
                // If user is on his own Register and may save own Sessions
                // or is on other's Register and may save other's Sessions..
                if ($view_helper->usercaps->canaddsession($view_helper->register, $userid)) {
                    // Do show Offline Sessions Form.
                    $this->doshowofflinesessionform = true;
        
                    // If action is saving Offline Session...
                    if ($inputaction == ATTENDANCEREGISTER_ACTION_SAVE_OFFLINE_SESSION) {
                        // Check Capabilities, to show an error if a security violation attempt occurs.
                        if (attendanceregister__iscurrentuser($userid)) {
                            require_capability(ATTENDANCEREGISTER_CAPABILITY_ADD_OWN_OFFLINE_SESSIONS, $context);
                        } else {
                            require_capability(ATTENDANCEREGISTER_CAPABILITY_ADD_OTHER_OFFLINE_SESSIONS, $context);
                        }
                        // Do save Offline Session.
                        $this->dosaveofflinesession = true;
                    }
                }
            }
        }
    }

    function show_offline_sessions_form($userid, $usersessions, $view_helper) {
        if ($userid && $this->doshowofflinesessionform) {
            // Prepare form.
            $customformdata = ['register' => $view_helper->register, 'courses' => $usersessions->trackedcourses->courses];
            // Also pass userid only if is saving for another user.
            if (!attendanceregister__iscurrentuser($userid)) {
                $customformdata['userid'] = $userid;
            }
            $this->mform = new mod_attendanceregister_selfcertification_edit_form(null, $customformdata);
        
        
            // Process Self.Cert Form submission.
            if ($this->mform->is_cancelled()) {
                redirect($PAGE->url);
            } else if ($this->dosaveofflinesession && ($formdata = $this->mform->get_data())) {
                attendanceregister_save_offline_session($view_helper->register, $formdata);
                echo $this->OUTPUT->notification(get_string('offline_session_saved', 'attendanceregister'), 'notifysuccess');
                echo $this->OUTPUT->continue_button(attendanceregister_makeurl($view_helper->register, $userid));
                return false;
            }
        }
        return true;
    }
}



// Main parameters.

$groupid = optional_param('group', 0, PARAM_INT);

$view_helper = \mod_attendanceregister\view\view_helper::make_view_helper($OUTPUT);

$view_helper->active_user->can_view_some_registers($view_helper->context);
$printable = false;
$state = new DisplayOnScreen($OUTPUT);
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
        $printable = true;
        $state = new Printable($OUTPUT);
    
        break;
    case ATTENDANCEREGISTER_ACTION_SAVE_OFFLINE_SESSION:
        $command = $view_helper->prepare_show_offline_session_form();
        break;
    case ATTENDANCEREGISTER_ACTION_DELETE_OFFLINE_SESSION:
        $command = $view_helper->prepare_delete_offline_session();
        break;
    default:

        
}

// Only if Offline Sessions are enabled (and No printable-version action).
if (!$printable) {
    $view_helper->prepare_show_offline_session_form();
    //$state->should_save_offline_sessions($view_helper->active_user->userid, $view_helper->context, $view_helper, $inputaction);
}

//$view_helper->active_user->session_deleter->delete($view_helper->context);

// Retrieve Course Completion info object.
$completion = new completion_info($view_helper->course);

$usersessions = null;
if ($view_helper->userid()) {
    $usersessions = new attendanceregister_user_sessions($view_helper->register, $view_helper->userid(), $view_helper->usercaps);
} else {
    $view_helper->trackedusers = new attendanceregister_tracked_users($view_helper->register, $view_helper->usercaps, $groupid);
}

$url = attendanceregister_makeurl($view_helper->register, $view_helper->userid(), $groupid, $inputaction);
$PAGE->set_url($url->out());
$PAGE->set_context($view_helper->context);
$PAGE->set_title(format_string($view_helper->course->shortname . ': ' . $view_helper->register->name . $view_helper->active_user->fullname()));

$PAGE->set_heading($view_helper->course->fullname);
if ($printable) {
    $PAGE->set_pagelayout('print');
}

$view_helper->trigger_event();

if ($view_helper->userid() == $USER->id && $completion->is_enabled($view_helper->cm)) {
    $completion->set_module_viewed($view_helper->cm, $view_helper->userid());
}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($view_helper->register->name . $view_helper->active_user->fullname()));

// if (!$printable) {
//     $doshowcontents = $state->show_offline_sessions_form($view_helper->userid(), $usersessions, $view_helper);
// }


$doshowcontents = $command();


if ($doshowcontents) {
    if ($view_helper->userid()) {
        $view_helper->display_log_button();
        echo '<br />';
        if ($view_helper->mform && $view_helper->register->offlinesessions && !$printable) {
            echo "<br />";
            echo $OUTPUT->box_start('generalbox attendanceregister_offlinesessionform');
            $view_helper->mform->display();
            echo $OUTPUT->box_end();
        }
        echo html_writer::div(html_writer::table($usersessions->useraggregates->html_table()), 'table-responsive');
        echo html_writer::div(html_writer::table($usersessions->html_table()), 'table-responsive');
    } else {
        if ($view_helper->usercaps->canrecalc && !$printable) {
            echo groups_allgroups_course_menu($view_helper->course, $url, true, $groupid);
        }

        if ($view_helper->register->pendingrecalc && $view_helper->usercaps->canrecalc && !$printable) {
            echo $OUTPUT->notification(get_string('recalc_scheduled_on_next_cron', 'attendanceregister'));
        }
        
        $view_helper->display_show_my_sessions_button();
        $view_helper->display_trackedusers();
    }
}

if (!$printable) {
    echo $OUTPUT->footer();
}
