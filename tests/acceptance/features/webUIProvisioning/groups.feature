@webUI @insulated @disablePreviews
Feature: add group
  As an administrator
  I want to be able to add, delete and modify groups via the webUI
  So that I can easily manage groups when user LDAP is enabled

  Background:
    # In drone the ldap groups have not synced yet. So this occ command is required to sync them.
    Given the administrator has invoked occ command "group:list"
    And user admin has logged in using the webUI
    And the administrator has browsed to the users page

  Scenario: Adding a simple database group should be possible
    When the administrator adds group "simple-group" using the webUI
    Then the group name "simple-group" should be listed on the webUI
    And group "simple-group" should exist

  Scenario: Add group with same name as existing ldap group
    And group "grp1" should exist
    When the administrator adds group "grp1" using the webUI
    Then the group name "grp1" should be listed on the webUI
    And a notification should be displayed on the webUI with the text "Error creating group: Group already exists."
    And group "grp1" should exist

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
    Then the group name "db-group_2" should be listed on the webUI
    And group "db-group_2" should exist

