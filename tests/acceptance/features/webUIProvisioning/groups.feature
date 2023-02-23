@webUI @insulated @disablePreviews
Feature: add group
  As an administrator
  I want to be able to add, delete and modify groups via the webUI
  So that I can easily manage groups when user LDAP is enabled

  Background:
    And these users have been created with default attributes and without skeleton files:
      | username |
      | Alice    |
      | Brian    |
      | Carol    |
    And group "grp1" has been created
    # In drone the ldap groups have not synced yet. So this occ command is required to sync them.
    And the administrator has invoked occ command "group:list"
    And user "Brian" has been added to group "grp1"
    And user "Carol" has been added to group "grp1"
    And user admin has logged in using the webUI
    And the administrator has browsed to the users page

  @skipOnOcV10.7 @skipOnOcV10.8 @skipOnOcV10.9.0 @skipOnOcV10.9.1
  Scenario: An LDAP group should be listed but the member count is not displayed
    Then the group name "grp1" should be listed on the webUI
    And the user count of group "grp1" should not be displayed on the webUI


  Scenario: Adding a simple database group should be possible
    When the administrator adds group "simple-group" using the webUI
    Then the group name "simple-group" should be listed on the webUI
    And group "simple-group" should exist


  Scenario: Add group with same name as existing ldap group
    When the administrator adds group "grp1" using the webUI
    Then the group name "grp1" should be listed on the webUI
    And a notification should be displayed on the webUI with the text "Error creating group: Group already exists."


  Scenario: Add ldap group with same name as existing database group
    Given group "db-group" has been created in the database user backend
    When the administrator imports this ldif data:
      """
      dn: cn=db-group,ou=TestGroups,dc=owncloud,dc=com
      cn: db-group
      gidnumber: 4700
      objectclass: top
      objectclass: posixGroup
      """
    And the administrator reloads the users page
    Then the group name "db-group_2" should not be listed on the webUI
    And group "db-group_2" should not exist


  Scenario: delete group
    Given group "simple group" has been created in the database user backend
    And the administrator has browsed to the users page
    When the administrator deletes the group named "simple group" using the webUI and confirms the deletion using the webUI
    Then the group name "simple group" should not be listed on the webUI
    When the administrator reloads the users page
    Then the group name "simple group" should not be listed on the webUI
    And group "simple group" should not exist


  Scenario: delete ldap defined group
    When the administrator deletes the group named "grp1" using the webUI and confirms the deletion using the webUI
    Then dialog should be displayed on the webUI
      | title                 | content                 |
      | Unable to delete grp1 | Unable to delete group. |
    And group "grp1" should exist

  @skipOnOcV10.7 @skipOnOcV10.8 @skipOnOcV10.9.0 @skipOnOcV10.9.1
  Scenario: Adding database user to database group should be possible
    Given user "db-user" has been created with default attributes in the database user backend
    And group "db-group" has been created in the database user backend
    And the administrator has browsed to the users page
    When the administrator adds user "db-user" to group "db-group" using the webUI
    Then user "db-user" should exist
    And user "db-user" should belong to group "db-group"
    And the user count of group "db-group" should not be displayed on the webUI
    # for a database group and user, we should really see the user in the count
    #And the user count of group "db-group" should display 1 users on the webUI

  @issue-core-25224
  Scenario: Adding database user to LDAP group should not be possible
    Given user "db-user" has been created with default attributes in the database user backend
    And the administrator has browsed to the users page
    When the administrator tries to add user "db-user" to group "grp1" using the webUI
    Then user "db-user" should exist
    But user "db-user" should not belong to group "grp1"

  @skipOnOcV10.7 @skipOnOcV10.8 @skipOnOcV10.9.0 @skipOnOcV10.9.1
  Scenario: Adding LDAP user to database group should be possible
    Given group "db-group" has been created in the database user backend
    And the administrator has browsed to the users page
    When the administrator adds user "Alice" to group "db-group" using the webUI
    Then user "Alice" should exist
    And user "Alice" should belong to group "db-group"
    And the user count of group "db-group" should not be displayed on the webUI

  @issue-core-25224
  Scenario: Adding LDAP user to LDAP group should not be possible
    Given the administrator has browsed to the users page
    When the administrator tries to add user "Alice" to group "grp1" using the webUI
    Then user "Alice" should exist
    But user "Alice" should not belong to group "grp1"
