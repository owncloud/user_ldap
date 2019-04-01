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

use OCA\User_LDAP\LDAP;
use OCA\User_LDAP\User\Manager;
use OCP\AppFramework\Http\DataResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use Test\TestCase;

/**
 * Class WizardControllerTest
 *
 * @package OCA\User_LDAP\Controller
 */
class WizardControllerTest extends TestCase {

	/** @var IRequest|\PHPUnit\Framework\MockObject\MockObject */
	private $request;
	/** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
	private $config;
	/** @var Manager|\PHPUnit\Framework\MockObject\MockObject */
	private $manager;
	/** @var IL10N|\PHPUnit\Framework\MockObject\MockObject */
	private $l10n;
	/** @var LDAP|\PHPUnit\Framework\MockObject\MockObject */
	private $ldap;

	/** @var WizardController */
	private $controller;

	protected function setUp() {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->config = $this->createMock(IConfig::class);
		$this->manager = $this->createMock(Manager::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->ldap = $this->createMock(LDAP::class);

		$this->controller = new WizardController(
			'user_ldap',
			$this->request,
			$this->config,
			$this->manager,
			$this->l10n,
			$this->ldap
		);
	}

	public function testCastUnknownAction() {
		$result = $this->controller->cast('t01', 'unknownAction');

		$this->assertInstanceOf(DataResponse::class, $result);
		$data = $result->getData();
		$this->assertArraySubset([
			'status' => 'error',
		], $data, true);
	}
}
