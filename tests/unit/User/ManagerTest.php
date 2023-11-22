<?php
/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Philippe Jung <phil.jung@free.fr>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

use OCA\User_LDAP\Access;
use OCA\User_LDAP\Connection;
use OCA\User_LDAP\FilesystemHelper;
use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\User\Manager;
use OCA\User_LDAP\User\UserEntry;
use OCP\IAvatarManager;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\ILogger;
use OCP\IUserManager;

/**
 * Class Test_User_Manager
 *
 * @group DB
 *
 * @package OCA\User_LDAP\Tests\User
 */
class ManagerTest extends \Test\TestCase {
	/**
	 * @var IConfig|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected $config;
	/**
	 * @var ILogger|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected $logger;
	/**
	 * @var Connection|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected $connection;
	/**
	 * @var Access|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected $access;
	/**
	 * @var Manager|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected $manager;

	protected function setUp(): void {
		parent::setUp();
		$this->config     = $this->createMock(IConfig::class);

		/** @var FilesystemHelper|\PHPUnit\Framework\MockObject\MockObject $filesystem */
		$filesystem = $this->createMock(FilesystemHelper::class);
		/** @var ILogger|\PHPUnit\Framework\MockObject\MockObject $logger */
		$logger     = $this->createMock(ILogger::class);
		/** @var IAvatarManager|\PHPUnit\Framework\MockObject\MockObject $avatarManager */
		$avatarManager = $this->createMock(IAvatarManager::class);
		/** @var IDBConnection|\PHPUnit\Framework\MockObject\MockObject $dbConn */
		$dbConn = $this->createMock(IDBConnection::class);
		/** @var IUserManager|\PHPUnit\Framework\MockObject\MockObject $userMgr */
		$userMgr = $this->createMock(IUserManager::class);
		$this->access     = $this->createMock(Access::class);
		$this->connection     = $this->createMock(Connection::class);

		$this->connection->expects($this->any())
			->method('__get')
			->will($this->returnCallback(function ($method) {
				switch ($method) {
					case 'ldapUserFilter':
						return '(objectclass=inetorgperson)';
					case 'ldapUserDisplayName':
						return 'displayName';
					case 'ldapQuotaAttribute':
					case 'ldapUserDisplayName2':
					case 'ldapUuidUserAttribute':
						return '';
					case 'ldapEmailAttribute':
						return 'mail';
					case 'homeFolderNamingRule':
					case 'ldapExpertUsernameAttr':
						return null;
					case 'ldapAttributesForUserSearch':
						return ['uidNumber'];
					case 'ldapBaseUsers':
						return 'dc=foobar,dc=bar';
					default:
						return false;
				}
			}));

		$this->access
			->method('getConnection')
			->willReturn($this->connection);

		$this->manager = new Manager(
			$this->config,
			$filesystem,
			$logger,
			$avatarManager,
			$dbConn,
			$userMgr
		);
		$this->manager->setLdapAccess($this->access);
	}

	public function testGetAttributesAll() {
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('enable_avatars', true)
			->will($this->returnValue(true));

		$attributes = $this->manager->getAttributes();

		$this->assertContains('dn', $attributes);
		$this->assertContains('mail', $attributes);
		$this->assertContains('displayName', $attributes);
		$this->assertContains('jpegphoto', $attributes);
		$this->assertContains('thumbnailphoto', $attributes);
		$this->assertContains('uidNumber', $attributes);
	}

	public function testGetAttributesAvatarsDisabled() {
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('enable_avatars', true)
			->will($this->returnValue(false));

		$attributes = $this->manager->getAttributes();

		$this->assertContains('dn', $attributes);
		$this->assertContains('mail', $attributes);
		$this->assertContains('displayName', $attributes);
		$this->assertFalse(\in_array('jpegphoto', $attributes, true));
		$this->assertFalse(\in_array('thumbnailphoto', $attributes, true));
		$this->assertContains('uidNumber', $attributes);
	}

	public function testGetAttributesMinimal() {
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('enable_avatars', true)
			->will($this->returnValue(true));

		$attributes = $this->manager->getAttributes(true);

		$this->assertContains('dn', $attributes);
		$this->assertContains('mail', $attributes);
		$this->assertFalse(\in_array('jpegphoto', $attributes, true));
		$this->assertFalse(\in_array('thumbnailphoto', $attributes, true));
	}

	/**
	 * Prepares the Access and Cnnection mock for getUsers tests
	 * @return void
	 */
	private function prepareForGetUsers() {
		$this->access
			->method('escapeFilterPart')
			->will($this->returnCallback(function ($search) {
				return $search;
			}));

		$this->access
			->method('getFilterPartForUserSearch')
			->will($this->returnCallback(function ($search) {
				return $search;
			}));

		$this->access
			->method('combineFilterWithAnd')
			->will($this->returnCallback(function ($param) {
				return $param[2];
			}));

		$this->access
			->method('fetchListOfUsers')
			->will($this->returnCallback(function ($search, $a, $l, $o) {
				$users = [
					[ 'dn' => ['cn=alice,dc=foobar,dc=bar'] ],
					[ 'dn' => ['cn=bob,dc=foobar,dc=bar'] ],
					[ 'dn' => ['cn=carol,dc=foobar,dc=bar'] ],
				];
				if (empty($search)) {
					$result = $users;
				} else {
					$result = [];
					foreach ($users as $user) {
						if (\stripos($user['dn'][0], $search) !== false) {
							$result[] = $user;
						}
					}
				}
				if ($l !== null || $o !== null) {
					$result = \array_slice($result, $o, $l);
				}
				return $result;
			}));

		$this->access
			->method('fetchUsersByLoginName')
			->will($this->returnCallback(function ($uid) {
				switch ($uid) {
					case 'alice':
						return [['dn' => ['cn=alice,dc=foobar,dc=bar']]];
					case 'bob':
						return [['dn' => ['cn=bob,dc=foobar,dc=bar']]];
					case 'carol':
						return [['dn' => ['cn=carol,dc=foobar,dc=bar']]];
					default:
						return [];
				}
			}));

		$this->access
			->method('readAttribute')
			->with($this->anything(), $this->callback(function ($attr) {
				return \in_array($attr, [null, '', 'jpegPhoto', 'thumbnailPhoto'], true);
			}), $this->anything())
			->will($this->returnCallback(function ($attr) {
				if ($attr === 'jpegPhoto' || $attr === 'thumbnailPhoto') {
					return ['sdfsdljsdlkfjsadlkjfsdewuyriuweyiuyeiwuydjkfsh'];
				}
				return [];
			}));

		$this->access
			->method('ownCloudUserNames')
			->will($this->returnArgument(0));

		$this->access
			->method('areCredentialsValid')
			->will($this->returnValue(true));

		$this->access
			->method('isDNPartOfBase')
			->with($this->stringEndsWith('dc=foobar,dc=bar'))
			->will($this->returnValue(true));

		$mapper = $this->createMock(UserMapping::class);
		$mapper
			->method('getNameByDN')
			->will($this->returnCallback(function ($dn) {
				switch ($dn) {
					case 'cn=alice,dc=foobar,dc=bar':
						return 'alice';
					case 'cn=bob,dc=foobar,dc=bar':
						return 'bob';
					case 'cn=carol,dc=foobar,dc=bar':
						return 'carol';
					default:
						return false;
				}
			}));

		$this->access
			->method('getUserMapper')
			->will($this->returnValue($mapper));

		$this->connection
			->method('__get')
			->will($this->returnCallback(function ($method) {
				switch ($method) {
					case 'ldapUserFilter':
						return '(objectclass=inetorgperson)';
					case 'ldapUserDisplayName':
						return 'displayName';
					case 'ldapQuotaAttribute':
					case 'ldapUserDisplayName2':
						return '';
					case 'ldapEmailAttribute':
					case 'homeFolderNamingRule':
					case 'ldapAttributesForUserSearch':
						return null;
					case 'ldapBaseUsers':
						return 'dc=foobar,dc=bar';
					default:
						return false;
				}
			}));
	}

	public function testGetUsersNoParam() {
		$this->prepareForGetUsers();
		$result = $this->manager->getUsers();
		$this->assertCount(3, $result);
	}

	public function testGetUsersLimitOffset() {
		$this->prepareForGetUsers();
		$result = $this->manager->getUsers('', 1, 2);
		$this->assertCount(1, $result);
	}

	public function testGetUsersLimitOffset2() {
		$this->prepareForGetUsers();
		$result = $this->manager->getUsers('', 2, 1);
		$this->assertCount(2, $result);
	}

	public function testGetUsersSearchWithResult() {
		$this->prepareForGetUsers();
		$result = $this->manager->getUsers('l');
		$this->assertCount(2, $result);
	}

	public function testGetUsersSearchEmptyResult() {
		$this->prepareForGetUsers();
		$result = $this->manager->getUsers('noone');
		$this->assertCount(0, $result);
	}

	public function testGetLdapUsers() {
		$this->prepareForGetUsers();
		$result = $this->manager->getLdapUsers('');
		$expected = [
			[ 'dn' => ['cn=alice,dc=foobar,dc=bar'] ],
			[ 'dn' => ['cn=bob,dc=foobar,dc=bar'] ],
			[ 'dn' => ['cn=carol,dc=foobar,dc=bar'] ],
		];
		$this->assertEquals($expected, $result);
	}

	public function testGetUserEntryByDn() {
		$this->access->expects($this->once())
			->method('executeRead')
			->will($this->returnValue([
				'count' => 1, // TODO this mixing of count and dn smells bad
				'dn' => ['cn=foo,ou=users,dc=foobar,dc=bar'], // all ldap array values are multivalue
			]));

		$mapper = $this->createMock(UserMapping::class);
		$mapper->expects($this->once())
			->method('getNameByDN')
			->with($this->equalTo('cn=foo,ou=users,dc=foobar,dc=bar'))
			->will($this->returnValue('foo'));

		$this->access
			->method('getUserMapper')
			->will($this->returnValue($mapper));

		$this->access->expects($this->once())
			->method('isDNPartOfBase')
			->will($this->returnValue(true));

		$this->assertInstanceOf(UserEntry::class, $this->manager->getUserEntryByDn('cn=foo,ou=users,dc=foobar,dc=bar'));
	}

	/**
	 */
	public function testGetUserEntryByDnNotPartOfBase() {
		$this->expectException(\OutOfBoundsException::class);

		$this->access->expects($this->once())
			->method('executeRead')
			->will($this->returnValue([
				'count' => 1, // TODO this mixing of count and dn smells bad
				'dn' => ['cn=foo,ou=users,dc=foobar,dc=bar'], // all ldap array values are multivalue
			]));
		$this->access->expects($this->once())
			->method('isDNPartOfBase')
			->will($this->returnValue(false));

		$this->manager->getUserEntryByDn('cn=foo,ou=users,dc=foobar,dc=bar');
	}

	/**
	 */
	public function testGetUserEntryByDnNotFound() {
		$this->expectException(\OCA\User_LDAP\Exceptions\DoesNotExistOnLDAPException::class);

		$this->access->expects($this->once())
			->method('executeRead')
			->will($this->returnValue([
				'count' => 0,
			]));
		$this->manager->getUserEntryByDn('dc=foobar,dc=bar');
	}

	/**
	 * FIXME the ldap error should bubble up ... and not be converted to a DoesNotExistOnLDAPException
	 */
	public function testGetUserEntryByDnLDAPError() {
		$this->expectException(\OCA\User_LDAP\Exceptions\DoesNotExistOnLDAPException::class);

		$this->access->expects($this->once())
			->method('executeRead')
			->will($this->returnValue(false));
		$this->manager->getUserEntryByDn('dc=foobar,dc=bar');
	}

	public function testGetCachedEntryCached() {
		$this->access->expects($this->once())
			->method('username2dn')
			->with('usertest')
			->willReturn('uid=usertest,ou=users,dc=example,dc=com');

		$this->access->method('executeRead')
			->with($this->anything(), 'uid=usertest,ou=users,dc=example,dc=com', $this->anything(), $this->anything(), $this->anything())
			->willReturn([
				'count' => 5,
				0 => 'dn',
				'dn' => [
					'count' => 1,
					0 => 'uid=usertest,ou=users,dc=example,dc=com',
				],
				1 => 'uid',
				'uid' => [
					'count' => 1,
					0 => 'usertest',
				],
				2 => 'displayname',
				'displayname' => [
					'count' => 0,
					0 => 'Test user',
				],
				3 => 'quota',
				'quota' => [
					'count' => 1,
					0 => '7GB',
				],
				4 => 'mail',
				'mail' => [
					'count' => 1,
					0 => 'usertest@example.com',
				],
			]);

		$mapper = $this->createMock(UserMapping::class);
		$mapper->expects($this->once())
			->method('getNameByDN')
			->with('uid=usertest,ou=users,dc=example,dc=com')
			->will($this->returnValue('usertest'));

		$this->access->expects($this->any())
			->method('getUserMapper')
			->will($this->returnValue($mapper));

		$this->access->method('isDNPartOfBase')
			->willReturn(true);

		$cachedEntry = $this->manager->getCachedEntry('usertest');
		$this->assertEquals('uid=usertest,ou=users,dc=example,dc=com', $cachedEntry->getDN());
		$this->assertEquals('usertest', $cachedEntry->getOwnCloudUID());

		$cachedEntry2 = $this->manager->getCachedEntry('usertest');
		$this->assertEquals($cachedEntry, $cachedEntry2);
	}

	public function testGetCachedEntryFailed() {
		$this->access->expects($this->once())
			->method('username2dn')
			->willReturn(false);

		$this->assertNull($this->manager->getCachedEntry('usertest'));
	}

	public function testGetCachedEntryMissingEntry() {
		$this->access->expects($this->once())
			->method('username2dn')
			->with('usertest')
			->willReturn('uid=usertest,ou=users,dc=example,dc=com');

		$this->access->method('executeRead')
			->with($this->anything(), 'uid=usertest,ou=users,dc=example,dc=com', $this->anything(), $this->anything(), $this->anything())
			->willReturn(false);

		$this->assertNull($this->manager->getCachedEntry('usertest'));
	}

	public function testGetCachedEntryOutsideOfBase() {
		$this->access->expects($this->once())
			->method('username2dn')
			->with('usertest')
			->willReturn('uid=usertest,ou=users,dc=example,dc=com');

		$this->access->method('executeRead')
			->with($this->anything(), 'uid=usertest,ou=users,dc=example,dc=com', $this->anything(), $this->anything(), $this->anything())
			->willReturn([
				'count' => 5,
				0 => 'dn',
				'dn' => [
					'count' => 1,
					0 => 'uid=usertest,ou=users,dc=example,dc=com',
				],
				1 => 'uid',
				'uid' => [
					'count' => 1,
					0 => 'usertest',
				],
				2 => 'displayname',
				'displayname' => [
					'count' => 0,
					0 => 'Test user',
				],
				3 => 'quota',
				'quota' => [
					'count' => 1,
					0 => '7GB',
				],
				4 => 'mail',
				'mail' => [
					'count' => 1,
					0 => 'usertest@example.com',
				],
			]);

		$this->access->method('isDNPartOfBase')
			->willReturn(false);

		$this->assertNull($this->manager->getCachedEntry('usertest'));
	}

	public function testResolveUID() {
		$mapper = $this->createMock(UserMapping::class);
		$mapper->expects($this->exactly(2))
			->method('getNameByDN')
			->withConsecutive(
				['cn=lastname\, firstname,ou=development,dc=owncloud,dc=com'],
				['cn=lastname\5c2C firstname,ou=development,dc=owncloud,dc=com']
			)
			->willReturnOnConsecutiveCalls(
				'',
				'lastname'
			);
		$mapper->expects($this->once())
			->method('getNameByUUID')
			->willReturn('');
		$mapper->expects($this->once())
			->method('updateDN')
			->with(
				'cn=lastname\5c2C firstname,ou=development,dc=owncloud,dc=com',
				'cn=lastname\, firstname,ou=development,dc=owncloud,dc=com'
			);

		$this->access
			->method('getUserMapper')
			->will($this->returnValue($mapper));

		$this->config
			->method('getAppValue')
			->willReturn('1');

		/** @var UserEntry|\PHPUnit\Framework\MockObject\MockObject $entry */
		$entry =  $this->createMock(UserEntry::class);
		$entry->method('getDN')->willReturn('cn=lastname\, firstname,ou=development,dc=owncloud,dc=com');
		$entry->method('getUUID')->willReturn('a-b-c-d');

		self::assertSame('lastname', $this->manager->resolveUID($entry));
	}

	public function testDnUpdatedWhenUuidMatches() {
		$oldDn = 'cn=olddn,ou=users,dc=example,dc=com';
		$newDn = 'cn=newdn,ou=users,dc=example,dc=com';
		$uuid = 'aaaa-bbbb-cccc-dddd';
		$mapper = $this->createMock(UserMapping::class);
		$mapper->expects($this->once())
			->method('getUUIDByDN')
			->with($oldDn)
			->willReturn($uuid);

		$this->access->expects($this->any())
			->method('getUserMapper')
			->willReturn($mapper);
		$this->access->expects($this->any())
			->method('getUserDnByUuid')
			->with($uuid)
			->willReturn($newDn);
		$this->access->expects($this->any())
			->method('readAttribute')
			->willReturn([]);
		$isRemapped = $this->manager->resolveMissingDN($oldDn);
		$this->assertTrue($isRemapped);
	}

	public function testDnNotUpdatedWhenUuidNotFound() {
		$oldDn = 'cn=olddn,ou=users,dc=example,dc=com';
		$newDn = 'cn=newdn,ou=users,dc=example,dc=com';
		$uuid = 'aaaa-bbbb-cccc-dddd';
		$mapper = $this->createMock(UserMapping::class);
		$mapper->expects($this->once())
			->method('getUUIDByDN')
			->with($oldDn)
			->willReturn($uuid);

		$this->access->expects($this->any())
			->method('getUserMapper')
			->willReturn($mapper);
		$this->access->expects($this->any())
			->method('getUserDnByUuid')
			->with($uuid)
			->willReturn($newDn);
		$this->access->expects($this->any())
			->method('readAttribute')
			->willReturn(null);

		$isRemapped = $this->manager->resolveMissingDN($oldDn);
		$this->assertFalse($isRemapped);
	}

	public function testDnNotUpdatedWhenNewDnNotFound() {
		$oldDn = 'cn=olddn,ou=users,dc=example,dc=com';
		$uuid = 'aaaa-bbbb-cccc-dddd';
		$mapper = $this->createMock(UserMapping::class);
		$mapper->expects($this->once())
			->method('getUUIDByDN')
			->with($oldDn)
			->willReturn($uuid);

		$this->access->expects($this->any())
			->method('getUserMapper')
			->willReturn($mapper);
		$this->access->expects($this->any())
			->method('getUserDnByUuid')
			->with($uuid)
			->willThrowException(new \OutOfBoundsException());

		$isRemapped = $this->manager->resolveMissingDN($oldDn);
		$this->assertFalse($isRemapped);
	}
}
