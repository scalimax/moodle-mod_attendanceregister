@iplus @mod @mod_attendanceregister
Feature: attendance register
  Background:
    Given the following "courses" exist:
      | fullname | shortname | idnumber  | numsections | startdate | enddate   | enablecompletion |
      | Course 1 | ENPRO     | ENPRO     | 2           | 957139200 | 960163200 | 0                |
      | Course 2 | FRMAS     | FRMAS     | 2           | 957139200 | 960163200 | 1                |
    And the following "users" exist:
      | username | firstname | lastname |
      | user1    | Username  | 1        |
      | user2    | Username  | 2        |
      | teacher  | Teacher   | 3        |
      | manager  | Manager   | 4        |
    And the following "course enrolments" exist:
      | user    | course   | role           |
      | user1   | ENPRO    | student        |
      | user1   | FRMAS    | student        |
      | user2   | ENPRO    | student        |
      | teacher | ENPRO    | editingteacher |
      | teacher | FRMAS    | editingteacher |
    And the following "system role assigns" exist:
      | user    | course   | role    |
      | manager | ENPRO    | manager |
    And the following "activities" exist:
      | activity           | name         | intro   | course   | idnumber    | section |
      | lesson             | lesson       | Test l  | ENPRO    | lessons1    | 1       |
      | lesson             | lesson       | Test 2  | FRMAS    | lessons2    | 1       |
      | attendanceregister | attendance   | Test 3  | ENPRO    | attendance1 | 1       |
      | attendanceregister | attendance   | Test 4  | FRMAS    | attendance2 | 1       |
    And I log in as "admin"
    And I am on "Course 2" course homepage with editing mode on
    And I add a "Page" to section "1" and I fill the form with:
      | Name                | TestPage |
      | Description         | x        |
      | Page content        | x        |
      | Completion tracking | 2        |
      | Require view        | 1        |
    And I trigger cron
    And I log out
    And I log in as "user1"
    And I am on "Course 2" course homepage
    And I follow "TestPage"
    And I log out
    And I trigger cron
    And I am on "Course 2" course homepage

  Scenario: Users should be tracked
    Given I log in as "manager"
    And I am on "Course 1" course homepage
    Then I should see "attendance"
    And I follow "attendance"
    Then I should see "user1"
    And I should not see "user2"
    And I am on "Course 2" course homepage
    Then I should see "attendance"
    And I follow "attendance"
    Then I should see "user1"
    And I should see "user2"    