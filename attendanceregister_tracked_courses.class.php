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
 * attendanceregister_tracked_courses.class.php - Class containing Attendance Register's tracked Courses
 *
 * @package mod_attendanceregister
 * @author  Lorenzo Nicora <fad@nicus.it>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 defined('MOODLE_INTERNAL') || die();

/**
 * Holds all tracked Course of an Attendance Register
 *
 * Implements method to return html_table to render it. *
 *
 * @author nicus
 */
class attendanceregister_tracked_courses
{

    /**
     * Array of Courses
     * Keyed by CourseID
     */
    public $courses;

    /**
     * Ref. to AttendanceRegister instance
     */
    private $register;

    public function __construct($register) 
    {
        $this->register = $register;
        $courses = attendanceregister_get_tracked_courses($register);
        // Save courseses using id as key.
        $this->courses = array();
        foreach ($courses as $course) {
            $this->courses[$course->id] = $course;
        }
    }

    /**
     * Build the html_table object to represent list of tracked Courses
     *
     * @return html_table
     */
    public function html_table() 
    {
        $table = new html_table();
        $table->attributes['class'] .= ' attendanceregister_courselist table table-condensed table-bordered table-striped table-hover';

        $tableHeadCell = new html_table_cell(get_string('tracked_courses', 'attendanceregister'));
        $tableHeadCell->colspan = 2;
        $table->head = array($tableHeadCell);

        $rowcount = 0;
        foreach ($this->courses as $course) {
            $rowcount++;
            $tableRow = new html_table_row(array($course->shortname, $course->fullname));

            // Add class for zebra stripes.
            $tableRow->attributes['class'] .= (  ($rowcount % 2)?' attendanceregister_oddrow':' attendanceregister_evenrow' );

            $table->data[] = $tableRow;
        }
        return $table;
    }
}
