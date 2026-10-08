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
      | username | firstname | lastname | lang |
      | alumno1  | Alumno    | Uno      | es   |
      | student1 | Student   | One      | en   |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Curso 1  | C1        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | alumno1  | C1     | student |
      | student1 | C1     | student |
    And the following "activities" exist:
      | activity | course | name    | completion |
      | page     | C1     | Cuento  | 1          |

  @javascript
  Scenario: A Spanish-speaking learner gets the theme in Spanish
    Given I log in as "alumno1"
    When I am on "Curso 1" course homepage
    Then I should see "Ajustes de visualización"
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
