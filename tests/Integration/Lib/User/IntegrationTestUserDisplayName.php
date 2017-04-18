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

namespace OCA\User_LDAP\Tests\Integration\Lib\User;

use OCA\User_LDAP\User\Manager as LDAPUserManager;
use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\Tests\Integration\AbstractIntegrationTest;

require_once __DIR__ . '/../../AbstractIntegrationTest.php';

class IntegrationTestUserDisplayName extends AbstractIntegrationTest {
	/** @var  UserMapping */
	protected $mapping;

	/**
	 * prepares the LDAP environment and sets up a test configuration for
	 * the LDAP backend.
	 */
	public function init() {
		require(__DIR__ . '/../../setup-scripts/createExplicitUsers.php');
		parent::init();
		$this->mapping = new UserMapping(\OC::$server->getDatabaseConnection());
		$this->mapping->clear();
		$this->access->setUserMapper($this->mapping);
		$userBackend  = new \OCA\User_LDAP\User_LDAP($this->access, \OC::$server->getConfig());
		\OC_User::useBackend($userBackend);
	}

	/**
	 * adds a map entry for the user, so we know the username
	 *
	 * @param $dn
	 * @param $username
	 */
	private function prepareUser($dn, $username) {
		// assigns our self-picked oc username to the dn
		$this->mapping->map($dn, $username, 'fakeUUID-' . $username);
	}

	/**
	 * initializes an LDAP user manager instance
	 * @return LDAPUserManager
	 */
	protected function initUserManager() {
		$this->userManager = new LDAPUserManager(
			\OC::$server->getConfig(),
			new \OCA\User_LDAP\FilesystemHelper(),
			new \OCA\User_LDAP\LogWrapper(),
			\OC::$server->getAvatarManager(),
			new \OCP\Image(),
			\OC::$server->getDatabaseConnection(),
			\OC::$server->getUserManager()
		);
	}

	/**
	 * tests whether a display name consisting of two parts is created correctly
	 *
	 * @return bool
	 */
	protected function case1() {
		$username = 'alice';
		$dn = 'uid=alice,ou=users,' . $this->base;
		$this->prepareUser($dn, $username);

		$userSession = \OC::$server->getUserSession();
		if ($userSession->isLoggedIn()) {
			$userSession->logout();
		}
		$userSession->login($username, $username);

		\OC_Util::setupFS($username);

		$displayName = \OC::$server->getUserManager()->get($username)->getDisplayName();
		return strpos($displayName, '(Alice@example.com)') !== false;
	}

	/**
	 * tests whether a display name consisting of one part is created correctly
	 *
	 * @return bool
	 */
	protected function case2() {
		$this->connection->setConfiguration([
			'ldapUserDisplayName2' => '',
		]);
		$this->connection->saveConfiguration();

		$username = 'boris';
		$dn = 'uid=boris,ou=users,' . $this->base;
		$this->prepareUser($dn, $username);

		$userSession = \OC::$server->getUserSession();
		if ($userSession->isLoggedIn()) {
			$userSession->logout();
		}
		$userSession->login($username, $username);

		$displayName = \OC::$server->getUserManager()->get($username)->getDisplayName();
		return strpos($displayName, '(Boris@example.com)') === false;
	}

	/**
	 * sets up the LDAP configuration to be used for the test
	 */
	protected function initConnection() {
		parent::initConnection();
		$this->connection->setConfiguration([
			'ldapUserDisplayName' => 'displayName',
			'ldapUserDisplayName2' => 'mail',
		]);
		$this->connection->saveConfiguration();
	}
}

require_once(__DIR__ . '/../../setup-scripts/config.php');
/** @global $host string */
/** @global $port int */
/** @global $adn string */
/** @global $apwd string */
/** @global $bdn string */
$test = new IntegrationTestUserDisplayName($host, $port, $adn, $apwd, $bdn);
$test->init();
$test->run();
