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

declare(strict_types=1);

namespace mod_attendanceregister\completion;

use core_completion\activity_custom_completion;

require_once($CFG->dirroot.'/mod/attendanceregister/locallib.php');

/**
 * Activity custom completion subclass for the attendanceregister activity.
 *
 * Contains the class for defining mod_attendanceregister's custom completion rules
 * and fetching an attendanceregister instance's completion statuses for a user.
 *
 * @package mod_attendanceregister
 * @copyright Massimo Scali <massimo.scali@ardea.srl>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {

    /**
     * Fetches the completion state for a given completion rule.
     *
     * @param string $rule The completion rule.
     * @return int The completion state.
     */
    public function get_state(string $rule): int {
        global $DB;

        $this->validate_rule($rule);

        if (!($register = $DB->get_record('attendanceregister', ['id' => $this->cm->instance]))) {
            throw new Exception("Can't find attendanceregister {$this->cm->instance}");
        }

        switch ($rule) {
            case 'completiontotaldurationmins':
                $status = COMPLETION_INCOMPLETE;
                if (attendanceregister__calculatecompletion($register, $this->userid)) {
                    $status = COMPLETION_COMPLETE;
                };

                break;

            default:
                $status = COMPLETION_INCOMPLETE;
                break;
        }

        return $status;
    }

    /**
     * Fetch the list of custom completion rules that this module defines.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return [
            'completiontotaldurationmins'
        ];
    }

    /**
     * Returns an associative array of the descriptions of custom completion rules.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        if (empty($this->cm->customdata['customcompletionrules']) || $this->cm->completion != COMPLETION_TRACKING_AUTOMATIC) {
            return [];
        }
    
        $descriptions = [];
        foreach ($this->cm->customdata['customcompletionrules'] as $key => $val) {
            switch ($key) {
                case 'completiontotaldurationmins':
                     if (!empty($val)) {
                         $descriptions[$key] = get_string('completiontotalduration', 'attendanceregister', $val);
                     }
                    break;
                default:
                    break;
            }
        }
        return $descriptions;
        }

    /**
     * Returns an array of all completion rules, in the order they should be displayed to users.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return [
            'completionview',
            'completiontotaldurationmins',
        ];
    }
}

