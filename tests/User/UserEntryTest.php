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
	public function testGetUsernameFallbackOnUUID() {
		$this->connection->expects($this->exactly(3))
			->method('__get')
			->withConsecutive(
				[$this->equalTo('ldapExpertUsernameAttr')],
				[$this->equalTo('ldapExpertUUIDUserAttr')],
				[$this->equalTo('ldapExpertUUIDUserAttr')]
			)
			->willReturnOnConsecutiveCalls(
				null,
				'objectguid',
				'objectguid'
			);
		$userEntry = new UserEntry($this->config, $this->logger, $this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'objectguid' => [0 => '563418fc-423b-1033-8d1c-ad5f418ee02e']
			]
		);
		self::assertEquals('563418fc-423b-1033-8d1c-ad5f418ee02e', $userEntry->getUsername());
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

	public function testGetUUIDIsConfigured() {
		$this->connection->expects($this->exactly(2))
			->method('__get')
			->with($this->equalTo('ldapExpertUUIDUserAttr'))
			->will($this->returnValue('objectguid'));
		$userEntry = new UserEntry($this->config, $this->logger, $this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'objectguid' => [0 => '563418fc-423b-1033-8d1c-ad5f418ee02e']
			]
		);
		self::assertEquals('563418fc-423b-1033-8d1c-ad5f418ee02e', $userEntry->getUUID());
	}

	public function testGetUUIDIsAuto() {
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
				'guid' => [0 => '563418fc-423b-1033-8d1c-ad5f418ee02e']
			]
		);
		self::assertEquals('563418fc-423b-1033-8d1c-ad5f418ee02e', $userEntry->getUUID());
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
		self::assertEquals('default', $userEntry->getQuota());
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
		self::assertEquals('default', $userEntry->getQuota());
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

	public function testGetMemberOf() {
		$userEntry = new UserEntry($this->config, $this->logger, $this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'memberof' => [
					0 => 'group1',
					1 => 'group2',
				]
			]
		);
		self::assertEquals(['group1', 'group2'], $userEntry->getMemberOfGroups());
		//TODO can we better verify the group names?
	}

	public function testGetMemberOfEmpty() {
		$userEntry = new UserEntry($this->config, $this->logger, $this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
			]
		);
		self::assertEquals([], $userEntry->getMemberOfGroups());
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

}
