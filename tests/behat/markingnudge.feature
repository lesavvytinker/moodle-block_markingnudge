@block @block_markingnudge
Feature: Marking nudge on the Dashboard, a course or a category
  In order to know when marking is waiting
  As an assessor
  I need a nudge on my Dashboard, course or category page that takes me to the marking page

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Tess      | Teacher  |
      | student1 | Sam       | Student  |
    And the following "categories" exist:
      | name   | category | idnumber |
      | Design | 0        | DES      |
      | Other  | 0        | OTH      |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | DES      |
      | Course 2 | C2        | OTH      |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

  Scenario: An assessor adds the block and goes to the marking page
    Given I log in as "teacher1"
    And I turn editing mode on
    And I add the "Marking nudge" block
    Then I should see "All caught up." in the "Marking nudge" "block"
    And I click on "Go to marking" "link" in the "Marking nudge" "block"
    And I should see "Course 1"

  Scenario: A teacher adds the block to their course
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add the "Marking nudge" block
    Then I should see "All caught up." in the "Marking nudge" "block"
    And I click on "Go to marking" "link" in the "Marking nudge" "block"
    And I should see "Course 1"
    And I log out
    And I am on the "Course 1" course page logged in as "student1"
    And "Marking nudge" "block" should not exist

  Scenario: The block on a category page counts the courses in that category
    Given the following "blocks" exist:
      | blockname    | contextlevel | reference | pagetypepattern       | defaultregion |
      | markingnudge | Category     | DES       | course-index-category | side-pre      |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | teacher1 | C2     | teacher |
    When I log in as "teacher1"
    And I am on course index
    And I follow "Design"
    Then I should see "All caught up." in the "Marking nudge" "block"
    And I click on "Go to marking" "link" in the "Marking nudge" "block"
    And I should see "Marking: Design"
    And I should see "Course 1"
    And I should not see "Course 2"
