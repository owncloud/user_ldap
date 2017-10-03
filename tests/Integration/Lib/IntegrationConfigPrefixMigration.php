<?php
/**
 * @author Tom Needham <tom@owncloud.com>
 *
 * @copyright Copyright (c) 2017 ownCloud GmbH
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

namespace OCA\User_LDAP\Tests\Integration\Lib;

use OCA\User_LDAP\Configuration;
use OCA\User_LDAP\Connection;
use OCA\User_LDAP\Helper;
use OCA\User_LDAP\Tests\Integration\AbstractIntegrationTest;
use OCA\User_LDAP\User_LDAP;
use OCA\User_LDAP\Migrations\Version20170927125822;
use OCP\Migration\IOutput;


require_once __DIR__ . '/../AbstractIntegrationTest.php';

class IntegrationConfigPrefixMigration extends AbstractIntegrationTest {

	/** @var User_LDAP */
	protected $backend;

	/**
	 * prepares the LDAP environment and sets up a test configuration for
	 * the LDAP backend.
	 */
	public function init() {
		require(__DIR__ . '/../setup-scripts/createExplicitUsers.php');

		// clear all exiting configurations
		$qb = \OC::$server->getDatabaseConnection()->getQueryBuilder();
		$qb
			->delete('appconfig')
			->where($qb->expr()->eq('appid', 'user_ldap')) // ldap appid
			->where($qb->expr()->like('configkey', '%ldap')); // all keys like ldap%
		$qb->execute();

		parent::init();

		$this->backend = new \OCA\User_LDAP\User_LDAP($this->access, \OC::$server->getConfig());
	}

	/**
	 * Overwrite the connection init so that we can set the configuration
	 */
	public function initConnection() {
		$config = $this->createConfig('');
		$this->connection = new Connection($this->ldap, '');
	}

	/**
	 * Creates a test configuration
	 * @param $prefix
	 * @return Configuration
	 */
	protected function createConfig($prefix) {
		$c = new Configuration($prefix);
		$c->setConfiguration($c->getDefaults());
		$c->setConfiguration([
			'ldapHost' => $this->server['host'],
			'ldapPort' => $this->server['port'],
			'ldapBase' => $this->base,
			'ldapAgentName' => $this->server['dn'],
			'ldapAgentPassword' => $this->server['pwd'],
			'ldapUserFilter' => 'objectclass=inetOrgPerson',
			'ldapUserDisplayName' => 'cn',
			'ldapGroupDisplayName' => 'cn',
			'ldapLoginFilter' => '(|(uid=%uid)(samaccountname=%uid))',
			'ldapCacheTTL' => 0,
			'ldapConfigurationActive' => 1,
		]);
		// persist
		$c->saveConfiguration();
		return $c;
	}

	/**
	 * tests that paging works properly against a simple example (reading all
	 * of few users in smallest steps)
	 *
	 * @return bool
	 */
	protected function case1() {
		// Check the user is there at the start
		$result = $this->access->fetchUsersByLoginName('alice');
		if(count($result) !== 1) {
			echo 'user not found at start of migration test';
			return false;
		}

		// Migrate the config
		$migration = new Version20170927125822(new Helper());
		$migration->run(new IntegrationOutput());

		// Update the connection using the new prefix
		$h = new Helper();
		$prefixes = $h->getServerConfigurationPrefixes();
		if(count($prefixes) > 1) {
			echo 'Should only receive one server config prefix, got '.count($prefixes);
			return false;
		}
		$prefix = array_shift($prefixes);
		$this->initConnection();
		$this->initAccess();

		// Check that we can still get to the users
		$result = $this->access->fetchUsersByLoginName('alice');
		return count($result) === 1;

	}
}

require_once(__DIR__ . '/../setup-scripts/config.php');
/** @global $host string */
/** @global $port int */
/** @global $adn string */
/** @global $apwd string */
/** @global $bdn string */
$test = new IntegrationConfigPrefixMigration($host, $port, $adn, $apwd, $bdn);
$test->init();
$test->run();


class IntegrationOutput implements IOutput {
	public function info($message) { echo $message; }
	public function warning($message) { echo $message; }
	public function startProgress($max = 0) {}
	public function advance($step = 1, $description = '') {}
	public function finishProgress() {}
}