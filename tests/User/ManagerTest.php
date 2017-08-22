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
use OCP\IAvatarManager;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\ILogger;
use OCP\Image;
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
		$image = $this->createMock(Image::class);
		$dbConn = $this->createMock(IDBConnection::class);
		$userMgr = $this->createMock(IUserManager::class);
		$this->access     = $this->createMock(Access::class);
		$this->connection     = $this->createMock(Connection::class);

		$this->access->expects($this->any())
			->method('getConnection')
			->willReturn($this->connection);

		$this->manager = new Manager(
			$this->config, $filesystem, $logger, $avatarManager, $image,
			$dbConn, $userMgr
		);
		$this->manager->setLdapAccess($this->access);

	}
/*
	public function testGetByDNExisting() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		$inputDN = 'cn=foo,dc=foobar,dc=bar';
		$uid = '563418fc-423b-1033-8d1c-ad5f418ee02e';

		$access->expects($this->once())
			->method('stringResemblesDN')
			->with($this->equalTo($inputDN))
			->will($this->returnValue(true));

		$access->expects($this->once())
			->method('dn2username')
			->with($this->equalTo($inputDN))
			->will($this->returnValue($uid));

		$access->expects($this->never())
			->method('username2dn');

		$manager = new Manager($config, $filesys, $log, $avaMgr, $image, $dbc, $userMgr);
		$manager->setLdapAccess($access);
		$user = $manager->get($inputDN);

		// Now we fetch the user again. If this leads to a failing test,
		// runtime caching the manager is broken.
		$user = $manager->get($inputDN);

		$this->assertInstanceOf('\OCA\User_LDAP\User\User', $user);
	}

	public function testGetByEDirectoryDN() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		$inputDN = 'uid=foo,o=foobar,c=bar';
		$uid = '563418fc-423b-1033-8d1c-ad5f418ee02e';

		$access->expects($this->once())
			->method('stringResemblesDN')
			->with($this->equalTo($inputDN))
			->will($this->returnValue(true));

		$access->expects($this->once())
			->method('dn2username')
			->with($this->equalTo($inputDN))
			->will($this->returnValue($uid));

		$access->expects($this->never())
			->method('username2dn');

		$manager = new Manager($config, $filesys, $log, $avaMgr, $image, $dbc, $userMgr);
		$manager->setLdapAccess($access);
		$user = $manager->get($inputDN);

		$this->assertInstanceOf('\OCA\User_LDAP\User\User', $user);
	}

	public function testGetByExoticDN() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		$inputDN = 'ab=cde,f=ghei,mno=pq';
		$uid = '563418fc-423b-1033-8d1c-ad5f418ee02e';

		$access->expects($this->once())
			->method('stringResemblesDN')
			->with($this->equalTo($inputDN))
			->will($this->returnValue(true));

		$access->expects($this->once())
			->method('dn2username')
			->with($this->equalTo($inputDN))
			->will($this->returnValue($uid));

		$access->expects($this->never())
			->method('username2dn');

		$manager = new Manager($config, $filesys, $log, $avaMgr, $image, $dbc, $userMgr);
		$manager->setLdapAccess($access);
		$user = $manager->get($inputDN);

		$this->assertInstanceOf('\OCA\User_LDAP\User\User', $user);
	}

	public function testGetByDNNotExisting() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		$inputDN = 'cn=gone,dc=foobar,dc=bar';

		$access->expects($this->once())
			->method('stringResemblesDN')
			->with($this->equalTo($inputDN))
			->will($this->returnValue(true));

		$access->expects($this->once())
			->method('dn2username')
			->with($this->equalTo($inputDN))
			->will($this->returnValue(false));

		$access->expects($this->once())
			->method('username2dn')
			->with($this->equalTo($inputDN))
			->will($this->returnValue(false));

		$manager = new Manager($config, $filesys, $log, $avaMgr, $image, $dbc, $userMgr);
		$manager->setLdapAccess($access);
		$user = $manager->get($inputDN);

		$this->assertNull($user);
	}

	public function testGetByUidExisting() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		$dn = 'cn=foo,dc=foobar,dc=bar';
		$uid = '563418fc-423b-1033-8d1c-ad5f418ee02e';

		$access->expects($this->never())
			->method('dn2username');

		$access->expects($this->once())
			->method('username2dn')
			->with($this->equalTo($uid))
			->will($this->returnValue($dn));

		$access->expects($this->once())
			->method('stringResemblesDN')
			->with($this->equalTo($uid))
			->will($this->returnValue(false));

		$manager = new Manager($config, $filesys, $log, $avaMgr, $image, $dbc, $userMgr);
		$manager->setLdapAccess($access);
		$user = $manager->get($uid);

		// Now we fetch the user again. If this leads to a failing test,
		// runtime caching the manager is broken.
		$user = $manager->get($uid);

		$this->assertInstanceOf('\OCA\User_LDAP\User\User', $user);
	}

	public function testGetByUidNotExisting() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		$uid = 'gone';

		$access->expects($this->never())
			->method('dn2username');

		$access->expects($this->exactly(1))
			->method('username2dn')
			->with($this->equalTo($uid))
			->will($this->returnValue(false));

		$manager = new Manager($config, $filesys, $log, $avaMgr, $image, $dbc, $userMgr);
		$manager->setLdapAccess($access);
		$user = $manager->get($uid);

		$this->assertNull($user);
	}
*/
	public function testGetAttributesAll() {
		$this->connection->expects($this->exactly(6))
			->method('__get')
			->withConsecutive(
				[$this->equalTo('ldapQuotaAttribute')],
				[$this->equalTo('ldapEmailAttribute')],
				[$this->equalTo('ldapUserDisplayName')],
				[$this->equalTo('ldapUserDisplayName2')],
				[$this->equalTo('homeFolderNamingRule')],
				[$this->equalTo('ldapAttributesForUserSearch')]
			// uuidAttributes are a real member. the mock also has them
			)
			->willReturnOnConsecutiveCalls(
				'',
				'mail',
				'displayName',
				'',
				null,
				null
			);

		$attributes = $this->manager->getAttributes();

		$this->assertTrue(in_array('dn', $attributes));
		$this->assertTrue(in_array('mail', $attributes));
		$this->assertTrue(in_array('jpegphoto', $attributes));
		$this->assertTrue(in_array('thumbnailphoto', $attributes));
	}

	public function testGetAttributesWithCustomSearch() {
		$this->connection->expects($this->exactly(6))
			->method('__get')
			->withConsecutive(
				[$this->equalTo('ldapQuotaAttribute')],
				[$this->equalTo('ldapEmailAttribute')],
				[$this->equalTo('ldapUserDisplayName')],
				[$this->equalTo('ldapUserDisplayName2')],
				[$this->equalTo('homeFolderNamingRule')],
				[$this->equalTo('ldapAttributesForUserSearch')]
				// uuidAttributes are a real member. the mock also has them
			)
			->willReturnOnConsecutiveCalls(
				'',
				'mail',
				'displayName',
				'',
				null,
				['uidNumber']
			);


		$attributes = $this->manager->getAttributes();

		$this->assertTrue(in_array('uidNumber', $attributes));
		$this->assertTrue(in_array('dn', $attributes));
		$this->assertTrue(in_array('mail', $attributes));
		$this->assertTrue(in_array('jpegphoto', $attributes));
		$this->assertTrue(in_array('thumbnailphoto', $attributes));
	}

	public function testGetAttributesMinimal() {
		$this->connection->expects($this->exactly(6))
			->method('__get')
			->withConsecutive(
				[$this->equalTo('ldapQuotaAttribute')],
				[$this->equalTo('ldapEmailAttribute')],
				[$this->equalTo('ldapUserDisplayName')],
				[$this->equalTo('ldapUserDisplayName2')],
				[$this->equalTo('homeFolderNamingRule')],
				[$this->equalTo('ldapAttributesForUserSearch')]
			// uuidAttributes are a real member. the mock also has them
			)
			->willReturnOnConsecutiveCalls(
				'',
				null,
				'displayName',
				'',
				null,
				null
			);

		$attributes = $this->manager->getAttributes(true);

		$this->assertTrue(in_array('dn', $attributes));
		$this->assertFalse(in_array('mail', $attributes));
		$this->assertFalse(in_array('jpegphoto', $attributes));
		$this->assertFalse(in_array('thumbnailphoto', $attributes));
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
/*
		$this->access->expects($this->any())
			->method('fetchListOfUsers')
			->will($this->returnCallback(function($search, $a, $l, $o) {
				$users = array('gunslinger', 'newyorker', 'ladyofshadows');
				if(empty($search)) {
					$result = $users;
				} else {
					$result = array();
					foreach($users as $user) {
						if(stripos($user,  $search) !== false) {
							$result[] = $user;
						}
					}
				}
				if(!is_null($l) || !is_null($o)) {
					$result = array_slice($result, $o, $l);
				}
				return $result;
			}));*/

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
					case 'ldapEMailAttribute':
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

	//the deprecated getUsers() public api only uses the account table, tests removed

}
