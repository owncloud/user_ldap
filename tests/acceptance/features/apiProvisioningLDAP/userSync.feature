@api @provisioning_api-app-required @skipOnOcV10.3 @skipOnOcis
Feature: Single user sync using the OCS API

  Background:
    Given these users have been created with default attributes and without skeleton files:
      | username |
      | Alice    |
      | Brian    |


  Scenario Outline: admin deletes ldap users and syncs only one of them
    Given using OCS API version "<ocs-api-version>"
    When the administrator deletes user "Alice" using the provisioning API
    And the administrator deletes user "Brian" using the provisioning API
    Then user "Alice" should not exist
    And user "Brian" should not exist
    When the administrator tries to sync user "Alice" using the OCS API
    Then the HTTP status code should be "200"
    And the OCS status code should be "<ocs-status-code>"
    And user "Alice" should exist
    And user "Brian" should not exist
    Examples:
      | ocs-api-version | ocs-status-code |
      | 1               | 100             |
      | 2               | 200             |


  Scenario Outline: admin syncs after changing display name of a ldap user
    Given using OCS API version "<ocs-api-version>"
    When the administrator sets the ldap attribute "displayname" of the entry "uid=Alice,ou=TestUsers" to "ldap user zero"
    When the administrator sets the ldap attribute "displayname" of the entry "uid=Brian,ou=TestUsers" to "ldap user one"
    And the administrator tries to sync user "Alice" using the OCS API
    Then the HTTP status code should be "200"
    And the OCS status code should be "<ocs-status-code>"
    And user "Alice" should exist
    And the display name of user "Alice" should be "ldap user zero"
    And the display name of user "Brian" should be "Brian Murphy"
    Examples:
      | ocs-api-version | ocs-status-code |
      | 1               | 100             |
      | 2               | 200             |


  Scenario Outline: admin syncs after changing email address of a ldap user
    Given using OCS API version "<ocs-api-version>"
    When the administrator sets the ldap attribute "mail" of the entry "uid=Alice,ou=TestUsers" to "ldapAlice@example.com"
    When the administrator sets the ldap attribute "mail" of the entry "uid=Brian,ou=TestUsers" to "ldapBrian@example.com"
    And the administrator tries to sync user "Alice" using the OCS API
    Then the HTTP status code should be "200"
    And the OCS status code should be "<ocs-status-code>"
    And user "Alice" should exist
    And the email address of user "Alice" should be "ldapAlice@example.com"
    And the email address of user "Brian" should be "brian@example.org"
    Examples:
      | ocs-api-version | ocs-status-code |
      | 1               | 100             |
      | 2               | 200             |
