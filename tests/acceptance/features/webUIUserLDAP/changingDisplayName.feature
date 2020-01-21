@webUI @insulated @disablePreviews
Feature: changing display name

  As an admin
  I want the display name in owncloud to correspond with the one in LDAP
  So that users can be found by their LDAP names

  Scenario Outline: change display name on the LDAP server
    Given the administrator sets the ldap attribute "displayname" of the entry "uid=user1,ou=TestUsers" to "<new-displayname>"
    When user "user1" logs in using the webUI
    Then "<new-displayname>" should be shown as the name of the current user on the WebUI
    Examples:
      | new-displayname |
      | 999             |
      | मेरो नाम        |
      | null            |

  @skip @issue-core-30657
  Scenario: change display name on the LDAP server
    Given the administrator sets the ldap attribute "displayname" of the entry "uid=user1,ou=TestUsers" to "0"
    When user "user1" logs in using the webUI
    Then "0" should be shown as the name of the current user on the WebUI

  Scenario: delete display name on the LDAP server
    Given the administrator sets the ldap attribute "displayname" of the entry "uid=user1,ou=TestUsers" to ""
    When user "user1" logs in using the webUI
    Then "user1" should be shown as the name of the current user on the WebUI
    When the user re-logs in as "user2" using the webUI
    Then "User Two" should be shown as the name of the current user on the WebUI