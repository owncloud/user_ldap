@insulated @disablePreviews
Feature: providing an avatar by LDAP

As an user
I want to see my avatar from LDAP being used in owncloud
So that other users can recognize me by my picture

	@skip @issue-198 #we cannot revert this even with deleting the user
	Scenario: upload an avatar to the LDAP server
		When the admin sets the ldap attribute "jpegPhoto" of the entry "uid=user2,ou=TestUsers" to the content of the file "testavatar.jpg"
		And I am on the login page
		And I login with username "user2" and password "1234"
		Then the display name should not be visible in the WebUI
		And an avatar should be shown for the current user in the WebUI

	Scenario: set the avatar on the LDAP server to an invalid string
		When the admin sets the ldap attribute "jpegPhoto" of the entry "uid=user2,ou=TestUsers" to "0"
		And I am on the login page
		And I login with username "user2" and password "1234"
		Then the display name should be visible in the WebUI
		And "User Two" should be shown as the name of the current user in the WebUI
		And no avatar should be shown for the current user in the WebUI