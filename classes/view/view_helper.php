<?php

declare(strict_types=1);

namespace mod_attendanceregister\view;

abstract class view_helper {

    public $cm;
    public $course;
    public $register;

    public $usercaps;

    public $trackedusers = null;

    public $OUTPUT;

    function can_do_recalc($context, $inputaction, $recalc_single_user) {

        if ($inputaction == ATTENDANCEREGISTER_ACTION_RECALCULATE) {
            require_capability(ATTENDANCEREGISTER_CAPABILITY_RECALC_SESSIONS, $context);
            $recalc_all_users = !$recalc_single_user;
            return function() use ($recalc_all_users) {
                if ($recalc_all_users) {
                    $register = $this->register;
                    $usercaps = $this->usercaps;
                    if ($register->pendingrecalc) {
                        attendanceregister_set_pending_recalc($register, false);
                    }
                    set_time_limit(0);
                    attendanceregister_delete_all_users_online_sessions_and_aggregates($register);
                    $newtrackedusers = attendanceregister_get_tracked_users($register);
                    foreach ($newtrackedusers as $user) {
                        $progressbar = new \progress_bar('recalcbar_' . $user->id, 500, true);
                        attendanceregister_force_recalc_user_sessions($register, $user->id, $progressbar, false);
                        // No delete needed, having done before [issue #14].
                    }
                    $this->trackedusers = new \attendanceregister_tracked_users($register, $usercaps,  $groupid);
                }
                echo $this->OUTPUT->notification(get_string('recalc_complete', 'attendanceregister'), 'notifysuccess');
                return true;
            };
        }
        return function() {return false;};
    }

    function display_trackedusers() {
        echo '<br />';
        echo \html_writer::div(\html_writer::table($this->trackedusers->trackedcourses->html_table()), 'table-responsive');
        echo \html_writer::div(\html_writer::table($this->trackedusers->html_table()), 'table-responsive');
    }

    function can_do_sched_recalc($context, $inputaction) {
        if ($inputaction == ATTENDANCEREGISTER_ACTION_SCHEDULERECALC) {
            require_capability(ATTENDANCEREGISTER_CAPABILITY_RECALC_SESSIONS, $context);
            return function() {
                if (!$this->register->pendingrecalc) {
                    attendanceregister_set_pending_recalc($this->register, true);
                }
                echo $this->OUTPUT->notification(get_string('recalc_scheduled', 'attendanceregister'), 'notifysuccess');
                return true;
            };
        }
        return function() {return false;};
    }

    function trigger_event($context) {
        $params = ['context' => $context, 'objectid' => $this->register->id];
        $event = \mod_attendanceregister\event\course_module_viewed::create($params);
        $event->add_record_snapshot('course_modules', $this->cm);
        $event->add_record_snapshot('course', $this->course);
        $event->trigger();
    }

}