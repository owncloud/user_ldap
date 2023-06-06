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

namespace OCA\User_LDAP\Controller;

use OCA\User_LDAP\Helper;
use OCA\User_LDAP\LDAP;
use OCP\AppFramework\Http\DataResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ISession;
use Test\TestCase;

/**
 * Class ConfigurationControllerTest
 *
 * @group DB
 * @package OCA\User_LDAP\Controller
 */
class ConfigurationControllerTest extends TestCase {
	/** @var IRequest|\PHPUnit\Framework\MockObject\MockObject */
	private $request;
	/** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
	private $config;
	/** @var ISession|\PHPUnit\Framework\MockObject\MockObject */
	private $session;
	/** @var IL10N|\PHPUnit\Framework\MockObject\MockObject */
	private $l10n;
	/** @var LDAP|\PHPUnit\Framework\MockObject\MockObject */
	private $ldap;
	/** @var Helper|\PHPUnit\Framework\MockObject\MockObject */
	private $helper;

	/** @var ConfigurationController */
	private $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->config = $this->createMock(IConfig::class);
		$this->session = $this->createMock(ISession::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->ldap = $this->createMock(LDAP::class);
		$this->helper = $this->createMock(Helper::class);

		$this->controller = new ConfigurationController(
			'user_ldap',
			$this->request,
			$this->config,
			$this->session,
			$this->l10n,
			$this->ldap,
			$this->helper
		);
	}

	/**
	 * asserts whether array is subset of another array
	 *
	 * @param array $subset
	 * @param array $array
	 *
	 * @return void
	 * @throws \InvalidArgumentException
	 */
	private function assertArrayIsSubsetOf($subset, $array) {
		foreach ($subset as $key => $value) {
			$this->assertArrayHasKey($key, $array);
			if (\is_array($value)) {
				$this->assertArrayIsSubsetOf($value, $array[$key]);
			} else {
				$this->assertEquals(
					$value,
					$array[$key],
					"Expected $value but found: $array[$key]"
				);
			}
		}
	}

	public function testCreate() {
		$this->helper->expects($this->once())
			->method('nextPossibleConfigurationPrefix')
			->will($this->returnValue('tcr'));

		$this->config->expects($this->any())
			->method('setAppValue')
			->with('user_ldap', $this->stringStartsWith('tcr'), $this->anything());

		$result = $this->controller->create();

		$this->assertInstanceOf(DataResponse::class, $result);
		$data = $result->getData();
		$this->assertArrayIsSubsetOf([
			'status' => 'success',
			'configPrefix' => 'tcr',
			'defaults' => []
		], $data);
	}

	public function testCreateWithCopy() {
		$this->helper->expects($this->once())
			->method('nextPossibleConfigurationPrefix')
			->will($this->returnValue('tgt'));

		$this->config->expects($this->any())
			->method('getAppValue')
			->will($this->returnCallback(function ($app, $key, $default) {
				switch ($key) {
					case 'srcldap_host':
						return 'example.org';
					case 'srcldap_agent_password':
						return \base64_encode('secret');
					default:
						return $default;
				}
			}));

		$expectedValue = null;
		$this->config->expects($this->any())
			->method('setAppValue')
			->with(
				'user_ldap',
				$this->callback(function ($key) use (&$expectedValue) {
					switch ($key) {
						case 'tgtldap_host':
							$expectedValue = 'example.org';
							break;
						case 'tgtldap_agent_password':
							$expectedValue = \base64_encode('secret');
							break;
						default: $expectedValue = null;
					};
					return \strpos($key, 'tgt') === 0;
				}),
				$this->callback(function ($value) use (&$expectedValue) {
					if ($expectedValue !== null) {
						return $expectedValue === $value;
					};
					return true;
				})
			);

		$result = $this->controller->create('src');

		$this->assertInstanceOf(DataResponse::class, $result);
		$data = $result->getData();
		$this->assertArrayIsSubsetOf([
			'status' => 'success',
			'configPrefix' => 'tgt'
		], $data, true);
	}

	public function testRead() {
		$this->config->expects($this->any())
			->method('getAppValue')
			->will($this->returnCallback(function ($app, $key, $default) {
				switch ($key) {
					case 't01ldap_host':
						return 'example.org';
					case 't01ldap_agent_password':
						return  \base64_encode('secret');
					default:
						return $default;
				}
			}));

		$result = $this->controller->read('t01');

		$this->assertInstanceOf(DataResponse::class, $result);
		$data = $result->getData();
		$this->assertArrayIsSubsetOf([
			'status' => 'success',
			'configuration' => [
				'ldap_host' => 'example.org',
				'ldap_agent_password' => '**PASSWORD SET**'
			]
		], $data, true);
	}

	public function testTest() {
		// use valid looking config to pass critical validation
		$this->config->expects($this->any())
			->method('getAppValue')
			->will($this->returnCallback(function ($app, $key, $default) {
				switch ($key) {
					case 't01ldap_host':
						return 'example.org';
					case 't01ldap_port':
						return '389';
					case 't01ldap_display_name':
						return 'displayName';
					case 't01ldap_group_display_name':
						return 'cn';
					case 't01ldap_login_filter':
						return '(uid=%uid)';
					case 't01ldap_configuration_active':
						return '1';
					case 't01ldap_dn':
						return  'cn=admin';
					case 't01ldap_base':
						return  'dc=example,dc=org';
					case 't01ldap_agent_password':
						return  \base64_encode('secret');
					default:
						return $default;
				}
			}));

		$this->ldap->expects($this->any())
			->method('areLDAPFunctionsAvailable')
			->will($this->returnValue(true));

		$this->ldap->expects($this->once())
			->method('connect')
			->will($this->returnValue('ldapResource'));

		$this->ldap->expects($this->any())
			->method('isResource')
			->will($this->returnValue(true));

		$this->ldap->expects($this->any())
			->method('setOption')
			->will($this->returnValue(true));

		$this->ldap->expects($this->once())
			->method('bind')
			->will($this->returnValue(true));

		$this->ldap->expects($this->once())
			->method('read')
			->will($this->returnValue(['dn'=>'dummy']));

		$result = $this->controller->test('t01');

		$this->assertInstanceOf(DataResponse::class, $result);
		$data = $result->getData();
		$this->assertArrayIsSubsetOf(['status' => 'success'], $data, true);
	}

	//public function testDelete() {
	// TODO implement me!
	//}

	public function testDeleteNotExisting() {
		$result = $this->controller->delete('na');

		$this->assertInstanceOf(DataResponse::class, $result);
		$data = $result->getData();
		$this->assertArrayIsSubsetOf(['status' => 'error'], $data, true);
	}
}
