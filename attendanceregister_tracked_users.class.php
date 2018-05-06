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
 * Tracked Courses
 *
 * @package mod_attendanceregister
 * @author  Lorenzo Nicora <fad@nicus.it>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Holds all tracked Users of an Attendance Register
 *
 * Implements method to return html_table to render it.
 *
 * @author nicus
 */
class attendanceregister_tracked_users {

    /**
     * Array of User
     */
    public $users;

    /**
     * Array if attendanceregister_user_aggregates_summary
     * keyed by $userId
     */
    public $usersSummaryAggregates;


    /**
     * Instance of attendanceregister_tracked_courses
     * containing all tracked Courses
     *
     * @var type
     */
    public $trackedCourses;

    /**
     * Ref. to AttendanceRegister instance
     */
    private $register;

    /**
     * Ref to mod_attendanceregister_user_capablities instance
     */
    private $userCapabilites;


    /**
     * Constructor
     * Load all tracked User's and their summaris
     * Load list of tracked Courses
     *
     * @param object                              $register
     * @param attendanceregister_user_capablities $userCapabilities
     */
    function __construct($register, attendanceregister_user_capablities $userCapabilities, $groupId) 
    {
        $this->register = $register;
        $this->userCapabilities = $userCapabilities;
        $this->users = attendanceregister_get_tracked_users($register, $groupId);
        $this->trackedCourses = new attendanceregister_tracked_courses($register);
        $trackedUsersIds = attendanceregister__extract_property($this->users, 'id');
        // Retrieve Aggregates summaries.
        $aggregates = attendanceregister__get_all_users_aggregate_summaries($register);
        // Remap in an array of attendanceregister_user_aggregates_summary, mapped by userId.
        $this->usersSummaryAggregates = array();
        foreach ($aggregates as $aggregate) {
            // Retain only tracked users.
            if (in_array($aggregate->userid, $trackedUsersIds) ) {
                // Create User's attendanceregister_user_aggregates_summary instance if not exists.
                if (!isset($this->usersSummaryAggregates[ $aggregate->userid ])) {
                    $this->usersSummaryAggregates[ $aggregate->userid ] = new attendanceregister_user_aggregates_summary();
                }
                // Populate attendanceregister_user_aggregates_summary fields.
                if($aggregate->grandtotal ) {
                    $this->usersSummaryAggregates[ $aggregate->userid ]->grandTotalduration = $aggregate->duration;
                    $this->usersSummaryAggregates[ $aggregate->userid ]->lastSassionLogout = $aggregate->lastsessionlogout;
                } else if ($aggregate->total && $aggregate->onlinesess == 1 ) {
                    $this->usersSummaryAggregates[ $aggregate->userid ]->onlineTotalduration = $aggregate->duration;
                } else if ($aggregate->total && $aggregate->onlinesess == 0 ) {
                    $this->usersSummaryAggregates[ $aggregate->userid ]->offlineTotalduration = $aggregate->duration;
                }
            }
        }
    }

    /**
     * Build the html_table object to represent details
     *
     * @return html_table
     */
    public function html_table() {
        $table = new html_table();
        $table->attributes['class'] .=
            ' attendanceregister_userlist table table-condensed table-bordered table-striped table-hover';

        // Header.

        $table->head = array(
            get_string('count', 'attendanceregister'),
            get_string('fullname', 'attendanceregister'),
            get_string('total_time_online', 'attendanceregister'),
        );
        $table->align = array('left', 'left', 'right');

        if ($this->register->offlinesessions ) {
            $table->head[] = get_string('total_time_offline', 'attendanceregister');
            $table->align[] = 'right';
            $table->head[] = get_string('grandtotal_time', 'attendanceregister');
            $table->align[] = 'right';
        }

        $table->head[] = get_string('last_session_logout', 'attendanceregister');
        $table->align[] = 'left';


        // Table rows.

        if($this->users ) {
            $rowcount = 0;
            foreach ($this->users as $user) {
                $rowcount++;

                $userAggregate = null;
                if (isset($this->usersSummaryAggregates[$user->id]) ) {
                    $userAggregate = $this->usersSummaryAggregates[$user->id];
                }

                // Basic columns.
                $linkUrl = attendanceregister_makeUrl($this->register, $user->id);
                $fullnameWithLink = '<a href="' . $linkUrl . '">' . fullname($user) . '</a>';
                $onlineduration = ($userAggregate)?( $userAggregate->onlineTotalduration ):( null );
                $onlinedurationstr =  attendanceregister_format_duration($onlineduration);
                $tablerow = new html_table_row(array( $rowcount, $fullnameWithLink, $onlinedurationstr ));

                // Add class for zebra stripes.
                $tablerow->attributes['class'] .=
                   ($rowcount % 2) ? ' attendanceregister_oddrow' : ' attendanceregister_evenrow';

                // Optional columns.
                if ($this->register->offlinesessions ) {
                    $offlineduration = ($userAggregate)?($userAggregate->offlineTotalduration):( null );
                    $offlinedurationstr = attendanceregister_format_duration($offlineduration);
                    $tablecell = new html_table_cell($offlinedurationstr);
                    $tablerow->cells[] = $tablecell;

                    $grandtotalduration = ($userAggregate)?($userAggregate->grandTotalduration ):( null );
                    $grandtotaldurationstr = attendanceregister_format_duration($grandtotalduration);
                    $tablecell = new html_table_cell($grandtotaldurationstr);
                    $tablerow->cells[] = $tablecell;
                }

                $lastSessionLogoutStr = 
                   $userAggregate ? attendanceregister__formatDateTime($userAggregate->lastSassionLogout) : get_string('no_session', 'attendanceregister');
                $tablecell = new html_table_cell($lastSessionLogoutStr);
                $tablerow->cells[] = $tablecell;

                $table->data[] = $tablerow;
            }
        } else {
            // No User.
            $row = new html_table_row();
            $labelcell = new html_table_cell();
            $labelcell->colspan = count($table->head);
            $labelcell->text = get_string('no_tracked_user', 'attendanceregister');
            $row->cells[] = $labelcell;
            $table->data[] = $row;
        }

        return $table;
    }
}

