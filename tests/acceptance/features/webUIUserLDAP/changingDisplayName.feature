@webUI @insulated @disablePreviews
Feature: changing display name

  As an admin
  I want the display name in owncloud to correspond with the one in LDAP
  So that users can be found by their LDAP names

  Background:
    Given user "Alice" has been created with default attributes and without skeleton files


  Scenario Outline: change display name on the LDAP server
    Given the administrator sets the ldap attribute "displayname" of the entry "uid=Alice,ou=TestUsers" to "<new-displayname>"
    When user "Alice" logs in using the webUI
    Then "<new-displayname>" should be shown as the name of the current user on the webUI
    Examples:
      | new-displayname |
      | 999             |
      | मेरो नाम        |
      | null            |

  @skip @issue-core-30657
  Scenario: change display name on the LDAP server
    Given the administrator sets the ldap attribute "displayname" of the entry "uid=Alice,ou=TestUsers" to "0"
    When user "Alice" logs in using the webUI
    Then "0" should be shown as the name of the current user on the webUI


  Scenario: delete display name on the LDAP server
    Given user "Brian" has been created with default attributes and without skeleton files
    And the administrator sets the ldap attribute "displayname" of the entry "uid=Alice,ou=TestUsers" to ""
    When user "Alice" logs in using the webUI
    Then "Alice" should be shown as the name of the current user on the webUI
    When the user re-logs in as "Brian" using the webUI
    Then "Brian Murphy" should be shown as the name of the current user on the webUI
