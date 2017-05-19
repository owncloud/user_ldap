<?php
/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
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

use OCA\User_LDAP\User\User;
use OCP\IUserManager;

/**
 * Class UserTest
 *
 * @group DB
 *
 * @package OCA\User_LDAP\Tests\User
 */
class UserTest extends \Test\TestCase {

	private function getTestInstances() {
		$access  = $this->createMock('\OCA\User_LDAP\User\IUserTools');
		$config  = $this->createMock('\OCP\IConfig');
		$filesys = $this->createMock('\OCA\User_LDAP\FilesystemHelper');
		$log     = $this->createMock('\OCA\User_LDAP\LogWrapper');
		$avaMgr  = $this->createMock('\OCP\IAvatarManager');
		$image   = $this->createMock('\OCP\Image');
		$dbc     = $this->createMock('\OCP\IDBConnection');
		$userMgr  = $this->createMock('\OCP\IUserManager');

		return array($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr);
	}

	private function getAdvancedMocks($cfMock, $fsMock, $logMock, $avaMgr, $dbc, $userMgr = null) {
		static $conMethods;
		static $accMethods;
		static $umMethods;

		if(is_null($conMethods) || is_null($accMethods)) {
			$conMethods = get_class_methods('\OCA\User_LDAP\Connection');
			$accMethods = get_class_methods('\OCA\User_LDAP\Access');
			//getConnection shall not be replaced
			unset($accMethods[array_search('getConnection', $accMethods)]);
			$umMethods = get_class_methods('\OCA\User_LDAP\User\Manager');
		}
		$lw = $this->createMock('\OCA\User_LDAP\ILDAPWrapper');
		$im = $this->createMock('\OCP\Image');
		if (is_null($userMgr)) {
			$userMgr = $this->createMock('\OCP\IUserManager');
		}
		$um = $this->getMockBuilder('\OCA\User_LDAP\User\Manager')
			->setMethods($umMethods)
			->setConstructorArgs([$cfMock, $fsMock, $logMock, $avaMgr, $im, $dbc, $userMgr])
			->getMock();
		$connector = $this->getMockBuilder('\OCA\User_LDAP\Connection')
			->setMethods($conMethods)
			->setConstructorArgs([$lw, null, null])
			->getMock();
		$access = $this->getMockBuilder('\OCA\User_LDAP\Access')
			->setMethods($accMethods)
			->setConstructorArgs([$connector, $lw, $um])
			->getMock();

		return array($access, $connector);
	}

	public function testGetDNandUsername() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $db, $userMgr) =
			$this->getTestInstances();

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr, $userMgr);

		$this->assertSame($dn, $user->getDN());
		$this->assertSame($uid, $user->getUsername());
	}

	public function testUpdateEmailProvided() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc, $userMgr);

		$connection->expects($this->once())
			->method('__get')
			->with($this->equalTo('ldapEmailAttribute'))
			->will($this->returnValue('email'));

		$access->expects($this->once())
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('email'))
			->will($this->returnValue(array('alice@foo.bar')));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$uuser = $this->getMockBuilder('\OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$uuser->expects($this->once())
			->method('setEMailAddress')
			->with('alice@foo.bar');
		/** @var IUserManager | \PHPUnit_Framework_MockObject_MockObject $userMgr */
		$userMgr->expects($this->any())
			->method('get')
			->willReturn($uuser);
		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr, $userMgr);

		$user->updateEmail();
	}

	public function testUpdateEmailNotProvided() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$connection->expects($this->once())
			->method('__get')
			->with($this->equalTo('ldapEmailAttribute'))
			->will($this->returnValue('email'));

		$access->expects($this->once())
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('email'))
			->will($this->returnValue(false));

		$config->expects($this->never())
			->method('setUserValue');

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr, $userMgr);

		$user->updateEmail();
	}

	public function testUpdateEmailNotConfigured() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$connection->expects($this->once())
			->method('__get')
			->with($this->equalTo('ldapEmailAttribute'))
			->will($this->returnValue(''));

		$access->expects($this->never())
			->method('readAttribute');

		$config->expects($this->never())
			->method('setUserValue');

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr, $userMgr);

		$user->updateEmail();
	}

	public function testUpdateSearchAttributesNotConfigured() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$connection->expects($this->exactly(1))
			->method('__get')
			->with($this->equalTo('ldapAttributesForUserSearch'))
			->will($this->returnValue(null));

		$access->expects($this->never())
			->method('readAttribute');

		$uuser = $this->getMockBuilder('\OCP\IUser')
			->disableOriginalConstructor()
			->getMock();

		$uuser->expects($this->once())
			->method('getSearchTerms')
			->will($this->returnValue([]));

		$userMgr->expects($this->any())
			->method('get')
			->willReturn($uuser);

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr, $userMgr);

		$user->updateSearchAttributes();
	}

	public function testUpdateSearchAttributesNotProvided() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$connection->expects($this->exactly(1))
			->method('__get')
			->with($this->equalTo('ldapAttributesForUserSearch'))
			->will($this->returnValue(['uidNumber']));

		$access->expects($this->once())
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('uidnumber'))
			->will($this->returnValue(['1234']));

		$uuser = $this->getMockBuilder('\OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$uuser->expects($this->once())
			->method('getSearchTerms')
			->will($this->returnValue([]));
		$uuser->expects($this->once())
			->method('setSearchTerms')
			->with(['1234']);
		/** @var IUserManager | \PHPUnit_Framework_MockObject_MockObject $userMgr */
		$userMgr->expects($this->any())
			->method('get')
			->willReturn($uuser);

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr, $userMgr);

		$user->updateSearchAttributes();
	}

	public function testUpdateSearchAttributesNotProvidedMultiValueField() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$connection->expects($this->exactly(1))
			->method('__get')
			->with($this->equalTo('ldapAttributesForUserSearch'))
			->will($this->returnValue(['mailAddresses']));

		$access->expects($this->once())
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('mailaddresses'))
			->will($this->returnValue(['test@test.com', 'test2@test.com']));

		$uuser = $this->getMockBuilder('\OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$uuser->expects($this->once())
			->method('getSearchTerms')
			->will($this->returnValue([]));
		$uuser->expects($this->once())
			->method('setSearchTerms')
			->with(['test@test.com', 'test2@test.com']);
		/** @var IUserManager | \PHPUnit_Framework_MockObject_MockObject $userMgr */
		$userMgr->expects($this->any())
			->method('get')
			->willReturn($uuser);

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr, $userMgr);

		$user->updateSearchAttributes();
	}



	public function testUpdateSearchAttributesNotProvidedMultiple() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$connection->expects($this->exactly(1))
			->method('__get')
			->with($this->equalTo('ldapAttributesForUserSearch'))
			->will($this->returnValue(['uidNumber', 'zipcode']));

		$access->expects($this->exactly(2))
			->method('readAttribute')
			->withConsecutive(
				[$this->equalTo('uid=alice,dc=foo,dc=bar'), $this->equalTo('uidnumber')],
				[$this->equalTo('uid=alice,dc=foo,dc=bar'), $this->equalTo('zipcode')]
			)
			->will($this->onConsecutiveCalls(['1234'], ['nr140st']));

		$uuser = $this->getMockBuilder('\OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$uuser->expects($this->once())
			->method('getSearchTerms')
			->will($this->returnValue(['1234']));
		$uuser->expects($this->once())
			->method('setSearchTerms')
			->with(['1234', 'nr140st']);
		/** @var IUserManager | \PHPUnit_Framework_MockObject_MockObject $userMgr */
		$userMgr->expects($this->any())
			->method('get')
			->willReturn($uuser);

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr, $userMgr);

		$user->updateSearchAttributes();
	}

	public function testUpdateSearchAttributesProvided() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$access->expects($this->never())
			->method('readAttribute');

		$connection->expects($this->exactly(1))
			->method('__get')
			->with($this->equalTo('ldapAttributesForUserSearch'))
			->will($this->returnValue(['uidNumber']));

		$uuser = $this->getMockBuilder('\OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$uuser->expects($this->once())
			->method('getSearchTerms')
			->will($this->returnValue([]));
		$uuser->expects($this->once())
			->method('setSearchTerms')
			->with(['1234']);
		/** @var IUserManager | \PHPUnit_Framework_MockObject_MockObject $userMgr */
		$userMgr->expects($this->any())
			->method('get')
			->willReturn($uuser);

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr, $userMgr);

		$user->updateSearchAttributes(['uidnumber' => ['1234']]);
	}

	public function testUpdateQuotaAllProvided() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$connection->expects($this->at(0))
			->method('__get')
			->with($this->equalTo('ldapQuotaAttribute'))
			->will($this->returnValue('myquota'));

		/* Having a quota defined, the ldapQuotaDefault won't be used
		$connection->expects($this->at(1))
			->method('__get')
			->with($this->equalTo('ldapQuotaDefault'))
			->will($this->returnValue('23 GB'));
		*/

		$connection->expects($this->exactly(1))
			->method('__get');

		$access->expects($this->once())
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('myquota'))
			->will($this->returnValue(array('42 GB')));

		$user = $this->createMock('\OCP\IUser');
		$user->expects($this->once())
			->method('setQuota')
			->with('42 GB');

		$userMgr->expects($this->once())
			->method('get')
			->with('alice')
			->will($this->returnValue($user));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr, $userMgr);

		$user->updateQuota();
	}

	public function testUpdateQuotaToDefaultAllProvided() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$connection->expects($this->at(0))
			->method('__get')
			->with($this->equalTo('ldapQuotaAttribute'))
			->will($this->returnValue('myquota'));

		$connection->expects($this->exactly(1))
			->method('__get');

		$access->expects($this->once())
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('myquota'))
			->will($this->returnValue(array('default')));

		$user = $this->createMock('\OCP\IUser');
		$user->expects($this->once())
			->method('setQuota')
			->with('default');

		$userMgr->expects($this->once())
			->method('get')
			->with('alice')
			->will($this->returnValue($user));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr, $userMgr);

		$user->updateQuota();
	}

	public function testUpdateQuotaToNoneAllProvided() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$connection->expects($this->at(0))
			->method('__get')
			->with($this->equalTo('ldapQuotaAttribute'))
			->will($this->returnValue('myquota'));

		$connection->expects($this->exactly(1))
			->method('__get');

		$access->expects($this->once())
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('myquota'))
			->will($this->returnValue(array('none')));

		$user = $this->createMock('\OCP\IUser');
		$user->expects($this->once())
			->method('setQuota')
			->with('none');

		$userMgr->expects($this->once())
			->method('get')
			->with('alice')
			->will($this->returnValue($user));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr, $userMgr);

		$user->updateQuota();
	}

	public function testUpdateQuotaDefaultProvided() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$connection->expects($this->at(0))
			->method('__get')
			->with($this->equalTo('ldapQuotaAttribute'))
			->will($this->returnValue('myquota'));

		$connection->expects($this->at(1))
			->method('__get')
			->with($this->equalTo('ldapQuotaDefault'))
			->will($this->returnValue('25 GB'));

		$connection->expects($this->exactly(2))
			->method('__get');

		$access->expects($this->once())
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('myquota'))
			->will($this->returnValue(false));

		$user = $this->createMock('\OCP\IUser');
		$user->expects($this->once())
			->method('setQuota')
			->with('25 GB');

		$userMgr->expects($this->once())
			->method('get')
			->with('alice')
			->will($this->returnValue($user));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr, $userMgr);

		$user->updateQuota();
	}

	public function testUpdateQuotaIndividualProvided() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$connection->expects($this->at(0))
			->method('__get')
			->with($this->equalTo('ldapQuotaAttribute'))
			->will($this->returnValue('myquota'));

		/* Having a quota set this won't be used
		$connection->expects($this->at(1))
			->method('__get')
			->with($this->equalTo('ldapQuotaDefault'))
			->will($this->returnValue(''));
		*/

		$connection->expects($this->exactly(1))
			->method('__get');

		$access->expects($this->once())
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('myquota'))
			->will($this->returnValue(array('27 GB')));

		$user = $this->createMock('\OCP\IUser');
		$user->expects($this->once())
			->method('setQuota')
			->with('27 GB');

		$userMgr->expects($this->once())
			->method('get')
			->with('alice')
			->will($this->returnValue($user));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr, $userMgr);

		$user->updateQuota();
	}

	public function testUpdateQuotaNoneProvided() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$connection->expects($this->at(0))
			->method('__get')
			->with($this->equalTo('ldapQuotaAttribute'))
			->will($this->returnValue('myquota'));

		$connection->expects($this->at(1))
			->method('__get')
			->with($this->equalTo('ldapQuotaDefault'))
			->will($this->returnValue(''));

		$connection->expects($this->exactly(2))
			->method('__get');

		$access->expects($this->once())
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('myquota'))
			->will($this->returnValue(false));

		$user = $this->createMock('\OCP\IUser');
		$user->expects($this->once())
			->method('setQuota')
			->with('default');

		$userMgr->expects($this->once())
			->method('get')
			->with('alice')
			->will($this->returnValue($user));

		$config->expects($this->never())
			->method('setUserValue');

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr, $userMgr);

		$user->updateQuota();
	}

	public function testUpdateQuotaNoneConfigured() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$connection->expects($this->at(0))
			->method('__get')
			->with($this->equalTo('ldapQuotaAttribute'))
			->will($this->returnValue(''));

		$connection->expects($this->at(1))
			->method('__get')
			->with($this->equalTo('ldapQuotaDefault'))
			->will($this->returnValue(''));

		$connection->expects($this->exactly(2))
			->method('__get');

		$user = $this->createMock('\OCP\IUser');
		$user->expects($this->once())
			->method('setQuota')
			->with('default');

		$userMgr->expects($this->once())
			->method('get')
			->with('alice')
			->will($this->returnValue($user));


		$access->expects($this->never())
			->method('readAttribute');

		$config->expects($this->never())
			->method('setUserValue');

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr, $userMgr);

		$user->updateQuota();
	}

	public function testUpdateQuotaFromValue() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$readQuota = '19 GB';

		$connection->expects($this->never())
			->method('__get')
			->with($this->equalTo('ldapQuotaDefault'));

		$access->expects($this->never())
			->method('readAttribute');

		$user = $this->createMock('\OCP\IUser');
		$user->expects($this->once())
			->method('setQuota')
			->with($readQuota);

		$userMgr->expects($this->once())
			->method('get')
			->with('alice')
			->will($this->returnValue($user));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr, $userMgr);

		$user->updateQuota($readQuota);
	}

	/**
	 * Unparseable quota will fallback to use the LDAP default
	 */
	public function testUpdateWrongQuotaAllProvided() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$connection->expects($this->at(0))
			->method('__get')
			->with($this->equalTo('ldapQuotaAttribute'))
			->will($this->returnValue('myquota'));

		$connection->expects($this->at(1))
			->method('__get')
			->with($this->equalTo('ldapQuotaDefault'))
			->will($this->returnValue('23 GB'));

		$connection->expects($this->exactly(2))
			->method('__get');

		$access->expects($this->once())
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('myquota'))
			->will($this->returnValue(array('42 GBwos')));

		$user = $this->createMock('\OCP\IUser');
		$user->expects($this->once())
			->method('setQuota')
			->with('23 GB');

		$userMgr->expects($this->once())
			->method('get')
			->with('alice')
			->will($this->returnValue($user));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr, $userMgr);

		$user->updateQuota();
	}

	/**
	 * No user quota and wrong default will set 'default' as quota
	 */
	public function testUpdateWrongDefaultQuotaProvided() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$connection->expects($this->at(0))
			->method('__get')
			->with($this->equalTo('ldapQuotaAttribute'))
			->will($this->returnValue('myquota'));

		$connection->expects($this->at(1))
			->method('__get')
			->with($this->equalTo('ldapQuotaDefault'))
			->will($this->returnValue('23 GBwowowo'));

		$connection->expects($this->exactly(2))
			->method('__get');

		$access->expects($this->once())
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('myquota'))
			->will($this->returnValue(false));

		$user = $this->createMock('\OCP\IUser');
		$user->expects($this->once())
			->method('setQuota')
			->with('default');

		$userMgr->expects($this->once())
			->method('get')
			->with('alice')
			->will($this->returnValue($user));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr, $userMgr);

		$user->updateQuota();
	}

	/**
	 * Wrong user quota and wrong default will set 'default' as quota
	 */
	public function testUpdateWrongQuotaAndDefaultAllProvided() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$connection->expects($this->at(0))
			->method('__get')
			->with($this->equalTo('ldapQuotaAttribute'))
			->will($this->returnValue('myquota'));

		$connection->expects($this->at(1))
			->method('__get')
			->with($this->equalTo('ldapQuotaDefault'))
			->will($this->returnValue('23 GBwowowo'));

		$connection->expects($this->exactly(2))
			->method('__get');

		$access->expects($this->once())
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('myquota'))
			->will($this->returnValue(array('23 flush')));

		$user = $this->createMock('\OCP\IUser');
		$user->expects($this->once())
			->method('setQuota')
			->with('default');

		$userMgr->expects($this->once())
			->method('get')
			->with('alice')
			->will($this->returnValue($user));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr, $userMgr);

		$user->updateQuota();
	}

	/**
	 * No quota attribute set and wrong default will set 'default' as quota
	 */
	public function testUpdateWrongDefaultQuotaNotProvided() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$connection->expects($this->at(0))
			->method('__get')
			->with($this->equalTo('ldapQuotaAttribute'))
			->will($this->returnValue(''));

		$connection->expects($this->at(1))
			->method('__get')
			->with($this->equalTo('ldapQuotaDefault'))
			->will($this->returnValue('23 GBwowowo'));

		$connection->expects($this->exactly(2))
			->method('__get');

		$access->expects($this->never())
			->method('readAttribute');

		$user = $this->createMock('\OCP\IUser');
		$user->expects($this->once())
			->method('setQuota')
			->with('default');

		$userMgr->expects($this->once())
			->method('get')
			->with('alice')
			->will($this->returnValue($user));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr, $userMgr);

		$user->updateQuota();
	}

	//the testUpdateAvatar series also implicitely tests getAvatarImage
	public function testUpdateAvatarJpegPhotoProvided() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$access->expects($this->once())
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('jpegPhoto'))
			->will($this->returnValue(array('this is a photo')));

		$image->expects($this->once())
			->method('valid')
			->will($this->returnValue(true));
		$image->expects($this->once())
			->method('width')
			->will($this->returnValue(128));
		$image->expects($this->once())
			->method('height')
			->will($this->returnValue(128));
		$image->expects($this->once())
			->method('centerCrop')
			->will($this->returnValue(true));

		$filesys->expects($this->once())
			->method('isLoaded')
			->will($this->returnValue(true));

		$avatar = $this->createMock('\OCP\IAvatar');
		$avatar->expects($this->once())
			->method('set')
			->with($this->isInstanceOf($image));

		$avaMgr->expects($this->once())
			->method('getAvatar')
			->with($this->equalTo('alice'))
			->will($this->returnValue($avatar));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr, $userMgr);

		$user->updateAvatar();
	}

	public function testUpdateAvatarThumbnailPhotoProvided() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$access->expects($this->at(0))
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('jpegPhoto'))
			->will($this->returnValue(false));

		$access->expects($this->at(1))
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('thumbnailPhoto'))
			->will($this->returnValue(array('this is a photo')));

		$access->expects($this->exactly(2))
			->method('readAttribute');

		$image->expects($this->once())
			->method('valid')
			->will($this->returnValue(true));
		$image->expects($this->once())
			->method('width')
			->will($this->returnValue(128));
		$image->expects($this->once())
			->method('height')
			->will($this->returnValue(128));
		$image->expects($this->once())
			->method('centerCrop')
			->will($this->returnValue(true));

		$filesys->expects($this->once())
			->method('isLoaded')
			->will($this->returnValue(true));

		$avatar = $this->createMock('\OCP\IAvatar');
		$avatar->expects($this->once())
			->method('set')
			->with($this->isInstanceOf($image));

		$avaMgr->expects($this->once())
			->method('getAvatar')
			->with($this->equalTo('alice'))
			->will($this->returnValue($avatar));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr, $userMgr);

		$user->updateAvatar();
	}

	public function testUpdateAvatarNotProvided() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$access->expects($this->at(0))
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('jpegPhoto'))
			->will($this->returnValue(false));

		$access->expects($this->at(1))
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('thumbnailPhoto'))
			->will($this->returnValue(false));

		$access->expects($this->exactly(2))
			->method('readAttribute');

		$image->expects($this->never())
			->method('valid');
		$image->expects($this->never())
			->method('width');
		$image->expects($this->never())
			->method('height');
		$image->expects($this->never())
			->method('centerCrop');

		$filesys->expects($this->never())
			->method('isLoaded');

		$avaMgr->expects($this->never())
			->method('getAvatar');

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr, $userMgr);

		$user->updateAvatar();
	}

	public function testUpdateBeforeFirstLogin() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$config->expects($this->at(0))
			->method('getUserValue')
			->with($this->equalTo('alice'), $this->equalTo('user_ldap'),
				$this->equalTo(User::USER_PREFKEY_FIRSTLOGIN),
				$this->equalTo(0))
			->will($this->returnValue(0));

		$config->expects($this->at(1))
			->method('getUserValue')
			->with($this->equalTo('alice'), $this->equalTo('user_ldap'),
				$this->equalTo(User::USER_PREFKEY_LASTREFRESH),
				$this->equalTo(0))
			->will($this->returnValue(0));

		$config->expects($this->exactly(2))
			->method('getUserValue');

		$config->expects($this->never())
			->method('setUserValue');

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr, $userMgr);

		$user->update();
	}

	public function testUpdateAfterFirstLogin() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$config->expects($this->at(0))
			->method('getUserValue')
			->with($this->equalTo('alice'), $this->equalTo('user_ldap'),
				$this->equalTo(User::USER_PREFKEY_FIRSTLOGIN),
				$this->equalTo(0))
			->will($this->returnValue(1));

		$config->expects($this->at(1))
			->method('getUserValue')
			->with($this->equalTo('alice'), $this->equalTo('user_ldap'),
				$this->equalTo(User::USER_PREFKEY_LASTREFRESH),
				$this->equalTo(0))
			->will($this->returnValue(0));

		$config->expects($this->exactly(2))
			->method('getUserValue');

		$config->expects($this->once())
			->method('setUserValue')
			->with($this->equalTo('alice'), $this->equalTo('user_ldap'),
				$this->equalTo(User::USER_PREFKEY_LASTREFRESH),
				$this->anything())
			->will($this->returnValue(true));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr, $userMgr);

		$user->update();
	}

	public function testUpdateNoRefresh() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$config->expects($this->at(0))
			->method('getUserValue')
			->with($this->equalTo('alice'), $this->equalTo('user_ldap'),
				$this->equalTo(User::USER_PREFKEY_FIRSTLOGIN),
				$this->equalTo(0))
			->will($this->returnValue(1));

		$config->expects($this->at(1))
			->method('getUserValue')
			->with($this->equalTo('alice'), $this->equalTo('user_ldap'),
				$this->equalTo(User::USER_PREFKEY_LASTREFRESH),
				$this->equalTo(0))
			->will($this->returnValue(time()));

		$config->expects($this->exactly(2))
			->method('getUserValue');

		$config->expects($this->never())
			->method('setUserValue');

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr, $userMgr);

		$user->update();
	}

	public function testMarkLogin() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $db, $userMgr) =
			$this->getTestInstances();

		$config->expects($this->once())
			->method('setUserValue')
			->with($this->equalTo('alice'),
				$this->equalTo('user_ldap'),
				$this->equalTo(User::USER_PREFKEY_FIRSTLOGIN),
				$this->equalTo(1))
			->will($this->returnValue(true));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr, $userMgr);

		$user->markLogin();
	}

	public function testGetAvatarImageProvided() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $db, $userMgr) =
			$this->getTestInstances();

		$access->expects($this->once())
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('jpegPhoto'))
			->will($this->returnValue(array('this is a photo')));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr, $userMgr);

		$photo = $user->getAvatarImage();
		$this->assertSame('this is a photo', $photo);
		//make sure readAttribute is not called again but the already fetched
		//photo is returned
		$photo = $user->getAvatarImage();
	}

	public function testProcessAttributes() {
		list(, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$uid = 'alice';
		$dn = 'uid=alice';

		$requiredMethods = array(
			'markRefreshTime',
			'updateQuota',
			'updateEmail',
			'updateSearchAttributes',
			'composeAndStoreDisplayName',
			'getHomePath',
			'updateAvatar'
		);

		$userMock = $this->getMockBuilder('OCA\User_LDAP\User\User')
			->setConstructorArgs(array($uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr, $userMgr))
			->setMethods($requiredMethods)
			->getMock();

		$connection->setConfiguration(array(
			'homeFolderNamingRule' => 'homeDirectory'
		));

		$connection->expects($this->any())
			->method('__get')
			//->will($this->returnArgument(0));
			->will($this->returnCallback(function($name) {
				if($name === 'homeFolderNamingRule') {
					return 'attr:homeDirectory';
				}
				if($name === 'ldapAttributesForUserSearch') {
					return ['uidNumber'];
				}
				return $name;
			}));

		$record = array(
			strtolower($connection->ldapQuotaAttribute) => array('4096'),
			strtolower($connection->ldapEmailAttribute) => array('alice@wonderland.org'),
			strtolower($connection->ldapUserDisplayName) => array('Aaaaalice'),
			strtolower($connection->ldapAttributesForUserSearch[0]) => '12345',
			'uid' => array($uid),
			'homedirectory' => array('Alice\'s Folder'),
			'memberof' => array('cn=groupOne', 'cn=groupTwo'),
			'jpegphoto' => array('here be an image')
		);

		foreach($requiredMethods as $method) {
			$userMock->expects($this->once())
				->method($method);
		}

		$userMock->processAttributes($record);
		\OC_Hook::emit('OC_User', 'post_login', array('uid' => $uid));
	}

	public function emptyHomeFolderAttributeValueProvider() {
		return array(
			'empty' => array(''),
			'prefixOnly' => array('attr:'),
		);
	}

	/**
	 * @dataProvider emptyHomeFolderAttributeValueProvider
	 */
	public function testGetHomePathNotConfigured($attributeValue) {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$connection->expects($this->any())
			->method('__get')
			->with($this->equalTo('homeFolderNamingRule'))
			->will($this->returnValue($attributeValue));

		$access->expects($this->never())
			->method('readAttribute');

		$config->expects($this->never())
			->method('getAppValue');

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr, $userMgr);

		$path = $user->getHomePath();
		$this->assertSame($path, false);
	}

	public function testGetHomePathConfiguredNotAvailableAllowed() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$connection->expects($this->any())
			->method('__get')
			->with($this->equalTo('homeFolderNamingRule'))
			->will($this->returnValue('attr:foobar'));

		$access->expects($this->once())
			->method('readAttribute')
			->will($this->returnValue(false));

		// asks for "enforce_home_folder_naming_rule"
		$config->expects($this->once())
			->method('getAppValue')
			->will($this->returnValue(false));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr, $userMgr);

		$path = $user->getHomePath();

		$this->assertSame($path, false);
	}

	/**
	 * @expectedException \Exception
	 */
	public function testGetHomePathConfiguredNotAvailableNotAllowed() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc, $userMgr);

		$connection->expects($this->any())
			->method('__get')
			->with($this->equalTo('homeFolderNamingRule'))
			->will($this->returnValue('attr:foobar'));

		$access->expects($this->once())
			->method('readAttribute')
			->will($this->returnValue(false));

		// asks for "enforce_home_folder_naming_rule"
		$config->expects($this->once())
			->method('getAppValue')
			->will($this->returnValue(true));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr, $userMgr);

		$user->getHomePath();
	}

	public function displayNameProvider() {
		return [
			['Roland Deschain', '', 'Roland Deschain'],
			['Roland Deschain', null, 'Roland Deschain'],
			['Roland Deschain', 'gunslinger@darktower.com', 'Roland Deschain (gunslinger@darktower.com)'],
		];
	}

	/**
	 * @dataProvider displayNameProvider
	 */
	public function testComposeAndStoreDisplayName($part1, $part2, $expected) {
		list($access, $config, $filesys, $image, $log, $avaMgr, , $userMgr) =
			$this->getTestInstances();

		$user = new User(
			'user', 'cn=user', $access, $config, $filesys, $image, $log, $avaMgr, $userMgr);

		$displayName = $user->composeAndStoreDisplayName($part1, $part2);
		$this->assertSame($expected, $displayName);
	}
}
