<?php
/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
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

class ConfigurationTest extends \Test\TestCase {
	/** @var Configuration */
	private $configuration;

	protected function setUp(): void {
		parent::setUp();
		$config = \OC::$server->getConfig(); // TODO use mock
		$this->configuration = new Configuration($config, 't01', false);
	}

	public function configurationDataProvider() {
		$inputWithDN = [
			'cn=someUsers,dc=example,dc=org',
			'  ',
			' cn=moreUsers,dc=example,dc=org '
		];
		$expectWithDN = [
			'cn=someUsers,dc=example,dc=org',
			'cn=moreUsers,dc=example,dc=org'
		];

		$inputNames = [
			'  uid  ',
			'cn ',
			' ',
			'',
			' whats my name',
			'	'
		];
		$expectedNames = ['uid', 'cn', 'whats my name'];

		$inputString = ' alea iacta est ';
		$expectedString = 'alea iacta est';

		$inputHomeFolder = [
			' homeDirectory ',
			' attr:homeDirectory ',
			' '
		];

		$expectedHomeFolder = [
			'attr:homeDirectory', 'attr:homeDirectory', ''
		];

		$password = ' such a passw0rd ';

		return [
			'set general base' => ['ldapBase', $inputWithDN, $expectWithDN],
			'set user base'    => ['ldapBaseUsers', $inputWithDN, $expectWithDN],
			'set group base'   => ['ldapBaseGroups', $inputWithDN, $expectWithDN],

			'set search attributes users'  => ['ldapAttributesForUserSearch', $inputNames, $expectedNames],
			'set search attributes groups' => ['ldapAttributesForGroupSearch', $inputNames, $expectedNames],

			'set user filter objectclasses'  => ['ldapUserFilterObjectclass', $inputNames, $expectedNames],
			'set user filter groups'         => ['ldapUserFilterGroups', $inputNames, $expectedNames],
			'set group filter objectclasses' => ['ldapGroupFilterObjectclass', $inputNames, $expectedNames],
			'set group filter groups'        => ['ldapGroupFilterGroups', $inputNames, $expectedNames],
			'set login filter attributes'    => ['ldapLoginFilterAttributes', $inputNames, $expectedNames],

			'set agent password' => ['ldapAgentPassword', $password, $password],

			'set home folder, variant 1' => ['homeFolderNamingRule', $inputHomeFolder[0], $expectedHomeFolder[0]],
			'set home folder, variant 2' => ['homeFolderNamingRule', $inputHomeFolder[1], $expectedHomeFolder[1]],
			'set home folder, empty'     => ['homeFolderNamingRule', $inputHomeFolder[2], $expectedHomeFolder[2]],

			// default behaviour, one case is enough, special needs must be tested
			// individually
			'set string value' => ['ldapHost', $inputString, $expectedString],
		];
	}

	/**
	 * @dataProvider configurationDataProvider
	 */
	public function testSetValue($key, $input, $expected) {
		$this->configuration->setConfiguration([$key => $input]);
		$this->assertSame($this->configuration->$key, $expected);
	}
}
