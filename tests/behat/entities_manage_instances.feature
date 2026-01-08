@local @local_entities @local_entities_manage_instances.feature
Feature: Baisc functionality of local_entities works as expected

  Background:
    Given the following "custom field categories" exist:
      | name      | component      | area     | itemid |
      | CustomCat | local_entities | entities | 0      |
    And the following "custom fields" exist:
      | name   | category  | type | shortname |
      | Sport1 | CustomCat | text | spt1      |
    And the following "local_entities > entities" exist:
      | name    | shortname | pricefactor | maxallocation | openinghours |
      ##| Entity1 | entity1   | 1           | 10            | [{"title":"openinghours","daysOfWeek":"2,5,6","startTime":"10:11","endTime":"15:12"}] |
      | Entity1 | entity1   | 1           | 10            |              |
      | Entity2 | entity2   | 2           | 20            |              |
    And I change viewport size to "1366x10000"

  @javascript
  Scenario: Entity management: Crate view and edit entity instance
    Given I log in as "admin"
    And I visit "/local/entities/entities.php"
    ## Validate existing entities
    And I should see "Entity1" in the "#region-main" "css_element"
    And I should see "Entity2" in the "#region-main" "css_element"
    ## Create new entirty as child entity of Entity1
    And I follow "Add entity"
    And I expand all fieldsets
    And I set the field "Name" to "E1Child1"
    And I set the field "Short name" to "e1child1"
    And I set the field "Entity description" to "child_description"
    And I set the field "Weekdays" to "Wednesday"
    And I set the field "Start hh" to "09"
    And I set the field "End hh" to "20"
    And I set the field "Country" to "Ukraine"
    And I set the field "City" to "Ternopil"
    And I set the field "Street name" to "Brovarna"
    And I set the field "Surname" to "Smith"
    And I set the field "E-Mail" to "smith@example.com"
    And I set the field "Sport1" to "Football"
    And I set the field "Entity parent" to "Entity1"
    And I press "Save changes"
    And I should see "E1Child1" in the "#region-main" "css_element"
    ## Edit 1st (parent) entity.
    And I click on "Edit" "text" in the "#region-main" "css_element"
    And I set the field "Name" to "Parent1"
    And I set the field "Short name" to "parent1"
    And I set the field "Entity description" to "parent_description"
    And I press "Save changes"
    And I should see "Parent1" in the "#region-main" "css_element"
    ## Workaround to access "view" link for the child entity due to lack of identities in the DOM
    And I click on "//h4[normalize-space(.)='E1Child1']/preceding-sibling::a[contains(@class,'btn_edit')][contains(normalize-space(.),'View')][1]" "xpath_element" in the "#region-main" "css_element"
    ## Validate child entitiy's view page
    And I should see "E1Child1" in the "#region-main" "css_element"
    And I should see "Parent1" in the "#region-main" "css_element"
    And I should see "child_description" in the "#region-main" "css_element"
    And I should see "Football" in the "#region-main" "css_element"
    And I should see "Smith" in the "#region-main" "css_element"
    And I should see "smith@example.com" in the "#region-main" "css_element"
    And I should see "Ternopil" in the "#region-main" "css_element"
    And I should see "Brovarna" in the "#region-main" "css_element"
