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

use OCA\User_LDAP\Configuration;
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

	/** @var IRequest|\PHPUnit_Framework_MockObject_MockObject */
	private $request;
	/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject */
	private $config;
	/** @var ISession|\PHPUnit_Framework_MockObject_MockObject */
	private $session;
	/** @var IL10N|\PHPUnit_Framework_MockObject_MockObject */
	private $l10n;
	/** @var LDAP|\PHPUnit_Framework_MockObject_MockObject */
	private $ldap;
	/** @var Helper|\PHPUnit_Framework_MockObject_MockObject */
	private $helper;

	/** @var ConfigurationController */
	private $controller;

	protected function setUp() {
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

	public function testCreate() {
		$this->helper->expects($this->once())
			->method('nextPossibleConfigurationPrefix')
			->will($this->returnValue('tcr'));

		$this->config->expects($this->any())
			->method('setAppValue')
			->with('user_ldap', Configuration::CONFIG_PREFIX . 'tcr', $this->anything());

		$result = $this->controller->create();

		$this->assertInstanceOf(DataResponse::class, $result);
		$data = $result->getData();
		$this->assertArraySubset([
			'status' => 'success',
			'configPrefix' => 'tcr',
			'defaults' => []
		], $data, true);
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
					return $this->stringStartsWith('tgt');
				}),
				$this->callback(function ($value) use (&$expectedValue) {
					if ($expectedValue !== null) {
						return $expectedValue === $value;
					};
					return true;
				}));

		$result = $this->controller->create('src');

		$this->assertInstanceOf(DataResponse::class, $result);
		$data = $result->getData();
		$this->assertArraySubset([
			'status' => 'success',
			'configPrefix' => 'tgt'
		], $data, true);
	}

	public function testRead() {
		$config = $this->getLdapConfig(
			[
				'ldapHost' => 'example.org',
				'ldapAgentPassword' => \base64_encode('secret')
			]
		);
		$this->config->expects($this->any())
			->method('getAppValue')
			->willReturn(\json_encode($config));

		$result = $this->controller->read('t01');

		$this->assertInstanceOf(DataResponse::class, $result);
		$data = $result->getData();
		$this->assertArraySubset([
			'status' => 'success',
			'configuration' => [
				'ldap_host' => 'example.org',
				'ldap_agent_password' => '**PASSWORD SET**'
			]
		], $data, true);
	}

	public function testTest() {
		// use valid looking config to pass critical validation
		$config = $this->getLdapConfig(
			[
				'ldapHost' => 'example.org',
				'ldapPort' => '389',
				'ldapDisplayName' => 'displayName',
				'ldapGroupDisplayName' => 'cn',
				'ldapLoginFilter' => '(uid=%uid)',
				'ldapConfigurationActive' => 1,
				'ldapAgentName' => 'cn=admin',
				'ldapBase' => 'dc=example,dc=org',
				'ldapAgentPassword' => \base64_encode('secret')
			]
		);
		$this->config->expects($this->any())
			->method('getAppValue')
			->willReturn(\json_encode($config));

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
		$this->assertArraySubset(['status' => 'success'], $data, true);
	}

	public function testDelete() {
		// TODO implement me!
	}

	public function testDeleteNotExisting() {
		$result = $this->controller->delete('na');

		$this->assertInstanceOf(DataResponse::class, $result);
		$data = $result->getData();
		$this->assertArraySubset(['status' => 'error'], $data, true);
	}

	protected function getLdapConfig($values) {
		$config = \array_merge(
			(new Configuration($this->config, ''))->getDefaults(),
			$values
		);
		return $config;
	}
}
