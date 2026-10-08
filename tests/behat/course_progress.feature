@theme @theme_chinijo
Feature: Course progress and completion feedback
  In order to know where I am in a course
  As a learner
  I need to see my real progress and a gentle confirmation when I finish an activity

  Background:
    Given the following config values are set as admin:
      | theme | chinijo |
    And the following "users" exist:
      | username | firstname | lastname |
      | student1 | Student   | One      |
      | teacher1 | Teacher   | One      |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | C1        | 1                |
      | Course 2 | C2        | 0                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | teacher1 | C1     | editingteacher |
      | student1 | C2     | student        |
    And the following "activities" exist:
      | activity | course | name     | completion |
      | page     | C1     | Page one | 1          |
      | page     | C1     | Page two | 1          |
      | page     | C2     | Page     | 0          |

  @javascript
  Scenario: Marking an activity as done updates the progress after Moodle confirms it
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I should see "Your progress: 0 of 2 activities completed (0%)"
    When I toggle the manual completion state of "Page one"
    Then I should see "Well done! “Page one” is marked as done."
    And I should see "Your progress: 1 of 2 activities completed (50%)"
    And I reload the page
    And I should see "Your progress: 1 of 2 activities completed (50%)"
    And I toggle the manual completion state of "Page one"
    And I should see "“Page one” is no longer marked as done."
    And I should see "Your progress: 0 of 2 activities completed (0%)"

  Scenario: Activity pages show the breadcrumb and the progress
    Given I am on the "Page one" "page activity" page logged in as "student1"
    Then I should see "Your progress: 0 of 2 activities completed (0%)"
    And "Course 1" "link" should exist in the ".breadcrumb" "css_element"

  Scenario: Teachers and courses without completion show no progress
    Given I log in as "teacher1"
    When I am on "Course 1" course homepage
    Then I should not see "Your progress"
    And I log out
    And I log in as "student1"
    And I am on "Course 2" course homepage
    And I should not see "Your progress"
