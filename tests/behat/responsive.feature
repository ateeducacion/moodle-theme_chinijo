@theme @theme_chinijo @javascript
Feature: Chinijo on phones and tablets
  In order to use Moodle on the school's tablets
  As a learner
  I need pages that reflow, with large enough targets, even with the largest text

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
      | activity | course | name           | completion |
      | page     | C1     | Read the story | 1          |
    And the following "theme_chinijo > pictograms" exist:
      | course | activity       | alttext |
      | C1     | Read the story | Book    |
    And the following "user preferences" exist:
      | user     | preference               | value   |
      | student1 | theme_chinijo_fontsize   | xxlarge |
      | student1 | theme_chinijo_lineheight | wider   |

  Scenario Outline: The course page and the display settings reflow and keep large targets
    Given I change viewport size to "<size>"
    And I log in as "student1"
    When I am on "Course 1" course homepage
    Then I should see "0 of 1 activities done"
    And "img[alt='Book']" "css_element" should exist
    And the page should not scroll horizontally
    And the ".theme-chinijo-next__start" "css_element" should be at least "44" pixels wide and high
    And the "Display settings" "button" should be at least "44" pixels wide and high
    And I click on "Display settings" "button"
    And the "label[for='theme-chinijo-pref-dlg-contrast-high']" "css_element" should be at least "44" pixels wide and high
    And the "Save" "button" should be at least "44" pixels wide and high
    And the page should not scroll horizontally

    Examples:
      | size      |
      | 425x750   |
      | 768x1024  |
      | 1024x768  |
