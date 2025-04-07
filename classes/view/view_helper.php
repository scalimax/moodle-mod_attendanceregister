<?php

declare(strict_types=1);

namespace mod_attendanceregister\view;

class view_helper {

    public $cm;
    public $course;
    public $context;
    public $register;

    public $usercaps;

    public $trackedusers = null;

    public $usersessions = null;

    public $active_user;

    public $mform;

    public $OUTPUT;

    function userid() {
        return $this->active_user->userid;
    }

    private static function get_user_id($usercaps) {
        global $USER;
        $userid = optional_param('userid', 0, PARAM_INT);
        if (!$userid && !$usercaps->canviewother) {
            $userid = $USER->id;
        }
        return $userid; 
    }

    static function make_view_helper($OUTPUT) {
        global $DB;
    
        $view_helper = new \mod_attendanceregister\view\view_helper();
        $view_helper->OUTPUT = $OUTPUT;
        
        $cm_id = optional_param('id', 0, PARAM_INT);
        if ($cm_id) {
            $view_helper->cm = get_coursemodule_from_id('attendanceregister', $cm_id, 0, false, MUST_EXIST);
            $view_helper->register = $DB->get_record('attendanceregister', ['id' => $view_helper->cm->instance], '*', MUST_EXIST);
        } else {
            $register_id = optional_param('a', 0, PARAM_INT);
            $view_helper->register = $DB->get_record('attendanceregister', ['id' => $register_id], '*', MUST_EXIST);
            $view_helper->cm = get_coursemodule_from_instance('attendanceregister', $register_id, $view_helper->register->course, false, MUST_EXIST);
        }
        $view_helper->course = $DB->get_record('course', ['id' => $view_helper->cm->course], '*', MUST_EXIST);
    
        require_course_login($view_helper->course, false, $view_helper->cm);
    
        if (!($view_helper->context = \context_module::instance($view_helper->cm->id))) {
            throw new \moodle_exception('badcontext');
        }

        $view_helper->usercaps = new \attendanceregister_user_capablities($view_helper->context);
        $userid = view_helper::get_user_id($view_helper->usercaps);
        $view_helper->active_user = new \mod_attendanceregister\view\active_user($userid, $OUTPUT);

        return $view_helper;
    }

    function user_fullname() {
        return $this->active_user->fullname();
    }

    private function recalc_single_user() {
        $userid = optional_param('userid', 0, PARAM_INT);
        $inputaction = optional_param('action', '', PARAM_ALPHA);
        return $userid && $inputaction == ATTENDANCEREGISTER_ACTION_RECALCULATE;
    }

    function can_do_recalc() {
        require_capability(ATTENDANCEREGISTER_CAPABILITY_RECALC_SESSIONS, $this->context);
        $recalc_all_users = !$this->recalc_single_user();
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
            } else {
                $this->active_user->recalculate($this);
            }
            echo $this->OUTPUT->notification(get_string('recalc_complete', 'attendanceregister'), 'notifysuccess');
            echo $this->OUTPUT->continue_button(attendanceregister_makeurl($this->register, $this->userid()));
        
            return true;
        };
    }

    function choose_view_type() {
        if ($this->userid()) {
            $this->usersessions = new \attendanceregister_user_sessions($this->register, $this->userid(), $this->usercaps);
        } else {
            $this->trackedusers = new \attendanceregister_tracked_users($this->register, $this->usercaps, $groupid);
        }
    }

    function display_trackedusers() {
        echo '<br />';
        echo \html_writer::div(\html_writer::table($this->trackedusers->trackedcourses->html_table()), 'table-responsive');
        echo \html_writer::div(\html_writer::table($this->trackedusers->html_table()), 'table-responsive');
    }

    function display_usersessions() {
        //echo \html_writer::div(\html_writer::table($this->usersessions->useraggregates->html_table()), 'table-responsive');
        //echo \html_writer::div(\html_writer::table($this->usersessions->html_table()), 'table-responsive');

        echo \html_writer::table($this->usersessions->useraggregates->html_table());
        echo \html_writer::table($this->usersessions->html_table());
    }

    function can_do_sched_recalc() {
        require_capability(ATTENDANCEREGISTER_CAPABILITY_RECALC_SESSIONS, $this->context);
        return function() {
            if (!$this->register->pendingrecalc) {
                attendanceregister_set_pending_recalc($this->register, true);
            }
            echo $this->OUTPUT->notification(get_string('recalc_scheduled', 'attendanceregister'), 'notifysuccess');
            $this->active_user->recalculate($this);
            echo $this->OUTPUT->continue_button(attendanceregister_makeurl($this->register, $this->userid()));
            return true;
        };
    }

    function prepare_delete_offline_session() {
        $session_deleter = $this->active_user->session_deleter;
        $context = $this->context;
        $register = $this->register;
        $userid = $this->active_user->userid;
        return function() use ($session_deleter, $context, $register, $userid) {
            $session_deleter->should_delete($context);
            $session_deleter->delete_offline_session($register, $userid);
            return false;
        };
    }

    function prepare_show_offline_session_form() {
        if ($this->register->offlinesessions) {
            // Only if User is NOT logged-in-as, or ATTENDANCEREGISTER_ALLOW_LOGINAS_OFFLINE_SESSIONS is enabled.
            if (!\core\session\manager::is_loggedinas() || ATTENDANCEREGISTER_ALLOW_LOGINAS_OFFLINE_SESSIONS) {
        
                // If user is on his own Register and may save own Sessions
                // or is on other's Register and may save other's Sessions..
                if ($this->usercaps->canaddsession($this->register, $this->userid())) {
                    $customformdata = ['register' => $this->register, 'courses' => $this->usersessions->trackedcourses->courses];
                    // Also pass userid only if is saving for another user.
                    if (!attendanceregister__iscurrentuser($this->userid())) {
                        $customformdata['userid'] = $this->userid();
                    }
                    $this->mform = new \mod_attendanceregister_selfcertification_edit_form(null, $customformdata);
    
                    return function() {
                        if (attendanceregister__iscurrentuser($this->userid())) {
                            require_capability(ATTENDANCEREGISTER_CAPABILITY_ADD_OWN_OFFLINE_SESSIONS, $this->context);
                        } else {
                            require_capability(ATTENDANCEREGISTER_CAPABILITY_ADD_OTHER_OFFLINE_SESSIONS, $this->context);
                        }
                        // Process Self.Cert Form submission.
                        if ($this->mform->is_cancelled()) {
                            redirect($PAGE->url);
                        } else if ($formdata = $this->mform->get_data()) {
                            attendanceregister_save_offline_session($this->register, $formdata);
                            echo $this->OUTPUT->notification(get_string('offline_session_saved', 'attendanceregister'), 'notifysuccess');
                            echo $this->OUTPUT->continue_button(attendanceregister_makeurl($this->register, $this->userid()));
                            return false;
                        }
                    };
                }
            }
        }
        return function() {return true;};
    }

    function trigger_event() {
        $params = ['context' => $this->context, 'objectid' => $this->register->id];
        $event = \mod_attendanceregister\event\course_module_viewed::create($params);
        $event->add_record_snapshot('course_modules', $this->cm);
        $event->add_record_snapshot('course', $this->course);
        $event->trigger();
    }

    function display_show_my_sessions_button() {
        global $USER;

        echo $this->OUTPUT->container_start('attendanceregister_buttonbar btn-group');
        if ($this->usercaps->istracked) {
            $linkurl = attendanceregister_makeurl($this->register, $USER->id);
            echo $this->OUTPUT->single_button($linkurl, get_string('show_my_sessions', 'attendanceregister'), 'get');
        }
        echo $this->OUTPUT->container_end();
    }

    function display_log_button() {
        echo $this->OUTPUT->container_start('attendanceregister_buttonbar btn-group');
        if ($this->usercaps->canviewother /* && !$printable*/) {
            echo $this->OUTPUT->single_button(attendanceregister_makeurl($this->register),
                get_string('back_to_tracked_user_list', 'attendanceregister'), 'get');
            $logurl = new \moodle_url('/report/log/index.php', ['chooselog' => 1, 'showusers' => 1,
               'showcourses' => 1, 'id' => 1, 'user' => $this->userid(), 'logformat' => 'showashtml', ]);
            echo $this->OUTPUT->single_button($logurl, 'Logs', 'get');
        }
        echo $this->OUTPUT->container_end();
    }

}