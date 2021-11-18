@iplus @mod @mod_attendanceregister
Feature: attendance register
  Background:
    Given the following "courses" exist:
      | fullname | idnumber  | shortname |
      | Course 1 | ENPRO     | ENPRO     |
      | Course 2 | FRMAS     | FRMAS     |
    And the following "users" exist:
      | username  | firstname | lastname |
      | user1     | Username  | 1        |
      | user2     | Username  | 2        |
      | teacher1  | Teacher   | 1        |
      | manager1  | Manager   | 1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | user1    | ENPRO  | student        |
      | user1    | FRMAS  | student        |
      | user2    | ENPRO  | student        |
      | teacher1 | ENPRO  | editingteacher |
    And the following "system role assigns" exist:
      | user     | course | role    |
      | manager1 | ENPRO  | manager |
    And the following "activities" exist:
      | activity           | name         | intro   | course | idnumber    |
      | page               | page         | Test l  | ENPRO  | page1       |
      | page               | page         | Test 2  | FRMAS  | page2       |
      | attendanceregister | attendance   | Test 3  | ENPRO  | attendance1 |
      | attendanceregister | attendance   | Test 4  | FRMAS  | attendance2 |

  Scenario: Students should be tracked
    When I am on the "page2" "page activity" page logged in as user1
    And I log out
    And I trigger cron
    When I am on the "attendance1" "attendanceregister activity" page logged in as teacher1
    Then I should see "Username 1"
    And I should see "Username 2"
    And I am on the "attendance2" "attendanceregister activity" page logged in as manager1
    Then I should see "Username 1"
    And I should not see "Username 2"

  Scenario: Teachers can be tracked
    When I am on the "page2" "page activity" page logged in as teacher1
    And I log out
    And I trigger cron
    When I am on the "attendance1" "attendanceregister activity" page logged in as manager1
    Then I should see "Username 1"
    And I should see "Username 2"
    And I should see "Teacher 1"

  Scenario: Managers can be tracked
    When I am on the "page1" "page activity" page logged in as manager1
    And I log out
    And I trigger cron
    When I am on the "attendance1" "attendanceregister activity" page logged in as teacher1
    Then I should see "Username 1"
    And I should see "Username 2"
    And I should see "Teacher 1"
    And I should see "Manager 1"
    And I am on the "attendance2" "attendanceregister activity" page logged in as manager1
    Then I should see "Username 1"
    And I should not see "Username 2"
    And I should not see "Teacher 1"
    And I should see "Manager 1"
    