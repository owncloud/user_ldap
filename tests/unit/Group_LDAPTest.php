<?php
/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Frédéric Fortier <frederic.fortier@oronospolytechnique.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
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

use OCA\User_LDAP\Config\Server;
use OCA\User_LDAP\Connection\BackendManager;
use OCA\User_LDAP\Connection\FilterBuilder;
use OCA\User_LDAP\Group_LDAP;
use OCA\User_LDAP\Mapping\GroupMapping;
use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\User\Manager;
use OCP\IAvatarManager;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\ILogger;
use OCP\IUserManager;

/**
 * Class GroupLDAPTest
 *
 * @group DB
 *
 * @package OCA\User_LDAP
 */
class Group_LDAPTest extends \Test\TestCase {
	/** @var ICacheFactory|\PHPUnit\Framework\MockObject\MockObject */
	protected $cf;

	/** @var ILDAPWrapper|\PHPUnit\Framework\MockObject\MockObject */
	protected $ldap;

	/** @var Server|\PHPUnit\Framework\MockObject\MockObject */
	protected $server;

	/** @var Access|\PHPUnit\Framework\MockObject\MockObject */
	protected $access;

	/** @var FilterBuilder|\PHPUnit\Framework\MockObject\MockObject */
	protected $filterBuilder;

	/** @var Connection|\PHPUnit\Framework\MockObject\MockObject */
	protected $connection;

	/** @var Group_LDAP|\PHPUnit\Framework\MockObject\MockObject */
	protected $backend;

	public function setUp() {
		parent::setUp();

		$cf = $this->createMock(ICacheFactory::class);
		$this->ldap = $this->createMock(ILDAPWrapper::class);

		$this->ldap
			->method('explodeDN')
			->will($this->returnCallback(
				function ($dn, $flag) {
					return \explode(',', $dn);
				}
			));

		$this->server = new Server([
			'id' => 'test',
			'active' => true,
			'ldapHost' => 'ldap://fake.ldap',
			'ldapPort' => 389,
			'bindDN' => 'uid=agent',
			'password' => '123456',
			'supportsMemberOf' => true,
			'userTrees' => [
				'dc=foobar,dc=bar' => [
					'baseDN' => 'dc=foobar,dc=bar',
					'filterObjectclass' => ['objectclass=inetorgperson']
				],
			],
			'groupTrees' => [
				'dc=foobar,dc=bar' => [
					'baseDN' => 'dc=foobar,dc=bar',
					'filterObjectclass' => ['objectclass=*'],
					'memberAttribute' => 'uniqueMember',
					'nestedGroups' => true
				]
			]
		]);

		$config = $this->createMock(IConfig::class);
		$logger = $this->createMock(ILogger::class);

		$tm = new BackendManager(
			$config,
			$logger,
			$cf,
			$this->createMock(IAvatarManager::class),
			$this->createMock(IUserManager::class),
			$this->createMock(IDBConnection::class),
			$this->ldap,
			$this->createMock(UserMapping::class),
			$this->createMock(GroupMapping::class),
			$this->createMock(FilesystemHelper::class)
		);
		$tm->registerServer($this->server);

		// we use a mock here to replace the cache mechanism, due to missing DI in LDAP backend.
		$this->connection = $this->getMockBuilder(Connection::class)
			->setMethods(['getFromCache', 'writeToCache'])
			->setConstructorArgs([$cf, $this->ldap, $this->server])
			->getMock();

		$this->access = $this->createMock(Access::class);
		$this->access->expects($this->any())
			->method('getConnection')
			->will($this->returnValue($this->connection));

		$this->filterBuilder = new FilterBuilder($config);

		$this->backend = new Group_LDAP(
			$logger,
			$this->server,
			$this->server->getGroupTree('dc=foobar,dc=bar'),
			$tm,
			$this->access,
			$this->filterBuilder
		);
	}

	public function testCountEmptySearchString() {
		$this->access->expects($this->any())
			->method('groupname2dn')
			->will($this->returnValue('cn=group,dc=foo,dc=bar'));

		$this->access->expects($this->any())
			->method('readAttribute')
			->will($this->returnValue(['u11', 'u22', 'u33', 'u34']));

		// for primary groups
		$this->access->expects($this->once())
			->method('countUsers')
			->will($this->returnValue(2));

		$users = $this->backend->countUsersInGroup('group');

		$this->assertSame(6, $users);
	}

	public function testCountWithSearchString() {
		$this->access->expects($this->any())
			->method('groupname2dn')
			->will($this->returnValue('cn=group,dc=foo,dc=bar'));

		$this->access->expects($this->any())
			->method('fetchListOfUsers')
			->will($this->returnValue([]));

		$this->access->expects($this->any())
			->method('readAttribute')
			->will($this->returnCallback(function ($dn, $attr, $filter) {
				//the search operation will call readAttribute, thus we need
				//to analyze the "dn". All other times we just need to return
				//something that is neither null or false, but once an array
				//with the users in the group – so we do so all other times for
				//simplicity.

				// groupExists check
				if ($dn === 'cn=group,dc=foo,dc=bar' && $attr === '') {
					return [];
				}
				if ($dn === 'cn=group,dc=foo,dc=bar' && $attr === 'uniqueMember') {
					return [
						'cn=u11,ou=users,dc=foobar,dc=bar',
						'cn=u22,ou=users,dc=foobar,dc=bar',
						'cn=u33,ou=users,dc=foobar,dc=bar',
						'cn=u34,ou=users,dc=foobar,dc=bar'
					];
				}
				if (\strpos($dn, 'ou=users') > 0) {
					return \strpos($dn, '3');
				}
				return ['u11', 'u22', 'u33', 'u34'];
			}));

		$this->access->expects($this->any())
			->method('dn2username')
			->will($this->returnCallback(function () {
				return 'foobar' . \OCP\Util::generateRandomBytes(7);
			}));

		$users = $this->backend->countUsersInGroup('group', '3');

		$this->assertSame(2, $users);
	}

	public function testPrimaryGroupID2NameSuccess() {
		$userDN = 'cn=alice,cn=foo,dc=barfoo,dc=bar';

		$this->access->expects($this->once())
			->method('getSID')
			->with($userDN)
			->will($this->returnValue('S-1-5-21-249921958-728525901-1594176202'));

		$this->access->expects($this->once())
			->method('searchGroups')
			->will($this->returnValue([['dn' => ['cn=foo,dc=barfoo,dc=bar']]]));

		$this->access->expects($this->once())
			->method('dn2groupname')
			->with('cn=foo,dc=barfoo,dc=bar')
			->will($this->returnValue('MyGroup'));

		$group = $this->backend->primaryGroupID2Name('3117', $userDN);

		$this->assertSame('MyGroup', $group);
	}

	public function testPrimaryGroupID2NameNoSID() {
		$userDN = 'cn=alice,cn=foo,dc=barfoo,dc=bar';

		$this->access->expects($this->once())
			->method('getSID')
			->with($userDN)
			->will($this->returnValue(false));

		$this->access->expects($this->never())
			->method('searchGroups');

		$this->access->expects($this->never())
			->method('dn2groupname');

		$group = $this->backend->primaryGroupID2Name('3117', $userDN);

		$this->assertSame(false, $group);
	}

	public function testPrimaryGroupID2NameNoGroup() {
		$userDN = 'cn=alice,cn=foo,dc=barfoo,dc=bar';

		$this->access->expects($this->once())
			->method('getSID')
			->with($userDN)
			->will($this->returnValue('S-1-5-21-249921958-728525901-1594176202'));

		$this->access->expects($this->once())
			->method('searchGroups')
			->will($this->returnValue([]));

		$this->access->expects($this->never())
			->method('dn2groupname');

		$group = $this->backend->primaryGroupID2Name('3117', $userDN);

		$this->assertSame(false, $group);
	}

	public function testPrimaryGroupID2NameNoName() {
		$userDN = 'cn=alice,cn=foo,dc=barfoo,dc=bar';

		$this->access->expects($this->once())
			->method('getSID')
			->with($userDN)
			->will($this->returnValue('S-1-5-21-249921958-728525901-1594176202'));

		$this->access->expects($this->once())
			->method('searchGroups')
			->will($this->returnValue([['dn' => ['cn=foo,dc=barfoo,dc=bar']]]));

		$this->access->expects($this->once())
			->method('dn2groupname')
			->will($this->returnValue(false));

		$group = $this->backend->primaryGroupID2Name('3117', $userDN);

		$this->assertSame(false, $group);
	}

	public function testGetEntryGroupIDValue() {
		//tests getEntryGroupID via getGroupPrimaryGroupID
		//which is basically identical to getUserPrimaryGroupIDs

		$dn = 'cn=foobar,cn=foo,dc=barfoo,dc=bar';
		$attr = 'primaryGroupToken';

		$this->access->expects($this->once())
			->method('readAttribute')
			->with($dn, $attr)
			->will($this->returnValue(['3117']));

		$gid = $this->backend->getGroupPrimaryGroupID($dn);

		$this->assertSame('3117', $gid);
	}

	public function testGetEntryGroupIDNoValue() {
		//tests getEntryGroupID via getGroupPrimaryGroupID
		//which is basically identical to getUserPrimaryGroupIDs

		$dn = 'cn=foobar,cn=foo,dc=barfoo,dc=bar';
		$attr = 'primaryGroupToken';

		$this->access->expects($this->once())
			->method('readAttribute')
			->with($dn, $attr)
			->will($this->returnValue(false));

		$gid = $this->backend->getGroupPrimaryGroupID($dn);

		$this->assertSame(false, $gid);
	}

	/**
	 * tests whether Group Backend behaves correctly when cache with uid and gid
	 * is hit
	 */
	public function testInGroupHitsUidGidCache() {
		$uid = 'someUser';
		$gid = 'someGroup';
		$cacheKey = 'inGroup' . $uid . ':' . $gid;

		$this->connection->expects($this->once())
			->method('getFromCache')
			->with($cacheKey)
			->will($this->returnValue(true));

		$this->access->expects($this->never())
			->method('username2dn');

		$this->backend->inGroup($uid, $gid);
	}

	public function testGetGroupsWithOffset() {
		$this->access->expects($this->once())
			->method('ownCloudGroupNames')
			->will($this->returnValue(['group1', 'group2']));

		$groups = $this->backend->getGroups('', 2, 2);

		$this->assertSame(2, \count($groups));
	}

	/**
	 * tests that a user listing is complete, if all it's members have the group
	 * as their primary.
	 */
	public function testUsersInGroupPrimaryMembersOnly() {
		$this->markTestSkipped('Group_LDAP::getUsersInPrimaryGroup is broken');
		$this->connection->expects($this->any())
			->method('getFromCache')
			->will($this->returnValue(null));

		$this->access->expects($this->any())
			->method('readAttribute')
			->will($this->returnCallback(function ($dn, $attr) {
				if ($attr === 'primaryGroupToken') {
					return [1337];
				}
				return [];
			}));

		$this->access->expects($this->any())
			->method('groupname2dn')
			->will($this->returnValue('cn=foobar,dc=foo,dc=bar'));

		$this->access->expects($this->once())
			->method('ownCloudUserNames')
			->will($this->returnValue(['lisa', 'bart', 'kira', 'brad']));

		$userManager = $this->createMock(Manager::class);
		$userManager->expects($this->once())
			->method('getAttributes')
			->will($this->returnValue([
				'dn', 'uid', 'samaccountname', 'memberof'
			]));

		$this->access->expects($this->any())
			->method('getUserManager')
			->will($this->returnValue($userManager));

		$users = $this->backend->usersInGroup('foobar');

		$this->assertSame(4, \count($users));
	}

	/**
	 * tests that a user counting is complete, if all it's members have the group
	 * as their primary.
	 */
	public function testCountUsersInGroupPrimaryMembersOnly() {
		$this->connection->expects($this->any())
			->method('getFromCache')
			->will($this->returnValue(null));

		$this->access->expects($this->any())
			->method('readAttribute')
			->will($this->returnCallback(function ($dn, $attr) {
				if ($attr === 'primaryGroupToken') {
					return [1337];
				}
				return [];
			}));

		$this->access->expects($this->any())
			->method('groupname2dn')
			->will($this->returnValue('cn=foobar,dc=foo,dc=bar'));

		$this->access->expects($this->once())
			->method('countUsers')
			->will($this->returnValue(4));

		$users = $this->backend->countUsersInGroup('foobar');

		$this->assertSame(4, $users);
	}

	public function testGetUserGroupsMemberOf() {
		$dn = 'cn=userX,dc=foobar';

		// saves a readAttribute, test relies on this because it counts no of readAttribute calls
		$this->server->setSupportsPrimaryGroups(false);

		$this->access->expects($this->any())
			->method('username2dn')
			->will($this->returnValue($dn));

		$this->access->method('readAttribute')
			->willReturnOnConsecutiveCalls([], ['cn=groupA,dc=foobar', 'cn=groupB,dc=foobar'], []);

		$this->access->expects($this->exactly(2))
			->method('dn2groupname')
			->will($this->returnArgument(0));

		$this->access->expects($this->exactly(2))
			->method('groupsMatchFilter')
			->will($this->returnArgument(0));

		$groups = $this->backend->getUserGroups('userX');

		$this->assertSame(2, \count($groups));
	}

	private function setUpWithUseMemberOfToDetectMembershipDisabled() {
		$this->connection = $this->createMock(Connection::class);
		$ldap = $this->createMock(ILDAPWrapper::class);
		$ldap->method('escape')
			->willReturnCallback('ldap_escape');

		$this->connection
			->method('getLDAP')
			->willReturn($ldap);
	}

	public function testGetUserGroupsMemberOfDisabled() {
		$this->setUpWithUseMemberOfToDetectMembershipDisabled();
		$this->access->expects($this->any())
			->method('getConnection')
			->will($this->returnValue($this->connection));

		$dn = 'cn=userX,dc=foobar';

		// saves a readAttribute, test relies on this
		$this->server->setSupportsPrimaryGroups(false);
		$this->server->setSupportsMemberOf(false);
		$groupTrees = $this->server->getGroupTrees();
		$groupTree = \current($groupTrees);
		$groupTree->setNestedGroups(false);

		$this->access
			->method('username2dn')
			->willReturn($dn);

		$this->access->expects($this->once())
			->method('ownCloudGroupNames')
			->will($this->returnValue([]));

		$this->backend->getUserGroups('userX');
	}

	public function testGetGroupsByMember() {
		$this->setUpWithUseMemberOfToDetectMembershipDisabled();

		$dn = 'cn=userX,dc=foobar';

		$this->connection->hasPrimaryGroups = false;

		$this->access->expects($this->any())
			->method('getConnection')
			->will($this->returnValue($this->connection));
		$this->access->expects($this->exactly(2))
			->method('username2dn')
			->willReturn($dn);

		$group1 = [
			'cn' => 'group1',
			'dn' => ['cn=group1,ou=groups,dc=domain,dc=com'],
		];
		$group2 = [
			'cn' => 'group2',
			'dn' => ['cn=group2,ou=groups,dc=domain,dc=com'],
		];

		$this->server->setSupportsMemberOf(false);
		$groupTrees = $this->server->getGroupTrees();
		$groupTree = \current($groupTrees);
		$groupTree->setNestedGroups(false);

		$this->access->expects($this->once())
			->method('ownCloudGroupNames')
			->with([$group1, $group2])
			->will($this->returnValue(['group1', 'group2']));

		$this->access->expects($this->once())
			->method('fetchListOfGroups')
			->will($this->returnValue([$group1, $group2]));

		$groups = $this->backend->getUserGroups('userX');
		$this->assertEquals(['group1', 'group2'], $groups);

		$groupsAgain = $this->backend->getUserGroups('userX');
		$this->assertEquals(['group1', 'group2'], $groupsAgain);
	}
}
