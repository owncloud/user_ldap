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
use OCA\User_LDAP\Exceptions\DoesNotExistOnLDAPException;
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
	/**
	 * @var Access|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $access;
	/**
	 * @var Manager|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $manager;

	protected function setUp() {
		parent::setUp();
		$this->config     = $this->createMock(IConfig::class);
		$filesystem = $this->createMock(FilesystemHelper::class);
		$logger     = $this->createMock(ILogger::class);
		$avatarManager = $this->createMock(IAvatarManager::class);
		$dbConn = $this->createMock(IDBConnection::class);
		$userMgr = $this->createMock(IUserManager::class);
		$this->access     = $this->createMock(Access::class);
		$this->connection     = $this->createMock(Connection::class);

		$this->connection->expects($this->any())
			->method('__get')
			->will($this->returnCallback(function($method) {
				switch ($method) {
					case 'ldapUserFilter':
						return '(objectclass=inetorgperson)';
					case 'ldapUserDisplayName':
						return 'displayName';
					case 'ldapQuotaAttribute':
					case 'ldapUserDisplayName2':
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

		$this->access->expects($this->any())
			->method('getConnection')
			->willReturn($this->connection);

		$this->manager = new Manager(
			$this->config, $filesystem, $logger, $avatarManager,
			$dbConn, $userMgr
		);
		$this->manager->setLdapAccess($this->access);

	}

	public function testGetAttributesAll() {
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('enable_avatars', true)
			->will($this->returnValue(true));

		$attributes = $this->manager->getAttributes();

		$this->assertTrue(in_array('dn', $attributes, true));
		$this->assertTrue(in_array('mail', $attributes));
		$this->assertTrue(in_array('displayName', $attributes, true));
		$this->assertTrue(in_array('jpegphoto', $attributes, true));
		$this->assertTrue(in_array('thumbnailphoto', $attributes, true));
		$this->assertTrue(in_array('uidNumber', $attributes, true));

	}
	public function testGetAttributesAvatarsDisabled() {
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('enable_avatars', true)
			->will($this->returnValue(false));

		$attributes = $this->manager->getAttributes();

		$this->assertTrue(in_array('dn', $attributes, true));
		$this->assertTrue(in_array('mail', $attributes, true));
		$this->assertTrue(in_array('displayName', $attributes, true));
		$this->assertFalse(in_array('jpegphoto', $attributes, true));
		$this->assertFalse(in_array('thumbnailphoto', $attributes, true));
		$this->assertTrue(in_array('uidNumber', $attributes, true));

	}

	public function testGetAttributesMinimal() {
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('enable_avatars', true)
			->will($this->returnValue(true));

		$attributes = $this->manager->getAttributes(true);

		$this->assertTrue(in_array('dn', $attributes, true));
		$this->assertTrue(in_array('mail', $attributes, true));
		$this->assertFalse(in_array('jpegphoto', $attributes, true));
		$this->assertFalse(in_array('thumbnailphoto', $attributes, true));
	}

	/**
	 * Prepares the Access and Cnnection mock for getUsers tests
	 * @return void
	 */
	private function prepareForGetUsers() {
		$this->access->expects($this->any())
			->method('escapeFilterPart')
			->will($this->returnCallback(function($search) {
				return $search;
			}));

		$this->access->expects($this->any())
			->method('getFilterPartForUserSearch')
			->will($this->returnCallback(function($search) {
				return $search;
			}));

		$this->access->expects($this->any())
			->method('combineFilterWithAnd')
			->will($this->returnCallback(function($param) {
				return $param[2];
			}));

		$this->access->expects($this->any())
			->method('fetchListOfUsers')
			->will($this->returnCallback(function($search, $a, $l, $o) {
				$users = [
					[ 'dn' => ['cn=alice,dc=foobar,dc=bar'] ],
					[ 'dn' => ['cn=bob,dc=foobar,dc=bar'] ],
					[ 'dn' => ['cn=carol,dc=foobar,dc=bar'] ],
				];
				if(empty($search)) {
					$result = $users;
				} else {
					$result = [];
					foreach($users as $user) {
						if(stripos($user['dn'][0],  $search) !== false) {
							$result[] = $user;
						}
					}
				}
				if(!is_null($l) || !is_null($o)) {
					$result = array_slice($result, $o, $l);
				}
				return $result;
			}));

		$this->access->expects($this->any())
			->method('fetchUsersByLoginName')
			->will($this->returnCallback(function($uid) {
				switch ($uid) {
					case 'alice':
						return array(array('dn' => ['cn=alice,dc=foobar,dc=bar']));
					case 'bob':
						return array(array('dn' => ['cn=bob,dc=foobar,dc=bar']));
					case 'carol':
						return array(array('dn' => ['cn=carol,dc=foobar,dc=bar']));
					default:
						return array();
				}
			}));

		$this->access->expects($this->any())
			->method('readAttribute')
			->with($this->anything(), $this->callback(function($attr){
				return in_array($attr, [null, '', 'jpegPhoto', 'thumbnailPhoto'], true);
			}), $this->anything())
			->will($this->returnCallback(function($attr){
				if ($attr === 'jpegPhoto' || $attr === 'thumbnailPhoto') {
					return ['sdfsdljsdlkfjsadlkjfsdewuyriuweyiuyeiwuydjkfsh'];
				} else {
					return [];
				}
			}));

		$this->access->expects($this->any())
			->method('ownCloudUserNames')
			->will($this->returnArgument(0));

		$this->access->expects($this->any())
			->method('areCredentialsValid')
			->will($this->returnValue(true));

		$this->access->expects($this->any())
			->method('isDNPartOfBase')
			->with($this->stringEndsWith('dc=foobar,dc=bar'))
			->will($this->returnValue(true));

		$mapper = $this->createMock(UserMapping::class);
		$mapper->expects($this->any())
			->method('getNameByDN')
			->will($this->returnCallback(function($dn) {
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

		$this->access->expects($this->any())
			->method('getUserMapper')
			->will($this->returnValue($mapper));

		$this->connection->expects($this->any())
			->method('__get')
			->will($this->returnCallback(function($method) {
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
		$this->assertEquals(3, count($result));
	}

	public function testGetUsersLimitOffset() {
		$this->prepareForGetUsers();
		$result = $this->manager->getUsers('', 1, 2);
		$this->assertEquals(1, count($result));
	}

	public function testGetUsersLimitOffset2() {
		$this->prepareForGetUsers();
		$result = $this->manager->getUsers('', 2, 1);
		$this->assertEquals(2, count($result));
	}

	public function testGetUsersSearchWithResult() {
		$this->prepareForGetUsers();
		$result = $this->manager->getUsers('l');
		$this->assertEquals(2, count($result));
	}

	public function testGetUsersSearchEmptyResult() {
		$this->prepareForGetUsers();
		$result = $this->manager->getUsers('noone');
		$this->assertEquals(0, count($result));
	}

	public function testGetUserEntryByDn() {
		$this->access->expects($this->once())
			->method('executeRead')
			->will($this->returnValue([
				'count' => 1, // TODO this mixing of count and dn smells bad
				'dn' => ['cn=foo,ou=users,dc=foobar,dc=bar'], // all ldap array values are multivalue
			]));
		$this->access->expects($this->once())
			->method('isDNPartOfBase')
			->will($this->returnValue(true));

		$this->assertInstanceOf(UserEntry::class, $this->manager->getUserEntryByDn('cn=foo,ou=users,dc=foobar,dc=bar'));
	}

	/**
	 * @expectedException \OutOfBoundsException
	 */
	public function testGetUserEntryByDnNotPartOfBase() {
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
	 * @expectedException \OCA\User_LDAP\Exceptions\DoesNotExistOnLDAPException
	 */
	public function testGetUserEntryByDnNotFound() {
		$this->access->expects($this->once())
			->method('executeRead')
			->will($this->returnValue([
				'count' => 0,
			]));
		$this->manager->getUserEntryByDn('dc=foobar,dc=bar');
	}

	/**
	 * FIXME the ldap error should bubble up ... and not be converted to a DoesNotExistOnLDAPException
	 * @expectedException \OCA\User_LDAP\Exceptions\DoesNotExistOnLDAPException
	 */
	public function testGetUserEntryByDnLDAPError() {
		$this->access->expects($this->once())
			->method('executeRead')
			->will($this->returnValue(false));
		$this->manager->getUserEntryByDn('dc=foobar,dc=bar');
	}
}
