@theme @theme_chinijo
Feature: Chinijo in English and Spanish
  In order to use the theme in the language of the school
  As a learner
  I need the theme's own interface in English and in Spanish

  Background:
    Given the Spanish language is available for theme_chinijo tests
    And the following config values are set as admin:
      | theme | chinijo |
    And the following "users" exist:
      | username | firstname | lastname |
      | student1 | Student   | One      |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Curso 1  | C1        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And the following "activities" exist:
      | activity | course | name    | completion |
      | page     | C1     | Cuento  | 1          |

  @javascript
  Scenario: A learner switches to Spanish and gets the theme in Spanish
    Given I log in as "student1"
    And I am on "Curso 1" course homepage
    And I should see "Display settings"
    When I switch the session language to "es" for theme_chinijo tests
    Then I should see "Ajustes de visualización"
    And the "lang" attribute of "html" "css_element" should contain "es"
    And I should see "Tu progreso: 0 de 1 actividades completadas (0 %)"
    And I click on "Ajustes de visualización" "button"
    And I should see "Tamaño del texto" in the "Ajustes de visualización" "dialogue"
    And I should see "Fácil de leer (Atkinson Hyperlegible)" in the "Ajustes de visualización" "dialogue"
    And I click on "Grande" "radio"
    And I press "Guardar"
    And I should see "Se han guardado tus ajustes de visualización."

  Scenario: An English-speaking learner gets the theme in English
    Given I log in as "student1"
    When I am on "Curso 1" course homepage
    Then I should see "Display settings"
    And I should see "Your progress: 0 of 1 activities completed (0%)"
