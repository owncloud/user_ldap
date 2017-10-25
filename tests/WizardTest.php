<?php
/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Viktor Szépe <viktor@szepe.net>
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
use OCA\User_LDAP\Configuration;
use OCA\User_LDAP\ILDAPWrapper;
use \OCA\User_LDAP\Wizard;

/**
 * Class Test_Wizard
 *
 * @group DB
 *
 * @package OCA\User_LDAP\Tests
 */
class WizardTest extends \Test\TestCase {

	/**
	 * @var Configuration|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $configuration;

	/**
	 * @var ILDAPWrapper|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $ldap;

	/**
	 * @var Access|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $access;

	/**
	 * @var Wizard
	 */
	protected $wizard;

	protected function setUp() {
		parent::setUp();
		//we need to make sure the consts are defined, otherwise tests will fail
		//on systems without php5_ldap
		$ldapConsts = array('LDAP_OPT_PROTOCOL_VERSION',
							'LDAP_OPT_REFERRALS', 'LDAP_OPT_NETWORK_TIMEOUT');
		foreach($ldapConsts as $const) {
			if(!defined($const)) {
				define($const, 42);
			}
		}
		$this->configuration = $this->createMock(Configuration::class);

		// TODO refactor Configuration class as a POPO with defaults, then use it instead of building a mock?
		$this->configuration->expects($this->any())
			->method('__get')
			->will($this->returnCallback(function ($method) {
				switch ($method) {
					// multiline attributes are arrays
					case 'ldapBase':
						return ['dc=foobar,dc=bar'];
					case 'ldapBaseUsers':
						return ['dc=foobar,dc=bar'];
					case 'ldapAttributesForUserSearch':
						return [];
					// other attributes
					case 'ldapHost':
						return 'localhost';
					case 'ldapPort':
						return 369;
					case 'ldapEmailAttribute':
						return 'myEmailAttribute';
					case 'homeFolderNamingRule':
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

		$this->ldap = $this->createMock(ILDAPWrapper::class);
		$this->ldap->expects($this->any())
			->method('hasPagedResultSupport')
			->will($this->returnValue(false));

		$this->access = $this->createMock(Access::class);

		$this->wizard = new Wizard($this->configuration, $this->ldap, $this->access);
	}

	private function prepareLdapWrapperForConnections() {
		$this->ldap->expects($this->once())
			->method('connect')
			//dummy value, usually invalid
			->will($this->returnValue(true));

		$this->ldap->expects($this->exactly(3))
			->method('setOption')
			->will($this->returnValue(true));

		$this->ldap->expects($this->once())
			->method('bind')
			->will($this->returnValue(true));

	}

	public function testCumulativeSearchOnAttributeLimited() {

		$this->prepareLdapWrapperForConnections();

		$this->ldap->expects($this->any())
			->method('isResource')
			->will($this->returnValue(true));

		$this->ldap->expects($this->exactly(2))
			->method('search')
			//dummy value, usually invalid
			->will($this->returnValue(true));

		$this->ldap->expects($this->exactly(2))
			->method('countEntries')
			//an is_resource check will follow, so we need to return a dummy resource
			->will($this->returnValue(23));

		//5 DNs per filter means 2x firstEntry and 8x nextEntry
		$this->ldap->expects($this->exactly(2))
			->method('firstEntry')
			//dummy value, usually invalid
			->will($this->returnValue(true));

		$this->ldap->expects($this->exactly(8))
			->method('nextEntry')
			//dummy value, usually invalid
			->will($this->returnValue(true));

		$this->ldap->expects($this->exactly(10))
			->method('getAttributes')
			//dummy value, usually invalid
			->will($this->returnValue(array('cn' => array('foo'), 'count' => 1)));

		$uidnumber = 1;
		$this->ldap->expects($this->exactly(10))
			->method('getDN')
			//dummy value, usually invalid
			->will($this->returnCallback(function($a, $b) use (&$uidnumber) {
				return $uidnumber++;
			}));

		// The following expectations are the real test
		$filters = array('f1', 'f2', '*');
		$this->wizard->cumulativeSearchOnAttribute($filters, 'cn', 5);
		unset($uidnumber);
	}

	public function testCumulativeSearchOnAttributeUnlimited() {

		$this->prepareLdapWrapperForConnections();

		$uidNumber = 1;
		$this->ldap->expects($this->any())
			->method('isResource')
			->will($this->returnCallback(function($r) use (&$uidNumber) {
				if($r === true) {
					return true;
				}
				if($r % 24 === 0) {
					$uidNumber++;
					return false;
				}
				return true;
			}));

		$this->ldap->expects($this->exactly(2))
			->method('search')
			//dummy value, usually invalid
			->will($this->returnValue(true));

		$this->ldap->expects($this->exactly(2))
			->method('countEntries')
			//an is_resource check will follow, so we need to return a dummy resource
			->will($this->returnValue(23));

		//5 DNs per filter means 2x firstEntry and 8x nextEntry
		$this->ldap->expects($this->exactly(2))
			->method('firstEntry')
			//dummy value, usually invalid
			->will($this->returnCallback(function($r) use (&$uidNumber) {
				return $uidNumber;
			}));

		$this->ldap->expects($this->exactly(46))
			->method('nextEntry')
			//dummy value, usually invalid
			->will($this->returnCallback(function($r) use (&$uidNumber) {
				return $uidNumber;
			}));

		$this->ldap->expects($this->exactly(46))
			->method('getAttributes')
			//dummy value, usually invalid
			->will($this->returnValue(array('cn' => array('foo'), 'count' => 1)));

		$this->ldap->expects($this->exactly(46))
			->method('getDN')
			//dummy value, usually invalid
			->will($this->returnCallback(function($a, $b) use (&$uidNumber) {
				return $uidNumber++;
			}));

		// The following expectations are the real test
		$filters = array('f1', 'f2', '*');
		$this->wizard->cumulativeSearchOnAttribute($filters, 'cn', 0);
		unset($uidNumber);
	}

	public function testDetectEmailAttributeAlreadySet() {
		$this->configuration->expects($this->any())
			->method('__get')
			->will($this->returnCallback(function ($name) {
				if($name === 'ldapEmailAttribute') {
					return 'myEmailAttribute';
				} else {
					//for requirement checks
					return 'let me pass';
				}
			}));

		$this->access->expects($this->once())
			->method('countUsers')
			->will($this->returnValue(42));

		$this->wizard->detectEmailAttribute();
	}

	public function testDetectEmailAttributeOverrideSet() {

		$this->configuration->expects($this->any())
			->method('__get')
			->will($this->returnCallback(function ($name) {
				if($name === 'ldapEmailAttribute') {
					return 'myEmailAttribute';
				} else {
					//for requirement checks
					return 'let me pass';
				}
			}));

		$this->access->expects($this->exactly(3))
			->method('combineFilterWithAnd')
			->will($this->returnCallback(function ($filterParts) {
				return str_replace('=*', '', array_pop($filterParts));
			}));

		$this->access->expects($this->exactly(3))
			->method('countUsers')
			->will($this->returnCallback(function ($filter) {
				if($filter === 'myEmailAttribute') {
					return 0;
				} else if($filter === 'mail') {
					return 3;
				} else if($filter === 'mailPrimaryAddress') {
					return 17;
				}
				throw new \Exception('Untested filter: ' . $filter);
			}));

		$result = $this->wizard->detectEmailAttribute()->getResultArray();
		$this->assertSame('mailPrimaryAddress',
			$result['changes']['ldap_email_attr']);
	}

	public function testDetectEmailAttributeFind() {

		$this->configuration = $this->createMock(Configuration::class);
		$this->configuration->expects($this->any())
			->method('__get')
			->will($this->returnCallback(function ($name) {
				if($name === 'ldapEmailAttribute') {
					return '';
				} else {
					//for requirement checks
					return 'let me pass';
				}
			}));

		$this->access->expects($this->exactly(2))
			->method('combineFilterWithAnd')
			->will($this->returnCallback(function ($filterParts) {
				return str_replace('=*', '', array_pop($filterParts));
			}));

		$this->access->expects($this->exactly(2))
			->method('countUsers')
			->will($this->returnCallback(function ($filter) {
				if($filter === 'myEmailAttribute') {
					return 0;
				} else if($filter === 'mail') {
					return 3;
				} else if($filter === 'mailPrimaryAddress') {
					return 17;
				}
				throw new \Exception('Untested filter: ' . $filter);
			}));

		$this->wizard = new Wizard($this->configuration, $this->ldap, $this->access);
		$result = $this->wizard->detectEmailAttribute()->getResultArray();
		$this->assertSame('mailPrimaryAddress',
			$result['changes']['ldap_email_attr']);
	}

	public function testDetectEmailAttributeFindNothing() {

		$this->configuration->expects($this->any())
			->method('__get')
			->will($this->returnCallback(function ($name) {
				if($name === 'ldapEmailAttribute') {
					return 'myEmailAttribute';
				} else {
					//for requirement checks
					return 'let me pass';
				}
			}));

		$this->access->expects($this->exactly(3))
			->method('combineFilterWithAnd')
			->will($this->returnCallback(function ($filterParts) {
				return str_replace('=*', '', array_pop($filterParts));
			}));

		$this->access->expects($this->exactly(3))
			->method('countUsers')
			->will($this->returnCallback(function ($filter) {
				if($filter === 'myEmailAttribute') {
					return 0;
				} else if($filter === 'mail') {
					return 0;
				} else if($filter === 'mailPrimaryAddress') {
					return 0;
				}
				throw new \Exception('Untested filter: ' . $filter);
			}));

		$result = $this->wizard->detectEmailAttribute();
		$this->assertSame(false, $result->hasChanges());
	}

	public function testCumulativeSearchOnAttributeSkipReadDN() {
		// tests that there is no infinite loop, when skipping already processed
		// DNs (they can be returned multiple times for multiple filters )

		$this->configuration->expects($this->any())
			->method('__get')
			->will($this->returnCallback(function($name) {
					if($name === 'ldapBase') {
						return array('base');
					}
					return null;
			   }));

		$this->prepareLdapWrapperForConnections();

		$this->ldap->expects($this->any())
			->method('isResource')
			->will($this->returnCallback(function($res) {
				return (bool)$res;
			}));

		$this->ldap->expects($this->any())
			->method('search')
			//dummy value, usually invalid
			->will($this->returnValue(true));

		$this->ldap->expects($this->any())
			->method('countEntries')
			//an is_resource check will follow, so we need to return a dummy resource
			->will($this->returnValue(7));

		//5 DNs per filter means 2x firstEntry and 8x nextEntry
		$this->ldap->expects($this->any())
			->method('firstEntry')
			//dummy value, usually invalid
			->will($this->returnValue(1));

		$mark = false;
		// entries return order: 1, 2, 3, 4, 4, 5, 6
		$this->ldap->expects($this->any())
			->method('nextEntry')
			//dummy value, usually invalid
			->will($this->returnCallback(function($a, $prev) use (&$mark) {
				$current = $prev + 1;
				if($current === 7) {
					return false;
				}
				if($prev === 4 && !$mark) {
					$mark = true;
					return 4;
				}
				return $current;
			}));

		$this->ldap->expects($this->any())
			->method('getAttributes')
			//dummy value, usually invalid
			->will($this->returnCallback(function($a, $entry) {
				return array('cn' => array($entry), 'count' => 1);
			}));

		$this->ldap->expects($this->any())
			->method('getDN')
			//dummy value, usually invalid
			->will($this->returnCallback(function($a, $b) {
				return $b;
			}));

		// The following expectations are the real test
		$filters = array('f1', 'f2', '*');
		$resultArray = $this->wizard->cumulativeSearchOnAttribute($filters, 'cn', 0);
		$this->assertSame(6, count($resultArray));
		unset($mark);
	}

}
