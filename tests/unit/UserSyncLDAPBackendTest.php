<?php
/**
 * @copyright Copyright (c) 2023, ownCloud GmbH.
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

use OCA\User_LDAP\User_Proxy;
use OCA\User_LDAP\UserSyncLDAPBackend;
use OCA\User_LDAP\User\UserEntry;
use OCP\Sync\User\SyncingUser;
use OCP\Sync\User\SyncBackendUserFailedException;
use OCP\Sync\User\SyncBackendBrokenException;
use OCA\User_LDAP\Exceptions\BindFailedException;

/**
 * @package OCA\User_LDAP
 */
class UserSyncLDAPBackendTest extends \Test\TestCase {
	/** @var User_Proxy */
	private $userProxy;
	/** @var UserSyncLDAPBackend */
	private $backend;

	protected function setUp(): void {
		$this->userProxy = $this->createMock(User_Proxy::class);

		$this->backend = new UserSyncLDAPBackend($this->userProxy);
	}

	public function testResetPointer() {
		$this->assertNull($this->backend->resetPointer());
	}

	public function testResetPointerCheckData() {
		$this->backend->resetPointer();
		$this->assertSame(0, $this->backend->getPointer());
		$this->assertEquals(['min' => 0, 'max' => 0, 'last' => false], $this->backend->getCachedUserData());
	}

	private function createUserEntryMock($uid, $displayname, $quota, $email, $home, $terms) {
		$userEntry = $this->createMock(UserEntry::class);
		$userEntry->method('getOwnCloudUID')->willReturn($uid);
		$userEntry->method('getDisplayName')->willReturn($displayname);
		$userEntry->method('getQuota')->willReturn($quota);
		$userEntry->method('getEMailAddress')->willReturn($email);
		$userEntry->method('getHome')->willReturn($home);
		$userEntry->method('getSearchTerms')->willReturn($terms);
		return $userEntry;
	}

	public function testGetNextUser() {
		$userEntry1 = $this->createUserEntryMock('zombie4000', 'Supa Zombie', '20GB', 'zombie4000@ex.org', '/homes/zombie4000', []);
		$userEntry2 = $this->createUserEntryMock('zombie5000', 'Blob Rando', '30GB', 'zombie5000@ex.org', '/homes/zombie5000', []);
		$userEntry3 = $this->createUserEntryMock('mummy4000', 'Kamom', '2GB', 'mummy4000@ex2.org', '/homes/mummy4000', []);

		$syncingUser1 = new SyncingUser('zombie4000');
		$syncingUser1->setDisplayName('Supa Zombie');
		$syncingUser1->setQuota('20GB');
		$syncingUser1->setEmail('zombie4000@ex.org');
		$syncingUser1->setHome('/homes/zombie4000');
		$syncingUser1->setSearchTerms([]);

		$syncingUser2 = new SyncingUser('zombie5000');
		$syncingUser2->setDisplayName('Blob Rando');
		$syncingUser2->setQuota('30GB');
		$syncingUser2->setEmail('zombie5000@ex.org');
		$syncingUser2->setHome('/homes/zombie5000');
		$syncingUser2->setSearchTerms([]);

		$syncingUser3 = new SyncingUser('mummy4000');
		$syncingUser3->setDisplayName('Kamom');
		$syncingUser3->setQuota('2GB');
		$syncingUser3->setEmail('mummy4000@ex2.org');
		$syncingUser3->setHome('/homes/mummy4000');
		$syncingUser3->setSearchTerms([]);

		$this->userProxy->expects($this->once())
			->method('testConnection')
			->willReturn(true);
		$this->userProxy->expects($this->exactly(2))
			->method('getRawUsersEntriesWithPrefix')
			->will($this->onConsecutiveCalls(
				[
					[
						'prefix' => '',
						'entry' => [
							'dn' => ['uid=zombie4000,ou=zombies,dc=owncloud,dc=com'],
							'uid' => ['zombie4000'],
							'mail' => ['zombie4000@ex.org'],
						]
					],
					[
						'prefix' => '',
						'entry' => [
							'dn' => ['uid=zombie5000,ou=zombies,dc=owncloud,dc=com'],
							'uid' => ['zombie5000'],
							'mail' => ['zombie5000@ex.org'],
						]
					],
					[
						'prefix' => 's02',
						'entry' => [
							'dn' => ['uid=mummy4000,ou=mummies,dc=owncloud2,dc=com'],
							'uid' => ['mummy4000'],
							'mail' => ['mummy4000@ex2.org'],
						]
					],
				],
				[]
			));
		$this->userProxy->expects($this->exactly(3))
			->method('getUserEntryFromRawWithPrefix')
			->withConsecutive(
				['', $this->equalTo(['dn' => ['uid=zombie4000,ou=zombies,dc=owncloud,dc=com'], 'uid' => ['zombie4000'], 'mail' => ['zombie4000@ex.org']])],
				['', $this->equalTo(['dn' => ['uid=zombie5000,ou=zombies,dc=owncloud,dc=com'], 'uid' => ['zombie5000'], 'mail' => ['zombie5000@ex.org']])],
				['s02', $this->equalTo(['dn' => ['uid=mummy4000,ou=mummies,dc=owncloud2,dc=com'], 'uid' => ['mummy4000'], 'mail' => ['mummy4000@ex2.org']])],
			)->will($this->onConsecutiveCalls($userEntry1, $userEntry2, $userEntry3));

		$this->assertEquals($syncingUser1, $this->backend->getNextUser());
		$this->assertEquals($syncingUser2, $this->backend->getNextUser());
		$this->assertEquals($syncingUser3, $this->backend->getNextUser());
		$this->assertNull($this->backend->getNextUser());
	}

	public function testGetNextUser2() {
		$userEntry1 = $this->createUserEntryMock('zombie4000', 'Supa Zombie', '20GB', 'zombie4000@ex.org', '/homes/zombie4000', []);
		$userEntry2 = $this->createUserEntryMock('zombie5000', 'Blob Rando', '30GB', 'zombie5000@ex.org', '/homes/zombie5000', []);
		$userEntry3 = $this->createUserEntryMock('mummy4000', 'Kamom', '2GB', 'mummy4000@ex2.org', '/homes/mummy4000', []);

		$syncingUser1 = new SyncingUser('zombie4000');
		$syncingUser1->setDisplayName('Supa Zombie');
		$syncingUser1->setQuota('20GB');
		$syncingUser1->setEmail('zombie4000@ex.org');
		$syncingUser1->setHome('/homes/zombie4000');
		$syncingUser1->setSearchTerms([]);

		$syncingUser2 = new SyncingUser('zombie5000');
		$syncingUser2->setDisplayName('Blob Rando');
		$syncingUser2->setQuota('30GB');
		$syncingUser2->setEmail('zombie5000@ex.org');
		$syncingUser2->setHome('/homes/zombie5000');
		$syncingUser2->setSearchTerms([]);

		$syncingUser3 = new SyncingUser('mummy4000');
		$syncingUser3->setDisplayName('Kamom');
		$syncingUser3->setQuota('2GB');
		$syncingUser3->setEmail('mummy4000@ex2.org');
		$syncingUser3->setHome('/homes/mummy4000');
		$syncingUser3->setSearchTerms([]);

		$this->userProxy->expects($this->once())
			->method('testConnection')
			->willReturn(true);
		$this->userProxy->expects($this->exactly(3))
			->method('getRawUsersEntriesWithPrefix')
			->will($this->onConsecutiveCalls(
				[
					[
						'prefix' => '',
						'entry' => [
							'dn' => ['uid=zombie4000,ou=zombies,dc=owncloud,dc=com'],
							'uid' => ['zombie4000'],
							'mail' => ['zombie4000@ex.org'],
						]
					],
					[
						'prefix' => 's02',
						'entry' => [
							'dn' => ['uid=mummy4000,ou=mummies,dc=owncloud2,dc=com'],
							'uid' => ['mummy4000'],
							'mail' => ['mummy4000@ex2.org'],
						]
					],
				],
				[
					[
						'prefix' => '',
						'entry' => [
							'dn' => ['uid=zombie5000,ou=zombies,dc=owncloud,dc=com'],
							'uid' => ['zombie5000'],
							'mail' => ['zombie5000@ex.org'],
						]
					],
				],
				[]
			));
		$this->userProxy->expects($this->exactly(3))
			->method('getUserEntryFromRawWithPrefix')
			->withConsecutive(
				['', $this->equalTo(['dn' => ['uid=zombie4000,ou=zombies,dc=owncloud,dc=com'], 'uid' => ['zombie4000'], 'mail' => ['zombie4000@ex.org']])],
				['s02', $this->equalTo(['dn' => ['uid=mummy4000,ou=mummies,dc=owncloud2,dc=com'], 'uid' => ['mummy4000'], 'mail' => ['mummy4000@ex2.org']])],
				['', $this->equalTo(['dn' => ['uid=zombie5000,ou=zombies,dc=owncloud,dc=com'], 'uid' => ['zombie5000'], 'mail' => ['zombie5000@ex.org']])],
			)->will($this->onConsecutiveCalls($userEntry1, $userEntry3, $userEntry2));

		$this->assertEquals($syncingUser1, $this->backend->getNextUser());
		$this->assertEquals($syncingUser3, $this->backend->getNextUser());
		$this->assertEquals($syncingUser2, $this->backend->getNextUser());
		$this->assertNull($this->backend->getNextUser());
	}

	public function testGetNextUserBackendException() {
		$this->expectException(SyncBackendBrokenException::class);

		$this->userProxy->expects($this->once())
			->method('testConnection')
			->will($this->throwException(new BindFailedException('wrong password')));
		
		$this->backend->getNextUser();
	}

	public function testGetNextUserUserFailedException() {
		$userEntry1 = $this->createUserEntryMock('zombie4000', 'Supa Zombie', '20GB', 'zombie4000@ex.org', '/homes/zombie4000', []);
		$userEntry2 = $this->createUserEntryMock('zombie5000', 'Blob Rando', '30GB', 'zombie5000@ex.org', '/homes/zombie5000', []);
		$userEntry3 = $this->createUserEntryMock('mummy4000', 'Kamom', '2GB', 'mummy4000@ex2.org', '/homes/mummy4000', []);

		$syncingUser1 = new SyncingUser('zombie4000');
		$syncingUser1->setDisplayName('Supa Zombie');
		$syncingUser1->setQuota('20GB');
		$syncingUser1->setEmail('zombie4000@ex.org');
		$syncingUser1->setHome('/homes/zombie4000');
		$syncingUser1->setSearchTerms([]);

		$syncingUser2 = new SyncingUser('zombie5000');
		$syncingUser2->setDisplayName('Blob Rando');
		$syncingUser2->setQuota('30GB');
		$syncingUser2->setEmail('zombie5000@ex.org');
		$syncingUser2->setHome('/homes/zombie5000');
		$syncingUser2->setSearchTerms([]);

		$syncingUser3 = new SyncingUser('mummy4000');
		$syncingUser3->setDisplayName('Kamom');
		$syncingUser3->setQuota('2GB');
		$syncingUser3->setEmail('mummy4000@ex2.org');
		$syncingUser3->setHome('/homes/mummy4000');
		$syncingUser3->setSearchTerms([]);

		$this->userProxy->expects($this->once())
			->method('testConnection')
			->willReturn(true);
		$this->userProxy->expects($this->exactly(3))
			->method('getRawUsersEntriesWithPrefix')
			->will($this->onConsecutiveCalls(
				[
					[
						'prefix' => '',
						'entry' => [
							'dn' => ['uid=zombie4000,ou=zombies,dc=owncloud,dc=com'],
							'uid' => ['zombie4000'],
							'mail' => ['zombie4000@ex.org'],
						]
					],
					[
						'prefix' => 's02',
						'entry' => [
							'dn' => ['uid=mummy4000,ou=mummies,dc=owncloud2,dc=com'],
							'uid' => ['mummy4000'],
							'mail' => ['mummy4000@ex2.org'],
						]
					],
				],
				[
					[
						'prefix' => '',
						'entry' => [
							'dn' => ['uid=zombie5000,ou=zombies,dc=owncloud,dc=com'],
							'uid' => ['zombie5000'],
							'mail' => ['zombie5000@ex.org'],
						]
					],
				],
				[]
			));
		$this->userProxy->expects($this->exactly(3))
			->method('getUserEntryFromRawWithPrefix')
			->withConsecutive(
				['', $this->equalTo(['dn' => ['uid=zombie4000,ou=zombies,dc=owncloud,dc=com'], 'uid' => ['zombie4000'], 'mail' => ['zombie4000@ex.org']])],
				['s02', $this->equalTo(['dn' => ['uid=mummy4000,ou=mummies,dc=owncloud2,dc=com'], 'uid' => ['mummy4000'], 'mail' => ['mummy4000@ex2.org']])],
				['', $this->equalTo(['dn' => ['uid=zombie5000,ou=zombies,dc=owncloud,dc=com'], 'uid' => ['zombie5000'], 'mail' => ['zombie5000@ex.org']])],
			)->will(
				$this->returnCallback(function ($prefix, $entry) use ($userEntry1, $userEntry2) {
					static $i = 0;
					$i++;
					switch ($i) {
					case 1: return $userEntry1;
					case 2: throw new \OutOfBoundsException('cannot get user');
					case 3: return $userEntry2;
					}
				})
			);

		$this->assertEquals($syncingUser1, $this->backend->getNextUser());
		$ex = null;
		try {
			$this->backend->getNextUser();
		} catch (\Exception $e) {
			$ex = $e;
		}
		$this->assertNotNull($ex);
		$this->assertSame('Failed to get user with dn uid=mummy4000,ou=mummies,dc=owncloud2,dc=com', $ex->getMessage());
		$this->assertEquals($syncingUser2, $this->backend->getNextUser());
		$this->assertNull($this->backend->getNextUser());
	}

	public function testGetNextUserBadUserEntry() {
		$userEntry1 = $this->createUserEntryMock('zombie4000', 'Supa Zombie', '20GB', 'zombie4000@ex.org', '/homes/zombie4000', []);
		$userEntry2 = $this->createUserEntryMock('zombie5000', 'Blob Rando', '30GB', 'zombie5000@ex.org', '/homes/zombie5000', []);
		$userEntry3 = $this->createMock(UserEntry::class);
		$userEntry3->method('getOwnCloudUID')
			->will($this->throwException(new \OutOfBoundsException('something went wrong')));
		$userEntry3->method('getDN')->willReturn('uid=mummy4000,ou=mummies,dc=owncloud2,dc=com');

		$syncingUser1 = new SyncingUser('zombie4000');
		$syncingUser1->setDisplayName('Supa Zombie');
		$syncingUser1->setQuota('20GB');
		$syncingUser1->setEmail('zombie4000@ex.org');
		$syncingUser1->setHome('/homes/zombie4000');
		$syncingUser1->setSearchTerms([]);

		$syncingUser2 = new SyncingUser('zombie5000');
		$syncingUser2->setDisplayName('Blob Rando');
		$syncingUser2->setQuota('30GB');
		$syncingUser2->setEmail('zombie5000@ex.org');
		$syncingUser2->setHome('/homes/zombie5000');
		$syncingUser2->setSearchTerms([]);

		$syncingUser3 = new SyncingUser('mummy4000');
		$syncingUser3->setDisplayName('Kamom');
		$syncingUser3->setQuota('2GB');
		$syncingUser3->setEmail('mummy4000@ex2.org');
		$syncingUser3->setHome('/homes/mummy4000');
		$syncingUser3->setSearchTerms([]);

		$this->userProxy->expects($this->once())
			->method('testConnection')
			->willReturn(true);
		$this->userProxy->expects($this->exactly(2))
			->method('getRawUsersEntriesWithPrefix')
			->will($this->onConsecutiveCalls(
				[
					[
						'prefix' => '',
						'entry' => [
							'dn' => ['uid=zombie4000,ou=zombies,dc=owncloud,dc=com'],
							'uid' => ['zombie4000'],
							'mail' => ['zombie4000@ex.org'],
						]
					],
					[
						'prefix' => '',
						'entry' => [
							'dn' => ['uid=zombie5000,ou=zombies,dc=owncloud,dc=com'],
							'uid' => ['zombie5000'],
							'mail' => ['zombie5000@ex.org'],
						]
					],
					[
						'prefix' => 's02',
						'entry' => [
							'dn' => ['uid=mummy4000,ou=mummies,dc=owncloud2,dc=com'],
							'uid' => ['mummy4000'],
							'mail' => ['mummy4000@ex2.org'],
						]
					],
				],
				[]
			));
		$this->userProxy->expects($this->exactly(3))
			->method('getUserEntryFromRawWithPrefix')
			->withConsecutive(
				['', $this->equalTo(['dn' => ['uid=zombie4000,ou=zombies,dc=owncloud,dc=com'], 'uid' => ['zombie4000'], 'mail' => ['zombie4000@ex.org']])],
				['', $this->equalTo(['dn' => ['uid=zombie5000,ou=zombies,dc=owncloud,dc=com'], 'uid' => ['zombie5000'], 'mail' => ['zombie5000@ex.org']])],
				['s02', $this->equalTo(['dn' => ['uid=mummy4000,ou=mummies,dc=owncloud2,dc=com'], 'uid' => ['mummy4000'], 'mail' => ['mummy4000@ex2.org']])],
			)->will($this->onConsecutiveCalls($userEntry1, $userEntry2, $userEntry3));

		$this->assertEquals($syncingUser1, $this->backend->getNextUser());
		$this->assertEquals($syncingUser2, $this->backend->getNextUser());
		$ex = null;
		try {
			$this->backend->getNextUser();
		} catch (\Exception $e) {
			$ex = $e;
		}
		$this->assertSame('Can\'t sync user with dn uid=mummy4000,ou=mummies,dc=owncloud2,dc=com', $ex->getMessage());
		$this->assertNull($this->backend->getNextUser());
	}

	public function testGetSyncingUser() {
		$userEntry1 = $this->createUserEntryMock('zombie4000', 'Supa Zombie', '20GB', 'zombie4000@ex.org', '/homes/zombie4000', []);

		$this->userProxy->expects($this->once())
			->method('getUserEntry')
			->with('zombie4000')
			->willReturn($userEntry1);

		$syncingUser1 = new SyncingUser('zombie4000');
		$syncingUser1->setDisplayName('Supa Zombie');
		$syncingUser1->setQuota('20GB');
		$syncingUser1->setEmail('zombie4000@ex.org');
		$syncingUser1->setHome('/homes/zombie4000');
		$syncingUser1->setSearchTerms([]);

		$this->assertEquals($syncingUser1, $this->backend->getSyncingUser('zombie4000'));
	}

	public function testGetSyncingUserBrokenBackend() {
		$this->expectException(SyncBackendBrokenException::class);

		$userEntry1 = $this->createUserEntryMock('zombie4000', 'Supa Zombie', '20GB', 'zombie4000@ex.org', '/homes/zombie4000', []);

		$this->userProxy->expects($this->once())
			->method('getUserEntry')
			->with('zombie4000')
			->will($this->throwException(new BindFailedException('wrong password')));

		$this->backend->getSyncingUser('zombie4000');
	}

	public function testGetSyncingUserUserFailed() {
		$this->expectException(SyncBackendUserFailedException::class);

		$userEntry1 = $this->createMock(UserEntry::class);
		$userEntry1->method('getOwnCloudUID')
			->will($this->throwException(new \OutOfBoundsException('something wrong happened')));

		$this->userProxy->expects($this->once())
			->method('getUserEntry')
			->with('zombie4000')
			->willReturn($userEntry1);

		$this->backend->getSyncingUser('zombie4000');
	}

	public function testGetSyncingUserMissing() {
		$this->userProxy->expects($this->once())
			->method('getUserEntry')
			->with('zombie4000')
			->willReturn(null);

		$this->assertNull($this->backend->getSyncingUser('zombie4000'));
	}

	public function testUserCount() {
		$this->userProxy->method('countUsers')->willReturn(578);
		$this->assertSame(578, $this->backend->userCount());
	}

	public function testUserCountFail() {
		$this->userProxy->method('countUsers')->willReturn(false);
		$this->assertNull($this->backend->userCount());
	}

	public function testGetUserInterface() {
		$this->assertSame($this->userProxy, $this->backend->getUserInterface());
	}
}
