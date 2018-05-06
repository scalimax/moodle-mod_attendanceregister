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
 * The attendance upgrade.
 *
 * @package   mod_attendanceregister
 * @copyright 2015 CINECA
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

/**
 * The attendance upgrade.
 *
 * @package   mod_attendanceregister
 * @copyright 2015 CINECA
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function xmldb_attendanceregister_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2012081004) {
        // Add attendanceregister_session.addedbyuserid column.

        // Define field addedbyuser to be added to attendanceregister_session.
        $table = new xmldb_table('attendanceregister_session');
        $field = new xmldb_field('addedbyuserid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED);

        // Launch add field addedbyuserid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add attendanceregister.pendingrecalc column.
        // Define field addedbyuser to be added to attendanceregister.
        $table = new xmldb_table('attendanceregister');
        $field = new xmldb_field('pendingrecalc', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 1);

        // Launch add field addedbyuserid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2012081004, 'attendanceregister');
    }

    if ($oldversion < 2013020604 ) {
        // Issue #36 and #42.
        // Rename field attendanceregister_session.online to onlinessess.
        $table = new xmldb_table('attendanceregister_session');
        $field = new xmldb_field('online', XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 1);
        if ($dbman->field_exists($table, $field) ) {
            // Rename field.
            $dbman->rename_field($table, $field, 'onlinesess');
        }

        // Rename field attendanceregister_aggregate.online to onlinessess.
        $table = new xmldb_table('attendanceregister_aggregate');
        $field = new xmldb_field('online', XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, null, null, 1);
        if ($dbman->field_exists($table, $field) ) {
            // Rename field.
            $dbman->rename_field($table, $field, 'onlinesess');
        }
    }

    if ($oldversion < 2013040605 ) {
        // Feature #7.
        // Add field attendanceregister.completiontotaldurationmins.
        $table = new xmldb_table('attendanceregister');
        $field = new xmldb_field('completiontotaldurationmins', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2013040605, 'attendanceregister');
    }
    return true;
}
