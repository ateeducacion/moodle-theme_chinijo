@theme @theme_chinijo @_file_upload
Feature: European Union funding notice
  In order to meet the visibility rules of FEDER (ERDF) funding
  As an administrator
  I need to show the approved EU emblem and acknowledgement on the chosen pages

  Background:
    Given the following config values are set as admin:
      | theme | chinijo |

  Scenario: Nothing is shown until the notice is enabled and has content
    When I visit "/login/index.php"
    Then "[data-region='theme_chinijo-feder']" "css_element" should not exist
    And the following config values are set as admin:
      | federenabled | 1 | theme_chinijo |
    And I reload the page
    And "[data-region='theme_chinijo-feder']" "css_element" should not exist

  Scenario: The acknowledgement is shown on the landing pages only, unless every page is chosen
    Given the following config values are set as admin:
      | federenabled | 1                            | theme_chinijo |
      | federtext    | Project co-funded by the EU. | theme_chinijo |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    When I visit "/login/index.php"
    Then I should see "Project co-funded by the EU."
    And I log in as "admin"
    And I am on site homepage
    And I should see "Project co-funded by the EU."
    And I am on "Course 1" course homepage
    And I should not see "Project co-funded by the EU."
    And the following config values are set as admin:
      | federplacement | all | theme_chinijo |
    And I am on "Course 1" course homepage
    And I should see "Project co-funded by the EU."

  @javascript
  Scenario: An administrator uploads the approved emblem with its text alternative
    Given I log in as "admin"
    And I visit "/admin/settings.php?section=themesettingchinijo"
    And I click on "EU funding notice" "link"
    And I set the field "Show the EU funding notice" to "1"
    And I upload "theme/chinijo/tests/fixtures/pictogram-sun.png" file to "EU emblem" filemanager
    And I set the field "Emblem text alternative" to "Co-funded by the European Union"
    When I press "Save changes"
    And I log out
    And I visit "/login/index.php"
    Then "img[alt='Co-funded by the European Union']" "css_element" should exist in the "[data-region='theme_chinijo-feder']" "css_element"
