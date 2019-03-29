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

use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use OCP\AppFramework\Http\DataResponse;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IRequest;
use Test\TestCase;

/**
 * Class MappingControllerTest
 *
 * @package OCA\User_LDAP\Controller
 */
class MappingControllerTest extends TestCase {

	/** @var IRequest|\PHPUnit_Framework_MockObject_MockObject */
	private $request;
	/** @var IL10N|\PHPUnit_Framework_MockObject_MockObject */
	private $l10n;
	/** @var IDBConnection|\PHPUnit_Framework_MockObject_MockObject */
	private $connection;

	/** @var MappingController */
	private $controller;

	protected function setUp() {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->connection = $this->createMock(IDBConnection::class);

		$this->controller = new MappingController(
			'user_ldap',
			$this->request,
			$this->l10n,
			$this->connection
		);
	}

	public function dataTestClear() {
		return [
			['user'],
			['group'],
		];
	}

	/**
	 * @dataProvider dataTestClear
	 *
	 * @param $subject
	 */
	public function testClear($subject) {
		$truncateSQL = "TRUNCATE `*PREFIX*ldap_{$subject}_mapping`";
		$platform = $this->createMock(AbstractPlatform::class);
		$platform->expects($this->once())
			->method('getTruncateTableSQL')
			->with("`*PREFIX*ldap_{$subject}_mapping`")
			->will($this->returnValue($truncateSQL));

		$this->connection->expects($this->once())
			->method('getDatabasePlatform')
			->will($this->returnValue($platform));

		$statement = $this->createMock(Statement::class);
		$statement->expects($this->once())
			->method('execute')
			->will($this->returnValue(true));

		$this->connection->expects($this->once())
			->method('prepare')
			->with($truncateSQL)
			->will($this->returnValue($statement));

		$result = $this->controller->clear($subject);

		$this->assertInstanceOf(DataResponse::class, $result);
		$data = $result->getData();
		$this->assertEquals([
			'status' => 'success',
		], $data, true);
	}
}
