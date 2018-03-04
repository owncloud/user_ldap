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
		// TODO
	}

	public function testRead() {
		// TODO
	}

	public function testDeleteNotExisting() {
		$result = $this->controller->delete('na');
		$this->assertInstanceOf(DataResponse::class, $result);
		$data = $result->getData();
		$this->assertArraySubset(['status' => 'error'], $data, true);
	}

}
