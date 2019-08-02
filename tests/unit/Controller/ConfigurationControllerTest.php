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

use OCA\User_LDAP\Config\Config;
use OCA\User_LDAP\Config\ConfigMapper;
use OCA\User_LDAP\Helper;
use OCA\User_LDAP\LDAP;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http\DataResponse;
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
	/** @var ConfigMapper|\PHPUnit\Framework\MockObject\MockObject */
	private $configMapper;
	/** @var ISession|\PHPUnit\Framework\MockObject\MockObject */
	private $session;
	/** @var IL10N|\PHPUnit\Framework\MockObject\MockObject */
	private $l10n;
	/** @var LDAP|\PHPUnit\Framework\MockObject\MockObject */
	private $ldap;

	/** @var ConfigurationController */
	private $controller;

	protected function setUp() {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->configMapper = $this->createMock(ConfigMapper::class);
		$this->session = $this->createMock(ISession::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->ldap = $this->createMock(LDAP::class);

		$this->controller = new ConfigurationController(
			'user_ldap',
			$this->request,
			$this->configMapper,
			$this->session,
			$this->l10n,
			$this->ldap
		);
	}

	public function testCreate() {
		$this->configMapper->expects($this->once())
			->method('nextPossibleConfigurationPrefix')
			->will($this->returnValue('tcr'));

		$this->configMapper->expects($this->once())
			->method('insert');

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
		$this->configMapper->expects($this->once())
			->method('nextPossibleConfigurationPrefix')
			->will($this->returnValue('tgt'));

		$this->configMapper->expects($this->once())
			->method('find')
			->willReturn(
				new Config(
					[
						'id' => 'src',
						'ldapHost' => 'example.org',
						'ldapAgentPassword' => \base64_encode('secret')
					]
				)
			);
		$this->configMapper->expects($this->once())
			->method('insert')
			->with($this->callback(
				function (Config $config) {
					$data = $config->getData();
					return $config->getId() === 'tgt'
						&& $data['ldapHost'] === 'example.org'
						&& $data['ldapAgentPassword'] === 'secret';
					// TODO: fix double encoded password
				}
			));

		$result = $this->controller->create('src');

		$this->assertInstanceOf(DataResponse::class, $result);
		$data = $result->getData();
		$this->assertArraySubset([
			'status' => 'success',
			'configPrefix' => 'tgt'
		], $data, true);
	}

	public function testRead() {
		$config = [
				'ldapHost' => 'example.org',
				'ldapAgentPassword' => \base64_encode('secret')
			];
		$this->configMapper->expects($this->any())
			->method('find')
			->willReturn(new Config($config));

		$result = $this->controller->read('t01');

		$this->assertInstanceOf(DataResponse::class, $result);
		$data = $result->getData();
		$this->assertArraySubset([
			'status' => 'success',
			'configuration' => [
				'ldapHost' => 'example.org',
				'ldapAgentPassword' => '**PASSWORD SET**'
			]
		], $data, true);
	}

	public function testTest() {
		// use valid looking config to pass critical validation
		$config = [
				'ldapHost' => 'example.org',
				'ldapPort' => '389',
				'ldapDisplayName' => 'displayName',
				'ldapGroupDisplayName' => 'cn',
				'ldapLoginFilter' => '(uid=%uid)',
				'ldapConfigurationActive' => 1,
				'ldapAgentName' => 'cn=admin',
				'ldapBase' => 'dc=example,dc=org',
				'ldapAgentPassword' => \base64_encode('secret')
		];
		$this->configMapper->expects($this->any())
			->method('find')
			->willReturn(new Config($config));

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

	//public function testDelete() {
	// TODO implement me!
	//}

	public function testDeleteNotExisting() {
		$this->configMapper->method('delete')
			->willThrowException(new DoesNotExistException(''));

		$result = $this->controller->delete('na');
		$this->assertInstanceOf(DataResponse::class, $result);
		$data = $result->getData();
		$this->assertArraySubset(['status' => 'error'], $data, true);
	}
}
