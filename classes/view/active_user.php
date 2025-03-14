<?php

declare(strict_types=1);

namespace mod_attendanceregister\view;

class active_user {
    public $userid;
    
    public function __construct($userid) {
        $this->userid = $userid;
    }

    function can_view_some_registers($context) {
        // Requires capabilities to view own or others' register.
        if (attendanceregister__iscurrentuser($userid)) {
            require_capability(ATTENDANCEREGISTER_CAPABILITY_VIEW_OWN_REGISTERS, $context);
        } else {
            require_capability(ATTENDANCEREGISTER_CAPABILITY_VIEW_OTHER_REGISTERS, $context);
        }
    }

}