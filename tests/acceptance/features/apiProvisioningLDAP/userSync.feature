@api @provisioning_api-app-required @skipOnOcV10.3 @skipOnOcis
Feature: Single user sync using the OCS API

  Background:
    Given these users have been created with default attributes and without skeleton files:
      | username |
      | user0    |
      | user1    |

  Scenario Outline: admin deletes ldap users and syncs only one of them
    Given using OCS API version "<ocs-api-version>"
    When the administrator deletes user "user0" using the provisioning API
    And the administrator deletes user "user1" using the provisioning API
    Then user "user0" should not exist
    And user "user1" should not exist
    When the administrator tries to sync user "user0" using the OCS API
    Then the HTTP status code should be "200"
    And the OCS status code should be "<ocs-status-code>"
    And user "user0" should exist
    And user "user1" should not exist
    Examples:
      | ocs-api-version | ocs-status-code |
      | 1               | 100             |
      | 2               | 200             |
