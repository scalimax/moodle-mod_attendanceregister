<?php

declare(strict_types=1);

namespace mod_attendanceregister\view;

class active_user {
    public $userid;

    public $session_deleter;

    private $usertoprocess;

    public $usersessions;
    
    public function __construct($userid, $OUTPUT) {
        $this->userid = $userid;
        if ($userid) {
            $this->usertoprocess = attendanceregister__getuser($userid);
        }
        $this->session_deleter = new \mod_attendanceregister\view\delete_session($OUTPUT);
    }

    function fullname() {
        return $this->usertoprocess ? ': ' . fullname($this->usertoprocess) : '';
    }

    function recalculate($view_helper) {
        if ($this->usertoprocess) {
            $progressbar = new \progress_bar('recalcbar', 500, true);
            attendanceregister_force_recalc_user_sessions($view_helper->register, $this->userid, $progressbar);
            $this->usersessions = new \attendanceregister_user_sessions($view_helper->register, $this->userid, $view_helper->usercaps);
        }
    
    }

    function can_view_some_registers($context) {
        // Requires capabilities to view own or others' register.
        if (attendanceregister__iscurrentuser($this->userid)) {
            require_capability(ATTENDANCEREGISTER_CAPABILITY_VIEW_OWN_REGISTERS, $context);
        } else {
            require_capability(ATTENDANCEREGISTER_CAPABILITY_VIEW_OTHER_REGISTERS, $context);
        }
    }

}