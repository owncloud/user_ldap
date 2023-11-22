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
 * @copyright Copyright (c) 2018, ownCloud GmbH.
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

namespace OCA\User_LDAP;

use OC\ServerNotAvailableException;
use OCA\User_LDAP\Exceptions\DoesNotExistOnLDAPException;
use OCA\User_LDAP\User\Manager;
use OCA\User_LDAP\User\UserEntry;
use OCA\User_LDAP\Connection;
use OCP\IConfig;

/**
 * Class Test_User_Ldap_Direct
 *
 * @package OCA\User_LDAP
 * @group DB
 */
class User_LDAPTest extends \Test\TestCase {
	/**
	 * @var IConfig|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected $config;
	/**
	 * @var Manager|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected $manager;
	/**
	 * @var User_LDAP|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected $backend;

	protected function setUp(): void {
		parent::setUp();

		$this->config  = $this->createMock(IConfig::class);
		$this->manager = $this->createMock(Manager::class);
		$this->backend = new User_LDAP($this->config, $this->manager);

		\OC_User::clearBackends();
	}

	public function testGetRawUserEntries() {
		$expected = [
			[
				'dn' => ['uid=blab,dc=ex,dc=com'],
				'uid' => ['blab'],
			],
			[
				'dn' => ['uid=bleb,dc=ex,dc=com'],
				'uid' => ['bleb'],
			],
			[
				'dn' => ['uid=blib,dc=ex,dc=com'],
				'uid' => ['blib'],
			],
		];
		$this->manager->method('getLdapUsers')->willReturn($expected);
		$this->assertEquals($expected, $this->backend->getRawUserEntries());
	}

	public function testGetUserEntry() {
		$userEntry = $this->createMock(UserEntry::class);
		$this->manager->method('getCachedEntry')->willReturn($userEntry);
		$this->assertEquals($userEntry, $this->backend->getUserEntry('user1'));
	}

	public function testGetUserEntryMissing() {
		$this->manager->method('getCachedEntry')->willReturn(null);
		$this->assertFalse($this->backend->getUserEntry('user1'));
	}

	public function testGetUserEntryFromRaw() {
		$userEntry = $this->createMock(UserEntry::class);
		$this->manager->method('getFromEntry')->willReturn($userEntry);
		$this->assertEquals($userEntry, $this->backend->getUserEntryFromRaw(['dn' => ['uid=oo,dc=ex,dc=org'], 'uid' => ['oo']]));
	}

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

	public function testCheckPasswordDoesntExistDoesntLog() {
		$this->manager->expects($this->once())
			->method('getLDAPUserByLoginName')
			->with($this->equalTo('foo'))
			->willThrowException(new DoesNotExistOnLDAPException());
		$result = $this->backend->checkPassword('foo', 'secret');
		$this->manager->expects($this->never())->method('areCredentialsValid');
		$this->assertEquals(false, $result);
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
		$this->manager->expects($this->any())
			->method('getCachedEntry')
			->with($this->equalTo('563418fc-423b-1033-8d1c-ad5f418ee02e'))
			->will($this->returnValue($userEntry));

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

	public function testUserExistsCached() {
		$userEntry = $this->createMock(UserEntry::class);

		$this->manager->expects($this->once())
			->method('getCachedEntry')
			->with($this->equalTo('563418fc-423b-1033-8d1c-ad5f418ee02e'))
			->will($this->returnValue($userEntry));

		$result = $this->backend->userExists('563418fc-423b-1033-8d1c-ad5f418ee02e');
		$this->assertTrue($result);
	}

	public function testUserExistsNotInDB() {
		$this->manager->expects($this->once())
			->method('getCachedEntry')
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
			->method('getCachedEntry')
			->with($this->equalTo('563418fc-423b-1033-8d1c-ad5f418ee02e'))
			->will($this->returnValue(null));
		$this->manager->expects($this->once())
			->method('username2dn')
			->with($this->equalTo('563418fc-423b-1033-8d1c-ad5f418ee02e'))
			->will($this->returnValue('cn=foo,dc=foobar,dc=bar'));
		$e = new DoesNotExistOnLDAPException();
		$this->manager->expects($this->once())
			->method('getUserEntryByDn')
			->with($this->equalTo('cn=foo,dc=foobar,dc=bar'))
			->will($this->throwException($e));
		$this->manager->expects($this->once())
			->method('resolveMissingDN')
			->with($this->equalTo('cn=foo,dc=foobar,dc=bar'))
			->willReturn(false);

		$result = $this->backend->userExists('563418fc-423b-1033-8d1c-ad5f418ee02e');
		$this->assertFalse($result);
	}

	public function testUserExistsInDBBAndOnLDAP() {
		$this->manager->expects($this->once())
			->method('getCachedEntry')
			->with($this->equalTo('563418fc-423b-1033-8d1c-ad5f418ee02e'))
			->will($this->returnValue(null));
		$this->manager->expects($this->once())
			->method('username2dn')
			->with($this->equalTo('563418fc-423b-1033-8d1c-ad5f418ee02e'))
			->will($this->returnValue('cn=foo,dc=foobar,dc=bar'));
		$entry = $this->createMock(UserEntry::class);
		$this->manager->expects($this->once())
			->method('getUserEntryByDn')
			->with($this->equalTo('cn=foo,dc=foobar,dc=bar'))
			->will($this->returnValue($entry));

		$result = $this->backend->userExists('563418fc-423b-1033-8d1c-ad5f418ee02e');
		$this->assertTrue($result);
	}

	public function testGetHome() {
		$userEntry = $this->createMock(UserEntry::class);
		$userEntry->expects($this->once())
			->method('getHome')
			->will($this->returnValue('/relative/or/absolute path/'));
		$this->manager->expects($this->any())
			->method('getCachedEntry')
			->with($this->equalTo('563418fc-423b-1033-8d1c-ad5f418ee02e'))
			->will($this->returnValue($userEntry));

		$result = $this->backend->getHome('563418fc-423b-1033-8d1c-ad5f418ee02e');
		$this->assertEquals('/relative/or/absolute path/', $result);
	}

	public function testGetHomeNotCached() {
		$this->manager->expects($this->once())
			->method('getCachedEntry')
			->with($this->equalTo('563418fc-423b-1033-8d1c-ad5f418ee02e'))
			->will($this->returnValue(null));

		$this->assertFalse($this->backend->getHome('563418fc-423b-1033-8d1c-ad5f418ee02e'));
	}

	/**
	 * home folder naming rule enforcement is tested in UserEntryTest
	 */
	public function testGetHomeNoPath() {
		$userEntry = $this->createMock(UserEntry::class);
		$userEntry->expects($this->once())
			->method('getHome')
			->will($this->returnValue(null));

		$this->manager->expects($this->once())
			->method('getCachedEntry')
			->with($this->equalTo('563418fc-423b-1033-8d1c-ad5f418ee02e'))
			->will($this->returnValue($userEntry));

		$this->assertNull($this->backend->getHome('563418fc-423b-1033-8d1c-ad5f418ee02e'));
	}

	public function testGetDisplayName() {
		$userEntry = $this->createMock(UserEntry::class);
		$userEntry->expects($this->once())
			->method('getDisplayName')
			->will($this->returnValue('Foo'));

		$this->manager->expects($this->once())
			->method('getCachedEntry')
			->with($this->equalTo('563418fc-423b-1033-8d1c-ad5f418ee02e'))
			->will($this->returnValue($userEntry));

		$this->assertEquals('Foo', $this->backend->getDisplayName('563418fc-423b-1033-8d1c-ad5f418ee02e'));
	}
	public function testGetDisplayNameNotCached() {
		$this->manager->expects($this->once())
			->method('getCachedEntry')
			->with($this->equalTo('563418fc-423b-1033-8d1c-ad5f418ee02e'))
			->will($this->returnValue(null));

		$this->assertFalse($this->backend->getDisplayName('563418fc-423b-1033-8d1c-ad5f418ee02e'));
	}

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

	public function testCanChangeAvatarNotCached() {
		$this->manager->method('getCachedEntry')
			->willReturn(null);

		$this->assertFalse($this->backend->canChangeAvatar('usertest'));
	}

	public function testCanChangeAvatarNoLdapImage() {
		$userEntry = $this->createMock(UserEntry::class);
		$userEntry->method('getAvatarImage')
			->willReturn(null);

		$this->manager->method('getCachedEntry')
			->willReturn($userEntry);

		$this->assertTrue($this->backend->canChangeAvatar('usertest'));
	}

	public function testCanChangeAvatarImageSet() {
		$userEntry = $this->createMock(UserEntry::class);
		$userEntry->method('getAvatarImage')
			->willReturn('binaryDataForImageAsStringEncoded');

		$this->manager->method('getCachedEntry')
			->willReturn($userEntry);

		$this->assertFalse($this->backend->canChangeAvatar('usertest'));
	}

	public function testTestConnection() {
		$connection = $this->createMock(Connection::class);
		$connection->method('bind')->willReturn(true);

		$this->manager->method('getConnection')->willReturn($connection);
		$this->assertTrue($this->backend->testConnection());
	}

	public function testTestConnectionException() {
		$this->expectException(ServerNotAvailableException::class);

		$connection = $this->createMock(Connection::class);
		$connection->method('bind')
			->will($this->throwException(new ServerNotAvailableException('server died')));

		$this->manager->method('getConnection')->willReturn($connection);
		$this->backend->testConnection();
	}

	public function testClearConnectionCache() {
		$connection = $this->createMock(Connection::class);
		$connection->expects($this->once())->method('clearCache');

		$this->manager->method('getConnection')->willReturn($connection);

		$this->backend->clearConnectionCache();
	}
}
