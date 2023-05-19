<?php
/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Piotr Mrowczynski <piotr@owncloud.com>
 *
 * @copyright Copyright (c) 2020, ownCloud GmbH.
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

class IntegrationTestPaging extends AbstractIntegrationTest {
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
	 * tests that paging works properly against a simple example (reading
	 * a few users in small steps)
	 *
	 * @return bool
	 */
	protected function case1() {
		$limit = 2;
		$offset = 0;

		$filter = 'objectclass=inetorgperson';
		$attributes = ['cn', 'dn'];
		$users = [];
		do {
			$result = $this->access->searchUsers($filter, $attributes, $limit, $offset);
			foreach ($result as $user) {
				$users[] = $user['cn'];
			}
			$offset += $limit;
		} while ($this->access->hasMoreResults());

		if (\count($users) === 5) {
			return true;
		}

		return false;
	}

	/**
	 * tests that paging can retry for missing cookie for continued paged search
	 *
	 * @return bool
	 */
	protected function case2() {
		$filter = 'objectclass=inetorgperson';
		$attributes = ['cn', 'dn'];

		// start paged search from offset 0 with limit 2
		// and thus sets cookie so search can continue
		$result = $this->access->searchUsers($filter, $attributes, 2, 0);
		if (\count($result) !== 2) {
			return false;
		}

		// interrupt previous paged search with paged search that returns complete result
		// and thus sets '' cookie indicating completion
		$result = $this->access->searchUsers($filter, $attributes, 6, 0);
		if (\count($result) !== 5) {
			return false;
		}

		// we should be able to continue previous paged search when interrupted
		// by retrying search to repopulate cookie
		$result = $this->access->searchUsers($filter, $attributes, 2, 2);
		if (\count($result) !== 2) {
			return false;
		}
		$result = $this->access->searchUsers($filter, $attributes, 2, 4);
		if (\count($result) !== 1) {
			return false;
		}

		return true;
	}

	/**
	 * tests that paging works properly against a simple example (reading
	 * a few users in small steps)
	 *
	 * @return bool
	 */
	protected function case3() {
		$limit = 2;
		$offset = 0;

		$filter = 'objectclass=inetorgperson';
		$attributes = ['cn', 'dn'];
		$count = 0;
		do {
			$result = $this->access->countUsers($filter, $attributes, $limit, $offset);
			$count = $count + $result;
			$offset += $limit;
		} while ($this->access->hasMoreResults());

		if ($count === 5) {
			return true;
		}

		return false;
	}

	/**
	 * tests that paging can retry for missing cookie for continued paged search
	 *
	 * @return bool
	 */
	protected function case4() {
		$filter = 'objectclass=inetorgperson';
		$attributes = ['cn', 'dn'];

		// start paged search from offset 0 with limit 2
		// and thus sets cookie so search can continue
		$result = $this->access->countUsers($filter, $attributes, 2, 0);
		if ($result !== 2) {
			return false;
		}

		// interrupt previous paged search with paged search that returns complete result
		// and thus sets '' cookie indicating completion
		$result = $this->access->countUsers($filter, $attributes, 6, 0);
		if ($result !== 5) {
			return false;
		}

		// we should be able to continue previous paged search when interrupted
		// by retrying search to repopulate cookie
		$result = $this->access->countUsers($filter, $attributes, 2, 2);
		if ($result !== 2) {
			return false;
		}
		$result = $this->access->countUsers($filter, $attributes, 2, 4);
		if ($result !== 1) {
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
$bdnUsers = $bdn;
$bdnGroups = $bdn;
$test = new IntegrationTestPaging($host, $port, $adn, $apwd, $bdn, $bdnUsers, $bdnGroups);
$test->init();
$test->run();
