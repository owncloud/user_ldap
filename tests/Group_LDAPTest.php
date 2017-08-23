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

namespace OCA\User_LDAP\Tests;

use OCA\User_LDAP\Access;
use OCA\User_LDAP\Connection;
use OCA\User_LDAP\Group_LDAP as GroupLDAP;
use OCA\User_LDAP\User\Manager;

/**
 * Class GroupLDAPTest
 *
 * @group DB
 *
 * @package OCA\User_LDAP\Tests
 */
class Group_LDAPTest extends \Test\TestCase {
	/**
	 * @var Access|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $access;
	/**
	 * @var Connection|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $connection;

	public function setUp() {
		parent::setUp();
		$this->connection = $this->createMock(Connection::class);

		$this->connection->expects($this->any())
			->method('__get')
			->will($this->returnCallback(function ($method) {
				switch ($method) {
					case 'ldapEMailAttribute':
					case 'homeFolderNamingRule':
					case 'ldapAttributesForUserSearch':
						return null;
					case 'ldapQuotaAttribute':
					case 'ldapUserDisplayName2':
					case 'ldapGroupFilter':
						return '(objectclass=*)';
					case 'ldapDynamicGroupMemberURL':
						return '';
					case 'ldapUserFilter':
						return '(objectclass=inetorgperson)';
					case 'ldapUserDisplayName':
						return 'displayName';
					case 'ldapBaseUsers':
						return 'dc=foobar,dc=bar';
					case 'ldapGroupMemberAssocAttr':
						return 'uniqueMember';
					case 'hasMemberOfFilterSupport':
					case 'useMemberOfToDetectMembership':
					case 'ldapNestedGroups':
						return 1;
					default:
						return false;
				}
			}));

		$this->access = $this->createMock(Access::class);

		$this->access->expects($this->any())
			->method('getConnection')
			->will($this->returnValue($this->connection));
	}

	public function testCountEmptySearchString() {

		$this->access->expects($this->any())
			->method('groupname2dn')
			->will($this->returnValue('cn=group,dc=foo,dc=bar'));

		$this->access->expects($this->any())
			->method('readAttribute')
			->will($this->returnValue(array('u11', 'u22', 'u33', 'u34')));

		// for primary groups
		$this->access->expects($this->once())
			->method('countUsers')
			->will($this->returnValue(2));

		$groupBackend = new GroupLDAP($this->access);
		$users = $groupBackend->countUsersInGroup('group');

		$this->assertSame(6, $users);
	}

	public function testCountWithSearchString() {

		$this->access->expects($this->any())
			->method('groupname2dn')
			->will($this->returnValue('cn=group,dc=foo,dc=bar'));

		$this->access->expects($this->any())
			->method('fetchListOfUsers')
			->will($this->returnValue(array()));

		$this->access->expects($this->any())
			->method('readAttribute')
			->will($this->returnCallback(function ($name) {
				//the search operation will call readAttribute, thus we need
				//to analyze the "dn". All other times we just need to return
				//something that is neither null or false, but once an array
				//with the users in the group – so we do so all other times for
				//simplicity.
				if (strpos($name, 'u') === 0) {
					return strpos($name, '3');
				}
				return array('u11', 'u22', 'u33', 'u34');
			}));

		$this->access->expects($this->any())
			->method('dn2username')
			->will($this->returnCallback(function () {
				return 'foobar' . \OCP\Util::generateRandomBytes(7);
			}));

		$groupBackend = new GroupLDAP($this->access);
		$users = $groupBackend->countUsersInGroup('group', '3');

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

		$groupBackend = new GroupLDAP($this->access);

		$group = $groupBackend->primaryGroupID2Name('3117', $userDN);

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

		$groupBackend = new GroupLDAP($this->access);

		$group = $groupBackend->primaryGroupID2Name('3117', $userDN);

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
			->will($this->returnValue(array()));

		$this->access->expects($this->never())
			->method('dn2groupname');

		$groupBackend = new GroupLDAP($this->access);

		$group = $groupBackend->primaryGroupID2Name('3117', $userDN);

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

		$groupBackend = new GroupLDAP($this->access);

		$group = $groupBackend->primaryGroupID2Name('3117', $userDN);

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
			->will($this->returnValue(array('3117')));

		$groupBackend = new GroupLDAP($this->access);

		$gid = $groupBackend->getGroupPrimaryGroupID($dn);

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

		$groupBackend = new GroupLDAP($this->access);

		$gid = $groupBackend->getGroupPrimaryGroupID($dn);

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

		$groupBackend = new GroupLDAP($this->access);
		$groupBackend->inGroup($uid, $gid);
	}

	public function testGetGroupsWithOffset() {

		$this->access->expects($this->once())
			->method('ownCloudGroupNames')
			->will($this->returnValue(array('group1', 'group2')));

		$groupBackend = new GroupLDAP($this->access);
		$groups = $groupBackend->getGroups('', 2, 2);

		$this->assertSame(2, count($groups));
	}

	/**
	 * tests that a user listing is complete, if all it's members have the group
	 * as their primary.
	 */
	public function testUsersInGroupPrimaryMembersOnly() {


		$this->connection->expects($this->any())
			->method('getFromCache')
			->will($this->returnValue(null));

		$this->access->expects($this->any())
			->method('readAttribute')
			->will($this->returnCallback(function ($dn, $attr) {
				if ($attr === 'primaryGroupToken') {
					return array(1337);
				}
				return array();
			}));

		$this->access->expects($this->any())
			->method('groupname2dn')
			->will($this->returnValue('cn=foobar,dc=foo,dc=bar'));

		$this->access->expects($this->once())
			->method('ownCloudUserNames')
			->will($this->returnValue(array('lisa', 'bart', 'kira', 'brad')));

		$userManager = $this->createMock(Manager::class);
		$userManager->expects($this->once())
			->method('getAttributes')
			->will($this->returnValue([
				'dn', 'uid', 'samaccountname', 'memberof'
			]));

		$this->access->expects($this->any())
			->method('getUserManager')
			->will($this->returnValue($userManager));

		$groupBackend = new GroupLDAP($this->access);
		$users = $groupBackend->usersInGroup('foobar');

		$this->assertSame(4, count($users));
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
					return array(1337);
				}
				return array();
			}));

		$this->access->expects($this->any())
			->method('groupname2dn')
			->will($this->returnValue('cn=foobar,dc=foo,dc=bar'));

		$this->access->expects($this->once())
			->method('countUsers')
			->will($this->returnValue(4));

		$groupBackend = new GroupLDAP($this->access);
		$users = $groupBackend->countUsersInGroup('foobar');

		$this->assertSame(4, $users);
	}

	public function testGetUserGroupsMemberOf() {

		$dn = 'cn=userX,dc=foobar';

		$this->connection->hasPrimaryGroups = false;

		$this->access->expects($this->any())
			->method('username2dn')
			->will($this->returnValue($dn));

		$this->access->expects($this->exactly(3))
			->method('readAttribute')
			->will($this->onConsecutiveCalls(['cn=groupA,dc=foobar', 'cn=groupB,dc=foobar'], [], []));

		$this->access->expects($this->exactly(2))
			->method('dn2groupname')
			->will($this->returnArgument(0));

		$this->access->expects($this->exactly(3))
			->method('groupsMatchFilter')
			->will($this->returnArgument(0));

		$groupBackend = new GroupLDAP($this->access);
		$groups = $groupBackend->getUserGroups('userX');

		$this->assertSame(2, count($groups));
	}

	private function setUpWithUseMemberOfToDetectMembershipDisabled() {

		$this->connection = $this->createMock(Connection::class);

		$this->connection->expects($this->any())
			->method('__get')
			->will($this->returnCallback(function($method) {
				switch ($method) {
					case 'ldapEMailAttribute':
					case 'homeFolderNamingRule':
					case 'ldapAttributesForUserSearch':
					case 'ldapDynamicGroupMemberURL':
						return null;
					case 'ldapQuotaAttribute':
					case 'ldapUserDisplayName2':
						return '';
					case 'ldapGroupFilter':
						return '(objectclass=*)';
					case 'ldapUserFilter':
						return '(objectclass=inetorgperson)';
					case 'ldapUserDisplayName':
						return 'displayName';
					case 'ldapBaseUsers':
						return 'dc=foobar,dc=bar';
					case 'ldapGroupMemberAssocAttr':
						return 'uniqueMember';
					case 'useMemberOfToDetectMembership':
						return 0;
					case 'hasMemberOfFilterSupport':
						return 1;
					default:
						return false;
				}
			}));

		$this->access = $this->createMock(Access::class);

		$this->access->expects($this->any())
			->method('getConnection')
			->will($this->returnValue($this->connection));

	}

	public function testGetUserGroupsMemberOfDisabled() {
		$this->setUpWithUseMemberOfToDetectMembershipDisabled();

		$dn = 'cn=userX,dc=foobar';

		$this->connection->hasPrimaryGroups = false;

		$this->access->expects($this->once())
			->method('username2dn')
			->will($this->returnValue($dn));

		$this->access->expects($this->never())
			->method('readAttribute')
			->with($dn, 'memberOf');

		$this->access->expects($this->once())
			->method('ownCloudGroupNames')
			->will($this->returnValue([]));

		$groupBackend = new GroupLDAP($this->access);
		$groupBackend->getUserGroups('userX');
	}

	public function testGetGroupsByMember() {
		$this->setUpWithUseMemberOfToDetectMembershipDisabled();

		$dn = 'cn=userX,dc=foobar';

		$this->connection->hasPrimaryGroups = false;

		$this->access->expects($this->exactly(2))
			->method('username2dn')
			->will($this->returnValue($dn));

		$this->access->expects($this->never())
			->method('readAttribute')
			->with($dn, 'memberOf');

		$group1 = [
			'cn' => 'group1',
			'dn' => ['cn=group1,ou=groups,dc=domain,dc=com'],
		];
		$group2 = [
			'cn' => 'group2',
			'dn' => ['cn=group2,ou=groups,dc=domain,dc=com'],
		];

		$this->access->expects($this->once())
			->method('ownCloudGroupNames')
			->with([$group1, $group2])
			->will($this->returnValue(['group1', 'group2']));

		$this->access->expects($this->once())
			->method('fetchListOfGroups')
			->will($this->returnValue([$group1, $group2]));

		$groupBackend = new GroupLDAP($this->access);
		$groups = $groupBackend->getUserGroups('userX');
		$this->assertEquals(['group1', 'group2'], $groups);

		$groupsAgain = $groupBackend->getUserGroups('userX');
		$this->assertEquals(['group1', 'group2'], $groupsAgain);
	}
}
