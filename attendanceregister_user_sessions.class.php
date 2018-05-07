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
 * Attendance register user sessions
 *
 * @package mod_attendanceregister
 * @author  Lorenzo Nicora <fad@nicus.it>
 * @author  Renaat Debleu <rdebleu@eWallah.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Attendance register user sessions
 *
 * @package mod_attendanceregister
 * @author  Lorenzo Nicora <fad@nicus.it>
 * @author  Renaat Debleu <rdebleu@eWallah.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attendanceregister_user_sessions  {

    /**
     * attendanceregister_session records
     */
    public $usersessions;

    /**
     * Instance of attendanceregister_user_aggregates
     */
    public $useraggregates;

    /**
     * Instance of attendanceregister_tracked_courses
     * containing all tracked Courses
     *
     * @var type
     */
    public $trackedcourses;

    /**
     * Ref. to AttendanceRegister instance
     */
    private $register;

    /**
     * Ref to mod_attendanceregister_user_capablities instance
     */
    private $usercaps;

    /**
     * Constructor
     * Load User's Sessions
     * Load User's Aggregates
     *
     * @param object                              $register
     * @param int                                 $userid
     * @param attendanceregister_user_capablities $usercapabilities
     */
    public function __construct($register, $userid, attendanceregister_user_capablities $usercaps) {
        $this->register = $register;
        $this->usersessions = attendanceregister_get_user_sessions($register, $userid);
        $this->useraggregates = new attendanceregister_user_aggregates($register, $userid, $this);
        $this->trackedcourses = new attendanceregister_tracked_courses($register);
        $this->usercaps = $usercaps;
    }

    /**
     * Build the html_table object to represent details
     *
     * @return html_table
     */
    public function html_table() {
        $table = new html_table();
        $s = ' attendanceregister_sessionlist table table-condensed table-bordered table-striped table-hover';
        $table->attributes['class'] .= $s;

        $table->head = [
            get_string('count', 'attendanceregister'),
            get_string('start', 'attendanceregister'),
            get_string('end', 'attendanceregister'),
            get_string('online_offline', 'attendanceregister')];
        $table->align = ['left', 'left', 'left', 'right'];

        if ($this->register->offlinesessions) {
            $table->head[] = get_string('online_offline', 'attendanceregister');
            $table->align[] = 'center';
            if ($this->register->offlinespecifycourse) {
                $table->head[] = get_string('ref_course', 'attendanceregister');
                $table->align[] = 'left';
            }
            if ($this->register->offlinecomments) {
                $table->head[] = get_string('comments', 'attendanceregister');
                $table->align[] = 'left';
            }
        }

        if ($this->usersessions ) {
            $stronline = get_string('online', 'attendanceregister');
            $stroffline = get_string('offline', 'attendanceregister');

            $rowcount = 0;
            foreach ($this->usersessions as $session) {
                $rowcount++;

                $rowcountstr = (string)$rowcount;
                if (!$session->onlinesess && $this->usercaps->canDeleteThisUserOfflineSession($session->userid) ) {
                    $deleteurl = attendanceregister_makeurl($this->register, $session->userid, null,
                        ATTENDANCEREGISTER_ACTION_DELETE_OFFLINE_SESSION, ['session' => $session->id]);
                    $confirm = new confirm_action(get_string('are_you_sure_to_delete_offline_session', 'attendanceregister'));
                    $rowcountstr .= ' ' . $OUTPUT->action_icon($deleteurl, new pix_icon('t/delete',
                       get_string('delete')), $confirm);
                }

                $duration = attendanceregister_format_duration($session->duration);

                $tablerow = new html_table_row([$rowcountstr, attendanceregister__formatDateTime($session->login),
                    attendanceregister__formatDateTime($session->logout), $duration]);

                $tablerow->attributes['class'] .= ($rowcount % 2) ? ' attendanceregister_oddrow' : ' attendanceregister_evenrow';

                if ($this->register->offlinesessions) {
                    $online = $session->onlinesess ? $stronline : $stroffline;
                    if ($session->addedbyuserid ) {
                        $a = attendanceregister__otherUserFullnameOrUnknown($session->addedbyuserid);
                        $addedby = get_string('session_added_by_another_user', 'attendanceregister', $a);
                        $online = html_writer::tag('a', $online . '*', ['title' => $addedby, 'class' => 'addedbyother']);
                    }
                    $tablecell = new html_table_cell($online);
                    $tablecell->attributes['class'] .= $session->onlinesess ? ' online_label' : ' offline_label';
                    $tablerow->attributes['class'] .= $session->onlinesess ? ' success' : '';
                    $tablerow->cells[] = $tablecell;

                    if ($this->register->offlinespecifycourse  ) {
                        if ($session->onlinesess ) {
                            $refcoursename = '';
                        } else {
                            if ($session->refcourse ) {
                                $refcourse = $this->trackedcourses->courses[ $session->refcourse ];
                                $refcoursename = $refcourse->fullname . ' ('. $refcourse->shortname .')';
                            } else {
                                $refcoursename = get_string('not_specified', 'attendanceregister');
                            }
                        }
                        $tablecell = new html_table_cell($refcoursename);
                        $tablerow->cells[] = $tablecell;
                    }

                    if ($this->register->offlinecomments  ) {
                        if (!$session->onlinesess && $session->comments) {
                            $comment = $session->comments;
                        } else {
                            $comment = '';
                        }
                        $tablecell = new html_table_cell($comment);
                        $tablerow->cells[] = $tablecell;
                    }
                }
                $table->data[] = $tablerow;
            }
        } else {
            $row = new html_table_row();
            $labelcell = new html_table_cell(get_string('no_session_for_this_user', 'attendanceregister'));
            $labelcell->colspan = count($table->head);
            $row->cells[] = $labelcell;
            $table->data[] = $row;
        }
        return $table;
    }
}
