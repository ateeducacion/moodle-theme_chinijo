@theme @theme_chinijo @javascript
Feature: Moodle activities drawn for young learners
  In order to know what to do in each activity and to do it without help
  As a learner
  I need clear verbs, steps and large controls in assignments, quizzes, choices, forums and books

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
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | teacher1 | C1     | editingteacher |

  Scenario: Activity cards show what the learner does in each activity
    Given the following "activities" exist:
      | activity | course | name             | intro                         | completion | assignsubmission_onlinetext_enabled |
      | assign   | C1     | Draw your family | Draw the people in your home. | 1          | 1                                   |
      | page     | C1     | Read the story   | A story.                      | 1          |                                     |
      | label    | C1     | Note             | Bring your crayons tomorrow.  | 0          |                                     |
    When I am on the "Course 1" course page logged in as "student1"
    Then I should see "Up next" in the "[data-region='theme_chinijo-next']" "css_element"
    And I should see "Hand in" in the "[data-region='theme_chinijo-next']" "css_element"
    And I should see "Hand in" in the "[data-activityname='Draw your family']" "css_element"
    And I should see "Read" in the "[data-activityname='Read the story']" "css_element"
    And I should see "Bring your crayons tomorrow."
    And "Bring your crayons tomorrow." "text" should exist in the ".modtype_label" "css_element"
    And ".modtype_label .theme-chinijo-verb" "css_element" should not exist

  Scenario: An assignment shows its steps and whether the work has been handed in
    Given the following "activities" exist:
      | activity | course | name             | intro                         | assignsubmission_onlinetext_enabled | submissiondrafts |
      | assign   | C1     | Draw your family | Draw the people in your home. | 1                                   | 0                |
    When I am on the "Draw your family" "assign activity" page logged in as "student1"
    Then I should see "Read" in the "[data-region='theme_chinijo-steps']" "css_element"
    And I should see "Do your work" in the "[data-region='theme_chinijo-steps']" "css_element"
    And I should see "Hand it in" in the "[data-region='theme_chinijo-steps']" "css_element"
    And I should see "You have not handed it in yet."
    And the "Add submission" "button" should be at least "56" pixels wide and high
    And I press "Add submission"
    And I set the field "Online text" to "My mum, my dad and me."
    And I press "Save changes"
    And I should see "You have handed it in. Well done!"
    And I am on the "Draw your family" "assign activity" page logged in as "teacher1"
    And "[data-region='theme_chinijo-steps']" "css_element" should not exist

  Scenario: A quiz question can be answered by pressing anywhere on a large answer
    Given the following "activities" exist:
      | activity | course | name         | idnumber |
      | quiz     | C1     | Animals quiz | quiz1    |
    And the following "question categories" exist:
      | contextlevel    | reference | name           |
      | Activity module | quiz1     | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name | questiontext       |
      | Test questions   | truefalse | TF1  | The cat says miau. |
    And quiz "Animals quiz" contains the following questions:
      | question | page |
      | TF1      | 1    |
    And I am on the "Animals quiz" "quiz activity" page logged in as "student1"
    When I press "Attempt quiz"
    Then I should see "The cat says miau."
    And the ".que .answer .r0" "css_element" should be at least "56" pixels wide and high
    And I click on ".que .answer .r0" "css_element"
    And the field "True" matches value "1"
    And I click on ".que .answer .r1" "css_element"
    And the field "False" matches value "1"

  Scenario: A choice option can be chosen by pressing anywhere on it
    Given the following "activities" exist:
      | activity | course | name                  | intro                          | option   |
      | choice   | C1     | Your favourite animal | Which animal do you like best? | Cat, Dog |
    And I am on the "Your favourite animal" "choice activity" page logged in as "student1"
    And the "#page-mod-choice-view .choices .option" "css_element" should be at least "56" pixels wide and high
    When I click on "Dog" "text" in the ".choices" "css_element"
    And I press "Save my choice"
    Then I should see "Your choice has been saved"

  Scenario: Learners find the less used forum controls behind "More options"
    Given the following "activities" exist:
      | activity | course | name          | intro                            |
      | forum    | C1     | Show and tell | Tell us about an animal you know. |
    And I am on the "Show and tell" "forum activity" page logged in as "student1"
    And "Add discussion topic" "link" should be visible
    And "Search forums" "field" should not be visible
    When I press "More options"
    Then "Search forums" "field" should be visible
    And I press "Fewer options"
    And "Search forums" "field" should not be visible
    And I am on the "Show and tell" "forum activity" page logged in as "teacher1"
    And "More options" "button" should not exist
    And "Search forums" "field" should be visible

  Scenario: A book shows the next chapter as a large button
    Given the following "activities" exist:
      | activity | course | name            | intro                    |
      | book     | C1     | Animals at home | Two animals of the home. |
    And the following "mod_book > chapters" exist:
      | book            | title   | content                          | pagenum |
      | Animals at home | The dog | The dog likes to run in the park. | 1       |
      | Animals at home | The cat | The cat likes to sleep.           | 2       |
    When I am on the "Animals at home" "book activity" page logged in as "student1"
    Then I should see "The dog likes to run in the park."
    And the "Next: The cat" "link" should be at least "56" pixels wide and high
    And I click on "Next: The cat" "link"
    And I should see "The cat likes to sleep."
