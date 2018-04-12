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

use OCA\User_LDAP\Connection;
use OCA\User_LDAP\User\UserEntry;
use OCP\IConfig;
use OCP\ILogger;

class UserEntryTest extends \Test\TestCase {
	/**
	 * @var IConfig|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $config;
	/**
	 * @var ILogger|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $logger;
	/**
	 * @var Connection|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $connection;

	protected function setUp() {
		parent::setUp();
		$this->config     = $this->createMock(IConfig::class);
		$this->logger     = $this->createMock(ILogger::class);
		$this->connection = $this->createMock(Connection::class);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testInvalidNew() {
		new UserEntry($this->config, $this->logger, $this->connection, []);
	}

	public function testGetDN() {
		$userEntry = new UserEntry($this->config, $this->logger, $this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar']
			]
		);
		self::assertEquals('cn=foo,dc=foobar,dc=bar', $userEntry->getDN());
	}

	public function testGetUsernameIsConfigured() {
		$this->connection->expects($this->once())
			->method('__get')
			->with($this->equalTo('ldapExpertUsernameAttr'))
			->will($this->returnValue('mail'));
		$userEntry = new UserEntry($this->config, $this->logger, $this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'mail' => [0 => 'a@b.c']
			]
		);
		self::assertEquals('a@b.c', $userEntry->getUsername());
	}

	/**
	 * @dataProvider uuidDataProvider
	 * @param $uuidAttr string
	 * @param $uuidValue string
	 * @param $expected string
	 */
	public function testGetUsernameFallbackOnUUID($uuidAttr, $uuidValue, $expected) {
		$this->connection->expects($this->exactly(3))
			->method('__get')
			->withConsecutive(
				[$this->equalTo('ldapExpertUsernameAttr')],
				[$this->equalTo('ldapExpertUUIDUserAttr')],
				[$this->equalTo('ldapExpertUUIDUserAttr')]
			)
			->willReturnOnConsecutiveCalls(
				null,
				$uuidAttr,
				$uuidAttr
			);
		$userEntry = new UserEntry($this->config, $this->logger, $this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				$uuidAttr => [0 => $uuidValue]
			]
		);
		self::assertEquals($expected, $userEntry->getUsername());
	}

	/**
	 * @expectedException \OutOfBoundsException
	 */
	public function testGetUsernameUndetermined() {
		$this->connection->expects($this->exactly(1))
			->method('__get')
			->with($this->equalTo('ldapExpertUsernameAttr'))
			->will($this->returnValue('mail'));
		$userEntry = new UserEntry($this->config, $this->logger, $this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar']
			]
		);
		$userEntry->getUsername();
	}


	public function uuidDataProvider () {
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
		$this->connection->expects($this->exactly(2))
			->method('__get')
			->with($this->equalTo('ldapExpertUUIDUserAttr'))
			->will($this->returnValue($uuidAttr));
		$userEntry = new UserEntry($this->config, $this->logger, $this->connection,
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
		$this->connection->expects($this->exactly(2))
			->method('__get')
			->withConsecutive(
				[$this->equalTo('ldapExpertUUIDUserAttr')],
				[$this->equalTo('ldapExpertUUIDUserAttr')]
			)
			->willReturnOnConsecutiveCalls(
				'auto',
				'auto'
			);
		$userEntry = new UserEntry($this->config, $this->logger, $this->connection,
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
		$this->connection->expects($this->once())
			->method('__get')
			->with($this->equalTo('ldapExpertUUIDUserAttr'))
			->will($this->returnValue('auto'));
		$userEntry = new UserEntry($this->config, $this->logger, $this->connection,
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
		$this->connection->expects($this->exactly(2))
			->method('__get')
			->with($this->equalTo('ldapExpertUUIDUserAttr'))
			->will($this->returnValue('objectguid'));
		$userEntry = new UserEntry($this->config, $this->logger, $this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'objectguid'  => [0 => "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"],
			]
		);
		$userEntry->getUUID();
	}

	public function testGetDisplayName() {
		$this->connection->expects($this->exactly(2))
			->method('__get')
			->withConsecutive(
				[$this->equalTo('ldapUserDisplayName')],
				[$this->equalTo('ldapUserDisplayName2')]
			)
			->willReturnOnConsecutiveCalls(
				'displayname',
				''
			);
		$userEntry = new UserEntry($this->config, $this->logger, $this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'displayname' => [0 => 'Foo'],
			]
		);
		self::assertEquals('Foo', $userEntry->getDisplayName());
	}

	public function testGetDisplayNameWithSecondDisplayName() {
		$this->connection->expects($this->exactly(2))
			->method('__get')
			->withConsecutive(
				[$this->equalTo('ldapUserDisplayName')],
				[$this->equalTo('ldapUserDisplayName2')]
			)
			->willReturnOnConsecutiveCalls(
				'displayname',
				'mail'
			);
		$userEntry = new UserEntry($this->config, $this->logger, $this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'displayname' => [0 => 'Foo'],
				'mail' => [0 => 'foo@foobar.bar']
			]
		);
		self::assertEquals('Foo (foo@foobar.bar)', $userEntry->getDisplayName());
	}

	public function testGetDisplayNameFallback() {
		$this->connection->expects($this->exactly(3))
			->method('__get')
			->withConsecutive(
				[$this->equalTo('ldapUserDisplayName')],
				[$this->equalTo('ldapUserDisplayName2')],
				[$this->equalTo('ldapExpertUsernameAttr')]
			)
			->willReturnOnConsecutiveCalls(
				'displayname',
				'mail',
				'uid'
			);
		$userEntry = new UserEntry($this->config, $this->logger, $this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'uid' => [0 => 'foo'],
			]
		);
		self::assertEquals('foo', $userEntry->getDisplayName());
	}

	public function testGetQuota() {
		$this->connection->expects($this->once())
			->method('__get')
			->with($this->equalTo('ldapQuotaAttribute'))
			->will($this->returnValue('quota'));
		$userEntry = new UserEntry($this->config, $this->logger, $this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'quota' => [0 => '5 GB']
			]
		);
		self::assertEquals('5 GB', $userEntry->getQuota());
	}

	public function testGetQuotaInvalid() {
		$this->connection->expects($this->exactly(3))
			->method('__get')
			->withConsecutive(
				[$this->equalTo('ldapQuotaAttribute')],
				[$this->equalTo('ldapQuotaDefault')],
				[$this->equalTo('ldapQuotaDefault')]
			)
			->willReturnOnConsecutiveCalls(
				'invalid',
				'1 GB',
				'1 GB'
			);
		$userEntry = new UserEntry($this->config, $this->logger, $this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'quota' => [0 => 'invalid']
			]
		);
		self::assertEquals('1 GB', $userEntry->getQuota());
	}

	public function testGetQuotaDefault() {
		$this->connection->expects($this->exactly(3))
			->method('__get')
			->withConsecutive(
				[$this->equalTo('ldapQuotaAttribute')],
				[$this->equalTo('ldapQuotaDefault')],
				[$this->equalTo('ldapQuotaDefault')]
			)
			->willReturnOnConsecutiveCalls(
				null,
				'2 GB',
				'2 GB'
			);
		$userEntry = new UserEntry($this->config, $this->logger, $this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
			]
		);
		self::assertEquals('2 GB', $userEntry->getQuota());
	}

	public function testGetQuotaDefaultInvalid() {
		$this->connection->expects($this->exactly(3))
			->method('__get')
			->withConsecutive(
				[$this->equalTo('ldapQuotaAttribute')],
				[$this->equalTo('ldapQuotaDefault')],
				[$this->equalTo('ldapQuotaDefault')]
			)
			->willReturnOnConsecutiveCalls(
				'invalid',
				'invalid',
				'invalid'
			);
		$userEntry = new UserEntry($this->config, $this->logger, $this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'quota' => [0 => 'invalid']
			]
		);
		self::assertEquals(null, $userEntry->getQuota());
	}

	public function testGetQuotaDefaultFallback() {
		$this->connection->expects($this->exactly(2))
			->method('__get')
			->withConsecutive(
				[$this->equalTo('ldapQuotaAttribute')],
				[$this->equalTo('ldapQuotaDefault')]
			)
			->willReturnOnConsecutiveCalls(
				null,
				null
			);
		$userEntry = new UserEntry($this->config, $this->logger, $this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
			]
		);
		self::assertEquals(null, $userEntry->getQuota());
	}

	public function testGetEmailAddress() {
		$this->connection->expects($this->once())
			->method('__get')
			->with($this->equalTo('ldapEmailAttribute'))
			->will($this->returnValue('mail'));
		$userEntry = new UserEntry($this->config, $this->logger, $this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'mail' => [0 => 'a@b.c']
			]
		);
		self::assertEquals('a@b.c', $userEntry->getEMailAddress());
	}

	public function testGetEmailAddressUnset() {
		$this->connection->expects($this->once())
			->method('__get')
			->with($this->equalTo('ldapEmailAttribute'))
			->will($this->returnValue('mail'));
		$userEntry = new UserEntry($this->config, $this->logger, $this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar']
			]
		);
		self::assertNull($userEntry->getEMailAddress());
	}

	public function testGetHomeAttributeWithAbsolutePath() {
		$this->connection->expects($this->once())
			->method('__get')
			->with($this->equalTo('homeFolderNamingRule'))
			->will($this->returnValue('attr:home'));
		$userEntry = new UserEntry($this->config, $this->logger, $this->connection,
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
		$this->connection->expects($this->once())
			->method('__get')
			->with($this->equalTo('homeFolderNamingRule'))
			->will($this->returnValue('attr:home'));
		$userEntry = new UserEntry($this->config, $this->logger, $this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'home' => [0 => 'f/o/o']
			]
		);
		self::assertEquals('/path/to/data/f/o/o', $userEntry->getHome());
	}

	public function testGetHomeUnset() {
		$this->connection->expects($this->once())
			->method('__get')
			->with($this->equalTo('homeFolderNamingRule'))
			->will($this->returnValue('attr:home'));
		$userEntry = new UserEntry($this->config, $this->logger, $this->connection,
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
		$this->connection->expects($this->exactly(2))
			->method('__get')
			->withConsecutive(
				[$this->equalTo('homeFolderNamingRule')],
				[$this->equalTo('ldapExpertUsernameAttr')]
			)
			->willReturnOnConsecutiveCalls(
				'attr:home',
				'mail'
			);
		$userEntry = new UserEntry($this->config, $this->logger, $this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'mail' => [0 => 'a@b.c']
			]
		);
		$userEntry->getHome();
	}

	public function testGetAvatarImageInJpegPhoto() {
		$userEntry = new UserEntry($this->config, $this->logger, $this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'jpegphoto' => [0 => 'binarydata']
			]
		);
		self::assertEquals('binarydata', $userEntry->getAvatarImage());
	}

	public function testGetAvatarImageInThumbnailPhoto() {
		$userEntry = new UserEntry($this->config, $this->logger, $this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'thumbnailphoto' => [0 => 'binarydata']
			]
		);
		self::assertEquals('binarydata', $userEntry->getAvatarImage());
	}

	public function testGetSearchTerms() {
		$this->connection->expects($this->once())
			->method('__get')
			->with($this->equalTo('ldapAttributesForUserSearch'))
			->will($this->returnValue(['mail', 'uid', 'firstname']));
		$userEntry = new UserEntry($this->config, $this->logger, $this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'mail' => [0 => 'a@b.c', 1 => 'alt@b.c'], // all mails should be found
				'uid' => [0 => 'foo'],
				'firstname' => [0 => 'Foo'] // same ass foo, should be omitted
			]
		);
		self::assertEquals(['a@b.c', 'alt@b.c', 'foo'], $userEntry->getSearchTerms());
	}

	public function testGetSearchTermsUnconfigured() {
		$this->connection->expects($this->once())
			->method('__get')
			->with($this->equalTo('ldapAttributesForUserSearch'))
			->will($this->returnValue([]));
		$userEntry = new UserEntry($this->config, $this->logger, $this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
			]
		);
		self::assertEquals([], $userEntry->getSearchTerms());
	}

	public function testLdapEntryLowercasedKeys() {

		$val = 'cn=foo,dc=foobar,dc=bar';
		$input = ['Dn' => ['count' => 1, $val]];
		$userEntry = new UserEntry($this->config, $this->logger, $this->connection, $input);
		// This requests the dn using lowercase 'dn' so it should return the value properly
		$this->assertEquals($val, $userEntry->getDN());
	}

}
