@api
Feature: Sharing between local and LDAP users
  As a user
  I want to be able to share files and folders with any user regardless of the backend

  Background:
    Given user "local-user" has been created with default attributes in the database user backend
    And user "local-user" has created folder "/PARENT"
    And user "local-user" has uploaded file with content "ownCloud test text file parent" to "/PARENT/parent.txt"
    And these users have been created with default attributes and small skeleton files:
      | username |
      | Alice    |
      | Brian    |
      | Carol    |
    And group "grp1" has been created
    And user "Brian" has been added to group "grp1"
    And user "Carol" has been added to group "grp1"


  Scenario: Share a folder from an LDAP user to a local user
    When user "Alice" shares folder "/PARENT" with user "local-user" using the sharing API
    Then the content of file "/PARENT (2)/parent.txt" for user "local-user" should be:
    """
    ownCloud test text file parent

    """


  Scenario: Share a folder from an LDAP user to a local user and change folder content
    Given user "Alice" has shared folder "/PARENT" with user "local-user"
    When user "local-user" uploads file with content "new file" to "PARENT (2)/new-file.txt" using the WebDAV API
    And user "local-user" uploads file with content "changed file" to "PARENT (2)/parent.txt" using the WebDAV API
    Then the content of file "/PARENT/new-file.txt" for user "Alice" should be "new file"
    And the content of file "/PARENT/parent.txt" for user "Alice" should be "changed file"


  Scenario: Share a folder from an LDAP user to a local user read only
    Given user "Alice" has shared folder "/PARENT" with user "local-user" with permissions "read"
    When user "local-user" uploads file with content "new file" to "PARENT (2)/new-file.txt" using the WebDAV API
    Then the HTTP status code should be "403"
    And as "Alice" file "/PARENT/new-file.txt" should not exist
    When user "local-user" uploads file with content "changed file" to "PARENT (2)/parent.txt" using the WebDAV API
    Then the HTTP status code should be "403"
    And the content of file "/PARENT/parent.txt" for user "Alice" should be:
    """
    ownCloud test text file parent

    """


  Scenario: Share a folder from a local user to an LDAP user
    When user "local-user" shares folder "/PARENT" with user "Alice" using the sharing API
    Then the content of file "/PARENT (2)/parent.txt" for user "Alice" should be:
    """
    ownCloud test text file parent
    """


  Scenario: Share a folder from a local user to an LDAP user and change folder content
    Given user "local-user" has shared folder "/PARENT" with user "Alice"
    When user "Alice" uploads file with content "new file" to "PARENT (2)/new-file.txt" using the WebDAV API
    And user "Alice" uploads file with content "changed file" to "PARENT (2)/parent.txt" using the WebDAV API
    Then the content of file "/PARENT/new-file.txt" for user "local-user" should be "new file"
    And the content of file "/PARENT/parent.txt" for user "local-user" should be "changed file"


  Scenario: Share a folder from a local user to an LDAP user without write permissions
    Given user "local-user" has shared folder "/PARENT" with user "Alice" with permissions "read"
    When user "Alice" uploads file with content "new file" to "PARENT (2)/new-file.txt" using the WebDAV API
    Then the HTTP status code should be "403"
    And as "local-user" file "/PARENT/new-file.txt" should not exist
    When user "Alice" uploads file with content "changed file" to "PARENT (2)/parent.txt" using the WebDAV API
    Then the HTTP status code should be "403"
    And the content of file "/PARENT/parent.txt" for user "local-user" should be:
    """
    ownCloud test text file parent
    """


  Scenario: Share a folder from an LDAP user to a local group, delete the group
    Given group "local-group" has been created in the database user backend
    And user "local-user" has been added to database backend group "local-group"
    When user "Alice" shares folder "/PARENT" with group "local-group" using the sharing API
    Then the content of file "/PARENT (2)/parent.txt" for user "local-user" should be:
    """
    ownCloud test text file parent

    """
    When the administrator deletes group "local-group" using the provisioning API
    Then as "local-user" file "/PARENT (2)/parent.txt" should not exist


  Scenario: Share a folder from a local user to an LDAP group, delete the group
    When user "local-user" shares folder "/PARENT" with group "grp1" using the sharing API
    Then the content of file "/PARENT (2)/parent.txt" for user "Brian" should be:
    """
    ownCloud test text file parent
    """
    And the content of file "/PARENT (2)/parent.txt" for user "Carol" should be:
    """
    ownCloud test text file parent
    """
    When the administrator deletes ldap group "grp1"
    Then as "Brian" file "/PARENT (2)/parent.txt" should not exist
    And as "Carol" file "/PARENT (2)/parent.txt" should not exist


  Scenario: Share a folder from an LDAP user to a local group, take member out of the group
    Given group "local-group" has been created in the database user backend
    And user "local-user" has been added to database backend group "local-group"
    When user "Alice" shares folder "/PARENT" with group "local-group" using the sharing API
    Then the content of file "/PARENT (2)/parent.txt" for user "local-user" should be:
    """
    ownCloud test text file parent

    """
    When the administrator removes user "local-user" from group "local-group" using the provisioning API
    Then as "local-user" file "/PARENT (2)/parent.txt" should not exist


  Scenario: Share a folder from a local user to an LDAP group, take member out of the group
    When user "local-user" shares folder "/PARENT" with group "grp1" using the sharing API
    Then the content of file "/PARENT (2)/parent.txt" for user "Brian" should be:
    """
    ownCloud test text file parent
    """
    And the content of file "/PARENT (2)/parent.txt" for user "Carol" should be:
    """
    ownCloud test text file parent
    """
    When the administrator removes user "Brian" from ldap group "grp1"
    Then as "Brian" file "/PARENT (2)/parent.txt" should not exist
    But as "Carol" file "/PARENT (2)/parent.txt" should exist

  @issue-364
  Scenario: Share a folder from an LDAP user to a local user
    Given user "Alice" has shared folder "/PARENT" with user "local-user"
    When the administrator sets the LDAP config "LDAPTestId" key "ldapHost" to "not-existing" using the occ command
    And user "local-user" downloads file "/PARENT (2)/parent.txt" using the WebDAV API
    Then the HTTP status code should be "404"
#    Then the content of file "/PARENT (2)/parent.txt" for user "local-user" should be:
#    """
#    ownCloud test text file parent
#
#    """
    When user "local-user" gets the properties of file "/PARENT (2)/parent.txt" using the WebDAV API
    Then the HTTP status code should be "404"
#    When user "local-user" gets the properties of file "/PARENT (2)/parent.txt" using the WebDAV API
#    Then the properties response should contain an etag
    When the administrator sets the LDAP config "LDAPTestId" key "ldapHost" to "%ldap_host_without_scheme%" using the occ command
    And user "local-user" gets the properties of file "/PARENT (2)/parent.txt" using the WebDAV API
    Then the properties response should contain an etag
    And the content of file "/PARENT (2)/parent.txt" for user "local-user" should be:
    """
    ownCloud test text file parent

    """
