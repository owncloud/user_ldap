<?php
/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
use OCA\User_LDAP\Config\Server;
use OCP\ICacheFactory;

use OCA\User_LDAP\Config\Config;
use OCA\User_LDAP\Config\ConfigMapper;

/**
 * Class Test_Connection
 *
 * @group DB
 *
 * @package OCA\User_LDAP
 */
class ConnectionTest extends \Test\TestCase {
	/** @var ILDAPWrapper|\PHPUnit\Framework\MockObject\MockObject  */
	protected $ldap;

	/** @var Server|\PHPUnit\Framework\MockObject\MockObject  */
	protected $server;

	/** @var  Connection|\PHPUnit\Framework\MockObject\MockObject */
	protected $connection;

	/** @var  ConfigMapper | \PHPUnit\Framework\MockObject\MockObject */
	private $configMapper;

	public function setUp() {
		parent::setUp();

		$cf = $this->createMock(ICacheFactory::class);
		$this->ldap = $this->createMock(ILDAPWrapper::class);
		$this->server = new Server([
			'id' => 'test',
			'active' => true,
			'ldapHost' => 'ldap://fake.ldap',
			'ldapPort' => 389,
			'bindDN' => 'uid=agent',
			'password' => '123456',
		]);

		// we use a mock here to replace the cache mechanism, due to missing DI in LDAP backend.
		$this->connection = $this->getMockBuilder(Connection::class)
			->setMethods(['getFromCache', 'writeToCache'])
			->setConstructorArgs([$cf, $this->ldap, $this->server])
			->getMock();

		$this->ldap->expects($this->any())
			->method('areLDAPFunctionsAvailable')
			->will($this->returnValue(true));
	}
	
	public function testUseBackupServer() {
		$this->server->setBackupHost('ldap://backup.ldap');
		$this->server->setBackupPort(389);

		$this->ldap->expects($this->any())
			->method('isResource')
			->will($this->returnValue(true));

		$this->ldap->expects($this->any())
			->method('setOption')
			->will($this->returnValue(true));

		$this->ldap->expects($this->exactly(3))
			->method('connect')
			->will($this->returnValue('ldapResource'));

		// Not called often enough? Then, the fallback to the backup server is broken.
		$this->connection->expects($this->exactly(4))
			->method('getFromCache')
			->with('overrideMainServer')
			->will($this->onConsecutiveCalls(false, false, true, true));

		$this->connection->expects($this->once())
			->method('writeToCache')
			->with('overrideMainServer', true);

		$isThrown = false;
		$this->ldap->expects($this->exactly(3))
			->method('bind')
			->will($this->returnCallback(function () use (&$isThrown) {
				if (!$isThrown) {
					$isThrown = true;
					throw new \OC\ServerNotAvailableException();
				}
				return true;
			}));

		self::invokePrivate($this->connection, 'establishConnection');
		$this->connection->resetConnectionResource();
		// with the second establishConnection() we test whether caching works
		self::invokePrivate($this->connection, 'establishConnection');
	}

	/**
	 * @expectedException \OC\ServerNotAvailableException
	 */
	public function testConnectFails() {
		$this->ldap->expects($this->once())
			->method('connect')
			->will($this->returnValue(false));

		$this->ldap->expects($this->any())
			->method('isResource')
			->will($this->returnValue(false));

		$this->ldap->expects($this->any())
			->method('setOption')
			->will($this->returnValue(true));

		self::invokePrivate($this->connection, 'establishConnection');
	}

	public function testBind() {
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

		self::invokePrivate($this->connection, 'establishConnection');
	}

	/**
	 * @expectedException \OCA\User_LDAP\Exceptions\BindFailedException
	 */
	public function testBindFails() {
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
			->will($this->returnValue(false));

		self::invokePrivate($this->connection, 'establishConnection');
	}
}
