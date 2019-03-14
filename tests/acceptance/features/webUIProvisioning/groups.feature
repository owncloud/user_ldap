@webUI @insulated @disablePreviews
Feature: add group
  As an administrator
  I want to be able to add, delete and modify groups via the webUI
  So that I can easily manage groups when user LDAP is enabled

  Background:
    Given user admin has logged in using the webUI
    And the administrator has browsed to the users page

  @skip
  Scenario: admin gets all the groups the first time after LDAP has been setup
    # In drone the ldap groups have not synced yet. So this occ command is required to sync them.
    Given the administrator has invoked occ command "group:list"
    Then the command should have been successful
    And the command output should contain the text '- grp1'
    And the command output should contain the text '- ShareeGroup2'
    And the command output should contain the text '- make this scenario fail here'

  @skip
  Scenario: admin gets all the groups the first time after LDAP has been setup
    When the administrator gets the groups in JSON format using the occ command
    Then the command should have been successful
    And the groups returned by the occ command should be
      | group          |
      | admin          |
      | group1         |
      | group2         |
      | group3         |
      | groupuser      |
      | grp1           |
      | grp2           |
      | grp3           |
      | grp4           |
      | grpuser        |
      | ShareeGroup    |
      | ShareeGroup2   |
      | hash#group     |
      | ordinary-group |
      | group-3        |

  Scenario: Adding a simple database group should be possible
    When the administrator adds group "simple-group" using the webUI
    Then the group name "simple-group" should be listed on the webUI
    And group "simple-group" should exist

  Scenario: Add group with same name as existing ldap group (original)
    When the administrator adds group "grp1" using the webUI
    Then the group name "grp1" should be listed on the webUI
    And a notification should be displayed on the webUI with the text "Error creating group: Group already exists."
    And group "grp1" should exist

  Scenario: Add group with same name as existing ldap group (2)
    #And group "grp1" should exist
    # In drone the ldap groups have not synced yet. So this occ command is required to sync them.
    Given the administrator has invoked occ command "group:list"
    Then the command should have been successful
    When the administrator adds group "grp1" using the webUI
    Then the group name "grp1" should be listed on the webUI
    And a notification should be displayed on the webUI with the text "Error creating group: Group already exists."
    And group "grp1" should exist

  Scenario: Add group with same name as existing ldap group (3)
    #And group "grp1" should exist
    # In drone the ldap groups have not synced yet. So this occ command is required to sync them.
    When the administrator gets the groups in JSON format using the occ command
    Then the command should have been successful
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
