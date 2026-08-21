@local @local_entities @local_entities_calendar
Feature: Configure the entity calendar display
  In order to display entity availability consistently
  As an administrator
  I need the configured first weekday and time format to apply to every entity calendar

  Background:
    Given the following "local_entities > entities" exist:
      | name    | shortname | pricefactor | maxallocation | daysofweek                        | starthours | startminutes | endhours    | endminutes |
      | Entity1 | entity1   | 1           | 10            | [["1","2","6","7"],["2","3","4"]] | ["13","6"] | ["0","0"]    | ["16","11"] | ["0","0"]  |
    And I change viewport size to "1366x3000"

  @javascript
  Scenario Outline: Entites Calendar view: various calendarfirstday and calendartimeformat settings have been applied and verified
    Given the following config values are set as admin:
      | activeviewtemplate | calendar     | local_entities |
      | calendarfirstday   | <firstday>   | local_entities |
      | calendartimeformat | <timeformat> | local_entities |
    And I log in as "admin"
    And I visit "/local/entities/entities.php"
    ## Validate existing entities
    And I should see "Entity1" in the "#region-main" "css_element"
    And I click on "View" "link"
    And "//div[@id='entity-calendar']" "xpath_element" should exist
    And "//div[@id='entity-calendar']//div[contains(@class, 'fc-header-toolbar')]" "xpath_element" should exist
    ## Validate that "Sun" is the first day of the week and "Sat" is the last day of the week in the calendar view.
    And "//div[@id='entity-calendar']//thead[@role='presentation']/tr[@role='row']/th[1][contains(concat(' ', normalize-space(@class), ' '), ' fc-day-<css1st> ')]//a[normalize-space(.)='<1stday>']" "xpath_element" should exist
    And "//div[@id='entity-calendar']//thead[@role='presentation']/tr[@role='row']/th[last()][contains(concat(' ', normalize-space(@class), ' '), ' fc-day-<csslast> ')]//a[normalize-space(.)='<lastday>']" "xpath_element" should exist
    And I should see "<hours1>" in the "#entity-calendar" "css_element"
    And I should see "<hours2>" in the "#entity-calendar" "css_element"
    ## Resize the viewport to a smaller size to validate that the calendar view is responsive and that the hours are hidden and replaced with links to popups.
    And I change viewport size to "1024x3000"
    And I should not see "<hours1>" in the "#entity-calendar" "css_element"
    And I should not see "<hours2>" in the "#entity-calendar" "css_element"
    And I should see "+ weitere 1" in the "#entity-calendar" "css_element"
    And I should see "+ weitere 2" in the "#entity-calendar" "css_element"
    And I click on "+ weitere 1" "text" in the "#entity-calendar" "css_element"
    And I should see "<hours1>" in the "#entity-calendar" "css_element"
    ## Close popup
    And I press the escape key
    And I click on "+ weitere 2" "text" in the "#entity-calendar" "css_element"
    And I should see "<hours2>" in the "#entity-calendar" "css_element"

    Examples:
      | firstday | timeformat | 1stday | lastday | css1st | csslast | midnight | hours1          | hours2           |
      | 0        | 12         | Sun    | Sat     | sun    | sat     | 12 AM    | 1:00pm - 4:00pm | 6:00am - 11:00am |
      | 0        | 24         | Sun    | Sat     | sun    | sat     | 24:00    | 13:00 - 16:00   | 06:00 - 11:00    |
      | 1        | 12         | Mon    | Sun     | mon    | sun     | 12 AM    | 1:00pm - 4:00pm | 6:00am - 11:00am |
      | 1        | 24         | Mon    | Sun     | mon    | sun     | 24:00    | 13:00 - 16:00   | 06:00 - 11:00    |
      | 6        | 12         | Sat    | Fri     | sat    | fri     | 12 AM    | 1:00pm - 4:00pm | 6:00am - 11:00am |
      | 6        | 24         | Sat    | Fri     | sat    | fri     | 24:00    | 13:00 - 16:00   | 06:00 - 11:00    |
