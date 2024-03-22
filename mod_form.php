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
 * Attendance form
 *
 * @package mod_attendanceregister
 * @copyright 2016 CINECA
 * @author  Lorenzo Nicora <fad@nicus.it>
 * @author  Renaat Debleu <info@eWallah.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Attendance form
 *
 * @package mod_attendanceregister
 * @copyright 2016 CINECA
 * @author  Lorenzo Nicora <fad@nicus.it>
 * @author  Renaat Debleu <info@eWallah.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_attendanceregister_mod_form extends moodleform_mod {

    /**
     * Definition.
     */
    public function definition() {
        global $CFG;
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('registername', 'attendanceregister'), ['size' => '64']);
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();
        $registertypes = attendanceregister_get_register_types();
        $mform->addElement('select', 'type', get_string('registertype', 'attendanceregister'), $registertypes);
        $mform->addHelpButton('type', 'registertype', 'attendanceregister');
        $mform->setDefault('type', ATTENDANCEREGISTER_TYPE_COURSE);
        $minutes = ' ' . get_string('minutes');
        $sessionchoices = [
                5 => (' 5' . $minutes),
                10 => ('10' . $minutes),
                15 => ('15' . $minutes),
                20 => ('20' . $minutes),
                30 => ('30' . $minutes),
                45 => ('45' . $minutes),
                60 => ('60' . $minutes), ];
        $mform->addElement('select', 'sessiontimeout',
            get_string('sessiontimeout', 'attendanceregister'), $sessionchoices);
        $mform->addHelpButton('sessiontimeout', 'sessiontimeout', 'attendanceregister');
        $mform->setDefault('sessiontimeout', ATTENDANCEREGISTER_DEFAULT_SESSION_TIMEOUT);

        $mform->addElement('header', '', get_string('offline_sessions_certification', 'attendanceregister'));

        $mform->addElement('checkbox', 'offlinesessions',
          get_string('enable_offline_sessions_certification', 'attendanceregister'));
        $mform->addHelpButton('offlinesessions', 'offline_sessions_certification', 'attendanceregister');
        $mform->setDefault('offlinesessions', false);

        // Number of day before a self-certification will be accepted.
        $day = ' '.get_string('day');
        $days = ' '.get_string('days');
        $dayscertificable = [
            1 => ('1' . $day),
            2 => ('2' . $days),
            3 => ('3' . $days),
            4 => ('4' . $days),
            5 => ('5' . $days),
            6 => ('6' . $days),
            7 => ('7' . $days),
            10 => ('10' . $days),
            14 => ('14' . $days),
            21 => ('21' . $days),
            30 => ('30' . $days),
            60 => ('60' . $days),
            90 => ('90' . $days),
            120 => ('120' . $days),
            180 => ('180' . $days),
            365 => ('365'. $days), ];
        $mform->addElement('select', 'dayscertificable', get_string('dayscertificable', 'attendanceregister'), $dayscertificable);
        $mform->addHelpButton('dayscertificable', 'dayscertificable', 'attendanceregister');
        $mform->setDefault('dayscertificable', ATTENDANCEREGISTER_DEFAULT_DAYS_CERTIFICABLE);
        $mform->disabledIf('dayscertificable', 'offlinesessions');

        $mform->addElement('checkbox', 'offlinecomments', get_string('offlinecomments', 'attendanceregister'));
        $mform->addHelpButton('offlinecomments', 'offlinecomments', 'attendanceregister');
        $mform->setDefault('offlinecomments', false);
        $mform->disabledIf('offlinecomments', 'offlinesessions');

        $mform->addElement('checkbox', 'mandatoryofflinecomm',
            get_string('mandatory_offline_sessions_comments', 'attendanceregister'));
        $mform->setDefault('mandatoryofflinecomm', false);
        $mform->disabledIf('mandatoryofflinecomm', 'offlinesessions');
        $mform->disabledIf('mandatoryofflinecomm', 'offlinecomments');

        $mform->addElement('checkbox', 'offlinespecifycourse', get_string('offlinespecifycourse', 'attendanceregister'));
        $mform->addHelpButton('offlinespecifycourse', 'offlinespecifycourse', 'attendanceregister');
        $mform->setDefault('offlinespecifycourse', false);
        $mform->disabledIf('offlinespecifycourse', 'offlinesessions');

        $mform->addElement('checkbox', 'mandofflspeccourse', get_string('mandatoryofflinespecifycourse', 'attendanceregister'));
        $mform->addHelpButton('mandofflspeccourse', 'mandatoryofflinespecifycourse', 'attendanceregister');
        $mform->setDefault('mandofflspeccourse', false);
        $mform->disabledIf('mandofflspeccourse', 'offlinesessions');
        $mform->disabledIf('mandofflspeccourse', 'offlinespecifycourse');

        if (!PHPUNIT_TEST) {
            $this->standard_coursemodule_elements();
        }
        $this->add_action_buttons();
    }

    /**
     * Add completion rules
     * [feature #7]
     * @return array
     */
    public function add_completion_rules() {
        $mform =& $this->_form;
        $group = [];
        $group[] =& $mform->createElement('checkbox', $this->get_suffixed_name('completiontotaldurationenabled'), ' ',
            get_string('completiontotalduration', 'attendanceregister'));
        $group[] =& $mform->createElement('text', $this->get_suffixed_name('completiontotaldurationmins'), ' ', ['size' => 4]);
        $mform->setType('completiontotaldurationmins', PARAM_INT);
        $mform->addGroup($group, $this->get_suffixed_name('completiondurationgroup'), get_string('completiondurationgroup', 'attendanceregister'),
            [' '], false);
        $mform->disabledIf($this->get_suffixed_name('completiontotaldurationmins'), $this->get_suffixed_name('completiontotaldurationenabled'), 'notchecked');
        return ['completiondurationgroup'];
    }

    protected function get_suffixed_name(string $fieldname): string {
        return $fieldname . $this->get_suffix();
    }

    /**
     * Validate completion rules
     * [feature #7]
     * @param array $data
     * @return bool
     */
    public function completion_rule_enabled($data) {
        return((!empty($data[$this->get_suffixed_name('completiontotaldurationenabled')]) && $data[$this->get_suffixed_name('completiontotaldurationmins')] != 0));
    }

    /**
     * Extend get_data() to support completion checkbox behaviour
     * [feature #7]
     * @return array
     */
    public function get_data() {
        $data = parent::get_data();
        if (!$data) {
            return false;
        }
        if (!empty($data->completionunlocked)) {
            $autocompletion = !empty($data->completion) && $data->completion == COMPLETION_TRACKING_AUTOMATIC;
            if (empty($data->{$this->get_suffixed_name('completiontotaldurationenabled')}) || !$autocompletion) {
                $data->{$this->get_suffixed_name('completiontotaldurationmins')} = 0;
            }
        }
        return $data;
    }

    /**
     * Prepare completion checkboxes when form is displayed
     * @param array $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues) {
        parent::data_preprocessing($defaultvalues);
        $defaultvalues[$this->get_suffixed_name('completiontotaldurationenabled')] = !empty($defaultvalues[$this->get_suffixed_name('completiontotaldurationmins')]) ? 1 : 0;
        if (empty($defaultvalues[$this->get_suffixed_name('completiontotaldurationmins')])) {
            $defaultvalues[$this->get_suffixed_name('completiontotaldurationmins')] = ATTENDANCEREGISTER_DEFAULT_COMPLETION_TOTAL_DURATION_MINS;
        }
    }
}
