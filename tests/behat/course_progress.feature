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
  Scenario: Marking an activity as done updates the path and encourages the learner
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I should see "Hello, Student!"
    And I should see "0 of 2 activities done"
    And I should see "Page one" in the "[data-region='theme_chinijo-next']" "css_element"
    When I toggle the manual completion state of "Page one"
    Then I should see "Well done, Student!"
    And I should see "You have finished “Page one”. You have done 1 of 2."
    And I should see "1 of 2 activities done"
    And I should see "Page two" in the "[data-region='theme_chinijo-next']" "css_element"
    And I should see "Done" in the ".theme-chinijo-path__stop[data-state='done']" "css_element"
    And I click on "Close the message" "button"
    And I should not see "Well done, Student!"
    And I reload the page
    And I should see "1 of 2 activities done"
    And I should see "Page two" in the "[data-region='theme_chinijo-next']" "css_element"
    And I toggle the manual completion state of "Page one"
    And I should see "“Page one” is no longer marked as done."
    And I should see "0 of 2 activities done"
    And I should see "Page one" in the "[data-region='theme_chinijo-next']" "css_element"

  @javascript
  Scenario: The encouragement leads to the next activity, and the last one to the end of the path
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    When I toggle the manual completion state of "Page one"
    And I click on "Keep going: Page two" "link"
    Then I should see "Page two" in the "#page-header" "css_element"
    And I should see "1 of 2 activities done"
    And I click on "Back to the course" "link"
    And I toggle the manual completion state of "Page two"
    And I should see "You have done every activity. Well done!"
    And "Keep going" "link" should not exist

  Scenario: Start opens the next activity, and activity pages lead back to the course
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    When I click on "Start: Page one" "link"
    Then I should see "0 of 2 activities done"
    And "Course 1" "link" should exist in the ".breadcrumb" "css_element"
    And I click on "Back to the course" "link"
    And I should see "My path"

  Scenario: Teachers and courses without completion show no path
    Given I log in as "teacher1"
    When I am on "Course 1" course homepage
    Then I should not see "My path"
    And I should not see "activities done"
    And I log out
    And I log in as "student1"
    And I am on "Course 2" course homepage
    And I should not see "My path"
    And I should see "Hello, Student!"
