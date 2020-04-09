@api
Feature: User names that are valid in LDAP can also be used in ownCloud
  As an administrator
  I want the full range of user names from LDAP to be available in ownCloud
  So that all the LDAP users can work with their files in ownCloud

  Scenario Outline: A variety of user names from LDAP can be used in ownCloud
    Given user "<username>" has been created with default attributes and without skeleton files
    When user "<username>" uploads file with content "uploaded content" to "test.txt" using the WebDAV API
    And the HTTP status code should be "201"
    And the content of file "test.txt" for user "<username>" should be "uploaded content"
    And user "<username>" should exist
    Examples:
      | username              |
      | JohnSmith             |
      | dash-user             |
      | under_score           |
      | some.one@example.com  |
      | jack+jill             |
      | jack+jill@example.com |
