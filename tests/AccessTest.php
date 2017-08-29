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
use OCA\User_LDAP\ILDAPWrapper;
use OCA\User_LDAP\LDAP;
use OCA\User_LDAP\User\Manager;

/**
 * Class AccessTest
 *
 * @group DB
 *
 * @package OCA\User_LDAP\Tests
 */
class AccessTest extends \Test\TestCase {

	/**
	 * @var ILDAPWrapper|\PHPUnit_Framework_MockObject_MockObject
	 */
	private $ldapWrapper;

	/**
	 * @var Connection|\PHPUnit_Framework_MockObject_MockObject
	 */
	private $connection;

	/**
	 * @var Manager|\PHPUnit_Framework_MockObject_MockObject
	 */
	private $manager;

	/**
	 * @var Access|\PHPUnit_Framework_MockObject_MockObject
	 */
	private $access;

	public function setUp() {
		$this->ldapWrapper  = $this->createMock(ILDAPWrapper::class);
		$this->connection  = $this->createMock(Connection::class);
		$this->manager  = $this->createMock(Manager::class);
		$this->access = new Access($this->connection, $this->manager);

	}

	/**
	 * @dataProvider escapeFilterPartDataProvider
	 * @param $input string
	 * @param $expected string
	 */
	public function testEscapeFilterPartValidChars($input, $expected) {
		$this->assertSame($expected, $this->access->escapeFilterPart($input));
	}

	public function escapeFilterPartDataProvider () {
		return [
			['okay', 'okay'],
			['*', '\\\\*'], // escape wildcard
			['foo*bar', 'foo\\\\*bar'], // escape wildcard in valid chars
		];
	}

	/**
	 * @dataProvider convertSID2StrDataProvider
	 * @param $sidBinary string
	 * @param $sidExpected string
	 */
	public function testConvertSID2StrSuccess($sidBinary, $sidExpected) {
		$this->assertSame($sidExpected, $this->access->convertSID2Str($sidBinary));
	}

	public function convertSID2StrDataProvider() {
		return [
			// test valid mappings
			[
				implode('', [
					"\x01",
					"\x04",
					"\x00\x00\x00\x00\x00\x05",
					"\x15\x00\x00\x00",
					"\xa6\x81\xe5\x0e",
					"\x4d\x6c\x6c\x2b",
					"\xca\x32\x05\x5f",
				]),
				'S-1-5-21-249921958-728525901-1594176202',
			],
			[
				implode('', [
					"\x01",
					"\x02",
					"\xFF\xFF\xFF\xFF\xFF\xFF",
					"\xFF\xFF\xFF\xFF",
					"\xFF\xFF\xFF\xFF",
				]),
				'S-1-281474976710655-4294967295-4294967295',
			],
			// input error
			['foobar', ''], // TODO should throw an exception and not silently return emptystring
		];
	}

	public function testGetDomainDNFromDNSuccess() {
		$inputDN = 'uid=zaphod,cn=foobar,dc=my,dc=server,dc=com';
		$domainDN = 'dc=my,dc=server,dc=com';

		$this->ldapWrapper->expects($this->once())
			->method('explodeDN')
			->with($inputDN, 0)
			->will($this->returnValue(explode(',', $inputDN)));

		$this->connection->expects($this->any())
			->method('getLDAP')
			->willReturn($this->ldapWrapper);

		$this->assertSame($domainDN, $this->access->getDomainDNFromDN($inputDN));
	}

	public function testGetDomainDNFromDNError() {
		$inputDN = 'foobar';
		$expected = '';

		$this->ldapWrapper->expects($this->once())
			->method('explodeDN')
			->with($inputDN, 0)
			->will($this->returnValue(false));

		$this->connection->expects($this->any())
			->method('getLDAP')
			->willReturn($this->ldapWrapper);

		$this->assertSame($expected, $this->access->getDomainDNFromDN($inputDN));
	}

	public function resemblesDNDataProvider() {
		return [
			[
				'CN=username,OU=UNITNAME,OU=Region,OU=Country,DC=subdomain,DC=domain,DC=com',
				[
					'count' => 9,
					 0 => 'CN=username',
					 1 => 'OU=UNITNAME',
					 2 => 'OU=Region',
					 5 => 'OU=Country',
					 6 => 'DC=subdomain',
					 7 => 'DC=domain',
					 8 => 'DC=com',
				],
				true
			],
			[
				'foo=bar,bar=foo,dc=foobar',
				[
					'count' => 3,
					0 => 'foo=bar',
					1 => 'bar=foo',
					2 => 'dc=foobar'
				],
				true
			],
			[
				'foobarbarfoodcfoobar', false, false
			]
		];
	}

	/**
	 * @dataProvider resemblesDNDataProvider
	 * @param $input string
	 * @param $intermediateResult string
	 * @param $expected string
	 */
	public function testStringResemblesDNfake($input, $intermediateResult, $expected) {

		$this->ldapWrapper->expects($this->once())
			->method('explodeDN')
			->with($input)
			->will($this->returnValue($intermediateResult));

		$this->connection->expects($this->any())
			->method('getLDAP')
			->willReturn($this->ldapWrapper);

		$this->assertSame($expected, $this->access->stringResemblesDN($input));
	}

	/**
	 * @dataProvider resemblesDNDataProvider
	 * @param $input string
	 * @param $intermediateResult string
	 * @param $expected string
	 */
	public function testStringResemblesDNLDAPnative($input, $intermediateResult, $expected) {
		if(!function_exists('ldap_explode_dn')) {
			$this->markTestSkipped('LDAP Module not available');
		}

		$this->connection->expects($this->once())
			->method('getLDAP')
			->willReturn(new LDAP());

		$this->assertSame($expected, $this->access->stringResemblesDN($input));
	}

	public function testCacheUserHome() {

		$this->connection->expects($this->once())
			->method('writeToCache');

		$this->access->cacheUserHome('foobar', '/foobars/path');
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
		$dnFromServer = 'cn=Mixed Cases,ou=Are Sufficient To,ou=Test,dc=example,dc=org';

		$this->ldapWrapper->expects($this->any())
			->method('isResource')
			->will($this->returnValue(true));

		$this->ldapWrapper->expects($this->any())
			->method('getAttributes')
			->will($this->returnValue(array(
				$attribute => array('count' => 1, $dnFromServer)
			)));

		$this->connection->expects($this->any())
			->method('getLDAP')
			->willReturn($this->ldapWrapper);

		$values = $this->access->readAttribute('uid=whoever,dc=example,dc=org', $attribute);
		$this->assertSame($values[0], strtolower($dnFromServer));
	}
}
