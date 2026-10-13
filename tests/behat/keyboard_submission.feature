@theme @theme_chinijo @javascript
Feature: Submitting an assignment with the keyboard only
  In order to work without a mouse
  As a learner
  I need to reach and operate every control needed to submit an assignment with the keyboard

  Background:
    Given the following config values are set as admin:
      | theme | chinijo |
    And the following "users" exist:
      | username | firstname | lastname |
      | student1 | Student   | One      |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | C1        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And the following "activities" exist:
      | activity | course | name             | assignsubmission_onlinetext_enabled | assignsubmission_file_enabled | submissiondrafts |
      | assign   | C1     | Draw your family | 1                                   | 0                             | 0                |

  Scenario: A learner reaches, opens, fills in and submits an assignment with the tab and enter keys
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    When I press tab until "Draw your family" "link" in the "region-main" "region" is focused
    And I press the enter key
    And I press tab until "Add submission" "button" is focused
    And I press the enter key
    And I set the field "Online text" to "My family has four people."
    And I press tab until "Save changes" "button" is focused
    And I press the enter key
    Then I should see "Submitted for grading"
    And I should see "My family has four people."
