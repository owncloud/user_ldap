@cli
Feature: add group
  As an administrator
  I want to be able to add, delete and modify groups via the command line
  So that I can easily manage groups when user LDAP is enabled

  Background:
    # In drone the ldap groups have not synced yet. So this occ command is required to sync them.
    Given the administrator has invoked occ command "group:list"
    And this user has been created using the occ command:
      | username | displayname  |
      | Alice    | Alice Hansen |

  Scenario Outline: admin creates a group
    When the administrator creates group "<group_id>" using the occ command
    Then the command should have been successful
    And the command output should contain the text 'Created group "<group_id>"'
    And group "<group_id>" should exist
    Examples:
      | group_id    | comment                     |
      | simplegroup | nothing special here        |
      | España      | special European characters |
      | नेपाली      | Unicode group name          |

  Scenario Outline: admin removes a user from a group
    When the administrator creates group "<group_id>" using the occ command
    Then the command should have been successful
    When the administrator adds user "Alice" to group "<group_id>" using the occ command
    Then the command should have been successful
    When the administrator removes user "Alice" from group "<group_id>" using the occ command
    Then the command should have been successful
    And the command output should contain the text 'Member "Alice" removed from group "<group_id>"'
    And user "Alice" should not belong to group "<group_id>"
    Examples:
      | group_id    | comment                     |
      | simplegroup | nothing special here        |
      | España      | special European characters |
      | नेपाली      | Unicode group name          |

  @smokeTest
  Scenario Outline: adding a user to a group
    When the administrator creates group "<group_id>" using the occ command
    Then the command should have been successful
    When the administrator adds user "Alice" to group "<group_id>" using the occ command
    Then the command should have been successful
    And the command output should contain the text 'User "Alice" added to group "<group_id>"'
    And user "Alice" should belong to group "<group_id>"
    Examples:
      | group_id    | comment                     |
      | simplegroup | nothing special here        |
      | España      | special European characters |
      | नेपाली      | Unicode group name          |

  Scenario Outline: admin deletes a group
    When the administrator creates group "<group_id>" using the occ command
    Then the command should have been successful
    When the administrator deletes group "<group_id>" using the occ command
    Then the command should have been successful
    And the command output should contain the text 'The specified group was deleted'
    And group "<group_id>" should not exist
    Examples:
      | group_id    | comment                     |
      | simplegroup | nothing special here        |
      | España      | special European characters |
      | नेपाली      | Unicode group name          |

  @skip @issue-268
  Scenario: admin tries to delete ldap defined group
    When the LDAP users are resynced
    And the administrator deletes group "grp1" using the occ command
    Then the command should have failed with exit code 1
    And the command output should contain the text "The specified group could not be deleted"
    And group "grp1" should exist
