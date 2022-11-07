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

use OCA\User_LDAP\Attributes\GUIDConverter;
use OCA\User_LDAP\Attributes\ConverterException;

class GUIDConverterTest extends \Test\TestCase {
	/** @var GUIDConverter */
	private $guidConverter;

	protected function setUp(): void {
		parent::setUp();
		$this->guidConverter = new GUIDConverter();
	}

	public function bin2strProvider() {
		return [
			// 8A A0 73 BE 45 0A 3C 42 AC CD 33 F3 18 8C 74 6F
			["\x8A\xA0\x73\xBE\x45\x0A\x3C\x42\xAC\xCD\x33\xF3\x18\x8C\x74\x6F", 'BE73A08A-0A45-423C-ACCD-33F3188C746F'],
			// BE 7A 3E 3F AC 61 53 48 85 D2 3A 7C 81 36 9B DA
			["\xBE\x7A\x3E\x3F\xAC\x61\x53\x48\x85\xD2\x3A\x7C\x81\x36\x9B\xDA", '3F3E7ABE-61AC-4853-85D2-3A7C81369BDA'],
		];
	}

	/**
	 * @dataProvider bin2strProvider
	 */
	public function testBin2str($input, $expected) {
		$this->assertSame($expected, $this->guidConverter->bin2str($input));
	}

	public function bin2strExceptionProvider() {
		return [
			[""],
			["0"],
			["sdlfkj"],
			["1234567890abcdefghijk"],
		];
	}

	/**
	 * @dataProvider bin2strExceptionProvider
	 */
	public function testBin2strException($input) {
		$this->expectException(ConverterException::class);
		$this->guidConverter->bin2str($input);
	}

	public function testBin2strNoConversion() {
		$this->assertSame('BE73A08A-0A45-423C-ACCD-33F3188C746F', $this->guidConverter->bin2str('BE73A08A-0A45-423C-ACCD-33F3188C746F'));
		$this->assertSame('3F3E7ABE-61AC-4853-85D2-3A7C81369BDA', $this->guidConverter->bin2str('3F3E7ABE-61AC-4853-85D2-3A7C81369BDA'));
	}

	public function str2filterProvider() {
		return [
			// 8A A0 73 BE 45 0A 3C 42 AC CD 33 F3 18 8C 74 6F
			['BE73A08A-0A45-423C-ACCD-33F3188C746F', '\8A\A0\73\BE\45\0A\3C\42\AC\CD\33\F3\18\8C\74\6F'],
			// BE 7A 3E 3F AC 61 53 48 85 D2 3A 7C 81 36 9B DA
			['3F3E7ABE-61AC-4853-85D2-3A7C81369BDA', '\BE\7A\3E\3F\AC\61\53\48\85\D2\3A\7C\81\36\9B\DA'],
		];
	}

	/**
	 * @dataProvider str2filterProvider
	 */
	public function testStr2filter($input, $expected) {
		$this->assertSame($expected, $this->guidConverter->str2filter($input));
	}

	public function str2filterExceptionProvider() {
		return [
			[''],
			['BE73A08A'],
			['BE73A08A-0A45'],
			['BE73A08A-0A45-423C'],
			['BE73A08A-0A45-423C-ACCD'],
			['BE73A08A-0A45-423C-ACCD-33F3188C746F-ACDC'],
		];
	}

	/**
	 * @dataProvider str2filterExceptionProvider
	 */
	public function testStr2filterException($input) {
		$this->expectException(ConverterException::class);
		$this->guidConverter->str2filter($input);
	}
}
