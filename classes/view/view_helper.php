<?php

declare(strict_types=1);

namespace mod_attendanceregister\view;

abstract class view_helper {

    public $cm;
    public $course;
    public $register;

    public $usercaps;

    public $trackedusers;

    function can_do_recalc($context) {
        global $OUTPUT;

        if ($inputaction == ATTENDANCEREGISTER_ACTION_RECALCULATE) {
            require_capability(ATTENDANCEREGISTER_CAPABILITY_RECALC_SESSIONS, $context);
            return function($register, $usercaps) {
                if ($register->pendingrecalc) {
                    attendanceregister_set_pending_recalc($register, false);
                }
                set_time_limit(0);
                attendanceregister_delete_all_users_online_sessions_and_aggregates($register);
                $newtrackedusers = attendanceregister_get_tracked_users($register);
                foreach ($newtrackedusers as $user) {
                    $progressbar = new progress_bar('recalcbar_' . $user->id, 500, true);
                    attendanceregister_force_recalc_user_sessions($register, $user->id, $progressbar, false);
                    // No delete needed, having done before [issue #14].
                }
                $this->trackedusers = new attendanceregister_tracked_users($register, $usercaps,  $groupid);
                echo $OUTPUT->notification(get_string('recalc_complete', 'attendanceregister'), 'notifysuccess');
            };
        }
        return function($register, $usercaps) {
            return null;
        };
    }

    function can_do_sched_recalc($context) {
        if ($inputaction == ATTENDANCEREGISTER_ACTION_SCHEDULERECALC) {
            require_capability(ATTENDANCEREGISTER_CAPABILITY_RECALC_SESSIONS, $context);
            return true;
        }
        return false;
    }

}