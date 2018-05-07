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
 * User aggregates
 *
 * @package mod_attendanceregister
 * @author  Lorenzo Nicora <fad@nicus.it>
 * @author  Renaat Debleu <rdebleu@eWallah.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * User aggregates
 *
 * @package mod_attendanceregister
 * @author  Lorenzo Nicora <fad@nicus.it>
 * @author  Renaat Debleu <rdebleu@eWallah.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attendanceregister_user_aggregates {

    /**
     * Grandtotal of all sessions
     */
    public $grandTotalDuration = 0;

    /**
     * Total of all Online Sessions
     */
    public $onlineTotalDuration = 0;

    /**
     * Total of all Offline Sessions
     */
    public $offlineTotalDuration = 0;

    /**
     * Offline sessions, per refcourseid
     */
    public $perCourseOfflineSessions = array();

    /**
     * Offline Sessions w/o any RefCourse
     */
    public $noCourseOfflineSessions = 0;

    /**
     * Last calculated Session Logout
     */
    public $lastSassionLogout = 0;

    /**
     * Ref to attendanceregister_user_sessions instance
     */
    private $sessions;

    /**
     * User instance
     */
    public $user;

    /**
     * Create an instance for a given register and user
     *
     * @param object                           $register
     * @param int                              $userid
     * @param attendanceregister_user_sessions $sessions
     */
    public function __construct($register, $userid, attendanceregister_user_sessions $sessions) {
        global $DB;
        $this->usersessions = $sessions;
        $this->user = attendanceregister__getUser($userid);
        $aggregates = attendanceregister__get_user_aggregates($register, $userid);
        foreach ($aggregates as $aggregate) {
            if ($aggregate->grandtotal) {
                $this->grandTotalDuration = $aggregate->duration;
                $this->lastSassionLogout = $aggregate->lastsessionlogout;
            } else if ($aggregate->total && $aggregate->onlinesess == 1 ) {
                $this->onlineTotalDuration = $aggregate->duration;
            } else if ($aggregate->total && $aggregate->onlinesess == 0 ) {
                $this->offlineTotalDuration = $aggregate->duration;
            } else if (!$aggregate->total && $aggregate->onlinesess == 0 && $aggregate->refcourse != null ) {
                $this->perCourseOfflineSessions[$aggregate->refcourse] = $aggregate->duration;
            } else if (!$aggregate->total && $aggregate->onlinesess == 0 && $aggregate->refcourse == null ) {
                $this->noCourseOfflineSessions = $aggregate->duration;
            } else {
                // Should not happen!
                debugging('Unconsistent Aggregate: ' . print_r($aggregate, true), DEBUG_DEVELOPER);
            }
        }
    }


    /**
     * Build the html_table object to represent summary
     *
     * @return html_table
     */
    public function html_table() {
        $table = new html_table();
        $s = ' attendanceregister_usersummary table table-condensed table-bordered table-striped table-hover';
        $table->attributes['class'] .= $s;
        $table->head[] = get_string('user_sessions_summary', 'attendanceregister');
        $table->headspan = array(3);

        $row = new html_table_row();
        $label = new html_table_cell();
        $label->colspan = 2;
        $label->text = get_string('prev_site_login', 'attendanceregister');
        $row->cells[] = $label;
        $cvalue = new html_table_cell();
        $cvalue->text = attendanceregister__formatDateTime($this->user->lastlogin);
        $row->cells[] = $cvalue;
        $table->data[] = $row;

        $row = new html_table_row();
        $label = new html_table_cell();
        $label->colspan = 2;
        $label->text = get_string('last_site_login', 'attendanceregister');
        $row->cells[] = $label;
        $cvalue = new html_table_cell();
        $cvalue->text = attendanceregister__formatDateTime($this->user->currentlogin);
        $row->cells[] = $cvalue;
        $table->data[] = $row;

        $row = new html_table_row();
        $label = new html_table_cell();
        $label->colspan = 2;
        $label->text = get_string('last_site_access', 'attendanceregister');
        $row->cells[] = $label;
        $cvalue = new html_table_cell();
        $cvalue->text = attendanceregister__formatDateTime($this->user->lastaccess);
        $row->cells[] = $cvalue;
        $table->data[] = $row;

        $row = new html_table_row();
        $label = new html_table_cell();
        $label->colspan = 2;
        $label->text = get_string('last_calc_online_session_logout', 'attendanceregister');
        $row->cells[] = $label;
        $cvalue = new html_table_cell();
        $cvalue->text = attendanceregister__formatDateTime($this->lastSassionLogout);
        $row->cells[] = $cvalue;
        $table->data[] = $row;

        $table->data[] = 'hr';

        $row = new html_table_row();
        $row->attributes['class'] .= ' attendanceregister_onlinesubtotal success';
        $label = new html_table_cell();
        $label->colspan = 2;
        $label->text = get_string('online_sessions_total_duration', 'attendanceregister');
        $row->cells[] = $label;

        $cvalue = new html_table_cell();
        $cvalue->text = attendanceregister_format_duration($this->onlineTotalDuration);
        $row->cells[] = $cvalue;

        $table->data[] = $row;

        if ($this->offlineTotalDuration ) {
            $table->data[] = 'hr';

            foreach ($this->perCourseOfflineSessions as $refcourseid => $courseofflinesessions) {
                $row = new html_table_row();
                $row->attributes['class'] .= '';
                $label = new html_table_cell();
                $label->text = get_string('offline_refcourse_duration', 'attendanceregister');
                $row->cells[] = $label;

                $coursecell = new html_table_cell();
                if ($refcourseid ) {
                    $coursecell->text = $this->usersessions->trackedcourses->courses[$refcourseid]->fullname;
                } else {
                    $coursecell->text = get_string('not_specified', 'attendanceregister');
                }
                $row->cells[] = $coursecell;

                $cvalue = new html_table_cell();
                $cvalue->text = attendanceregister_format_duration($courseofflinesessions);
                $row->cells[] = $cvalue;

                $table->data[] = $row;
            }

            if ($this->noCourseOfflineSessions ) {
                $row = new html_table_row();
                $row->attributes['class'] .= '';
                $label = new html_table_cell();
                $label->text = get_string('offline_refcourse_duration', 'attendanceregister');
                $row->cells[] = $label;

                $coursecell = new html_table_cell();
                $coursecell->text = get_string('no_refcourse', 'attendanceregister');
                $row->cells[] = $coursecell;

                $cvalue = new html_table_cell();
                $cvalue->text = attendanceregister_format_duration($this->noCourseOfflineSessions);
                $row->cells[] = $cvalue;

                $table->data[] = $row;
            }

            $row = new html_table_row();
            $row->attributes['class'] .= ' attendanceregister_offlinesubtotal';
            $label = new html_table_cell();
            $label->colspan = 2;
            $label->text = get_string('offline_sessions_total_duration', 'attendanceregister');
            $row->cells[] = $label;

            $cvalue = new html_table_cell();
            $cvalue->text = attendanceregister_format_duration($this->offlineTotalDuration);
            $row->cells[] = $cvalue;
            $table->data[] = $row;

            $row = new html_table_row();
            $row->attributes['class'] .= ' attendanceregister_grandtotal active';
            $label = new html_table_cell();
            $label->colspan = 2;
            $label->text = get_string('sessions_grandtotal_duration', 'attendanceregister');
            $row->cells[] = $label;

            $cvalue = new html_table_cell();
            $cvalue->text = attendanceregister_format_duration($this->grandTotalDuration);
            $row->cells[] = $cvalue;

            $table->data[] = $row;
        }
        return $table;
    }
}
