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


function make_view_helper() {
    global $DB;

    $cm_id = optional_param('id', 0, PARAM_INT);
    $register_id = optional_param('a', 0, PARAM_INT);

    if ($cm_id) {
        $view_helper = new \mod_attendanceregister\view\view_by_module_id();
        $view_helper->cm = get_coursemodule_from_id('attendanceregister', $cm_id, 0, false, MUST_EXIST);
        $view_helper->course = $DB->get_record('course', ['id' => $view_helper->cm->course], '*', MUST_EXIST);
        $view_helper->register = $DB->get_record('attendanceregister', ['id' => $view_helper->cm->instance], '*', MUST_EXIST);
    } else {
        $view_helper = new \mod_attendanceregister\view\view_by_register_id();
        $view_helper->register = $DB->get_record('attendanceregister', ['id' => $register_id], '*', MUST_EXIST);
        $view_helper->cm = get_coursemodule_from_instance('attendanceregister', $register_id, $view_helper->register->course, false, MUST_EXIST);
        $view_helper->course = $DB->get_record('course', ['id' => $view_helper->cm->course], '*', MUST_EXIST);
    }
    return $view_helper;
}

function get_user_id($usercaps) {
    global $USER;
    $userid = optional_param('userid', 0, PARAM_INT);
    if (!$userid && !$usercaps->canviewother) {
        $userid = $USER->id;
    }
    return $userid; 
}



// Main parameters.

$groupid = optional_param('group', 0, PARAM_INT);

$view_helper = make_view_helper();

// Other parameters.
$inputaction = optional_param('action', '', PARAM_ALPHA);
$inputsessionid = optional_param('session', null, PARAM_INT);

$sessiontodelete = null;
if ($inputsessionid) {
    $sessiontodelete = attendanceregister_get_session($inputsessionid);
}

require_course_login($view_helper->course, false, $view_helper->cm);


if (!($context = context_module::instance($view_helper->cm->id))) {
    throw new \moodle_exception('badcontext');
}

$view_helper->usercaps = new attendanceregister_user_capablities($context);
$userid = get_user_id($view_helper->usercaps);
$active_user = new \mod_attendanceregister\view\active_user($userid);

$active_user->can_view_some_registers($context);

// Require capability to recalculate.
$dorecalc = $view_helper->can_do_recalc($context);
$doschedrecalc = $view_helper->can_do_sched_recalc($context);

// Printable version?
$printable = false;
if ($inputaction == ATTENDANCEREGISTER_ACTION_PRINTABLE) {
    $printable = true;
}

// Check permissions and ownership for showing offline session form or saving them.
$doshowofflinesessionform = false;
$dosaveofflinesession = false;
// Only if Offline Sessions are enabled (and No printable-version action).
if ($view_helper->register->offlinesessions &&  !$printable) {
    // Only if User is NOT logged-in-as, or ATTENDANCEREGISTER_ALLOW_LOGINAS_OFFLINE_SESSIONS is enabled.
    if (!\core\session\manager::is_loggedinas() || ATTENDANCEREGISTER_ALLOW_LOGINAS_OFFLINE_SESSIONS) {

        // If user is on his own Register and may save own Sessions
        // or is on other's Register and may save other's Sessions..
        if ($view_helper->usercaps->canaddsession($view_helper->register, $userid)) {
            // Do show Offline Sessions Form.
            $doshowofflinesessionform = true;

            // If action is saving Offline Session...
            if ($inputaction == ATTENDANCEREGISTER_ACTION_SAVE_OFFLINE_SESSION) {
                // Check Capabilities, to show an error if a security violation attempt occurs.
                if (attendanceregister__iscurrentuser($userid)) {
                    require_capability(ATTENDANCEREGISTER_CAPABILITY_ADD_OWN_OFFLINE_SESSIONS, $context);
                } else {
                    require_capability(ATTENDANCEREGISTER_CAPABILITY_ADD_OTHER_OFFLINE_SESSIONS, $context);
                }
                // Do save Offline Session.
                $dosaveofflinesession = true;
            }
        }
    }
}


// Check capabilities to delete self cert (in the meanwhile retrieve the record to delete).
$dodeleteofflinesession = false;
if ($sessiontodelete) {
    // Check if logged-in-as Session Delete.
    if (session_is_loggedinas() && !ATTENDANCEREGISTER_ACTION_SAVE_OFFLINE_SESSION) {
        throw new \moodle_exception('onlyrealusercandeleteofflinesessions', 'attendanceregister');
    } else if (attendanceregister__iscurrentuser($userid)) {
        require_capability(ATTENDANCEREGISTER_CAPABILITY_DELETE_OWN_OFFLINE_SESSIONS, $context);
        $dodeleteofflinesession = true;
    } else {
        require_capability(ATTENDANCEREGISTER_CAPABILITY_DELETE_OTHER_OFFLINE_SESSIONS, $context);
        $dodeleteofflinesession = true;
    }
}

// Retrieve Course Completion info object.
$completion = new completion_info($view_helper->course);

$usertoprocess = null;
$usersessions = null;
$view_helper->trackedusers = null;
$str = '';
if ($userid) {
    $usertoprocess = attendanceregister__getuser($userid);
    $usersessions = new attendanceregister_user_sessions($view_helper->register, $userid, $view_helper->usercaps);
    $str = ': ' . fullname($usertoprocess);
} else {
    $view_helper->trackedusers = new attendanceregister_tracked_users($view_helper->register, $view_helper->usercaps, $groupid);
}

$url = attendanceregister_makeurl($view_helper->register, $userid, $groupid, $inputaction);
$PAGE->set_url($url->out());
$PAGE->set_context($context);
$PAGE->set_title(format_string($view_helper->course->shortname . ': ' . $view_helper->register->name . $str));

$PAGE->set_heading($view_helper->course->fullname);
if ($printable) {
    $PAGE->set_pagelayout('print');
}

$params = ['context' => $context, 'objectid' => $view_helper->register->id];
$event = \mod_attendanceregister\event\course_module_viewed::create($params);
$event->add_record_snapshot('course_modules', $view_helper->cm);
$event->add_record_snapshot('course', $view_helper->course);
$event->trigger();

if ($userid == $USER->id && $completion->is_enabled($view_helper->cm)) {
    $completion->set_module_viewed($view_helper->cm, $userid);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($view_helper->register->name . $str));

$doshowcontents = true;
$mform = null;
if ($userid && $doshowofflinesessionform && !$printable) {

    // Prepare form.
    $customformdata = ['register' => $view_helper->register, 'courses' => $usersessions->trackedcourses->courses];
    // Also pass userid only if is saving for another user.
    if (!attendanceregister__iscurrentuser($userid)) {
        $customformdata['userid'] = $userid;
    }
    $mform = new mod_attendanceregister_selfcertification_edit_form(null, $customformdata);


    // Process Self.Cert Form submission.
    if ($mform->is_cancelled()) {
        redirect($PAGE->url);
    } else if ($dosaveofflinesession && ($formdata = $mform->get_data())) {
        attendanceregister_save_offline_session($view_helper->register, $formdata);
        echo $OUTPUT->notification(get_string('offline_session_saved', 'attendanceregister'), 'notifysuccess');
        echo $OUTPUT->continue_button(attendanceregister_makeurl($view_helper->register, $userid));
        $doshowcontents = false;
    }
}

if ($doshowcontents) {
    $dorecalc($view_helper->register, $view_helper->usercaps);
}

if ($doshowcontents && ($doschedrecalc)) {
    if ($usertoprocess) {
        $progressbar = new progress_bar('recalcbar', 500, true);
        attendanceregister_force_recalc_user_sessions($view_helper->register, $userid, $progressbar);
        $usersessions = new attendanceregister_user_sessions($view_helper->register, $userid, $view_helper->usercaps);
    } else {
        if ($doschedrecalc) {
            if (!$view_helper->register->pendingrecalc) {
                attendanceregister_set_pending_recalc($view_helper->register, true);
            }
            echo $OUTPUT->notification(get_string('recalc_scheduled', 'attendanceregister'), 'notifysuccess');
        }
    }

    echo $OUTPUT->continue_button(attendanceregister_makeurl($view_helper->register, $userid));
    $doshowcontents = false;
} else if ($doshowcontents && $dodeleteofflinesession) {
    attendanceregister_delete_offline_session($view_helper->register, $sessiontodelete->userid, $sessiontodelete->id);
    echo $OUTPUT->notification(get_string('offline_session_deleted', 'attendanceregister'), 'notifysuccess');
    echo $OUTPUT->continue_button(attendanceregister_makeurl($view_helper->register, $userid));
    $doshowcontents = false;
} else if ($doshowcontents) {
    if ($userid) {
        echo $OUTPUT->container_start('attendanceregister_buttonbar btn-group');
        if ($view_helper->usercaps->canviewother && !$printable) {
            echo $OUTPUT->single_button(attendanceregister_makeurl($view_helper->register),
                get_string('back_to_tracked_user_list', 'attendanceregister'), 'get');
            $logurl = new moodle_url('/report/log/index.php', ['chooselog' => 1, 'showusers' => 1,
               'showcourses' => 1, 'id' => 1, 'user' => $userid, 'logformat' => 'showashtml', ]);
            echo $OUTPUT->single_button($logurl, 'Logs', 'get');
        }
        echo $OUTPUT->container_end();
        echo '<br />';
        if ($mform && $view_helper->register->offlinesessions && !$printable) {
            echo "<br />";
            echo $OUTPUT->box_start('generalbox attendanceregister_offlinesessionform');
            $mform->display();
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
        
        echo $OUTPUT->container_start('attendanceregister_buttonbar btn-group');
        if ($view_helper->usercaps->istracked) {
            $linkurl = attendanceregister_makeurl($view_helper->register, $USER->id);
            echo $OUTPUT->single_button($linkurl, get_string('show_my_sessions', 'attendanceregister'), 'get');
        }
        echo $OUTPUT->container_end();
        $view_helper->display_trackedusers();
    }
}

if (!$printable) {
    echo $OUTPUT->footer();
}
