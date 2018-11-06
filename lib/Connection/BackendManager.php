<?php

namespace OCA\User_LDAP\Connection;

use OCA\User_LDAP\Access;
use OCA\User_LDAP\Config\Mapping;
use OCA\User_LDAP\Config\Server;
use OCA\User_LDAP\Connection;
use OCA\User_LDAP\FilesystemHelper;
use OCA\User_LDAP\ILDAPWrapper;
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
	 * @var Access[]
	 */
	private $backends = [];

	/**
	 * @param IConfig $coreConfig
	 * @param ILogger $logger
	 * @param IAvatarManager $avatarManager
	 * @param IUserManager $coreUserManager
	 * @param IDBConnection $db
	 * @param ILDAPWrapper $ldap
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
		ILDAPWrapper $ldap,
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


	public function createUserBackend(Server $server, Mapping $mapping) {
		$id = "{$server->getId()}:users:{$mapping->getBaseDN()}";
		if (!isset($this->backends[$id])) {
			$connector = new Connection($this->ldap, $server);

			$userManager = new Manager(
				$this->coreConfig,
				$this->fs,
				$this->logger,
				$this->avatarManager,
				$this->db,
				$this->coreUserManager
			);

			$access = new Access($connector, $userManager);
			$access->setUserMapper($this->userMap);
			$access->setGroupMapper($this->groupMap);

			$this->backends[$id] = new User_LDAP($this->coreConfig, $userManager);
		}
		return $this->backends[$id];

	}


	public function getUserBackends() {

	}
}