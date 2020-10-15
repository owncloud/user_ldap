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

namespace OCA\User_LDAP\Tests\Integration\Lib;

use OCA\User_LDAP\Tests\Integration\AbstractIntegrationTest;
use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\User_LDAP;

require_once __DIR__ . '/../AbstractIntegrationTest.php';

class IntegrationTestPagingMultipleBase extends AbstractIntegrationTest {
	/** @var  UserMapping */
	protected $mapping;

	/** @var User_LDAP */
	protected $backend;

	/**
	 * prepares the LDAP environment and sets up a test configuration for
	 * the LDAP backend.
	 */
	public function init() {
		require(__DIR__ . '/../setup-scripts/createExplicitUsers.php');
		parent::init();

		$this->backend = new \OCA\User_LDAP\User_LDAP(\OC::$server->getConfig(), $this->userManager);
	}

	/**
	 * tests that paging having multiple bases returns limit at offset
	 * for all bases in parallel
	 *
	 * @return bool
	 */
	protected function case1() {
		$filter = 'objectclass=inetorgperson';
		$attributes = ['cn', 'dn'];

		// having multiple bases there is currently an issue that:
		// - having searchUsers with limit 2 and offset 2, expected resultset is 2
		// - while having multiple base-dns specified, LDAP currently will return 4 results (while expected is 2 from range query)
		$result = $this->access->searchUsers($filter, $attributes, 2, 0);
		echo(\count($result) . PHP_EOL);
		if (\count($result) !== 4) {
			return false;
		}

		return true;
	}
}

require_once(__DIR__ . '/../setup-scripts/config.php');
/** @global $host string */
/** @global $port int */
/** @global $adn string */
/** @global $apwd string */
/** @global $bdn string */
$bdnUsers = 'ou=users,' . $bdn . ';' .'ou=admins,' . $bdn;
$bdnGroups = $bdn;
$test = new IntegrationTestPagingMultipleBase($host, $port, $adn, $apwd, $bdn, $bdnUsers, $bdnGroups);
$test->init();
$test->run();
