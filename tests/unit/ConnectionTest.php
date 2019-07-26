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

/**
 * Class Test_Connection
 *
 * @group DB
 *
 * @package OCA\User_LDAP
 */
class ConnectionTest extends \Test\TestCase {
	/** @var \OCA\User_LDAP\ILDAPWrapper|\PHPUnit\Framework\MockObject\MockObject  */
	protected $ldap;

	/** @var  Connection|\PHPUnit\Framework\MockObject\MockObject */
	protected $connection;

	public function setUp() {
		parent::setUp();
		$coreConfig  = \OC::$server->getConfig(); // TODO use Mock

		$configuration = new Configuration($coreConfig, 'test', false);

		$this->ldap       = $this->createMock(ILDAPWrapper::class);
		// we use a mock here to replace the cache mechanism, due to missing DI in LDAP backend.
		$this->connection = $this->getMockBuilder(Connection::class)
			->setMethods(['getFromCache', 'writeToCache'])
			->setConstructorArgs([$this->ldap, $configuration, null])
			->getMock();

		$this->ldap->expects($this->any())
			->method('areLDAPFunctionsAvailable')
			->will($this->returnValue(true));
	}

	public function testOriginalAgentUnchangedOnClone() {
		//background: upon login a bind is done with the user credentials
		//which is valid for the whole LDAP resource. It needs to be reset
		//to the agent's credentials
		$coreConfig  = \OC::$server->getConfig(); // TODO use Mock

		$configuration = new Configuration($coreConfig, 'test', false);
		$connection = new Connection($this->ldap, $configuration, null);
		$agent = [
			'ldapAgentName' => 'agent',
			'ldapAgentPassword' => '123456',
		];
		$connection->setConfiguration($agent);

		$testConnection = clone $connection;
		$user = [
			'ldapAgentName' => 'user',
			'ldapAgentPassword' => 'password',
		];
		$testConnection->setConfiguration($user);

		$agentName = $connection->ldapAgentName;
		$agentPawd = $connection->ldapAgentPassword;

		$this->assertSame($agentName, $agent['ldapAgentName']);
		$this->assertSame($agentPawd, $agent['ldapAgentPassword']);
	}

	public function testUseBackupServer() {
		$mainHost = 'ldap://nixda.ldap';
		$backupHost = 'ldap://fallback.ldap';
		$config = [
			'ldapConfigurationActive' => true,
			'ldapHost' => $mainHost,
			'ldapPort' => 389,
			'ldapBackupHost' => $backupHost,
			'ldapBackupPort' => 389,
			'ldapAgentName' => 'uid=agent',
			'ldapAgentPassword' => 'SuchASecret'
		];

		$this->connection->setIgnoreValidation(true);
		$this->connection->setConfiguration($config);

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

		$this->connection->getConnectionResource();
		$this->connection->resetConnectionResource();
		// with the second getConnectionResource() we test whether caching works
		$this->connection->getConnectionResource();
	}

	/**
	 * @expectedException \OC\ServerNotAvailableException
	 */
	public function testConnectFails() {
		$mainHost = 'ldap://nixda.ldap';
		$config = [
			'ldapConfigurationActive' => true,
			'ldapHost' => $mainHost,
			'ldapPort' => 389,
			'ldapAgentName' => 'uid=agent',
			'ldapAgentPassword' => 'WrongPassword'
		];

		$this->connection->setIgnoreValidation(true);
		$this->connection->setConfiguration($config);

		$this->ldap->expects($this->once())
			->method('connect')
			->will($this->returnValue(false));

		$this->ldap->expects($this->any())
			->method('isResource')
			->will($this->returnValue(false));

		$this->ldap->expects($this->any())
			->method('setOption')
			->will($this->returnValue(true));

		$this->connection->getConnectionResource();
	}

	public function testBind() {
		$mainHost = 'ldap://fake.ldap';
		$config = [
			'ldapConfigurationActive' => true,
			'ldapHost' => $mainHost,
			'ldapPort' => 389,
			'ldapAgentName' => 'uid=agent',
			'ldapAgentPassword' => 'Secret'
		];

		$this->connection->setIgnoreValidation(true);
		$this->connection->setConfiguration($config);

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

		$this->connection->getConnectionResource();
	}

	/**
	 * @expectedException \OCA\User_LDAP\Exceptions\BindFailedException
	 */
	public function testBindFails() {
		$mainHost = 'ldap://nixda.ldap';
		$config = [
			'ldapConfigurationActive' => true,
			'ldapHost' => $mainHost,
			'ldapPort' => 389,
			'ldapAgentName' => 'uid=agent',
			'ldapAgentPassword' => 'WrongPassword'
		];

		$this->connection->setIgnoreValidation(true);
		$this->connection->setConfiguration($config);

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

		$this->connection->getConnectionResource();
	}
}
