SELECT u.id FROM mdl_user u JOIN 
    (SELECT DISTINCT eu24_u.id FROM mdl_user eu24_u
            JOIN mdl_user_enrolments ej24_ue ON ej24_ue.userid = eu24_u.id
            JOIN mdl_enrol ej24_e ON (ej24_e.id = ej24_ue.enrolid AND ej24_e.courseid = 36)
            JOIN (SELECT DISTINCT userid FROM mdl_role_assignments
                       WHERE contextid IN (1,3,19423) AND roleid IN (5) ) ra ON ra.userid = eu24_u.id
            WHERE 1 = 1 AND eu24_u.deleted = 0 AND eu24_u.id <> 1 AND eu24_u.deleted = 0) je ON je.id = u.id
    WHERE u.lastaccess + (30 * 60) < 1701094029
        AND (NOT EXISTS (SELECT * FROM mdl_attendanceregister_session as3 WHERE as3.userid = u.id AND as3.register = 29 AND as3.onlinesess = 1)
            OR NOT EXISTS (SELECT * FROM mdl_attendanceregister_aggregate aa4 WHERE aa4.userid=u.id AND aa4.register=29 AND aa4.grandtotal = 1)
            OR EXISTS (SELECT * FROM mdl_attendanceregister_aggregate aa2, mdl_logstore_standard_log l2
                                    WHERE aa2.userid = u.id AND aa2.register = 29
                                      AND l2.course = 36 AND l2.userid = aa2.userid
                                      AND aa2.grandtotal = 1
                                      AND l2.time > aa2.lastsessionlogout)
            )


(NOT EXISTS (SELECT * FROM mdl_attendanceregister_session as3 WHERE as3.userid = 2372 AND as3.register = 29 AND as3.onlinesess = 1)
            OR NOT EXISTS (SELECT * FROM mdl_attendanceregister_aggregate aa4 WHERE aa4.userid=2372 AND aa4.register=29 AND aa4.grandtotal = 1)
            OR EXISTS (SELECT * FROM mdl_attendanceregister_aggregate aa2, mdl_logstore_standard_log l2
                                    WHERE aa2.userid = 2372 AND aa2.register = 29
                                      AND l2.courseid = 36 AND l2.userid = aa2.userid
                                      AND aa2.grandtotal = 1
                                      AND l2.timecreated > aa2.lastsessionlogout))

[array (
  0 => '36',
  1 => '1',
  2 => '30',
  3 => 1701094029,
  4 => '29',
  5 => '29',
  6 => '29',
  7 => '36',
)]                                      



SELECT u.id, u.username, u.email FROM mdl_user u 
                    JOIN mdl_user_lastaccess ula ON u.id = ula.userid
                    JOIN (SELECT DISTINCT eu24_u.id
              FROM mdl_user eu24_u
            JOIN mdl_user_enrolments ej24_ue ON ej24_ue.userid = eu24_u.id
JOIN mdl_enrol ej24_e ON (ej24_e.id = ej24_ue.enrolid AND ej24_e.courseid = 36)
JOIN (SELECT DISTINCT userid
                                FROM mdl_role_assignments
                               WHERE contextid IN (1,3,19423)
                                     AND roleid IN (5)
                             ) ra ON ra.userid = eu24_u.id
             WHERE 1 = 1 AND eu24_u.deleted = 0 AND eu24_u.id <> 1 AND eu24_u.deleted = 0) je ON ula.id = je.id
                WHERE u.lastaccess > 0 AND u.lastaccess + (30 * 60) < now()
                AND ula.courseid = 36
                AND (NOT EXISTS (SELECT * FROM mdl_attendanceregister_session as3 WHERE as3.userid = u.id AND as3.register = 29 AND as3.onlinesess = 1)
                OR NOT EXISTS (SELECT * FROM mdl_attendanceregister_aggregate aa4 WHERE aa4.userid = u.id AND aa4.register = 29 AND aa4.grandtotal = 1)
            OR EXISTS (SELECT * FROM mdl_attendanceregister_aggregate aa2, mdl_logstore_standard_log l2
                                    WHERE aa2.userid = u.id AND aa2.register = 29
                                      AND l2.courseid = 36 AND l2.userid = aa2.userid
                                      AND aa2.grandtotal = 1
                                      AND l2.timecreated > aa2.lastsessionlogout))

     
SELECT u.id, u.username, u.email FROM mdl_user u JOIN mdl_user_lastaccess ula ON ula.userid = u.id
WHERE ula.courseid = 36
                AND (NOT EXISTS (SELECT * FROM mdl_attendanceregister_session as3 WHERE as3.userid = u.id AND as3.register = 29 AND as3.onlinesess = 1)
                OR NOT EXISTS (SELECT * FROM mdl_attendanceregister_aggregate aa4 WHERE aa4.userid = u.id AND aa4.register = 29 AND aa4.grandtotal = 1)
            OR EXISTS (SELECT * FROM mdl_attendanceregister_aggregate aa2, mdl_logstore_standard_log l2
                                    WHERE aa2.userid = u.id AND aa2.register = 29
                                      AND l2.courseid = 36 AND l2.userid = aa2.userid
                                      AND aa2.grandtotal = 1
                                      AND l2.timecreated > aa2.lastsessionlogout))


SELECT u.id, u.username, u.email FROM mdl_user u 
                    JOIN (SELECT DISTINCT eu24_u.id
                        FROM mdl_user eu24_u
                        JOIN mdl_user_enrolments ej24_ue ON ej24_ue.userid = eu24_u.id
                        JOIN mdl_enrol ej24_e ON (ej24_e.id = ej24_ue.enrolid AND ej24_e.courseid = 36)
                        JOIN (SELECT DISTINCT userid
                                FROM mdl_role_assignments
                                WHERE contextid IN (1,3,19423)
                                     AND roleid IN (5)
                             ) ra ON ra.userid = eu24_u.id
             WHERE 1 = 1 AND eu24_u.deleted = 0 AND eu24_u.id <> 1 AND eu24_u.deleted = 0) je ON u.id = je.id
    JOIN mdl_user_lastaccess ula ON je.id = ula.userid
                WHERE u.lastaccess > 0 AND u.lastaccess + (30 * 60) < now()
                AND ula.courseid = 36