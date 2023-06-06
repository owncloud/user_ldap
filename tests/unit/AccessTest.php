<?php
/**
 * @author Andreas Fischer <bantu@owncloud.com>
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
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

use OCA\User_LDAP\User\Manager;

/**
 * Class AccessTest
 *
 * @group DB
 *
 * @package OCA\User_LDAP
 */
class AccessTest extends \Test\TestCase {
	/**
	 * @var ILDAPWrapper|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $ldapWrapper;

	/**
	 * @var Connection|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $connection;

	/**
	 * @var Manager|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $manager;

	/**
	 * @var Access|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $access;

	public function setUp(): void {
		$this->ldapWrapper  = $this->createMock(ILDAPWrapper::class);
		$this->connection  = $this->createMock(Connection::class);
		$this->manager  = $this->createMock(Manager::class);
		$this->access = new Access($this->connection, $this->manager);
	}

	/**
	 * @dataProvider sanitizeUsernameDataProvider
	 * @param $input string
	 * @param $expected string
	 */
	public function testSanitizeUsername($input, $expected) {
		$this->assertSame($expected, $this->access->sanitizeUsername($input));
	}

	public function sanitizeUsernameDataProvider() {
		return [
			['John-Smith', 'John-Smith'],
			['John.Smith@example.com', 'John.Smith@example.com'],
			['John+Smith@example.com', 'John+Smith@example.com'],
			['John_Smith', 'John_Smith'],
			['John Smith', 'John_Smith'],
			['John#Smith', 'JohnSmith'],
			['John.Smith(CEO)', 'John.SmithCEO'],
		];
	}

	/**
	 * @dataProvider escapeFilterPartDataProvider
	 * @param $input string
	 * @param $expected string
	 */
	public function testEscapeFilterPartValidChars($input, $expected) {
		$this->assertSame($expected, $this->access->escapeFilterPart($input));
	}

	public function escapeFilterPartDataProvider() {
		return [
			['okay', 'okay'],
			['*', '\\\\*'], // escape wildcard
			['foo*bar', 'foo\\\\*bar'], // escape wildcard in valid chars
		];
	}

	/**
	 * @dataProvider sid2strDataProvider
	 * @param $sidBinary string
	 * @param $sidExpected string
	 */
	public function testSid2strSuccess($sidBinary, $sidExpected) {
		$this->assertSame($sidExpected, Access::sid2str($sidBinary));
	}

	public function sid2strDataProvider() {
		return [
			// test valid mappings
			[
				\implode('', [
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
				\implode('', [
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
			->will($this->returnValue(\explode(',', $inputDN)));

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

	public function testCacheUserHome() {
		$this->connection->expects($this->once())
			->method('writeToCache');

		$this->access->cacheUserHome('foobar', '/foobars/path');
	}

	public function dNAttributeProvider() {
		// corresponds to Access::resemblesDN()
		return [
			'dn'				=> ['dn',				true],
			'uniqueMember'		=> ['uniquemember',		true],
			'member'			=> ['member',			true],
			'memberOf'			=> ['memberof',			true],
			'samaccountname'	=> ['samaccountname',	false]
		];
	}

	/**
	 * @dataProvider dNAttributeProvider
	 * @param string $attribute
	 * @param bool $expected
	 */
	public function testResemblesDN($attribute, $expected) {
		$actual = self::invokePrivate($this->access, 'resemblesDN', [$attribute]);
		$this->assertSame($expected, $actual);
	}
}
