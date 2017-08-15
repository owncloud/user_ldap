<?php
/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2017, ownCloud GmbH.
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

namespace OCA\User_LDAP\Tests;

use OCA\User_LDAP\User\Manager;
use OCA\User_LDAP\User\UserEntry;
use OCA\User_LDAP\User_LDAP;
use OCP\IConfig;

/**
 * Class Test_User_Ldap_Direct
 *
 * @package OCA\User_LDAP\Tests
 */
class User_LDAPTest extends \Test\TestCase {
	/**
	 * @var IConfig|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $config;
	/**
	 * @var Manager|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $manager;
	/**
	 * @var User_LDAP|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $backend;

	protected function setUp() {
		parent::setUp();

		$this->config  = $this->createMock(IConfig::class);
		$this->manager = $this->createMock(Manager::class);
		$this->backend = new User_LDAP($this->config, $this->manager);

		\OC_User::clearBackends();
	}
/*
	private function getAccessMock() {
		static $conMethods;
		static $accMethods;
		static $uMethods;

		if(is_null($conMethods) || is_null($accMethods)) {
			$conMethods = get_class_methods('\OCA\User_LDAP\Connection');
			$accMethods = get_class_methods('\OCA\User_LDAP\Access');
			unset($accMethods[array_search('getConnection', $accMethods)]);
			$uMethods   = get_class_methods('\OCA\User_LDAP\User\User');
			unset($uMethods[array_search('getUsername', $uMethods)]);
			unset($uMethods[array_search('getDN', $uMethods)]);
			unset($uMethods[array_search('__construct', $uMethods)]);
		}
		$lw  = $this->createMock('\OCA\User_LDAP\ILDAPWrapper');
		$connector = $this->getMockBuilder('\OCA\User_LDAP\Connection')
			->setMethods($conMethods)
			->setConstructorArgs([$lw, null, null])
			->getMock();

		$this->configMock = $this->createMock('\OCP\IConfig');

		$offlineUser = $this->getMockBuilder('\OCA\User_LDAP\User\OfflineUser')
			->disableOriginalConstructor()
			->getMock();

		$um = $this->getMockBuilder('\OCA\User_LDAP\User\Manager')
			->setMethods(['getDeletedUser'])
			->setConstructorArgs([
				$this->configMock,
				$this->createMock('\OCA\User_LDAP\FilesystemHelper'),
				$this->createMock('\OCP\ILogger'),
				$this->createMock('\OCP\IAvatarManager'),
				$this->createMock('\OCP\Image'),
				$this->createMock('\OCP\IDBConnection'),
				$this->createMock('\OCP\IUserManager')
			  ])
			->getMock();

		$um->expects($this->any())
			->method('getDeletedUser')
			->will($this->returnValue($offlineUser));

		$access = $this->getMockBuilder('\OCA\User_LDAP\Access')
			->setMethods($accMethods)
			->setConstructorArgs([$connector, $lw, $um])
			->getMock();

		$um->setLdapAccess($access);

		return $access;
	}

	private function prepareMockForUserExists(&$access) {
		$access->expects($this->any())
			   ->method('username2dn')
			   ->will($this->returnCallback(function($uid) {
					switch ($uid) {
						case 'gunslinger':
							return 'dnOfRoland,dc=test';
							break;
						case 'formerUser':
							return 'dnOfFormerUser,dc=test';
							break;
						case 'newyorker':
							return 'dnOfNewYorker,dc=test';
							break;
						case 'ladyofshadows':
							return 'dnOfLadyOfShadows,dc=test';
							break;
						default:
							return false;
					}
			   }));

		$access->expects($this->any())
			->method('dn2username')
			->will($this->returnCallback(function($dn){
				switch($dn) {
					case 'dnOfRoland,dc=test':
						return 'gunslinger';
					case 'dnOfFormerUser,dc=test':
						return 'formerUser';
					case 'dnOfNewYorker,dc=test':
						return 'newyorker';
					case 'dnOfLadyOfShadows,dc=test':
						return 'ladyofshadows';
					default:
						return false;
				}
		}));

		$access->expects($this->any())
			->method('stringResemblesDN')
			->will($this->returnCallback(function($dn){
				return in_array($dn, ['dnOfRoland,dc=test', 'dnOfFormerUser,dc=test', 'dnOfNewYorker,dc=test', 'dnOfLadyOfShadows,dc=test'], true);
		}));

		$access->expects($this->any())
			->method('fetchUsersByLoginName')
			->will($this->returnCallback(function($uid) {
				switch ($uid) {
					case 'gunslinger':
						return array(array('dn' => ['dnOfRoland,dc=test']));
					case 'formerUser':
						return array(array('dn' => ['dnOfFormerUser,dc=test']));
					case 'newyorker':
						return array(array('dn' => ['dnOfNewYorker,dc=test']));
					case 'ladyofshadows':
						return array(array('dn' => ['dnOfLadyOfShadows,dc=test']));
					default:
						return array();
				}
			}));

		$access->expects($this->any())
			   ->method('areCredentialsValid')
			   ->will($this->returnCallback(function($dn, $pwd) {
					if($pwd === 'dt19') {
						return true;
					}
					return false;
			   }));
	}
*/
	/**
	 * Prepares the Access mock for checkPassword tests
	 * @param \OCA\User_LDAP\Access $access mock
	 * @param bool $noDisplayName
	 * @return void
	 */
	/* TODO move parts of this to AccessTest?
	private function prepareAccessForCheckPassword(&$access, $noDisplayName = false) {
		$access->connection->expects($this->any())
			   ->method('__get')
			   ->will($this->returnCallback(function($name) {
					if($name === 'ldapLoginFilter') {
						return '%uid';
					}
					return null;
			   }));

		$access->expects($this->any())
			   ->method('fetchListOfUsers')
			   ->will($this->returnCallback(function($filter) {
					if($filter === 'roland') {
						return array(array('dn' => ['dnOfRoland,dc=test']));
					}
					return array();
			   }));

		$access->expects($this->any())
			->method('fetchUsersByLoginName')
			->will($this->returnCallback(function($uid) {
				if($uid === 'roland') {
					return array(array('dn' => ['dnOfRoland,dc=test']));
				}
				return array();
			}));

		$access->expects($this->any())
			->method('readAttribute')
			->with('dnOfRoland,dc=test', $this->callback(function($attr){
					return in_array($attr, [null, '', 'jpegPhoto', 'thumbnailPhoto'], true);
				}), $this->anything())
			->will($this->returnCallback(function($attr){
					if ($attr === 'jpegPhoto' || $attr === 'thumbnailPhoto') {
						return ['sdfsdljsdlkfjsadlkjfsdewuyriuweyiuyeiwuydjkfsh'];
					} else {
						return [];
					}
				}));

		$retVal = 'gunslinger';
		if($noDisplayName === true) {
			$retVal = false;
		} else {
			$access->expects($this->any())
				   ->method('username2dn')
				   ->with($this->equalTo($retVal))
				   ->will($this->returnValue('dnOfRoland,dc=test'));
		}
		$access->expects($this->any())
			   ->method('dn2username')
			   ->with($this->equalTo('dnOfRoland,dc=test'))
			   ->will($this->returnValue($retVal));

		$access->expects($this->any())
			   ->method('stringResemblesDN')
			   ->with($this->equalTo('dnOfRoland,dc=test'))
			   ->will($this->returnValue(true));

		$access->expects($this->any())
			   ->method('areCredentialsValid')
			   ->will($this->returnCallback(function($dn, $pwd) {
					if($pwd === 'dt19') {
						return true;
					}
					return false;
			   }));
	}
	*/

	public function testCheckPasswordUidReturn() {
		$userEntry = $this->createMock(UserEntry::class);
		$userEntry->expects($this->any())
			->method('getDN')
			->will($this->returnValue('cn=foo,dc=foobar,dc=bar'));
		$userEntry->expects($this->any())
			->method('getOwnCloudUID')
			->will($this->returnValue('563418fc-423b-1033-8d1c-ad5f418ee02e'));

		$this->manager->expects($this->once())
			->method('getLDAPUserByLoginName')
			->with($this->equalTo('foo'))
			->will($this->returnValue($userEntry));
		$this->manager->expects($this->once())
			->method('areCredentialsValid')
			->will($this->returnValue(true));

		$result = $this->backend->checkPassword('foo', 'secret');
		$this->assertEquals('563418fc-423b-1033-8d1c-ad5f418ee02e', $result);
	}

	public function testCheckPasswordWrongPassword() {
		$userEntry = $this->createMock(UserEntry::class);
		$userEntry->expects($this->any())
			->method('getDN')
			->will($this->returnValue('cn=foo,dc=foobar,dc=bar'));
		$userEntry->expects($this->never())
			->method('getOwnCloudUID');

		$this->manager->expects($this->once())
			->method('getLDAPUserByLoginName')
			->with($this->equalTo('foo'))
			->will($this->returnValue($userEntry));
		$this->manager->expects($this->once())
			->method('areCredentialsValid')
			->will($this->returnValue(false));

		$result = $this->backend->checkPassword('foo', 'wrong');
		$this->assertFalse($result);
	}

	public function testCheckPasswordWrongUser() {
		$this->manager->expects($this->once())
			->method('getLDAPUserByLoginName')
			->with($this->equalTo('mallory'))
			->will($this->throwException(new \Exception()));
		$this->manager->expects($this->never())
			->method('areCredentialsValid');

		$result = $this->backend->checkPassword('mallory', 'evil');
		$this->assertFalse($result);
	}

	/* TODO naming makes no sense: username2dn resolves the distinguished name, dn != displayname
	   TODO covered with testCheckPasswordWrongUser and should be moved to UserEntry?
	public function testCheckPasswordNoDisplayName() {
		$access = $this->getAccessMock();

		$this->prepareAccessForCheckPassword($access, true);
		$access->expects($this->once())
			->method('username2dn')
			->will($this->returnValue(false));

		$backend = new UserLDAP($access, $this->createMock('\OCP\IConfig'));
		\OC_User::useBackend($backend);

		$result = $backend->checkPassword('roland', 'dt19');
		$this->assertFalse($result);
	}
	*/

	public function testCheckPasswordPublicAPI() {
		$userEntry = $this->createMock(UserEntry::class);
		$userEntry->expects($this->any())
			->method('getDN')
			->will($this->returnValue('cn=foo,dc=foobar,dc=bar'));
		$userEntry->expects($this->any())
			->method('getOwnCloudUID')
			->will($this->returnValue('563418fc-423b-1033-8d1c-ad5f418ee02e'));

		$this->manager->expects($this->once())
			->method('getLDAPUserByLoginName')
			->with($this->equalTo('foo'))
			->will($this->returnValue($userEntry));
		$this->manager->expects($this->once())
			->method('areCredentialsValid')
			->will($this->returnValue(true));

		\OC_User::useBackend($this->backend);

		$result = \OCP\User::checkPassword('foo', 'secret');
		$this->assertEquals('563418fc-423b-1033-8d1c-ad5f418ee02e', $result);
	}

	public function testCheckPasswordPublicAPIWrongPassword() {
		$userEntry = $this->createMock(UserEntry::class);
		$userEntry->expects($this->any())
			->method('getDN')
			->will($this->returnValue('cn=foo,dc=foobar,dc=bar'));
		$userEntry->expects($this->never())
			->method('getOwnCloudUID');

		$this->manager->expects($this->once())
			->method('getLDAPUserByLoginName')
			->with($this->equalTo('foo'))
			->will($this->returnValue($userEntry));
		$this->manager->expects($this->once())
			->method('areCredentialsValid')
			->will($this->returnValue(false));

		\OC_User::useBackend($this->backend);

		$result = \OCP\User::checkPassword('foo', 'wrong');
		$this->assertFalse($result);
	}

	public function testCheckPasswordPublicAPIWrongUser() {
		$this->manager->expects($this->once())
			->method('getLDAPUserByLoginName')
			->with($this->equalTo('mallory'))
			->will($this->throwException(new \Exception()));
		$this->manager->expects($this->never())
			->method('areCredentialsValid');

		\OC_User::useBackend($this->backend);

		$result = \OCP\User::checkPassword('mallory', 'evil');
		$this->assertFalse($result);
	}

	/* FIXME deleteUser always returns true, test needs to move to AbstractMappingTest
	public function testDeleteUserSuccess() {
		$access = $this->getAccessMock();
		$mapping = $this->getMockBuilder('\OCA\User_LDAP\Mapping\UserMapping')
			->disableOriginalConstructor()
			->getMock();
		$mapping->expects($this->once())
			->method('unmap')
			->will($this->returnValue(true));
		$access->expects($this->exactly(2))
			->method('getUserMapper')
			->will($this->returnValue($mapping));

		$config = $this->createMock('\OCP\IConfig');

		$access->connection->expects($this->any())
			->method('getConnectionResource')
			->will($this->returnCallback(function() {
				return true;
			}));

		$backend = new UserLDAP($access, $config);

		$result = $backend->deleteUser('jeremy');
		$this->assertTrue($result);

		$home = $backend->getHome('jeremy');
		$this->assertFalse($home);
	}

	public function testDeleteUser() {
		$access = $this->getAccessMock();
		$mapping = $this->getMockBuilder('\OCA\User_LDAP\Mapping\UserMapping')
			->disableOriginalConstructor()
			->getMock();
		$mapping->expects($this->once())
			->method('unmap')
			->will($this->returnValue(true));
		$access->expects($this->once())
			->method('getUserMapper')
			->will($this->returnValue($mapping));

		$backend = new UserLDAP($access, $this->createMock('\OCP\IConfig'));

		$result = $backend->deleteUser('gunslinger');
		$this->assertTrue($result);
	}
	*/

	/**
	 * Prepares the Access mock for getUsers tests
	 * @param \OCA\User_LDAP\Access $access mock
	 * @return void
	 */
	/* FIXME move getUsers test to user Manager
	private function prepareAccessForGetUsers(&$access) {
		$access->expects($this->any())
			   ->method('escapeFilterPart')
			   ->will($this->returnCallback(function($search) {
				   return $search;
			   }));

		$access->expects($this->any())
			   ->method('getFilterPartForUserSearch')
			   ->will($this->returnCallback(function($search) {
					return $search;
			   }));

		$access->expects($this->any())
			   ->method('combineFilterWithAnd')
			   ->will($this->returnCallback(function($param) {
					return $param[2];
			   }));

		$access->expects($this->any())
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
			   }));

		$access->expects($this->any())
			->method('fetchUsersByLoginName')
			->will($this->returnCallback(function($uid) {
				switch ($uid) {
					case 'roland':
						return array(array('dn' => ['dnOfRoland,dc=test']));
					case 'newyorker':
						return array(array('dn' => ['dnOfNewYorker,dc=test']));
					case 'ladyofshadows':
						return array(array('dn' => ['dnOfLadyOfShadows,dc=test']));
					default:
						return array();
				}
			}));

		$access->expects($this->any())
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

		$access->expects($this->any())
			   ->method('ownCloudUserNames')
			   ->will($this->returnArgument(0));

		$access->expects($this->any())
			->method('areCredentialsValid')
			->will($this->returnValue(true));
	}

	public function testGetUsersNoParam() {
		$access = $this->getAccessMock();
		$this->prepareAccessForGetUsers($access);
		$backend = new UserLDAP($access, $this->createMock('\OCP\IConfig'));

		$result = $backend->getUsers();
		$this->assertEquals(3, count($result));
	}

	public function testGetUsersLimitOffset() {
		$access = $this->getAccessMock();
		$this->prepareAccessForGetUsers($access);
		$backend = new UserLDAP($access, $this->createMock('\OCP\IConfig'));

		$result = $backend->getUsers('', 1, 2);
		$this->assertEquals(1, count($result));
	}

	public function testGetUsersLimitOffset2() {
		$access = $this->getAccessMock();
		$this->prepareAccessForGetUsers($access);
		$backend = new UserLDAP($access, $this->createMock('\OCP\IConfig'));

		$result = $backend->getUsers('', 2, 1);
		$this->assertEquals(2, count($result));
	}

	public function testGetUsersSearchWithResult() {
		$access = $this->getAccessMock();
		$this->prepareAccessForGetUsers($access);
		$backend = new UserLDAP($access, $this->createMock('\OCP\IConfig'));

		$result = $backend->getUsers('yo');
		$this->assertEquals(2, count($result));
	}

	public function testGetUsersSearchEmptyResult() {
		$access = $this->getAccessMock();
		$this->prepareAccessForGetUsers($access);
		$backend = new UserLDAP($access, $this->createMock('\OCP\IConfig'));

		$result = $backend->getUsers('nix');
		$this->assertEquals(0, count($result));
	}

	public function testGetUsersViaAPINoParam() {
		$access = $this->getAccessMock();
		$this->prepareAccessForGetUsers($access);
		$backend = new UserLDAP($access, $this->createMock('\OCP\IConfig'));
		\OC_User::useBackend($backend);

		$userSession = \OC::$server->getUserSession();
		$users = array('roland', 'newyorker', 'ladyofshadows');
		foreach($users as $user) {
			$userSession->login($user, 'secret');
			@$userSession->logout();
		}

		$result = \OCP\User::getUsers();
		$this->assertEquals(3, count($result), print_r($result, true));
	}

	public function testGetUsersViaAPILimitOffset() {
		$access = $this->getAccessMock();
		$this->prepareAccessForGetUsers($access);
		$backend = new UserLDAP($access, $this->createMock('\OCP\IConfig'));
		\OC_User::useBackend($backend);

		$userSession = \OC::$server->getUserSession();
		$users = array('roland', 'newyorker', 'ladyofshadows');
		foreach($users as $user) {
			$userSession->login($user, 'secret');
			@$userSession->logout();
		}

		$result = \OCP\User::getUsers('', 1, 2);
		$this->assertEquals(1, count($result));
	}

	public function testGetUsersViaAPILimitOffset2() {
		$access = $this->getAccessMock();
		$this->prepareAccessForGetUsers($access);
		$backend = new UserLDAP($access, $this->createMock('\OCP\IConfig'));
		\OC_User::useBackend($backend);

		$userSession = \OC::$server->getUserSession();
		$users = array('roland', 'newyorker', 'ladyofshadows');
		foreach($users as $user) {
			$userSession->login($user, 'secret');
			@$userSession->logout();
		}

		$result = \OCP\User::getUsers('', 2, 1);
		$this->assertEquals(2, count($result));
	}

	public function testGetUsersViaAPISearchWithResult() {
		$access = $this->getAccessMock();
		$this->prepareAccessForGetUsers($access);
		$backend = new UserLDAP($access, $this->createMock('\OCP\IConfig'));
		\OC_User::useBackend($backend);

		$userSession = \OC::$server->getUserSession();
		$users = array('roland', 'newyorker', 'ladyofshadows');
		foreach($users as $user) {
			$userSession->login($user, 'secret');
			@$userSession->logout();
		}

		$result = \OCP\User::getUsers('yo');
		$this->assertEquals(2, count($result));
	}

	public function testGetUsersViaAPISearchEmptyResult() {
		$access = $this->getAccessMock();
		$this->prepareAccessForGetUsers($access);
		$backend = new UserLDAP($access, $this->createMock('\OCP\IConfig'));
		\OC_User::useBackend($backend);

		$result = \OCP\User::getUsers('nix');
		$this->assertEquals(0, count($result));
	}
	*/

	public function testUserExistsCached() {
		$userEntry = $this->createMock(UserEntry::class);

		$this->manager->expects($this->once())
			->method('getByOwnCloudUID')
			->with($this->equalTo('563418fc-423b-1033-8d1c-ad5f418ee02e'))
			->will($this->returnValue($userEntry));

		$result = $this->backend->userExists('563418fc-423b-1033-8d1c-ad5f418ee02e');
		$this->assertTrue($result);
	}

	public function testUserExistsNotInDB() {
		$this->manager->expects($this->once())
			->method('getByOwnCloudUID')
			->with($this->equalTo('563418fc-423b-1033-8d1c-ad5f418ee02e'))
			->will($this->returnValue(null));
		$this->manager->expects($this->once())
			->method('username2dn')
			->with($this->equalTo('563418fc-423b-1033-8d1c-ad5f418ee02e'))
			->will($this->returnValue(false));

		$result = $this->backend->userExists('563418fc-423b-1033-8d1c-ad5f418ee02e');
		$this->assertFalse($result);
	}

	public function testUserExistsInDBButNotOnLDAP() {
		$this->manager->expects($this->once())
			->method('getByOwnCloudUID')
			->with($this->equalTo('563418fc-423b-1033-8d1c-ad5f418ee02e'))
			->will($this->returnValue(null));
		$this->manager->expects($this->once())
			->method('username2dn')
			->with($this->equalTo('563418fc-423b-1033-8d1c-ad5f418ee02e'))
			->will($this->returnValue('cn=foo,dc=foobar,dc=bar'));
		$this->manager->expects($this->once())
			->method('dnExistsOnLDAP')
			->with($this->equalTo('cn=foo,dc=foobar,dc=bar'))
			->will($this->returnValue(false));

		$result = $this->backend->userExists('563418fc-423b-1033-8d1c-ad5f418ee02e');
		$this->assertFalse($result);
	}

	public function testUserExistsInDBBAndOnLDAP() {
		$this->manager->expects($this->once())
			->method('getByOwnCloudUID')
			->with($this->equalTo('563418fc-423b-1033-8d1c-ad5f418ee02e'))
			->will($this->returnValue(null));
		$this->manager->expects($this->once())
			->method('username2dn')
			->with($this->equalTo('563418fc-423b-1033-8d1c-ad5f418ee02e'))
			->will($this->returnValue('cn=foo,dc=foobar,dc=bar'));
		$this->manager->expects($this->once())
			->method('dnExistsOnLDAP')
			->with($this->equalTo('cn=foo,dc=foobar,dc=bar'))
			->will($this->returnValue(true));

		$result = $this->backend->userExists('563418fc-423b-1033-8d1c-ad5f418ee02e');
		$this->assertTrue($result);
	}

	//the deprecated userExists() public api only uses the account table, tests removed

	public function testGetHome() {
		$userEntry = $this->createMock(UserEntry::class);
		$userEntry->expects($this->once())
			->method('getHome')
			->will($this->returnValue('/relative/or/absolute path/'));

		$this->manager->expects($this->once())
			->method('getByOwnCloudUID')
			->with($this->equalTo('563418fc-423b-1033-8d1c-ad5f418ee02e'))
			->will($this->returnValue($userEntry));

		$result = $this->backend->getHome('563418fc-423b-1033-8d1c-ad5f418ee02e');
		$this->assertEquals('/relative/or/absolute path/', $result);
	}
	public function testGetHomeNotCached() {
		$this->manager->expects($this->once())
			->method('getByOwnCloudUID')
			->with($this->equalTo('563418fc-423b-1033-8d1c-ad5f418ee02e'))
			->will($this->returnValue(null));

		$this->assertFalse($this->backend->getHome('563418fc-423b-1033-8d1c-ad5f418ee02e'));
	}

	/* FIXME test in UserEntryTest
	public function testGetHomeAbsolutePath() {
		$access = $this->getAccessMock();
		$config = $this->createMock('\OCP\IConfig');
		$backend = new UserLDAP($access, $config);
		$this->prepareMockForUserExists($access);

		$access->connection->expects($this->any())
			->method('__get')
			->will($this->returnCallback(function($name) {
				if($name === 'homeFolderNamingRule') {
					return 'attr:testAttribute';
				}
				return null;
			}));

		$access->expects($this->any())
			->method('readAttribute')
			->will($this->returnCallback(function($dn, $attr) {
				switch ($dn) {
					case 'dnOfRoland,dc=test':
						if($attr === 'testAttribute') {
							return array('/tmp/rolandshome/');
						}
						return array();
						break;
					default:
						return false;
				}
			}));

		//absolut path
		$result = $backend->getHome('gunslinger');
		$this->assertEquals('/tmp/rolandshome/', $result);
	}

	public function testGetHomeRelative() {
		$access = $this->getAccessMock();
		$config = $this->createMock('\OCP\IConfig');
		$backend = new UserLDAP($access, $config);
		$this->prepareMockForUserExists($access);

		$dataDir = \OC::$server->getConfig()->getSystemValue(
			'datadirectory', \OC::$SERVERROOT.'/data');

		$this->configMock->expects($this->once())
			->method('getSystemValue')
			->will($this->returnValue($dataDir));

		$access->connection->expects($this->any())
			->method('__get')
			->will($this->returnCallback(function($name) {
				if($name === 'homeFolderNamingRule') {
					return 'attr:testAttribute';
				}
				return null;
			}));

		$access->expects($this->any())
			->method('readAttribute')
			->will($this->returnCallback(function($dn, $attr) {
				switch ($dn) {
					case 'dnOfLadyOfShadows,dc=test':
						if($attr === 'testAttribute') {
							return array('susannah/');
						}
						return array();
						break;
					default:
						return false;
				}
			}));

		$result = $backend->getHome('ladyofshadows');
		$this->assertEquals($dataDir.'/susannah/', $result);
	}
*/

	/**
	 * home folder naming rule enforcement is tested in UserEntryTest
	 */
	public function testGetHomeNoPath() {
		$userEntry = $this->createMock(UserEntry::class);
		$userEntry->expects($this->once())
			->method('getHome')
			->will($this->returnValue(null));

		$this->manager->expects($this->once())
			->method('getByOwnCloudUID')
			->with($this->equalTo('563418fc-423b-1033-8d1c-ad5f418ee02e'))
			->will($this->returnValue($userEntry));

		$this->assertNull($this->backend->getHome('563418fc-423b-1033-8d1c-ad5f418ee02e'));
	}
	/*
	private function prepareAccessForGetDisplayName(&$access) {
		$access->connection->expects($this->any())
			   ->method('__get')
			   ->will($this->returnCallback(function($name) {
					if($name === 'ldapUserDisplayName') {
						return 'displayname';
					}
					return null;
			   }));

		$access->expects($this->any())
			   ->method('readAttribute')
			   ->will($this->returnCallback(function($dn, $attr) {
					switch ($dn) {
						case 'dnOfRoland,dc=test':
							if($attr === 'displayname') {
								return array('Roland Deschain');
							}
							return array();
							break;

						default:
							return false;
				   }
			   }));

		$userMapper = $this->getMockBuilder('\OCA\User_LDAP\Mapping\UserMapping')
			->disableOriginalConstructor()
			->getMock();

		$access->expects($this->any())
			->method('getUserMapper')
			->will($this->returnValue($userMapper));
	}
	*/

	public function testGetDisplayName() {
		$userEntry = $this->createMock(UserEntry::class);
		$userEntry->expects($this->once())
			->method('getDisplayName')
			->will($this->returnValue('Foo'));

		$this->manager->expects($this->once())
			->method('getByOwnCloudUID')
			->with($this->equalTo('563418fc-423b-1033-8d1c-ad5f418ee02e'))
			->will($this->returnValue($userEntry));

		$this->assertEquals('Foo', $this->backend->getDisplayName('563418fc-423b-1033-8d1c-ad5f418ee02e'));
	}
	public function testGetDisplayNameNotCached() {
		$this->manager->expects($this->once())
			->method('getByOwnCloudUID')
			->with($this->equalTo('563418fc-423b-1033-8d1c-ad5f418ee02e'))
			->will($this->returnValue(null));

		$this->assertFalse($this->backend->getDisplayName('563418fc-423b-1033-8d1c-ad5f418ee02e'));
	}

	//the deprecated getDisplayName() public api only uses the account table, tests removed

	//no test for getDisplayNames, because it just invokes getUsers and
	//getDisplayName

	public function testCountUsers() {
		$this->manager->expects($this->once())
			->method('getFilterForUserCount')
			->will($this->returnValue('(objectclass=inetorgperson)'));
		$this->manager->expects($this->once())
			->method('countUsers')
			->with($this->equalTo('(objectclass=inetorgperson)'))
			->will($this->returnValue(123));

		$this->assertEquals(123, $this->backend->countUsers());
	}

	public function testCountUsersFailing() {
		$this->manager->expects($this->once())
			->method('getFilterForUserCount')
			->will($this->returnValue('(objectclass=inetorgperson)'));
		$this->manager->expects($this->once())
			->method('countUsers')
			->with($this->equalTo('(objectclass=inetorgperson)'))
			->will($this->returnValue(false));

		$this->assertFalse($this->backend->countUsers());
	}
}
