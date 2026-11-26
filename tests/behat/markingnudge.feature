@block @block_markingnudge
Feature: Marking nudge on the Dashboard
  In order to know when marking is waiting
  As an assessor
  I need a nudge on my Dashboard that takes me to the marking page

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Tess      | Teacher  |
      | student1 | Sam       | Student  |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | teacher1 | C1     | teacher |
      | student1 | C1     | student |

  Scenario: An assessor adds the block and goes to the marking page
    Given I log in as "teacher1"
    And I turn editing mode on
    And I add the "Marking nudge" block
    Then I should see "All caught up." in the "Marking nudge" "block"
    And I click on "Go to marking" "link" in the "Marking nudge" "block"
    And I should see "Course 1"
