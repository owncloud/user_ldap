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

use OCA\User_LDAP\Attributes\ConverterHub;
use OCA\User_LDAP\Attributes\IConverter;
use OCA\User_LDAP\Attributes\ConverterException;

class ConverterHubTest extends \Test\TestCase {
	/** @var ConverterHub */
	private $converterHub;

	protected function setUp(): void {
		parent::setUp();
		$this->converterHub = new ConverterHub();
	}

	public function testHasConverterFail() {
		$this->assertFalse($this->converterHub->hasConverter('aCustomMissingAttr'));
	}

	public function testRegisterAndHasConverter() {
		$converter = $this->createMock(IConverter::class);
		$this->assertFalse($this->converterHub->hasConverter('customAttr'));
		$this->converterHub->registerConverter('customAttr', $converter);
		$this->assertTrue($this->converterHub->hasConverter('customAttr'));
	}

	public function testRegisterDefaultConverters() {
		$this->converterHub->registerDefaultConverters();
		$this->assertTrue($this->converterHub->hasConverter('objectguid'));
		$this->assertTrue($this->converterHub->hasConverter('guid'));
		$this->assertTrue($this->converterHub->hasConverter('objectsid'));
	}

	public function testClearConverters() {
		$converter = $this->createMock(IConverter::class);
		$converter2 = $this->createMock(IConverter::class);
		$this->converterHub->registerConverter('customAttr', $converter);
		$this->converterHub->registerConverter('customAttr2', $converter2);
		$this->assertTrue($this->converterHub->hasConverter('customAttr'));
		$this->assertTrue($this->converterHub->hasConverter('customAttr2'));
		$this->converterHub->clearConverters();
		$this->assertFalse($this->converterHub->hasConverter('customAttr'));
		$this->assertFalse($this->converterHub->hasConverter('customAttr2'));
	}

	public function testBin2strException() {
		$this->expectException(ConverterException::class);
		$this->converterHub->bin2str('missingAttr', 'binary value');
	}

	public function testBin2strException2() {
		$this->expectException(ConverterException::class);

		$testString = 'binary string';

		$converter = $this->createMock(IConverter::class);
		$converter->expects($this->once())
			->method('bin2str')
			->with($testString)
			->will($this->returnCallback(static function ($value) {
				throw new ConverterException('Something happended in the converter');
			}));

		$this->converterHub->registerConverter('myAttr', $converter);
		$this->converterHub->bin2str('myAttr', $testString);
	}

	public function testBin2str() {
		$testString = 'binary string';

		$converter = $this->createMock(IConverter::class);
		$converter->expects($this->once())
			->method('bin2str')
			->with($testString)
			->will($this->returnCallback(static function ($value) {
				return \base64_encode($value);
			}));

		$this->converterHub->registerConverter('myAttr', $converter);
		$this->assertSame(\base64_encode($testString), $this->converterHub->bin2str('myAttr', $testString));
	}

	public function testStr2filterException() {
		$this->expectException(ConverterException::class);
		$this->converterHub->str2filter('missingAttr', 'binary value');
	}

	public function testStr2filterException2() {
		$this->expectException(ConverterException::class);

		$testString = 'binary string';

		$converter = $this->createMock(IConverter::class);
		$converter->expects($this->once())
			->method('str2filter')
			->with($testString)
			->will($this->returnCallback(static function ($value) {
				throw new ConverterException('Something happended in the converter');
			}));

		$this->converterHub->registerConverter('myAttr', $converter);
		$this->converterHub->str2filter('myAttr', $testString);
	}

	public function testStr2filter() {
		$testString = 'binary string';

		$converter = $this->createMock(IConverter::class);
		$converter->expects($this->once())
			->method('str2filter')
			->with($testString)
			->will($this->returnCallback(static function ($value) {
				return \strrev($value);
			}));

		$this->converterHub->registerConverter('myAttr', $converter);
		$this->assertSame(\strrev($testString), $this->converterHub->str2filter('myAttr', $testString));
	}
}
