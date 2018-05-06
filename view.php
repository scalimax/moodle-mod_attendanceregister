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
 * @author  Lorenzo Nicora <fad@nicus.it>
 * @author  Renaat Debleu <rdebleu@eWallah.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_OUTPUT_BUFFERING', true);

require '../../config.php';
require_once "lib.php";
require_once $CFG->libdir . '/completionlib.php';

// Main parameters.
$userId = optional_param('userid', 0, PARAM_INT);
$id = optional_param('id', 0, PARAM_INT);
$a = optional_param('a', 0, PARAM_INT);
$groupId = optional_param('group', 0, PARAM_INT);

// Other parameters.
$inputAction = optional_param('action', '', PARAM_ALPHA);
$inputSessionId = optional_param('session', null, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('attendanceregister', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $register = $DB->get_record('attendanceregister', ['id' => $cm->instance], '*', MUST_EXIST);
} else {
    $register = $DB->get_record('attendanceregister', ['id' => $a], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('attendanceregister', $register->id, $register->course, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $id = $cm->id;
}

$sessionToDelete = null;
if ($inputSessionId) {
    $sessionToDelete = attendanceregister_get_session($inputSessionId);
}

require_course_login($course, false, $cm);

if (!($context = context_module::instance($cm->id))) {
    print_error('badcontext');
}

$userCapabilities = new attendanceregister_user_capablities($context);

if (!$userId && !$userCapabilities->canViewOtherRegisters) {
    $userId = $USER->id;
}
// Requires capabilities to view own or others' register.
if (attendanceregister__isCurrentUser($userId) ) {
    require_capability(ATTENDANCEREGISTER_CAPABILITY_VIEW_OWN_REGISTERS, $context);
} else {
    require_capability(ATTENDANCEREGISTER_CAPABILITY_VIEW_OTHER_REGISTERS, $context);
}

// Require capability to recalculate.
$doRecalculate = false;
$doScheduleRecalc = false;
if ($inputAction == ATTENDANCEREGISTER_ACTION_RECALCULATE ) {
    require_capability(ATTENDANCEREGISTER_CAPABILITY_RECALC_SESSIONS, $context);
    $doRecalculate = true;
}
if ($inputAction == ATTENDANCEREGISTER_ACTION_SCHEDULERECALC ) {
    require_capability(ATTENDANCEREGISTER_CAPABILITY_RECALC_SESSIONS, $context);
    $doScheduleRecalc = true;
}

// Printable version?
$doShowPrintableVersion = false;
if ($inputAction == ATTENDANCEREGISTER_ACTION_PRINTABLE) {
    $doShowPrintableVersion = true;
}

/// Check permissions and ownership for showing offline session form or saving them.
$doShowOfflineSessionForm = false;
$doSaveOfflineSession = false;
// Only if Offline Sessions are enabled (and No printable-version action).
if ($register->offlinesessions &&  !$doShowPrintableVersion  ) {
    // Only if User is NOT logged-in-as, or ATTENDANCEREGISTER_ALLOW_LOGINAS_OFFLINE_SESSIONS is enabled
    if (!\core\session\manager::is_loggedinas() || ATTENDANCEREGISTER_ALLOW_LOGINAS_OFFLINE_SESSIONS ) {

        // If user is on his own Register and may save own Sessions
        // or is on other's Register and may save other's Sessions..
        if ($userCapabilities->canAddThisUserOfflineSession($register, $userId) ) {
            // Do show Offline Sessions Form
            $doShowOfflineSessionForm = true;

            // If action is saving Offline Session...
            if ($inputAction == ATTENDANCEREGISTER_ACTION_SAVE_OFFLINE_SESSION  ) {
                // Check Capabilities, to show an error if a security violation attempt occurs.
                if (attendanceregister__isCurrentUser($userId) ) {
                    require_capability(ATTENDANCEREGISTER_CAPABILITY_ADD_OWN_OFFLINE_SESSIONS, $context);
                } else {
                    require_capability(ATTENDANCEREGISTER_CAPABILITY_ADD_OTHER_OFFLINE_SESSIONS, $context);
                }

                // Do save Offline Session.
                $doSaveOfflineSession = true;
            }
        }
    }
}


// Check capabilities to delete self cert (in the meanwhile retrieve the record to delete).
$doDeleteOfflineSession = false;
if ($sessionToDelete) {
    // Check if logged-in-as Session Delete.
    if (session_is_loggedinas() && !ATTENDANCEREGISTER_ACTION_SAVE_OFFLINE_SESSION) {
        print_error('onlyrealusercandeleteofflinesessions', 'attendanceregister');
    } else if (attendanceregister__isCurrentUser($userId) ) {
        require_capability(ATTENDANCEREGISTER_CAPABILITY_DELETE_OWN_OFFLINE_SESSIONS, $context);
        $doDeleteOfflineSession = true;
    } else {
        require_capability(ATTENDANCEREGISTER_CAPABILITY_DELETE_OTHER_OFFLINE_SESSIONS, $context);
        $doDeleteOfflineSession = true;
    }
}

// Retrieve Course Completion info object.
$completion = new completion_info($course);

$userToProcess = null;
$userSessions = null;
$trackedUsers = null;
if ($userId ) {
    $userToProcess = attendanceregister__getUser($userId);
    $userToProcessFullname = fullname($userToProcess);
    $userSessions = new attendanceregister_user_sessions($register, $userId, $userCapabilities);
} else {
    $trackedUsers = new attendanceregister_tracked_users($register, $userCapabilities, $groupId);
}


$url = attendanceregister_makeUrl($register, $userId, $groupId, $inputAction);
$PAGE->set_url($url->out());
$PAGE->set_context($context);
$str = $userId ? ': ' . $userToProcessFullname : '';
$PAGE->set_title(format_string($course->shortname . ': ' . $register->name . $str));

$PAGE->set_heading($course->fullname);
if ($doShowPrintableVersion) {
    $PAGE->set_pagelayout('print');
}

// Add User's Register Navigation node.
if ($userToProcess ) {
    $registerNavNode = $PAGE->navigation->find($cm->id, navigation_node::TYPE_ACTIVITY);
    $userNavNode = $registerNavNode->add($userToProcessFullname, $url);
    $userNavNode->make_active();
}

$params = ['context' => $context, 'objectid' => $register->id];
$event = \mod_attendanceregister\event\course_module_viewed::create($params);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->trigger();

if ($userId == $USER->id && $completion->is_enabled($cm) ) {
    $completion->set_module_viewed($cm, $userId);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($register->name . $str));

$doShowContents = true;
$mform = null;
if ($userId && $doShowOfflineSessionForm && !$doShowPrintableVersion ) {

    // Prepare form.
    $customFormData = ['register' => $register, 'courses' => $userSessions->trackedCourses->courses];
    // Also pass userId only if is saving for another user.
    if (!attendanceregister__isCurrentUser($userId)) {
        $customFormData['userId'] = $userId;
    }
    $mform = new mod_attendanceregister_selfcertification_edit_form(null, $customFormData);


    // Process Self.Cert Form submission.
    if ($mform->is_cancelled()) {
        redirect($PAGE->url);
    } else if ($doSaveOfflineSession && ($formData = $mform->get_data())) {
        attendanceregister_save_offline_session($register, $formData);
        echo $OUTPUT->notification(get_string('offline_session_saved', 'attendanceregister'), 'notifysuccess');
        echo $OUTPUT->continue_button(attendanceregister_makeUrl($register, $userId));
        $doShowContents = false;
    }
}

if ($doShowContents && ($doRecalculate||$doScheduleRecalc)) {
    if ($userToProcess) {
        $progressbar = new progress_bar('recalcbar', 500, true);
        attendanceregister_force_recalc_user_sessions($register, $userId, $progressbar);
        $userSessions = new attendanceregister_user_sessions($register, $userId, $userCapabilities);
    } else {
        if ($doScheduleRecalc ) {
            if (!$register->pendingrecalc ) {
                attendanceregister_set_pending_recalc($register, true);
            }
        }
        if ($doRecalculate ) {
            if ($register->pendingrecalc ) {
                attendanceregister_set_pending_recalc($register, false);
            }
            set_time_limit(0);
            attendanceregister_delete_all_users_online_sessions_and_aggregates($register);
            $newTrackedUsers = attendanceregister_get_tracked_users($register);
            foreach ($newTrackedUsers as $user) {
                $progressbar = new progress_bar('recalcbar_' . $user->id, 500, true);
                attendanceregister_force_recalc_user_sessions($register, $user->id, $progressbar, false); // No delete needed, having done before [issue #14]
            }
            $trackedUsers = new attendanceregister_tracked_users($register, $userCapabilities,  $groupId);
        }
    }
    if ($doRecalculate || $doScheduleRecalc ) {
        $notificationStr = get_string(($doRecalculate)?'recalc_complete':'recalc_scheduled', 'attendanceregister');
        echo $OUTPUT->notification($notificationStr, 'notifysuccess');
    }
    echo $OUTPUT->continue_button(attendanceregister_makeUrl($register, $userId));
    $doShowContents = false;
} else if ($doShowContents && $doDeleteOfflineSession) {
    attendanceregister_delete_offline_session($register, $sessionToDelete->userid, $sessionToDelete->id);
  echo $OUTPUT->notification(get_string('offline_session_deleted', 'attendanceregister'), 'notifysuccess');
    echo $OUTPUT->continue_button(attendanceregister_makeUrl($register, $userId));
    $doShowContents = false;
} else if ($doShowContents) {
    if ($userId) {
        echo $OUTPUT->container_start('attendanceregister_buttonbar btn-group');
        $x = $doShowPrintableVersion ? null : ATTENDANCEREGISTER_ACTION_PRINTABLE;
        $linkUrl = attendanceregister_makeUrl($register, $userId, null, $x);
        if ($userCapabilities->canViewOtherRegisters && !$doShowPrintableVersion) {
            echo $OUTPUT->single_button(attendanceregister_makeUrl($register),
                get_string('back_to_tracked_user_list', 'attendanceregister'), 'get');
            $logurl = new moodle_url('/report/log/index.php',
               ['chooselog' => 1, 'showusers' => 1, 'showcourses' => 1, 'id' => 1, 'user' => $userId, 'logformat' => 'showashtml']);
            echo $OUTPUT->single_button($logurl, 'Logs', 'get');
        }
        echo $OUTPUT->container_end();
        echo '<br />';
        if ($mform && $register->offlinesessions && !$doShowPrintableVersion) {
            echo "<br />";
            echo $OUTPUT->box_start('generalbox attendanceregister_offlinesessionform');
            $mform->display();
            echo $OUTPUT->box_end();
        }
        echo '<div class="table-responsive">';
        echo html_writer::table($userSessions->userAggregates->html_table());
        echo '</div>';
        echo '<div class="table-responsive">';       
        echo html_writer::table($userSessions->html_table());
        echo '</div>';
    } else {
        if ($userCapabilities->canRecalcSessions && !$doShowPrintableVersion) {
            echo groups_allgroups_course_menu($course, $url, true, $groupId);
        }
        if ($register->pendingrecalc && $userCapabilities->canRecalcSessions && !$doShowPrintableVersion ) {
            echo $OUTPUT->notification(get_string('recalc_scheduled_on_next_cron', 'attendanceregister'));
        }
        else if (!attendanceregister__didCronRanAfterInstanceCreation($cm) ) {
            echo $OUTPUT->notification(get_string('first_calc_at_next_cron_run', 'attendanceregister'));
        }
        echo $OUTPUT->container_start('attendanceregister_buttonbar btn-group');
        if ($userCapabilities->isTracked ) {
            $linkUrl = attendanceregister_makeUrl($register, $USER->id);
            echo $OUTPUT->single_button($linkUrl, get_string('show_my_sessions', 'attendanceregister'), 'get');
        }
        echo $OUTPUT->container_end();
        echo '<br />';
        echo '<div class="table-responsive">'; 
        echo html_writer::table($trackedUsers->trackedCourses->html_table());
        echo '</div>';
        echo '<div class="table-responsive">'; 
        echo html_writer::table($trackedUsers->html_table());
        echo '</div>';
    }
}



// Output page footer;
if (!$doShowPrintableVersion) {
    echo $OUTPUT->footer();
}