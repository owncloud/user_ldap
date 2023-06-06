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

require_once __DIR__ . '/../AbstractIntegrationTest.php';

class IntegrationTestCountUsersByLoginName extends AbstractIntegrationTest {
	/**
	 * prepares the LDAP environment and sets up a test configuration for
	 * the LDAP backend.
	 */
	public function init() {
		require(__DIR__ . '/../setup-scripts/createExplicitUsers.php');
		parent::init();
	}

	/**
	 * tests countUsersByLoginName where it is expected that the login name does
	 * not match any LDAP user
	 *
	 * @return bool
	 */
	protected function case1() {
		$result = $this->access->countUsersByLoginName('notHere');
		return $result === 0;
	}

	/**
	 * tests countUsersByLoginName where it is expected that the login name does
	 * match one LDAP user
	 *
	 * @return bool
	 */
	protected function case2() {
		$result = $this->access->countUsersByLoginName('alice');
		return $result === 1;
	}
}

require_once(__DIR__ . '/../setup-scripts/config.php');
/** @global $host string */
/** @global $port int */
/** @global $adn string */
/** @global $apwd string */
/** @global $bdn string */
$test = new IntegrationTestCountUsersByLoginName($host, $port, $adn, $apwd, $bdn);
$test->init();
$test->run();
