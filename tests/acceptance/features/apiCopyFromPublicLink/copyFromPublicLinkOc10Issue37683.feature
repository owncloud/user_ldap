@api @files_sharing-app-required @public_link_share-feature-required @issue-ocis-reva-310 @notToImplementOnOCIS
Feature: copying from public link share

  Background:
    Given user "Alice" has been created with default attributes and without skeleton files
    And user "Alice" has created folder "/PARENT"

  @issue-ocis-reva-373 @issue-37683
  Scenario: Copy folder within a public link folder to the same folder name as an already existing file
    Given user "Alice" has created folder "/PARENT/testFolder"
    And user "Alice" has uploaded file with content "some data" to "/PARENT/testFolder/testfile.txt"
    And user "Alice" has uploaded file with content "some data 1" to "/PARENT/copy1.txt"
    And user "Alice" has created a public link share with settings
      | path        | /PARENT                   |
      | permissions | read,update,create,delete |
    When the public copies folder "/testFolder" to "/copy1.txt" using the new public WebDAV API
    Then the HTTP status code should be "204"
    And as "Alice" folder "/PARENT/testFolder" should exist
    And as "Alice" folder "/PARENT/copy1.txt" should exist
    And the content of file "/PARENT/copy1.txt/testfile.txt" for user "Alice" should be "some data"
    And the content of file "/PARENT/testFolder/testfile.txt" for user "Alice" should be "some data"

  @issue-ocis-reva-373 @issue-37683
  Scenario: Copy folder within a public link folder to the same folder name as an already existing file
    Given user "Alice" has created folder "/PARENT/testFolder"
    And user "Alice" has uploaded file with content "some data" to "/PARENT/testFolder/testfile.txt"
    And user "Alice" has uploaded file with content "some data 1" to "/PARENT/copy1.txt"
    And user "Alice" has created a public link share with settings
      | path        | /PARENT                   |
      | permissions | read,update,create,delete |
    When the public copies folder "/testFolder" to "/copy1.txt" using the new public WebDAV API
    Then the HTTP status code should be "204"
    And as "Alice" folder "/PARENT/testFolder" should exist
    And as "Alice" folder "/PARENT/copy1.txt" should exist
    And the content of file "/PARENT/copy1.txt/testfile.txt" for user "Alice" should be "some data"
    And the content of file "/PARENT/testFolder/testfile.txt" for user "Alice" should be "some data"

  @issue-ocis-reva-373 @issue-37683
  Scenario: Copy folder within a public link folder to the same folder name as an already existing file
    Given user "Alice" has created folder "/PARENT/testFolder"
    And user "Alice" has uploaded file with content "some data" to "/PARENT/testFolder/testfile.txt"
    And user "Alice" has uploaded file with content "some data 1" to "/PARENT/copy1.txt"
    And user "Alice" has created a public link share with settings
      | path        | /PARENT                   |
      | permissions | read,update,create,delete |
    When the public copies folder "/testFolder" to "/copy1.txt" using the new public WebDAV API
    Then the HTTP status code should be "204"
    And as "Alice" folder "/PARENT/testFolder" should exist
    And as "Alice" folder "/PARENT/copy1.txt" should exist
    And the content of file "/PARENT/copy1.txt/testfile.txt" for user "Alice" should be "some data"
    And the content of file "/PARENT/testFolder/testfile.txt" for user "Alice" should be "some data"

  @issue-ocis-reva-373 @issue-37683
  Scenario: Copy folder within a public link folder to the same folder name as an already existing file
    Given user "Alice" has created folder "/PARENT/testFolder"
    And user "Alice" has uploaded file with content "some data" to "/PARENT/testFolder/testfile.txt"
    And user "Alice" has uploaded file with content "some data 1" to "/PARENT/copy1.txt"
    And user "Alice" has created a public link share with settings
      | path        | /PARENT                   |
      | permissions | read,update,create,delete |
    When the public copies folder "/testFolder" to "/copy1.txt" using the new public WebDAV API
    Then the HTTP status code should be "204"
    And as "Alice" folder "/PARENT/testFolder" should exist
    And as "Alice" folder "/PARENT/copy1.txt" should exist
    And the content of file "/PARENT/copy1.txt/testfile.txt" for user "Alice" should be "some data"
    And the content of file "/PARENT/testFolder/testfile.txt" for user "Alice" should be "some data"

  @issue-ocis-reva-373 @issue-37683
  Scenario: Copy folder within a public link folder to the same folder name as an already existing file
    Given user "Alice" has created folder "/PARENT/testFolder"
    And user "Alice" has uploaded file with content "some data" to "/PARENT/testFolder/testfile.txt"
    And user "Alice" has uploaded file with content "some data 1" to "/PARENT/copy1.txt"
    And user "Alice" has created a public link share with settings
      | path        | /PARENT                   |
      | permissions | read,update,create,delete |
    When the public copies folder "/testFolder" to "/copy1.txt" using the new public WebDAV API
    Then the HTTP status code should be "204"
    And as "Alice" folder "/PARENT/testFolder" should exist
    And as "Alice" folder "/PARENT/copy1.txt" should exist
    And the content of file "/PARENT/copy1.txt/testfile.txt" for user "Alice" should be "some data"
    And the content of file "/PARENT/testFolder/testfile.txt" for user "Alice" should be "some data"

  @issue-ocis-reva-373 @issue-37683
  Scenario: Copy folder within a public link folder to the same folder name as an already existing file
    Given user "Alice" has created folder "/PARENT/testFolder"
    And user "Alice" has uploaded file with content "some data" to "/PARENT/testFolder/testfile.txt"
    And user "Alice" has uploaded file with content "some data 1" to "/PARENT/copy1.txt"
    And user "Alice" has created a public link share with settings
      | path        | /PARENT                   |
      | permissions | read,update,create,delete |
    When the public copies folder "/testFolder" to "/copy1.txt" using the new public WebDAV API
    Then the HTTP status code should be "204"
    And as "Alice" folder "/PARENT/testFolder" should exist
    And as "Alice" folder "/PARENT/copy1.txt" should exist
    And the content of file "/PARENT/copy1.txt/testfile.txt" for user "Alice" should be "some data"
    And the content of file "/PARENT/testFolder/testfile.txt" for user "Alice" should be "some data"

  @issue-ocis-reva-373 @issue-37683
  Scenario: Copy folder within a public link folder to the same folder name as an already existing file
    Given user "Alice" has created folder "/PARENT/testFolder"
    And user "Alice" has uploaded file with content "some data" to "/PARENT/testFolder/testfile.txt"
    And user "Alice" has uploaded file with content "some data 1" to "/PARENT/copy1.txt"
    And user "Alice" has created a public link share with settings
      | path        | /PARENT                   |
      | permissions | read,update,create,delete |
    When the public copies folder "/testFolder" to "/copy1.txt" using the new public WebDAV API
    Then the HTTP status code should be "204"
    And as "Alice" folder "/PARENT/testFolder" should exist
    And as "Alice" folder "/PARENT/copy1.txt" should exist
    And the content of file "/PARENT/copy1.txt/testfile.txt" for user "Alice" should be "some data"
    And the content of file "/PARENT/testFolder/testfile.txt" for user "Alice" should be "some data"

  @issue-ocis-reva-373 @issue-37683
  Scenario: Copy folder within a public link folder to the same folder name as an already existing file
    Given user "Alice" has created folder "/PARENT/testFolder"
    And user "Alice" has uploaded file with content "some data" to "/PARENT/testFolder/testfile.txt"
    And user "Alice" has uploaded file with content "some data 1" to "/PARENT/copy1.txt"
    And user "Alice" has created a public link share with settings
      | path        | /PARENT                   |
      | permissions | read,update,create,delete |
    When the public copies folder "/testFolder" to "/copy1.txt" using the new public WebDAV API
    Then the HTTP status code should be "204"
    And as "Alice" folder "/PARENT/testFolder" should exist
    And as "Alice" folder "/PARENT/copy1.txt" should exist
    And the content of file "/PARENT/copy1.txt/testfile.txt" for user "Alice" should be "some data"
    And the content of file "/PARENT/testFolder/testfile.txt" for user "Alice" should be "some data"

  @issue-ocis-reva-373 @issue-37683
  Scenario: Copy folder within a public link folder to the same folder name as an already existing file
    Given user "Alice" has created folder "/PARENT/testFolder"
    And user "Alice" has uploaded file with content "some data" to "/PARENT/testFolder/testfile.txt"
    And user "Alice" has uploaded file with content "some data 1" to "/PARENT/copy1.txt"
    And user "Alice" has created a public link share with settings
      | path        | /PARENT                   |
      | permissions | read,update,create,delete |
    When the public copies folder "/testFolder" to "/copy1.txt" using the new public WebDAV API
    Then the HTTP status code should be "204"
    And as "Alice" folder "/PARENT/testFolder" should exist
    And as "Alice" folder "/PARENT/copy1.txt" should exist
    And the content of file "/PARENT/copy1.txt/testfile.txt" for user "Alice" should be "some data"
    And the content of file "/PARENT/testFolder/testfile.txt" for user "Alice" should be "some data"

  @issue-ocis-reva-373 @issue-37683
  Scenario: Copy folder within a public link folder to the same folder name as an already existing file
    Given user "Alice" has created folder "/PARENT/testFolder"
    And user "Alice" has uploaded file with content "some data" to "/PARENT/testFolder/testfile.txt"
    And user "Alice" has uploaded file with content "some data 1" to "/PARENT/copy1.txt"
    And user "Alice" has created a public link share with settings
      | path        | /PARENT                   |
      | permissions | read,update,create,delete |
    When the public copies folder "/testFolder" to "/copy1.txt" using the new public WebDAV API
    Then the HTTP status code should be "204"
    And as "Alice" folder "/PARENT/testFolder" should exist
    And as "Alice" folder "/PARENT/copy1.txt" should exist
    And the content of file "/PARENT/copy1.txt/testfile.txt" for user "Alice" should be "some data"
    And the content of file "/PARENT/testFolder/testfile.txt" for user "Alice" should be "some data"

  @issue-ocis-reva-373 @issue-37683
  Scenario: Copy folder within a public link folder to the same folder name as an already existing file
    Given user "Alice" has created folder "/PARENT/testFolder"
    And user "Alice" has uploaded file with content "some data" to "/PARENT/testFolder/testfile.txt"
    And user "Alice" has uploaded file with content "some data 1" to "/PARENT/copy1.txt"
    And user "Alice" has created a public link share with settings
      | path        | /PARENT                   |
      | permissions | read,update,create,delete |
    When the public copies folder "/testFolder" to "/copy1.txt" using the new public WebDAV API
    Then the HTTP status code should be "204"
    And as "Alice" folder "/PARENT/testFolder" should exist
    And as "Alice" folder "/PARENT/copy1.txt" should exist
    And the content of file "/PARENT/copy1.txt/testfile.txt" for user "Alice" should be "some data"
    And the content of file "/PARENT/testFolder/testfile.txt" for user "Alice" should be "some data"

  @issue-ocis-reva-373 @issue-37683
  Scenario: Copy folder within a public link folder to the same folder name as an already existing file
    Given user "Alice" has created folder "/PARENT/testFolder"
    And user "Alice" has uploaded file with content "some data" to "/PARENT/testFolder/testfile.txt"
    And user "Alice" has uploaded file with content "some data 1" to "/PARENT/copy1.txt"
    And user "Alice" has created a public link share with settings
      | path        | /PARENT                   |
      | permissions | read,update,create,delete |
    When the public copies folder "/testFolder" to "/copy1.txt" using the new public WebDAV API
    Then the HTTP status code should be "204"
    And as "Alice" folder "/PARENT/testFolder" should exist
    And as "Alice" folder "/PARENT/copy1.txt" should exist
    And the content of file "/PARENT/copy1.txt/testfile.txt" for user "Alice" should be "some data"
    And the content of file "/PARENT/testFolder/testfile.txt" for user "Alice" should be "some data"