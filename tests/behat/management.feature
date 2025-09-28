@tool @tool_certificate @certificateelement @certificateelement_mucertify @MuTMS @javascript @tool_mucertify
Feature: Being able to manage certification elements in a certificate template

  Background:
    Given the following certificate templates exist:
      | name          | numberofpages |
      | Certificate 1 | 1             |

  Scenario: Add and edit standard certification elements in a certificate template
    Given I log in as "admin"
    And I change window size to "large"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I follow "Certificate 1"

    When I add the element "Certification" to page "1" of the "Certificate 1" site certificate template
    And I should see "Add 'Certification field' element" in the ".modal.show .modal-header" "css_element"
    And I set the following fields to these values:
      | Element name        | Nazev                  |
      | Certification field | Certification name     |
    And I click on "Save" "button" in the ".modal.show .modal-footer" "css_element"
    Then I should see "Nazev" in the "[data-region='elementlist']" "css_element"
    When I click on "Edit 'Nazev'" "link" in the "[data-region='elementlist']" "css_element"
    And the following fields match these values:
      | Element name        | Nazev                  |
      | Certification field | Certification name     |
    And I set the following fields to these values:
      | Element name        | ID certification       |
      | Certification field | Certification ID       |
    And I click on "Save" "button" in the ".modal.show .modal-footer" "css_element"
    Then I should see "ID certification" in the "[data-region='elementlist']" "css_element"

    When I click on "Edit 'ID certification'" "link" in the "[data-region='elementlist']" "css_element"
    And the following fields match these values:
      | Element name        | ID certification       |
      | Certification field | Certification ID       |
    And I click on "Cancel" "button" in the ".modal.show .modal-footer" "css_element"
    Then I should see "ID certification" in the "[data-region='elementlist']" "css_element"

    When I click on "Edit 'ID certification'" "link" in the "[data-region='elementlist']" "css_element"
    And I set the following fields to these values:
      | Element name        | Dokonceno               |
      | Certification field | Certification completion date |
      | Date format         | strftimedateshort       |
    And I click on "Save" "button" in the ".modal.show .modal-footer" "css_element"
    And I click on "Edit 'Dokonceno'" "link" in the "[data-region='elementlist']" "css_element"
    And the following fields match these values:
      | Element name        | Dokonceno               |
      | Certification field | Certification completion date |
      | Date format         | strftimedateshort       |
    And I set the following fields to these values:
      | Element name        |                         |
      | Date format         | strftimedatetime        |
    And I click on "Save" "button" in the ".modal.show .modal-footer" "css_element"
    And I click on "Edit 'Certification completion date'" "link" in the "[data-region='elementlist']" "css_element"
    Then the following fields match these values:
      | Element name        | Certification completion date |
      | Certification field | Certification completion date |
      | Date format         | strftimedatetime        |
    And I click on "Cancel" "button" in the ".modal.show .modal-footer" "css_element"

  Scenario: Add and edit custom field certification elements in a certificate template
    Given I log in as "admin"
    And I change window size to "large"
    And I navigate to "Certification > Certification custom fields" in site administration
    And I press "Add a new category"
    And I click on "Add a new custom field" "link"
    And I click on "Short text" "link"
    And I set the following fields to these values:
      | Name                                | Test field |
      | Short name                          | testfield  |
    And I click on "Save changes" "button" in the "Adding a new Short text" "dialogue"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I follow "Certificate 1"

    When I add the element "Certification" to page "1" of the "Certificate 1" site certificate template
    And I set the following fields to these values:
      | Element name        | Some tf          |
      | Certification field | Custom field     |
      | Custom field        | Test field       |
    And I click on "Save" "button" in the ".modal.show .modal-footer" "css_element"
    Then I should see "Some tf" in the "[data-region='elementlist']" "css_element"
    And I click on "Edit 'Some tf'" "link" in the "[data-region='elementlist']" "css_element"
    And the following fields match these values:
      | Element name        | Some tf          |
      | Certification field | Custom field     |
      | Custom field        | Test field       |
    And I click on "Cancel" "button" in the ".modal.show .modal-footer" "css_element"

    And I click on "Delete element" "link" in the "[data-region='elementlist']" "css_element"
    And I click on "Delete" "button" in the ".modal.show .modal-footer" "css_element"
    And I should not see "Some tf"

    When I add the element "Certification" to page "1" of the "Certificate 1" site certificate template
    And I set the following fields to these values:
      | Certification field | Custom field     |
      | Custom field        | Test field       |
    And I click on "Save" "button" in the ".modal.show .modal-footer" "css_element"
    Then I should see "Test field" in the "[data-region='elementlist']" "css_element"
