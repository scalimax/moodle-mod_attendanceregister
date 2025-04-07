<?php

namespace mod_attendanceregister\view;

class delete_session {

    private $sessiontodelete;

    public $dodeleteofflinesession;

    private $inputsessionid;

    private $OUTPUT;

    public function __construct($OUTPUT) {
        $this->OUTPUT = $OUTPUT;
        $this->inputsessionid = optional_param('session', null, PARAM_INT);

        $sessiontodelete = null;
        if ($this->inputsessionid) {
            $this->sessiontodelete = attendanceregister_get_session($this->inputsessionid);
        }
        
    }

    public function should_delete($context) {
        // Check capabilities to delete self cert (in the meanwhile retrieve the record to delete).
        $this->dodeleteofflinesession = false;
        if ($this->sessiontodelete) {
                // Check if logged-in-as Session Delete.
            if (\core\session\manager::is_loggedinas() && !ATTENDANCEREGISTER_ACTION_SAVE_OFFLINE_SESSION) {
                throw new \moodle_exception('onlyrealusercandeleteofflinesessions', 'attendanceregister');
            } else if (attendanceregister__iscurrentuser($this->sessiontodelete->userid)) {
                require_capability(ATTENDANCEREGISTER_CAPABILITY_DELETE_OWN_OFFLINE_SESSIONS, $context);
                $this->dodeleteofflinesession = true;
            } else {
                require_capability(ATTENDANCEREGISTER_CAPABILITY_DELETE_OTHER_OFFLINE_SESSIONS, $context);
                $this->dodeleteofflinesession = true;
            }
       }
    }

    public function delete_offline_session($register) {
        if (!$this->dodeleteofflinesession) return;

        attendanceregister_delete_offline_session($register, $this->sessiontodelete->userid, $this->sessiontodelete->id);
        echo $this->OUTPUT->notification(get_string('offline_session_deleted', 'attendanceregister'), 'notifysuccess');
        echo $this->OUTPUT->continue_button(attendanceregister_makeurl($register, $this->sessiontodelete->userid));
    }
}