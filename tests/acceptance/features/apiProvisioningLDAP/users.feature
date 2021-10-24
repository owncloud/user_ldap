@api @provisioning_api-app-required
Feature: Manage users using the Provisioning API
  As an administrator
  I want to be able to add, delete and modify users via the Provisioning API
  So that I can easily manage users when user LDAP is enabled

  Scenario: Admin creates a regular user
    Given using OCS API version "1"
    And user "Alice" has been deleted
    When the administrator sends a user creation request with the following attributes using the provisioning API:
      | username    | Alice          |
      | password    | %alt1%         |
      | displayname | Brand New User |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And user "Alice" should exist
    And user "Alice" should be able to upload file "filesForUpload/textfile.txt" to "/textfile.txt"
