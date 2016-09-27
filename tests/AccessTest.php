<?php
/**
 * @author Andreas Fischer <bantu@owncloud.com>
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

namespace OCA\User_LDAP\Tests;

use OCA\User_LDAP\Access;
use OCA\User_LDAP\Connection;

/**
 * Class AccessTest
 *
 * @group DB
 *
 * @package OCA\User_LDAP\Tests
 */
class AccessTest extends \Test\TestCase {
	private function getConnectorAndLdapMock() {
		static $conMethods;
		static $accMethods;
		static $umMethods;

		if(is_null($conMethods) || is_null($accMethods)) {
			$conMethods = get_class_methods('\OCA\User_LDAP\Connection');
			$accMethods = get_class_methods('\OCA\User_LDAP\Access');
			$umMethods  = get_class_methods('\OCA\User_LDAP\User\Manager');
		}
		$lw  = $this->createMock('\OCA\User_LDAP\ILDAPWrapper');
		$connector = $this->getMockBuilder('\OCA\User_LDAP\Connection')
			->setMethods($conMethods)
			->setConstructorArgs([$lw, null, null])
			->getMock();
		$um = $this->getMockBuilder('\OCA\User_LDAP\User\Manager')
			->setMethods($umMethods)
			->setConstructorArgs([
				$this->createMock('\OCP\IConfig'),
				$this->createMock('\OCA\User_LDAP\FilesystemHelper'),
				$this->createMock('\OCA\User_LDAP\LogWrapper'),
				$this->createMock('\OCP\IAvatarManager'),
				$this->createMock('\OCP\Image'),
				$this->createMock('\OCP\IDBConnection'),
				$this->createMock('\OCP\IUserManager')
			])
			->getMock();

		return [$lw, $connector, $um];
	}

	public function testEscapeFilterPartValidChars() {
		list($lw, $con, $um) = $this->getConnectorAndLdapMock();
		$access = new Access($con, $lw, $um);

		$input = 'okay';
		$this->assertTrue($input === $access->escapeFilterPart($input));
	}

	public function testEscapeFilterPartEscapeWildcard() {
		list($lw, $con, $um) = $this->getConnectorAndLdapMock();
		$access = new Access($con, $lw, $um);

		$input = '*';
		$expected = '\\\\*';
		$this->assertTrue($expected === $access->escapeFilterPart($input));
	}

	public function testEscapeFilterPartEscapeWildcard2() {
		list($lw, $con, $um) = $this->getConnectorAndLdapMock();
		$access = new Access($con, $lw, $um);

		$input = 'foo*bar';
		$expected = 'foo\\\\*bar';
		$this->assertTrue($expected === $access->escapeFilterPart($input));
	}

	/** @dataProvider convertSID2StrSuccessData */
	public function testConvertSID2StrSuccess(array $sidArray, $sidExpected) {
		list($lw, $con, $um) = $this->getConnectorAndLdapMock();
		$access = new Access($con, $lw, $um);

		$sidBinary = implode('', $sidArray);
		$this->assertSame($sidExpected, $access->convertSID2Str($sidBinary));
	}

	public function convertSID2StrSuccessData() {
		return array(
			array(
				array(
					"\x01",
					"\x04",
					"\x00\x00\x00\x00\x00\x05",
					"\x15\x00\x00\x00",
					"\xa6\x81\xe5\x0e",
					"\x4d\x6c\x6c\x2b",
					"\xca\x32\x05\x5f",
				),
				'S-1-5-21-249921958-728525901-1594176202',
			),
			array(
				array(
					"\x01",
					"\x02",
					"\xFF\xFF\xFF\xFF\xFF\xFF",
					"\xFF\xFF\xFF\xFF",
					"\xFF\xFF\xFF\xFF",
				),
				'S-1-281474976710655-4294967295-4294967295',
			),
		);
	}

	public function testConvertSID2StrInputError() {
		list($lw, $con, $um) = $this->getConnectorAndLdapMock();
		$access = new Access($con, $lw, $um);

		$sidIllegal = 'foobar';
		$sidExpected = '';

		$this->assertSame($sidExpected, $access->convertSID2Str($sidIllegal));
	}

	public function testGetDomainDNFromDNSuccess() {
		list($lw, $con, $um) = $this->getConnectorAndLdapMock();
		$access = new Access($con, $lw, $um);

		$inputDN = 'uid=zaphod,cn=foobar,dc=my,dc=server,dc=com';
		$domainDN = 'dc=my,dc=server,dc=com';

		$lw->expects($this->once())
			->method('explodeDN')
			->with($inputDN, 0)
			->will($this->returnValue(explode(',', $inputDN)));

		$this->assertSame($domainDN, $access->getDomainDNFromDN($inputDN));
	}

	public function testGetDomainDNFromDNError() {
		list($lw, $con, $um) = $this->getConnectorAndLdapMock();
		$access = new Access($con, $lw, $um);

		$inputDN = 'foobar';
		$expected = '';

		$lw->expects($this->once())
			->method('explodeDN')
			->with($inputDN, 0)
			->will($this->returnValue(false));

		$this->assertSame($expected, $access->getDomainDNFromDN($inputDN));
	}

	private function getResemblesDNInputData() {
		return  $cases = array(
			array(
				'input' => 'foo=bar,bar=foo,dc=foobar',
				'interResult' => array(
					'count' => 3,
					0 => 'foo=bar',
					1 => 'bar=foo',
					2 => 'dc=foobar'
				),
				'expectedResult' => true
			),
			array(
				'input' => 'foobarbarfoodcfoobar',
				'interResult' => false,
				'expectedResult' => false
			)
		);
	}

	public function testStringResemblesDN() {
		list($lw, $con, $um) = $this->getConnectorAndLdapMock();
		$access = new Access($con, $lw, $um);

		$cases = $this->getResemblesDNInputData();

		$lw->expects($this->exactly(2))
			->method('explodeDN')
			->will($this->returnCallback(function ($dn) use ($cases) {
				foreach($cases as $case) {
					if($dn === $case['input']) {
						return $case['interResult'];
					}
				}
				return null;
			}));

		foreach($cases as $case) {
			$this->assertSame($case['expectedResult'], $access->stringResemblesDN($case['input']));
		}
	}

	public function testStringResemblesDNLDAPmod() {
		list($lw, $con, $um) = $this->getConnectorAndLdapMock();
		$lw = new \OCA\User_LDAP\LDAP();
		$access = new Access($con, $lw, $um);

		if(!function_exists('ldap_explode_dn')) {
			$this->markTestSkipped('LDAP Module not available');
		}

		$cases = $this->getResemblesDNInputData();

		foreach($cases as $case) {
			$this->assertSame($case['expectedResult'], $access->stringResemblesDN($case['input']));
		}
	}

	public function testCacheUserHome() {
		list($lw, $con, $um) = $this->getConnectorAndLdapMock();
		$access = new Access($con, $lw, $um);

		$con->expects($this->once())
			->method('writeToCache');

		$access->cacheUserHome('foobar', '/foobars/path');
	}

	public function testBatchApplyUserAttributes() {
		list($lw, $con, $um) = $this->getConnectorAndLdapMock();
		$access = new Access($con, $lw, $um);
		$mapperMock = $this->getMockBuilder('\OCA\User_LDAP\Mapping\UserMapping')
			->disableOriginalConstructor()
			->getMock();

		$mapperMock->expects($this->any())
			->method('getNameByDN')
			->will($this->returnValue('a_username'));

		$userMock = $this->getMockBuilder('\OCA\User_LDAP\User\User')
			->disableOriginalConstructor()
			->getMock();

		$access->connection->expects($this->any())
			->method('__get')
			->will($this->returnValue('displayName'));

		$access->setUserMapper($mapperMock);

		$displayNameAttribute = strtolower($access->connection->ldapUserDisplayName);
		$data = array(
			array(
				'dn' => 'foobar',
				$displayNameAttribute => 'barfoo'
			),
			array(
				'dn' => 'foo',
				$displayNameAttribute => 'bar'
			),
			array(
				'dn' => 'raboof',
				$displayNameAttribute => 'oofrab'
			)
		);

		$userMock->expects($this->exactly(count($data)))
			->method('processAttributes');

		$um->expects($this->exactly(count($data)))
			->method('get')
			->will($this->returnValue($userMock));

		$access->batchApplyUserAttributes($data);
	}

	public function dNAttributeProvider() {
		// corresponds to Access::resemblesDN()
		return array(
			'dn' => array('dn'),
			'uniqueMember' => array('uniquemember'),
			'member' => array('member'),
			'memberOf' => array('memberof')
		);
	}

	/**
	 * @dataProvider dNAttributeProvider
	 */
	public function testSanitizeDN($attribute) {
		list($lw, $con, $um) = $this->getConnectorAndLdapMock();


		$dnFromServer = 'cn=Mixed Cases,ou=Are Sufficient To,ou=Test,dc=example,dc=org';

		$lw->expects($this->any())
			->method('isResource')
			->will($this->returnValue(true));

		$lw->expects($this->any())
			->method('getAttributes')
			->will($this->returnValue(array(
				$attribute => array('count' => 1, $dnFromServer)
			)));

		$access = new Access($con, $lw, $um);
		$values = $access->readAttribute('uid=whoever,dc=example,dc=org', $attribute);
		$this->assertSame($values[0], strtolower($dnFromServer));
	}
}
