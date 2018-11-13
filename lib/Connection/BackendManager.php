<?php
/**
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
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

namespace OCA\User_LDAP\Connection;

use OCA\User_LDAP\Access;
use OCA\User_LDAP\Config\GroupTree;
use OCA\User_LDAP\Config\Tree;
use OCA\User_LDAP\Config\Server;
use OCA\User_LDAP\Config\UserTree;
use OCA\User_LDAP\Connection;
use OCA\User_LDAP\FilesystemHelper;
use OCA\User_LDAP\Group_LDAP;
use OCA\User_LDAP\ILDAPWrapper;
use OCA\User_LDAP\LDAP;
use OCA\User_LDAP\Mapping\GroupMapping;
use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\User\Manager;
use OCA\User_LDAP\User_LDAP;
use OCP\IAvatarManager;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\ILogger;
use OCP\IUserManager;

class BackendManager {

	/**
	 * @var IConfig
	 */
	private $coreConfig;
	/**
	 * @var ILogger
	 */
	private $logger;
	/**
	 * @var IAvatarManager
	 */
	private $avatarManager;
	/**
	 * @var IUserManager
	 */
	private $coreUserManager;
	/**
	 * @var IDBConnection
	 */
	private $db;
	/**
	 * @var ILDAPWrapper
	 */
	private $ldap;
	/**
	 * @var UserMapping
	 */
	private $userMap;
	/**
	 * @var GroupMapping
	 */
	private $groupMap;
	/**
	 * @var FilesystemHelper
	 */
	private $fs;

	/**
	 * @var array
	 */
	private $servers = [];
	/**
	 * @var User_LDAP[]
	 */
	private $userBackends = [];
	/**
	 * @var Group_LDAP[]
	 */
	private $groupBackends = [];

	/**
	 * @param IConfig $coreConfig
	 * @param ILogger $logger
	 * @param IAvatarManager $avatarManager
	 * @param IUserManager $coreUserManager
	 * @param IDBConnection $db
	 * @param LDAP $ldap
	 * @param UserMapping $userMap
	 * @param GroupMapping $groupMap
	 * @param FilesystemHelper $fs
	 */
	public function __construct(
		IConfig $coreConfig,
		ILogger $logger,
		IAvatarManager $avatarManager,
		IUserManager $coreUserManager,
		IDBConnection $db,
		LDAP $ldap,
        UserMapping $userMap,
		GroupMapping $groupMap,
		FilesystemHelper $fs
	) {
		$this->coreConfig = $coreConfig;
		$this->logger = $logger;
		$this->avatarManager = $avatarManager;
		$this->coreUserManager = $coreUserManager;
		$this->db = $db;
		$this->ldap = $ldap;
		$this->userMap = $userMap;
		$this->groupMap = $groupMap;
		$this->fs = $fs;
	}

	const WITH_ATTRIBUTES = 0; // see description of http://php.net/manual/en/function.ldap-explode-dn.php

    private function setNode($id, $type, Tree $tree) {
        $baseDN = $tree->getBaseDN();
        $dn = $this->ldap->explodeDN($baseDN, self::WITH_ATTRIBUTES);
        unset($dn['count']);
        $dn = \array_reverse($dn);
        $node = &$this->servers[$id];
        foreach ($dn as $rdn) {
            $rdn = \strtolower($rdn);
            // explodeDN might return escaped UTF-8, see http://php.net/manual/en/function.ldap-explode-dn.php
            $rdn = preg_replace_callback(
                '/\\\([0-9a-f]{2})/',
                function ($matches) { return chr(hexdec($matches[1])); },
                $rdn
            );
            if (!isset($node[$rdn])) {
                $node[$rdn] = [];
            }
            $node = &$node[$rdn];
        }
        // $node now is the node in the tree of configured base dns that
        // contains the mapping configuration for that base dn
        $node[$type] = $tree;
    }

	public function registerServer(Server $server) {
		$id = $server->getId();
		if (!isset($this->servers[$id])) {
			$this->servers[$id] = [];
		}
        foreach ($server->getUserTrees() as $tree) {
            $this->setNode($id, 'users', $tree);
        }
        foreach ($server->getGroupTrees() as $tree) {
            $this->setNode($id, 'groups', $tree);
        }
	}

	/**
	 * @param Server $server
	 * @param string $dn
	 * @return UserTree
	 * @throws \Exception
	 */
	public function getUserConfig(Server $server, $dn) {
		return $this->getConfig($server, $dn, 'users');
	}

	/**
	 * @param Server $server
	 * @param string $dn
	 * @return UserTree
	 * @throws \Exception
	 */
	public function getGroupConfig(Server $server, $dn) {
		return $this->getConfig($server, $dn, 'groups');
	}
	/**
	 * @param Server $server
	 * @param string $dn
	 * @param string $type
	 * @return Tree
	 * @throws \Exception
	 */
	private function getConfig(Server $server, $dn, $type) {
		$id = $server->getId();
		if (!isset($this->servers[$id])) {
			throw new \Exception("unknown server id");
		}
		$dna = $this->ldap->explodeDN($dn, self::WITH_ATTRIBUTES);
		if ($dna === false) {
			throw new \Exception("invalid dn $dn");
		}
		unset($dna['count']);
		$dna = \array_reverse($dna);

		$node = &$this->servers[$id];
		$mapping = null;

		foreach ($dna as $rdn) {
			$rdn = \strtolower($rdn);
			if (isset($node[$type])) {
				$mapping = $node[$type]; // pick up the more specific mapping
			}

			if (isset($node[$rdn])) {    // do we have a more specific mapping?
				$node = &$node[$rdn];    // go deeper
			} else {
				break;                   // stop descending
			}
		}
		return $mapping;
	}

	/**
	 * @param Server $server
	 * @param UserTree $mapping
	 * @return User_LDAP
	 */
	public function createUserBackend(Server $server, UserTree $mapping) {
		$id = \strtolower("{$server->getId()}:u:{$mapping->getBaseDN()}");
		if (!isset($this->userBackends[$id])) {

			$filterBuilder = new FilterBuilder($this->coreConfig);

			$access = new Access(
				$this->coreUserManager,
				new Connection($this->ldap, $server),
				$server,
				$mapping,
				$this,
				$filterBuilder,
				$this->userMap,
				$this->groupMap
			);

			$userManager = new Manager(
				$this->coreConfig,
				$this->fs,
				$this->logger,
				$this->avatarManager,
				$this->db,
				$this->coreUserManager,
				$access,
				$filterBuilder,
				$mapping
			);

			$this->userBackends[$id] = new User_LDAP(
				$this->coreConfig,
				$userManager
			);
		}
		return $this->userBackends[$id];
	}

	/**
	 * @return User_LDAP[]
	 */
	public function getUserBackends() {
		return $this->userBackends;
	}

	/**
	 * @param $id
	 * @return null|User_LDAP
	 */
	public function getUserBackend($id) {
		return isset($this->userBackends[$id]) ? $this->userBackends[$id] : null;
	}

	/**
	 * @param Server $server
	 * @param GroupTree $mapping
	 * @return Group_LDAP
	 */
	public function createGroupBackend(Server $server, GroupTree $mapping) {
		$id = \strtolower("{$server->getId()}:g:{$mapping->getBaseDN()}");
		if (!isset($this->groupBackends[$id])) {

			$filterBuilder = new FilterBuilder($this->coreConfig);

			$access = new Access(
				$this->coreUserManager,
				new Connection($this->ldap, $server),
				$server,
				$mapping,
				$this,
				$filterBuilder,
				$this->userMap,
				$this->groupMap
			);

			$this->groupBackends[$id] = new Group_LDAP(
				$server,
				$mapping,
				$access,
				$filterBuilder
			);
		}
		return $this->groupBackends[$id];
	}

	/**
	 * @return Group_LDAP[]
	 */
	public function getGroupBackends() {
		return $this->groupBackends;
	}

	/**
	 * @param $id
	 * @return null|Group_LDAP
	 */
	public function getGroupBackend($id) {
		return isset($this->groupBackends[$id]) ? $this->groupBackends[$id] : null;
	}
}