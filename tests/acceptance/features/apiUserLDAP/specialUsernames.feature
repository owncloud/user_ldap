@api
Feature: User names that are valid in ownCloud can be created and used from LDAP
  As an administrator
  I want the full range of valid ownCloud user names to work from LDAP
  So that LDAP users can work with their files in ownCloud


  Scenario Outline: Valid ownCloud user names can be used from LDAP
    Given user "<username>" has been created with default attributes and without skeleton files
    When user "<username>" uploads file with content "uploaded content" to "test.txt" using the WebDAV API
    Then the HTTP status code should be "201"
    And user "<username>" should exist
    And the content of file "test.txt" for user "<username>" should be "uploaded content"
    And user "<username>" should be able to delete file "test.txt"
    Examples:
      | username              |
      | JohnSmith             |
      | dash-user             |
      | under_score           |
      | some.one@example.com  |
      | jack+jill             |
      | jack+jill@example.com |
