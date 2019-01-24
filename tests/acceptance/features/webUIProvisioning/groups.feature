@webUI
Feature: add group
  As an administrator
  I want to be able to add, delete and modify groups via the webUI
  So that I can easily manage groups when user LDAP is enabled

  Background:
    Given user admin has logged in using the webUI
    And the administrator has browsed to the users page

  Scenario: Add group
    When the administrator adds group "simple-group" using the webUI
    Then the group name "simple-group" should be listed on the webUI

  Scenario: Add group with same name as existing ldap group
    When the administrator adds group "simple-group" using the webUI
    Then the group name "simple-group" should be listed on the webUI
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
    Then the group name "db-group_2" should be listed on the webUI
    And group "db-group_2" should exist

  Scenario: delete group
    Given group "do-not-delete" has been created in the database user backend
    And group "space group" has been created in the database user backend
    And group "quotes'" has been created in the database user backend
    And group "quotes" has been created in the database user backend
    And group "do-not-delete2" has been created in the database user backend
    And the administrator has browsed to the users page
    When the administrator deletes these groups and confirms the deletion using the webUI:
      | groupname   |
      | space group |
      | quotes'     |
      | quotes"     |
    Then these groups should be listed on the webUI:
      |groupname     |
      |do-not-delete |
      |do-not-delete2|
    But these groups should not be listed on the webUI:
      | groupname   |
      | space group |
      | quotes'     |
      | quotes"     |
    And the administrator reloads the users page
    Then these groups should be listed on the webUI:
      | groupname      |
      | do-not-delete  |
      | do-not-delete2 |
    But these groups should not be listed on the webUI:
      | groupname   |
      | space group |
      | quotes'     |
      | quotes"     |
    And these groups should exist:
      | groupname      |
      | do-not-delete  |
      | do-not-delete2 |
    But these groups should not exist:
      | groupname   |
      | space group |
      | quotes'     |
      | quotes"     |

  Scenario: delete ldap defined group
    When the administrator deletes the group named "grp1" using the webUI and confirms the deletion using the webUI
    Then dialog should be displayed on the webUI
    | title                       | content                     |
    | Unable to delete grp1       | Unable to delete group.     |
    And group "grp1" should exist

  Scenario: Add database user to database group
    Given user "db-user" has been created with default attributes in the database user backend
    And group "db-group" has been created in the database user backend
    And the administrator has browsed to the users page
    When the administrator adds user "db-user" to group "db-group" using the webUI
    Then user "db-user" should exist
    And user "db-user" should belong to group "db-group"

  @issue-core-25224
  Scenario: Add database user to ldap group
    Given user "db-user" has been created with default attributes in the database user backend
    And the administrator has browsed to the users page
    When the administrator adds user "db-user" to group "grp1" using the webUI
    Then user "db-user" should exist
    And user "db-user" should not belong to group "grp1"

  Scenario: Add ldap user to database group
    Given group "db-group" has been created in the database user backend
    And the administrator has browsed to the users page
    When the administrator adds user "user0" to group "db-group" using the webUI
    Then user "user0" should exist
    And user "user0" should belong to group "db-group"

  @issue-core-25224
  Scenario: Add ldap user to ldap group
    Given the administrator has browsed to the users page
    When the administrator adds user "user0" to group "grp1" using the webUI
    Then user "user0" should exist
    And user "user0" should not belong to group "grp1"
