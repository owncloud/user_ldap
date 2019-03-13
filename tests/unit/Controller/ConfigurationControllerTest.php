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

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OC\AppFramework\Http;
use OCA\User_LDAP\Config\Server;
use OCA\User_LDAP\Config\ServerMapper;
use OCA\User_LDAP\Exceptions\ConfigException;
use OCA\User_LDAP\LDAP;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http\DataResponse;
use OCP\ICacheFactory;
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
	/** @var ICacheFactory|\PHPUnit\Framework\MockObject\MockObject */
	private $cf;
	/** @var ISession|\PHPUnit\Framework\MockObject\MockObject */
	private $session;
	/** @var ServerMapper|\PHPUnit\Framework\MockObject\MockObject */
	private $mapper;
	/** @var IL10N|\PHPUnit\Framework\MockObject\MockObject */
	private $l10n;
	/** @var LDAP|\PHPUnit\Framework\MockObject\MockObject */
	private $ldap;

	/** @var ConfigurationController */
	private $controller;

	protected function setUp() {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->config = $this->createMock(IConfig::class);
		$this->cf = $this->createMock(ICacheFactory::class);
		$this->session = $this->createMock(ISession::class);
		$this->mapper = $this->createMock(ServerMapper::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->ldap = $this->createMock(LDAP::class);

		$this->controller = new ConfigurationController(
			'user_ldap',
			$this->request,
			$this->config,
			$this->cf,
			$this->session,
			$this->mapper,
			$this->l10n,
			$this->ldap
		);
	}

	public function testCreate() {
		$this->markTestSkipped('To be implemented');

		$this->request->method('getParams')
			->will($this->returnValue(\json_decode('{"id":"testid","password":"secret"}', true)));

		$this->mapper->expects(self::once())
			->method('insert');

		$result = $this->controller->create();

		self::assertInstanceOf(DataResponse::class, $result);
		self::assertSame(Http::STATUS_OK, $result->getStatus());
		/** @var Server $c */
		$c = $result->getData();
		self::assertInstanceOf(Server::class, $c);
		self::assertSame('testid', $c->getId());
		self::assertFalse($c->isActive());
		self::assertSame(600, $c->getCacheTTL());
		self::assertSame([], $c->getUserTrees());
		self::assertTrue($c->getPassword(), 'The password must never be exposed to the web frontend');
	}

	/**
	 * // TODO check more wrong configs
	 */
	public function testCreateInvalid() {
		$this->request->method('getParams')
			->will($this->returnValue(\json_decode('{"id":null}', true)));

		$this->mapper->expects(self::never())
			->method('insert');

		$result = $this->controller->create();

		self::assertInstanceOf(DataResponse::class, $result);
		self::assertSame(Http::STATUS_UNPROCESSABLE_ENTITY, $result->getStatus());
	}

	public function testCreateExisting() {
		$this->request->method('getParams')
			->will($this->returnValue(\json_decode('{"id":"idexists"}', true)));

		$this->mapper->expects(self::once())
			->method('insert')
		->willThrowException($this->createMock(UniqueConstraintViolationException::class));

		$result = $this->controller->create();

		self::assertInstanceOf(DataResponse::class, $result);
		self::assertSame(Http::STATUS_CONFLICT, $result->getStatus());
	}

	public function testRead() {
		$c = new Server(['id' => 'testid']);
		$this->mapper->expects(self::once())
			->method('find')
			->with('testid')
			->willReturn($c);

		$result = $this->controller->read('testid');

		self::assertInstanceOf(DataResponse::class, $result);
		self::assertSame(Http::STATUS_OK, $result->getStatus());
		/** @var Server $d */
		$d = $result->getData();
		self::assertSame($c, $d);
		self::assertSame($c, $d);
	}

	public function testReadNotExisting() {
		$this->mapper->expects(self::once())
			->method('find')
			->with('notexistingid')
			->willThrowException(new DoesNotExistException(''));

		$result = $this->controller->read('notexistingid');

		self::assertInstanceOf(DataResponse::class, $result);
		self::assertSame(Http::STATUS_NOT_FOUND, $result->getStatus());
	}

	public function testReadBroken() {
		$this->mapper->expects(self::once())
			->method('find')
			->with('brokenid')
			->willThrowException(new ConfigException());

		$result = $this->controller->read('brokenid');

		self::assertInstanceOf(DataResponse::class, $result);
		self::assertSame(Http::STATUS_UNPROCESSABLE_ENTITY, $result->getStatus());
	}

	public function testDelete() {
		$this->mapper->expects(self::once())
			->method('delete')
			->with('existingid');

		$result = $this->controller->delete('existingid');

		self::assertInstanceOf(DataResponse::class, $result);
		self::assertSame(Http::STATUS_OK, $result->getStatus());
	}

	public function testDeleteNotExisting() {
		$this->mapper->expects(self::once())
			->method('delete')
			->with('notexistingid')
			->willThrowException(new DoesNotExistException(''));

		$result = $this->controller->delete('notexistingid');

		self::assertInstanceOf(DataResponse::class, $result);
		self::assertSame(Http::STATUS_NOT_FOUND, $result->getStatus());
	}
}
