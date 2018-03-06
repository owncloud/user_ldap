@insulated @disablePreviews
Feature: changing password

As an user
I want to change my LDAP password and be able to use it in owncloud
So that I do not have to remember multiple passwords

	Scenario Outline: change password on the LDAP server
		When the admin sets the ldap attribute "userpassword" of the entry "uid=user1,ou=TestUsers" to "<new-password>"
		Then it should not be possible to login with the username "user1" and password "1234" into the WebUI
		But it should be possible to login with the username "user1" and password "<new-password>" into the WebUI
		Examples:
		|new-password|
		|999         |
		|0           |
		|пароль      |
		|null        |