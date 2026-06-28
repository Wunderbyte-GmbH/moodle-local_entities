@local @local_entities @local_entities_templates
Feature: The entity field template follows the chosen entity type
  As an admin creating an entity I want the relevant extra fields to appear automatically
  depending on whether I create a location or a piece of equipment.

  Background:
    Given I log in as "admin"
    And I change viewport size to "1366x10000"

  @javascript
  Scenario: A new entity shows the template matching its entity type and switches with it
    When I visit "/local/entities/edit.php"
    And I expand all fieldsets
    # Default entity type is "location" -> the location template fields are shown.
    Then I should see "Building"
    And I should not see "Inventory number"
    # Switching to equipment swaps the template: equipment fields appear, location fields go.
    When I set the field "Entity type" to "Equipment / resource"
    And I expand all fieldsets
    Then I should see "Inventory number"
    And I should see "Condition"
    But I should not see "Building"
