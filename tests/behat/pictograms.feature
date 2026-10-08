@theme @theme_chinijo @_file_upload
Feature: Pictograms for course sections and activities
  In order to help learners who find reading difficult
  As a teacher
  I need to add pictograms to the sections and activities of my course

  Background:
    Given the following config values are set as admin:
      | theme | chinijo |
    And the following "users" exist:
      | username | firstname | lastname |
      | student1 | Student   | One      |
      | teacher1 | Teacher   | One      |
    And the following "courses" exist:
      | fullname | shortname | numsections |
      | Course 1 | C1        | 2           |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
      | activity | course | name           | section | visible |
      | page     | C1     | Read the story | 1       | 1       |
      | page     | C1     | Hidden page    | 1       | 0       |

  @javascript
  Scenario: A teacher adds a pictogram and learners see it next to the activity name
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Pictograms" in current page administration
    And I click on "Choose a pictogram: Read the story" "link"
    And I upload "theme/chinijo/tests/fixtures/pictogram-book.png" file to "Pictogram image" filemanager
    And I set the following fields to these values:
      | Text alternative  | Book             |
      | Author and source | Test pictograms  |
    And I press "Save changes"
    Then I should see "The pictogram of “Read the story” has been saved."
    And "img[alt='Book']" "css_element" should exist in the "region-main" "region"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And "img.theme-chinijo-pictogram[alt='Book']" "css_element" should exist in the "Read the story" "activity"
    And I should see "Read the story"
    And I click on "Pictogram credits" "text"
    And I should see "Test pictograms"

  @javascript
  Scenario: The text alternative is required
    Given I am on the "C1" "theme_chinijo > Pictograms" page logged in as "teacher1"
    And I click on "Choose a pictogram: Read the story" "link"
    And I upload "theme/chinijo/tests/fixtures/pictogram-book.png" file to "Pictogram image" filemanager
    And I set the field "Text alternative" to ""
    When I press "Save changes"
    Then I should see "Required"
    And "Save changes" "button" should exist

  @javascript
  Scenario: Pictograms of hidden activities and sections are not shown to students
    Given the following "theme_chinijo > pictograms" exist:
      | course | activity       | section | alttext |
      | C1     | Read the story |         | Book    |
      | C1     | Hidden page    |         | Secret  |
      | C1     |                | 2       | Sun     |
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I hide section "2"
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    Then "img[alt='Book']" "css_element" should exist
    And "img[alt='Secret']" "css_element" should not exist
    And "img[alt='Sun']" "css_element" should not exist

  @javascript
  Scenario: A teacher removes a pictogram
    Given the following "theme_chinijo > pictograms" exist:
      | course | activity       | alttext |
      | C1     | Read the story | Book    |
    And I am on the "C1" "theme_chinijo > Pictograms" page logged in as "teacher1"
    When I click on "Remove the pictogram: Read the story" "link"
    And I press "Continue"
    Then I should see "The pictogram of “Read the story” has been removed."
    And I am on "Course 1" course homepage
    And "img[alt='Book']" "css_element" should not exist

  Scenario: Students cannot manage pictograms
    Given I am on the "C1" "theme_chinijo > Pictograms" page logged in as "student1"
    Then I should see "Manage course pictograms"
    And I should not see "Choose a pictogram"

  Scenario: The pictogram page is not linked for students
    Given I log in as "student1"
    When I am on "Course 1" course homepage
    Then I should not see "Pictograms"
