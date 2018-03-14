<?php
/**
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 *
 * @copyright Copyright (c) 2016, ownCloud GmbH.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\User_LDAP\Tests\User;

use OCA\User_LDAP\Config\UserTree;
use OCA\User_LDAP\User\UserEntry;
use OCP\IConfig;
use OCP\ILogger;

class UserEntryTest extends \Test\TestCase {
	/**
	 * @var IConfig|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected $config;
	/**
	 * @var ILogger|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected $logger;
	/**
	 * @var UserTree|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected $userTree;

	protected function setUp() {
		parent::setUp();
		$this->config     = $this->createMock(IConfig::class);
		$this->logger     = $this->createMock(ILogger::class);
		$this->userTree = $this->createMock(UserTree::class);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testInvalidNew() {
		new UserEntry($this->config, $this->logger, $this->userTree, []);
	}

	public function testGetDN() {
		$userEntry = new UserEntry($this->config, $this->logger, $this->userTree,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar']
			]
		);
		self::assertEquals('cn=foo,dc=foobar,dc=bar', $userEntry->getDN());
	}

	public function testGetUserName() {
		$this->userTree->expects($this->once())
			->method('getUsernameAttribute')
			->will($this->returnValue('uid'));
		$userEntry = new UserEntry($this->config, $this->logger, $this->userTree,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'uid' => [0 => 'foo']
			]
		);
		self::assertEquals('foo', $userEntry->getUserName());
	}

	public function testGetUserIdIsConfigured() {
		$this->userTree->expects($this->once())
			->method('getExpertUsernameAttr')
			->will($this->returnValue('mail'));
		$userEntry = new UserEntry($this->config, $this->logger, $this->userTree,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'mail' => [0 => 'a@b.c']
			]
		);
		self::assertEquals('a@b.c', $userEntry->getUserId());
	}

	/**
	 * @dataProvider uuidDataProvider
	 * @param $uuidAttr string
	 * @param $uuidValue string
	 * @param $expected string
	 */
	public function testGetUsernameFallbackOnUUID($uuidAttr, $uuidValue, $expected) {
		$this->userTree->expects($this->any())
			->method('getExpertUsernameAttr')
			->will($this->returnValue(null));
		$this->userTree->expects($this->any())
			->method('getUuidAttribute')
			->will($this->returnValue($uuidAttr));
		$userEntry = new UserEntry($this->config, $this->logger, $this->userTree,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				$uuidAttr => [0 => $uuidValue]
			]
		);
		self::assertEquals($expected, $userEntry->getUserId());
	}

	/**
	 * @expectedException \OutOfBoundsException
	 */
	public function testGetUsernameUndetermined() {
		$this->userTree->expects($this->once())
			->method('getExpertUsernameAttr')
			->will($this->returnValue('mail'));
		$userEntry = new UserEntry($this->config, $this->logger, $this->userTree,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar']
			]
		);
		$userEntry->getUserId();
	}

	public function uuidDataProvider() {
		return [
			// openldap
			['entryuuid', '563418fc-423b-1033-8d1c-ad5f418ee02e', '563418fc-423b-1033-8d1c-ad5f418ee02e'],
			// redhad FreeIPA
			['ipauniqueid', '9ca8bb70-bc3a-11df-9a4d-000c29a5c12c', '9ca8bb70-bc3a-11df-9a4d-000c29a5c12c'],
			// Microsoft AD
			['objectguid', "\x53\xdf\x4e\x49\x3e\xb3\xd1\x4e\x80\x0b\x53\xdf\x4e\x49\x3e\xb3", '494EDF53-B33E-4ED1-800B-53DF4E493EB3'],
			// binary UUID may end with a \r readline char, don't trim it away
			['objectguid', "\x13\x5C\x27\xB5\x66\x64\xFD\x43\xA1\x29\xA1\x2A\x6D\x3D\x9A\x0D", 'B5275C13-6466-43FD-A129-A12A6D3D9A0D'],
			// Novell eDirectory
			['guid', "\x81\xC9\x53\x4C\xBA\x5D\xD9\x11\x89\xA2\x89\x0B\x9B\xD4\x8A\x51", '4C53C981-5DBA-11D9-89A2-890B9BD48A51'],
			// 389 Directory Server / Oracle Directory Server
			['nsuniqueid', '66446001-1dd211b2-66225011-2ee211db', '66446001-1dd211b2-66225011-2ee211db'],
		];
	}

	/**
	 * @dataProvider uuidDataProvider
	 * @param $uuidAttr string
	 * @param $uuidValue string
	 * @param $expected string
	 */
	public function testGetUUIDIsConfigured($uuidAttr, $uuidValue, $expected) {
		$this->userTree
			->method('getUuidAttribute')
			->will($this->returnValue($uuidAttr));
		$userEntry = new UserEntry($this->config, $this->logger, $this->userTree,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				$uuidAttr => [0 => $uuidValue]
			]
		);
		self::assertEquals($expected, $userEntry->getUUID());
	}

	/**
	 * @dataProvider uuidDataProvider
	 * @param $uuidAttr string
	 * @param $uuidValue string
	 * @param $expected string
	 */
	public function testGetUUIDIsAuto($uuidAttr, $uuidValue, $expected) {
		$this->userTree->expects($this->exactly(2))
			->method('getUuidAttribute')
			->will($this->returnValue('auto'));
		$userEntry = new UserEntry($this->config, $this->logger, $this->userTree,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				$uuidAttr => [0 => $uuidValue]
			]
		);
		self::assertEquals($expected, $userEntry->getUUID());
	}

	/**
	 * @expectedException \OutOfBoundsException
	 */
	public function testGetUUIDUndetermined() {
		$this->userTree->expects($this->once())
			->method('getUuidAttribute')
			->will($this->returnValue('auto'));
		$userEntry = new UserEntry($this->config, $this->logger, $this->userTree,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
			]
		);
		$userEntry->getUUID();
	}

	/**
	 * @expectedException \OutOfBoundsException
	 */
	public function testGetUUIDInvalidBinaryUUID() {
		$this->userTree->expects($this->exactly(2))
			->method('getUuidAttribute')
			->will($this->returnValue('objectguid'));
		$userEntry = new UserEntry($this->config, $this->logger, $this->userTree,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'objectguid'  => [0 => "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"],
			]
		);
		$userEntry->getUUID();
	}

	public function testGetDisplayName() {
		$this->userTree->expects($this->any())
			->method('getDisplayNameAttribute')
			->will($this->returnValue('displayname'));
		$this->userTree->expects($this->any())
			->method('getDisplayName2Attribute')
			->will($this->returnValue(''));
		$userEntry = new UserEntry($this->config, $this->logger, $this->userTree,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'displayname' => [0 => 'Foo'],
			]
		);
		self::assertEquals('Foo', $userEntry->getDisplayName());
	}

	public function testGetDisplayNameWithSecondDisplayName() {
		$this->userTree->expects($this->any())
			->method('getDisplayNameAttribute')
			->will($this->returnValue('displayname'));
		$this->userTree->expects($this->any())
			->method('getDisplayName2Attribute')
			->will($this->returnValue('mail'));
		$userEntry = new UserEntry($this->config, $this->logger, $this->userTree,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'displayname' => [0 => 'Foo'],
				'mail' => [0 => 'foo@foobar.bar']
			]
		);
		self::assertEquals('Foo (foo@foobar.bar)', $userEntry->getDisplayName());
	}

	public function testGetDisplayNameFallback() {
		$this->userTree->expects($this->any())
			->method('getDisplayNameAttribute')
			->will($this->returnValue('displayname'));
		$this->userTree->expects($this->any())
			->method('getDisplayName2Attribute')
			->will($this->returnValue('mail'));
		$this->userTree->expects($this->any())
			->method('getExpertUsernameAttr')
			->will($this->returnValue('uid'));
		$userEntry = new UserEntry($this->config, $this->logger, $this->userTree,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'uid' => [0 => 'foo'],
			]
		);
		self::assertEquals('foo', $userEntry->getDisplayName());
	}

	public function testGetQuota() {
		$this->userTree->expects($this->any())
			->method('getQuotaAttribute')
			->will($this->returnValue('quota'));
		$userEntry = new UserEntry($this->config, $this->logger, $this->userTree,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'quota' => [0 => '5 GB']
			]
		);
		self::assertEquals('5 GB', $userEntry->getQuota());
	}

	public function testGetQuotaInvalid() {
		$this->userTree->expects($this->any())
			->method('getQuotaAttribute')
			->will($this->returnValue('invalid'));
		$this->userTree->expects($this->any())
			->method('getQuotaDefault')
			->will($this->returnValue('1 GB'));
		$userEntry = new UserEntry($this->config, $this->logger, $this->userTree,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'quota' => [0 => 'invalid']
			]
		);
		self::assertEquals('1 GB', $userEntry->getQuota());
	}

	public function testGetQuotaDefault() {
		$this->userTree->expects($this->any())
			->method('getQuotaAttribute')
			->will($this->returnValue(null));
		$this->userTree->expects($this->any())
			->method('getQuotaDefault')
			->will($this->returnValue('2 GB'));
		$userEntry = new UserEntry($this->config, $this->logger, $this->userTree,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
			]
		);
		self::assertEquals('2 GB', $userEntry->getQuota());
	}

	public function testGetQuotaDefaultInvalid() {
		$this->userTree->expects($this->any())
			->method('getQuotaAttribute')
			->will($this->returnValue('invalid'));
		$this->userTree->expects($this->any())
			->method('getQuotaDefault')
			->will($this->returnValue('invalid'));
		$userEntry = new UserEntry($this->config, $this->logger, $this->userTree,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'quota' => [0 => 'invalid']
			]
		);
		self::assertEquals(null, $userEntry->getQuota());
	}

	public function testGetQuotaDefaultFallback() {
		$this->userTree->expects($this->any())
			->method('getQuotaAttribute')
			->will($this->returnValue(null));
		$this->userTree->expects($this->any())
			->method('getQuotaDefault')
			->will($this->returnValue(null));
		$userEntry = new UserEntry($this->config, $this->logger, $this->userTree,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
			]
		);
		self::assertEquals(null, $userEntry->getQuota());
	}

	public function testGetEmailAddress() {
		$this->userTree->expects($this->any())
			->method('getEmailAttribute')
			->will($this->returnValue('mail'));
		$userEntry = new UserEntry($this->config, $this->logger, $this->userTree,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'mail' => [0 => 'a@b.c']
			]
		);
		self::assertEquals('a@b.c', $userEntry->getEMailAddress());
	}

	public function testGetEmailAddressUnset() {
		$this->userTree->expects($this->any())
			->method('getEmailAttribute')
			->will($this->returnValue('mail'));
		$userEntry = new UserEntry($this->config, $this->logger, $this->userTree,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar']
			]
		);
		self::assertNull($userEntry->getEMailAddress());
	}

	public function testGetHomeAttributeWithAbsolutePath() {
		$this->userTree->expects($this->any())
			->method('getHomeFolderNamingRule')
			->will($this->returnValue('attr:home'));
		$userEntry = new UserEntry($this->config, $this->logger, $this->userTree,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'home' => [0 => '/absolute/path/to/home']
			]
		);
		self::assertEquals('/absolute/path/to/home', $userEntry->getHome());
	}

	public function testGetHomeAttributeWithRelativePath() {
		$this->config->expects($this->once())
			->method('getSystemValue')
			->will($this->returnValue('/path/to/data'));
		$this->userTree->expects($this->any())
			->method('getHomeFolderNamingRule')
			->will($this->returnValue('attr:home'));
		$userEntry = new UserEntry($this->config, $this->logger, $this->userTree,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'home' => [0 => 'f/o/o']
			]
		);
		self::assertEquals('/path/to/data/f/o/o', $userEntry->getHome());
	}

	public function testGetHomeUnset() {
		$this->userTree->expects($this->any())
			->method('getHomeFolderNamingRule')
			->will($this->returnValue('attr:home'));
		$userEntry = new UserEntry($this->config, $this->logger, $this->userTree,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
			]
		);
		self::assertNull($userEntry->getHome());
	}

	/**
	 * @expectedException \Exception
	 */
	public function testGetHomeUnsetButRequired() {
		$this->config->expects($this->once())
			->method('getAppValue')
			->will($this->returnValue(true));
		$this->userTree->expects($this->any())
			->method('getHomeFolderNamingRule')
			->will($this->returnValue('attr:home'));
		$this->userTree->expects($this->any())
			->method('getExpertUsernameAttr')
			->will($this->returnValue('mail'));
		$userEntry = new UserEntry($this->config, $this->logger, $this->userTree,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'mail' => [0 => 'a@b.c']
			]
		);
		$userEntry->getHome();
	}

	public function testGetAvatarImageInJpegPhoto() {
		$userEntry = new UserEntry($this->config, $this->logger, $this->userTree,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'jpegphoto' => [0 => 'binarydata']
			]
		);
		self::assertEquals('binarydata', $userEntry->getAvatarImage());
	}

	public function testGetAvatarImageInThumbnailPhoto() {
		$userEntry = new UserEntry($this->config, $this->logger, $this->userTree,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'thumbnailphoto' => [0 => 'binarydata']
			]
		);
		self::assertEquals('binarydata', $userEntry->getAvatarImage());
	}

	public function testGetSearchTerms() {
		$this->userTree->expects($this->any())
			->method('getAdditionalSearchAttributes')
			->will($this->returnValue(['mail', 'uid', 'firstname']));
		$userEntry = new UserEntry($this->config, $this->logger, $this->userTree,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'mail' => [0 => 'a@b.c', 1 => 'alt@b.c'], // all mails should be found
				'uid' => [0 => 'foo'],
				'firstname' => [0 => 'Foo'] // same ass foo, should be omitted
			]
		);
		self::assertEquals(['a@b.c', 'alt@b.c', 'foo'], $userEntry->getSearchTerms());
	}

	public function testGetSearchTermsEmpty() {
		$this->userTree->expects($this->any())
			->method('getAdditionalSearchAttributes')
			->will($this->returnValue([]));
		$userEntry = new UserEntry($this->config, $this->logger, $this->userTree,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
			]
		);
		self::assertEquals([], $userEntry->getSearchTerms());
	}

	public function testGetSearchTermsNull() {
		$this->userTree->expects($this->any())
			->method('getAdditionalSearchAttributes')
			->will($this->returnValue(null));
		$userEntry = new UserEntry($this->config, $this->logger, $this->userTree,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
			]
		);
		self::assertEquals([], $userEntry->getSearchTerms());
	}

	public function testLdapEntryLowercasedKeys() {
		$val = 'cn=foo,dc=foobar,dc=bar';
		$input = ['Dn' => ['count' => 1, $val]];
		$userEntry = new UserEntry($this->config, $this->logger, $this->userTree, $input);
		// This requests the dn using lowercase 'dn' so it should return the value properly
		$this->assertEquals($val, $userEntry->getDN());
	}
}
