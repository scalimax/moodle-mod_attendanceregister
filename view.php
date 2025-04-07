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
// require_once('classes/view/view_state.php');


// Main parameters.

$groupid = optional_param('group', 0, PARAM_INT);

$view_helper = \mod_attendanceregister\view\view_helper::make_view_helper($OUTPUT);

$view_helper->active_user->can_view_some_registers($view_helper->context);
$state = \mod_attendanceregister\view\view_state::make_display_onscreen($OUTPUT, $view_helper);
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
        $format = optional_param('format', '', PARAM_ALPHA);
        switch ($format) {
            case 'pdf':
                $state = \mod_attendanceregister\view\view_state::make_pdf($OUTPUT, $view_helper);
                break;
            default:
                $state = \mod_attendanceregister\view\view_state::make_printable($OUTPUT, $view_helper);
        }
        break;
    case ATTENDANCEREGISTER_ACTION_SAVE_OFFLINE_SESSION:
        $command = $view_helper->prepare_show_offline_session_form();
        break;
    case ATTENDANCEREGISTER_ACTION_DELETE_OFFLINE_SESSION:
        $command = $view_helper->prepare_delete_offline_session();
        break;
    default:

        
}

$view_helper->choose_view_type();

$url = attendanceregister_makeurl($view_helper->register, $view_helper->userid(), $groupid, $inputaction);
$PAGE->set_url($url->out());
$PAGE->set_context($view_helper->context);
$PAGE->set_title(format_string($view_helper->course->shortname . ': ' . $view_helper->register->name . ' - ' . $view_helper->user_fullname()));

$PAGE->set_heading($view_helper->course->fullname);
$state->set_pagelayout($PAGE);

$view_helper->trigger_event();

// Retrieve Course Completion info object.
$completion = new completion_info($view_helper->course);
if ($view_helper->userid() == $USER->id && $completion->is_enabled($view_helper->cm)) {
    $completion->set_module_viewed($view_helper->cm, $view_helper->userid());
}

$state->open_printer();

$state->display_header();

$doshowcontents = $command();

if ($doshowcontents) {
    if ($view_helper->userid()) {
        $state->display_user_sessions();
    } else {
        $state->display_groups_menu($groupid);
        $state->notify_recalc_scheduled();
        $view_helper->display_show_my_sessions_button();
        $view_helper->display_trackedusers();
    }
}

$state->display_footer();
$state->close_printer();