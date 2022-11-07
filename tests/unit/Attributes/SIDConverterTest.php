<?php
/**
 * @copyright Copyright (c) 2022, ownCloud GmbH.
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

namespace OCA\User_LDAP\Tests\Attributes;

use OCA\User_LDAP\Attributes\SIDConverter;

class SIDConverterTest extends \Test\TestCase {
	/** @var SIDConverter */
	private $sidConverter;

	protected function setUp(): void {
		parent::setUp();
		$this->sidConverter = new SIDConverter();
	}

	public function bin2strProvider() {
		return [
			// 01 05 00 00 00 00 00 05 15 00 00 00 57 9A B7 00 F7 AB 69 48 2F 99 DE C3 4F 04 00 00
			["\x01\x05\x00\x00\x00\x00\x00\x05\x15\x00\x00\x00\x57\x9A\xB7\x00\xF7\xAB\x69\x48\x2F\x99\xDE\xC3\x4F\x04\x00\x00", 'S-1-5-21-12032599-1214884855-3286145327-1103'],
			// 01 05 00 00 00 00 00 05 15 00 00 00 57 9A B7 00 F7 AB 69 48 2F 99 DE C3 F4 01 00 00
			["\x01\x05\x00\x00\x00\x00\x00\x05\x15\x00\x00\x00\x57\x9A\xB7\x00\xF7\xAB\x69\x48\x2F\x99\xDE\xC3\xF4\x01\x00\x00", 'S-1-5-21-12032599-1214884855-3286145327-500'],
			// 01 02 00 00 00 00 00 05 20 00 00 00 22 02 00 00
			["\x01\x02\x00\x00\x00\x00\x00\x05\x20\x00\x00\x00\x22\x02\x00\x00", 'S-1-5-32-546'],
			// 01 01 00 00 00 00 00 05 0B 00 00 00
			["\x01\x01\x00\x00\x00\x00\x00\x05\x0B\x00\x00\x00", 'S-1-5-11'],
		];
	}

	/**
	 * @dataProvider bin2strProvider
	 */
	public function testBin2str($input, $expected) {
		$this->assertSame($expected, $this->sidConverter->bin2str($input));
	}

	public function str2filterProvider() {
		return [
			['S-1-5-21-12032599-1214884855-3286145327-1103', '\01\05\00\00\00\00\00\05\15\00\00\00\57\9A\B7\00\F7\AB\69\48\2F\99\DE\C3\4F\04\00\00'],
			['S-1-5-21-12032599-1214884855-3286145327-500', '\01\05\00\00\00\00\00\05\15\00\00\00\57\9A\B7\00\F7\AB\69\48\2F\99\DE\C3\F4\01\00\00'],
			['S-1-5-32-546', '\01\02\00\00\00\00\00\05\20\00\00\00\22\02\00\00'],
			['S-1-5-11', '\01\01\00\00\00\00\00\05\0B\00\00\00'],
		];
	}

	/**
	 * @dataProvider str2filterProvider
	 */
	public function testStr2filter($input, $expected) {
		$this->assertSame($expected, $this->sidConverter->str2filter($input));
	}
}
