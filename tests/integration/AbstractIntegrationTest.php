<?php
/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
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

namespace OCA\User_LDAP\Tests\Integration;

use Exception;
use OC;
use OCA\User_LDAP\Access;
use OCA\User_LDAP\Connection;
use OCA\User_LDAP\Configuration;
use OCA\User_LDAP\LDAP;
use OCA\User_LDAP\User\Manager;
use function get_class_methods;
use function strpos;

require_once __DIR__  . '/../../../../lib/base.php';
require_once __DIR__ . '/FakeManager.php';

abstract class AbstractIntegrationTest {
	/** @var  LDAP */
	protected LDAP $ldap;

	/** @var  Connection */
	protected $connection;

	/** @var Access */
	protected $access;

	/** @var Manager */
	protected $userManager;

	/** @var  string */
	protected $bdn;

	/** @var  string */
	protected $bdnUsers;

	/** @var  string */
	protected $bdnGroups;

	/** @var string[] */
	protected $server;
	protected $base;

	public function __construct(string $host, $port, $bind, $pwd, $base, $baseUsers = null, $baseGroups = null) {
		$this->base = $base;
		$this->bdnUsers = $baseUsers ?? $base;
		$this->bdnGroups = $baseGroups ?? $base;
		$this->server = [
			'host' => $host,
			'port' => $port,
			'dn'   => $bind,
			'pwd'  => $pwd
		];
	}

	/**
	 * prepares the LDAP environment and sets up a test configuration for
	 * the LDAP backend.
	 */
	public function init() {
		// wipe account table
		$qb = OC::$server->getDatabaseConnection()->getQueryBuilder();
		$qb->delete('accounts')->execute();

		$this->initLDAPWrapper();
		$this->initConnection();
		$this->initUserManager();
		$this->initAccess();
	}

	/**
	 * initializes the test LDAP wrapper
	 */
	protected function initLDAPWrapper(): void {
		$this->ldap = new LDAP();
	}

	/**
	 * sets up the LDAP configuration to be used for the test
	 */
	protected function initConnection() {
		$coreConfig = OC::$server->getConfig();
		$configuration = new Configuration($coreConfig, 'test');
		$this->connection = new Connection($this->ldap, $configuration);
		// use the defaults to make sure we don't use any remnants
		$this->connection->setConfiguration($configuration->getDefaults());
		$this->connection->setConfiguration([
			'ldapHost' => $this->server['host'],
			'ldapPort' => $this->server['port'],
			'ldapBase' => $this->bdn,
			'ldapBaseUsers' => $this->bdnUsers,
			'ldapBaseGroups' => $this->bdnGroups,
			'ldapAgentName' => $this->server['dn'],
			'ldapAgentPassword' => $this->server['pwd'],
			'ldapUserFilter' => 'objectclass=inetOrgPerson',
			'ldapUserDisplayName' => 'cn',
			'ldapGroupDisplayName' => 'cn',
			'ldapLoginFilter' => '(|(uid=%uid)(samaccountname=%uid))',
			'ldapCacheTTL' => 0,
			'ldapConfigurationActive' => 1,
		]);
	}

	/**
	 * initializes an LDAP user manager instance
	 */
	protected function initUserManager() {
		$this->userManager = new FakeManager();
	}

	/**
	 * initializes the Access test instance
	 */
	protected function initAccess(): void {
		$this->access = new Access($this->connection, $this->userManager);
	}

	/**
	 * runs the test cases while outputting progress and result information
	 *
	 * If a test failed, the script is exited with return code 1.
	 */
	public function run(): void {
		$methods = get_class_methods($this);
		$atLeastOneCaseRan = false;
		foreach ($methods as $method) {
			if (strpos($method, 'case') === 0) {
				$this->runTest($method);
				$atLeastOneCaseRan = true;
			}
		}
		if ($atLeastOneCaseRan) {
			print('Tests succeeded' . PHP_EOL);
		} else {
			print('No Test was available.' . PHP_EOL);
			exit(1);
		}
	}

	private function runTest(string $method): void {
		print("running $method " . PHP_EOL);
		try {
			if (!$this->$method()) {
				print(PHP_EOL . '>>> !!! Test ' . $method . ' FAILED !!! <<<' . PHP_EOL . PHP_EOL);
				exit(1);
			}
		} catch (Exception $ex) {
			print(PHP_EOL . '>>> !!! Test ' . $method . ' FAILED !!! <<<' . PHP_EOL . PHP_EOL);
			print((string)$ex);
			exit(1);
		}
	}
}
