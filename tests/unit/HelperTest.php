<?php
/**
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
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

/**
 * Class HelperTest
 *
 * @package OCA\User_LDAP
 */
class HelperTest extends \Test\TestCase {
	public function getNormalizeDNTests() {
		return [
			'dn with \,' => [
				'cn=\\Allison\, Alice \"Allie\", cn=Users \#\+\;\<\=\>, dc=example, dc=com',
				'cn=\\allison\, alice \"allie\",cn=users \#\+\;\<\=\>,dc=example,dc=com',
			],
			'dn with \2c,' => [
				'cn=\5cAllison\2c Alice \22Allie\22, cn=Users \23\2b\3b\3c\3d\3e, dc=example, dc=com',
				'cn=\\\\allison\, alice \"allie\",cn=users \#\+\;\<\=\>,dc=example,dc=com',
			],
			'multibyte utf8' => [
				'cn=\\Allison\, Alice \"\f0\9f\92\a9\", cn=Users \#\+\;\<\=\>, dc=example, dc=com',
				'cn=\\allison\, alice \"💩\",cn=users \#\+\;\<\=\>,dc=example,dc=com',
			],
		];
	}

	/**
	 * @dataProvider getNormalizeDNTests
	 *
	 * @param $value
	 * @param $expected
	 */
	public function testNormalizeDN($value, $expected) {
		self::assertSame($expected, Helper::normalizeDN($value));
	}
}
