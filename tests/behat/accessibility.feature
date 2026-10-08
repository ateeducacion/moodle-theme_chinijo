@theme @theme_chinijo @javascript @accessibility
Feature: Automated accessibility checks of the pages Chinijo changes
  In order to keep Chinijo usable with assistive technologies
  As a developer
  I need the representative pages to pass axe-core (WCAG 2.2 A and AA, plus best practices)

  # Pages with the course index drawer are checked against WCAG A/AA as a whole, and against the
  # best-practice rules in the main region and the dialogue. Reason: on Moodle 5.3, Boost itself fails the
  # best-practice "region" rule on the new course index drawer heading (.drawerheading, MDL-89050); the same
  # finding appears with theme_boost and is recorded in docs/accessibility-audit.md as a core issue.

  Background:
    Given the following config values are set as admin:
      | theme | chinijo |
    And the following config values are set as admin:
      | federenabled | 1                            | theme_chinijo |
      | federtext    | Project co-funded by the EU. | theme_chinijo |
    And the following "users" exist:
      | username | firstname | lastname |
      | student1 | Student   | One      |
      | teacher1 | Teacher   | One      |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion | numsections |
      | Course 1 | C1        | 1                | 2           |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
      | activity | course | name              | section | completion | assignsubmission_onlinetext_enabled | submissiondrafts |
      | page     | C1     | Read the story    | 1       | 1          |                                     |                  |
      | assign   | C1     | Draw your family  | 2       | 0          | 1                                   | 0                |
    And the following "theme_chinijo > pictograms" exist:
      | course | activity       | section | alttext | author          | license      |
      | C1     | Read the story |         | Book    | Test pictograms | cc-nc-sa-4.0 |
      | C1     |                | 1       | Sun     |                 |              |

  Scenario: Login page with the display settings toolbar and the EU funding notice
    When I visit "/login/index.php"
    Then I should see "Display settings"
    And I should see "Project co-funded by the EU."
    And the page should meet accessibility standards with "best-practice" extra tests

  Scenario: Dashboard and course page of a learner
    Given I log in as "student1"
    And the page should meet accessibility standards with "best-practice" extra tests
    When I am on "Course 1" course homepage
    Then I should see "Your progress: 0 of 1 activities completed (0%)"
    And "img[alt='Book']" "css_element" should exist
    And the page should meet accessibility standards
    And the "region-main" "region" should meet accessibility standards with "best-practice" extra tests

  Scenario: Activity page and assignment submission form
    Given I am on the "Read the story" "page activity" page logged in as "student1"
    And the page should meet accessibility standards
    And the "region-main" "region" should meet accessibility standards with "best-practice" extra tests
    When I am on the "Draw your family" "assign activity" page
    And I press "Add submission"
    Then the page should meet accessibility standards
    And the "region-main" "region" should meet accessibility standards with "best-practice" extra tests

  Scenario Outline: Display settings dialogue in every contrast and size
    Given the following "user preferences" exist:
      | user     | preference             | value      |
      | student1 | theme_chinijo_contrast | <contrast> |
      | student1 | theme_chinijo_fontsize | <fontsize> |
      | student1 | theme_chinijo_font     | <font>     |
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And the page should meet accessibility standards
    And the "region-main" "region" should meet accessibility standards with "best-practice" extra tests
    When I click on "Display settings" "button"
    Then "Display settings" "dialogue" should be visible
    And the "[role=dialog]" "css_element" should meet accessibility standards with "best-practice" extra tests

    Examples:
      | contrast | fontsize | font    |
      | default  | default  | default |
      | high     | xxlarge  | legible |
      | high     | large    | default |

  Scenario: Stand-alone display settings page
    Given I log in as "student1"
    When I am on the "theme_chinijo > Display settings" page
    Then the page should meet accessibility standards with "best-practice" extra tests

  Scenario: Pictogram management, its form and an error state
    Given I am on the "C1" "theme_chinijo > Pictograms" page logged in as "teacher1"
    And the page should meet accessibility standards
    And the "region-main" "region" should meet accessibility standards with "best-practice" extra tests
    When I click on "Choose a pictogram: Draw your family" "link"
    And the page should meet accessibility standards
    And the "region-main" "region" should meet accessibility standards with "best-practice" extra tests
    And I press "Save changes"
    Then I should see "Required"
    And the page should meet accessibility standards
    And the "region-main" "region" should meet accessibility standards with "best-practice" extra tests
