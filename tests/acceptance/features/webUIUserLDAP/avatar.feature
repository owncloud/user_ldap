@webUI @insulated @disablePreviews
Feature: providing an avatar by LDAP

  As an user
  I want to see my avatar from LDAP being used in owncloud
  So that other users can recognize me by my picture

  Background:
    Given user "user2" has been created with default attributes and without skeleton files

  @skip @issue-198 #we cannot revert this even with deleting the user
  Scenario: upload an avatar to the LDAP server
    When the administrator sets the ldap attribute "jpegPhoto" of the entry "uid=user2,ou=TestUsers" to the content of the file "testavatar.jpg"
    And user "user2" has logged in using the webUI
    Then the display name should not be visible on the WebUI
    And an avatar should be shown for the current user on the WebUI

  Scenario: set the avatar on the LDAP server to an invalid string
    When the administrator sets the ldap attribute "jpegPhoto" of the entry "uid=user2,ou=TestUsers" to "0"
    And user "user2" has logged in using the webUI
    Then the display name should be visible on the WebUI
    And "User Two" should be shown as the name of the current user on the WebUI
    And no avatar should be shown for the current user on the WebUI