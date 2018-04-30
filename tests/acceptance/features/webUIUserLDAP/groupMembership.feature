@webUI @insulated @disablePreviews
Feature: group membership

As an admin
I want to the owncloud groups to reflect the LDAP groups
So that I only need to configure group membership once

	Background:
		Given these users have been initialized:
			|username|password|
			|user1   |1234    |
			|user2   |1234    |
			|user3   |1234    |
		And the user browses to the login page
		And the user has logged in with username "user1" and password "1234" using the webUI
			
	Scenario: adding a new user to a group after a folder was shared with that group
		When the user shares the folder "simple-folder" with the group "grp1" using the webUI
		#ToDo use API calls
		And the admin adds the user "user3" to the ldap group "grp1"
		When the user re-logs in with username "user3" and password "1234" using the webUI
		Then the folder "simple-folder (2)" should be listed on the webUI
		And the folder "simple-folder (2)" should be marked as shared with "grp1" by "User One" on the webUI
		
	Scenario: deleting a user from a group after a folder was shared with that group
		When the user shares the folder "simple-folder" with the group "grp1" using the webUI
		#ToDo use API calls
		And the admin removes user "user2" from the ldap group "grp1"
		And the user re-logs in with username "user2" and password "1234" using the webUI
		Then the folder "simple-folder (2)" should not be listed on the webUI
		
	Scenario: simple sharing with a group
		When the user shares the folder "simple-folder" with the group "grp1" using the webUI
		#ToDo use API calls
		And the user re-logs in with username "user2" and password "1234" using the webUI
		Then the folder "simple-folder (2)" should be listed on the webUI
		
	Scenario: deleting a group after a folder was shared with that group
		When the user shares the folder "simple-folder" with the group "grp1" using the webUI
		#ToDo use API calls
		And the admin deletes the ldap group "grp1"
		And the user re-logs in with username "user2" and password "1234" using the webUI
		Then the folder "simple-folder (2)" should not be listed on the webUI

	Scenario: sharing with non unique group name (using unique oC group name)
		Given the admin creates the group "grp1" in the ldap OU "TestUsers"
		And the admin adds the user "user3" to the group "grp1" in the ldap OU "TestUsers"
		When the user shares the folder "simple-folder" with the group "grp1_2" using the webUI
		#ToDo use API calls
		And the user re-logs in with username "user3" and password "1234" using the webUI
		Then the folder "simple-folder (2)" should be listed on the webUI

	Scenario: sharing with non unique group name (using non-unique group name)
		Given the admin creates the group "grp1" in the ldap OU "TestUsers"
		And the admin adds the user "user3" to the group "grp1" in the ldap OU "TestUsers"
		When the user shares the folder "simple-folder" with the group "grp1" using the webUI
		#ToDo use API calls
		And the user re-logs in with username "user3" and password "1234" using the webUI
		Then the folder "simple-folder (2)" should not be listed on the webUI

	Scenario: sharing with a group that is filtered out
	  #ToDo use API calls
		When the user shares the folder "simple-folder" with the group "grp1" using the webUI
		And the LDAP config "LDAPTestId" has these settings:
		|key                   | value                                        |
		|ldapGroupFilter       | (&(\|(objectclass=posixGroup))(\|(cn=grp2))) |
		|ldapGroupFilterGroups | grp2                                         |
		And the user re-logs in with username "user2" and password "1234" using the webUI
		Then the folder "simple-folder (2)" should not be listed on the webUI

	Scenario: search for groups by alternative attribute
		#ToDo use API calls
		Given the LDAP config "LDAPTestId" has these settings:
		|key                          | value       |
		|ldapAttributesForGroupSearch | description |
		And the admin sets the ldap attribute "description" of the entry "cn=grp1,ou=TestGroups" to "my first group"
		And the user has opened the share dialog for the folder "simple-folder"
		When the user types "my first" in the share-with-field
		Then all users and groups that contain the string "grp1" in their name should be listed in the autocomplete list on the webUI
