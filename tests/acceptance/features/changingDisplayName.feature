@webUI @insulated @disablePreviews
Feature: changing display name

As an admin
I want the display name in owncloud to correspond with the one in LDAP
So that users can be found by their LDAP names

	Scenario Outline: change display name on the LDAP server
		Given the admin sets the ldap attribute "displayname" of the entry "uid=user1,ou=TestUsers" to "<new-displayname>"
		When I am on the login page
		And I login with username "user1" and password "1234"
		Then "<new-displayname>" should be shown as the name of the current user in the WebUI
		Examples:
		|new-displayname|
		|999         |
		|मेरो नाम      |
		|null        |

	@skip @issue-core-30657
	Scenario: change display name on the LDAP server
		Given the admin sets the ldap attribute "displayname" of the entry "uid=user1,ou=TestUsers" to "0"
		When I am on the login page
		And I login with username "user1" and password "1234"
		Then "0" should be shown as the name of the current user in the WebUI

	Scenario: delete display name on the LDAP server
		Given the admin sets the ldap attribute "displayname" of the entry "uid=user1,ou=TestUsers" to ""
		When I am on the login page
		And I login with username "user1" and password "1234"
		Then "user1" should be shown as the name of the current user in the WebUI
		When I relogin with username "user2" and password "1234"
		Then "User Two" should be shown as the name of the current user in the WebUI