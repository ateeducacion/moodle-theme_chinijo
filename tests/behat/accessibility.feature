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
    Then I should see "0 of 1 activities done"
    And "img[alt='Book']" "css_element" should exist
    And the page should meet accessibility standards
    And the "region-main" "region" should meet accessibility standards with "best-practice" extra tests

  Scenario: Encouragement after marking an activity as done
    Given the following "activities" exist:
      | activity | course | name       | section | completion |
      | page     | C1     | Count more | 1       | 1          |
    And I log in as "student1"
    And I am on "Course 1" course homepage
    When I toggle the manual completion state of "Read the story"
    Then I should see "Well done, Student!"
    And the page should meet accessibility standards
    And the "[data-region='theme_chinijo-cheer']" "css_element" should meet accessibility standards with "best-practice" extra tests

  Scenario: Activity page and assignment submission form
    Given I am on the "Read the story" "page activity" page logged in as "student1"
    And the page should meet accessibility standards
    And the "region-main" "region" should meet accessibility standards with "best-practice" extra tests
    When I am on the "Draw your family" "assign activity" page
    And I press "Add submission"
    Then the page should meet accessibility standards
    And the "region-main" "region" should meet accessibility standards with "best-practice" extra tests

  Scenario Outline: Quiz attempt, choice, book and forum pages in every contrast
    Given the following "user preferences" exist:
      | user     | preference             | value      |
      | student1 | theme_chinijo_contrast | <contrast> |
    And the following "activities" exist:
      | activity | course | name                  | intro                          | idnumber | option   | section |
      | quiz     | C1     | Animals quiz          | Answer two questions.          | quiz1    |          | 2       |
      | choice   | C1     | Your favourite animal | Which animal do you like best? | choice1  | Cat, Dog | 2       |
      | book     | C1     | Animals at home       | Two animals of the home.       | book1    |          | 2       |
      | forum    | C1     | Show and tell         | Tell us about an animal.       | forum1   |          | 2       |
    And the following "question categories" exist:
      | contextlevel    | reference | name           |
      | Activity module | quiz1     | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype       | name | questiontext       |
      | Test questions   | truefalse   | TF1  | The cat says miau. |
      | Test questions   | multichoice | MC1  | Which one is a pet? |
    And quiz "Animals quiz" contains the following questions:
      | question | page |
      | TF1      | 1    |
      | MC1      | 2    |
    And the following "mod_book > chapters" exist:
      | book            | title   | content                           | pagenum |
      | Animals at home | The dog | The dog likes to run in the park. | 1       |
    And the following "mod_forum > discussions" exist:
      | user     | forum  | name        | message                     |
      | teacher1 | forum1 | My cat Luna | This is my cat. And yours? |
    And I am on the "Animals quiz" "quiz activity" page logged in as "student1"
    # The quiz attempt is checked against WCAG A/AA only: Moodle's question number is an h3 right
    # after the page's h1, which fails the best-practice "heading-order" rule with theme_boost too (F-06).
    When I press "Attempt quiz"
    Then the page should meet accessibility standards
    And the "region-main" "region" should meet accessibility standards
    And I press "Next page"
    And the page should meet accessibility standards
    And I am on the "Your favourite animal" "choice activity" page
    And the page should meet accessibility standards
    And the "region-main" "region" should meet accessibility standards with "best-practice" extra tests
    And I am on the "Animals at home" "book activity" page
    And the page should meet accessibility standards
    And the "region-main" "region" should meet accessibility standards with "best-practice" extra tests
    And I am on the "Show and tell" "forum activity" page
    And I press "More options"
    And the page should meet accessibility standards
    And the "region-main" "region" should meet accessibility standards with "best-practice" extra tests
    # A discussion is checked against WCAG A/AA only: Moodle prints two "Browse discussions" navigation
    # landmarks with the same name, which fails the best-practice "landmark-unique" rule with theme_boost too (F-07).
    And I click on "My cat Luna" "link"
    And the page should meet accessibility standards
    And the "region-main" "region" should meet accessibility standards

    Examples:
      | contrast |
      | default  |
      | high     |

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
