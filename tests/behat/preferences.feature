@theme @theme_chinijo
Feature: Personal display settings
  In order to read Moodle comfortably
  As a learner
  I need to choose my own contrast, text size, typeface, spacing and motion settings

  Background:
    Given the following config values are set as admin:
      | theme | chinijo |
    And the following "users" exist:
      | username | firstname | lastname |
      | student1 | Student   | One      |
      | teacher1 | Teacher   | One      |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | teacher1 | C1     | editingteacher |

  @javascript
  Scenario: A student changes their display settings with the keyboard and keeps them
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    When I set the focus on the "Display settings" "button"
    And I press the space key
    Then "Display settings" "dialogue" should be visible
    And I click on "High contrast" "radio"
    And the "data-chinijo-contrast" attribute of "html" "css_element" should contain "high"
    And I click on "Huge" "radio"
    And I click on "Easy to read (Atkinson Hyperlegible)" "radio"
    And I click on "Extra wide" "radio" in the "Space between lines" "fieldset"
    And I press "Save"
    And I should see "Your display settings have been saved."
    And the focused element is "Display settings" "button"
    And I reload the page
    And the "data-chinijo-contrast" attribute of "html" "css_element" should contain "high"
    And the "data-chinijo-fontsize" attribute of "html" "css_element" should contain "xxlarge"
    And the "data-chinijo-font" attribute of "html" "css_element" should contain "legible"
    And the "data-chinijo-lineheight" attribute of "html" "css_element" should contain "wider"

  @javascript
  Scenario: Display settings belong to each person only
    Given the following "user preferences" exist:
      | user     | preference             | value |
      | student1 | theme_chinijo_contrast | high  |
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    Then the "data-chinijo-contrast" attribute of "html" "css_element" should contain "default"
    And I log out
    And I log in as "student1"
    And the "data-chinijo-contrast" attribute of "html" "css_element" should contain "high"

  @javascript
  Scenario: Closing the dialogue without saving keeps the saved settings
    Given I log in as "student1"
    And I click on "Display settings" "button"
    And I click on "Large" "radio"
    And the "data-chinijo-fontsize" attribute of "html" "css_element" should contain "large"
    When I press the escape key
    Then the "data-chinijo-fontsize" attribute of "html" "css_element" should contain "default"
    And the focused element is "Display settings" "button"

  @javascript
  Scenario: A student restores the default display settings
    Given the following "user preferences" exist:
      | user     | preference                  | value  |
      | student1 | theme_chinijo_fontsize      | xlarge |
      | student1 | theme_chinijo_letterspacing | wider  |
    And I log in as "student1"
    And the "data-chinijo-fontsize" attribute of "html" "css_element" should contain "xlarge"
    When I click on "Display settings" "button"
    And I press "Restore default settings"
    Then I should see "Your display settings are back to their defaults."
    And the "data-chinijo-fontsize" attribute of "html" "css_element" should contain "default"
    And I reload the page
    And the "data-chinijo-letterspacing" attribute of "html" "css_element" should contain "default"

  Scenario: The display settings page works without JavaScript
    Given I log in as "student1"
    When I am on the "theme_chinijo > Display settings" page
    And I set the field "Large" to "1"
    And I set the field "Reduce animations" to "1"
    And I press "Save"
    Then I should see "Your display settings have been saved."
    And the "data-chinijo-fontsize" attribute of "html" "css_element" should contain "large"
    And the "data-chinijo-motion" attribute of "html" "css_element" should contain "reduce"

  Scenario: Visitors who are not logged in keep their settings for the session only
    Given I visit "/login/index.php"
    When I click on "Display settings" "link"
    Then I should see "You are not logged in, so these settings are only kept until you close your browser."
    And I set the field "High contrast" to "1"
    And I press "Save"
    And I visit "/login/index.php"
    And the "data-chinijo-contrast" attribute of "html" "css_element" should contain "high"

  Scenario: Display settings are linked from the user's preferences page
    Given I log in as "student1"
    When I follow "Preferences" in the user menu
    And I follow "Display settings"
    Then I should see "Choose how pages look for you."
