<?php
/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
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

namespace OCA\User_LDAP;

class Group_Proxy extends Proxy implements \OCP\GroupInterface {
	private $backends = array();
	private $refBackend = null;

	/**
	 * Constructor
	 * @param string[] $serverConfigPrefixes array containing the config Prefixes
	 */
	public function __construct($serverConfigPrefixes, ILDAPWrapper $ldap) {
		parent::__construct($ldap);
		foreach($serverConfigPrefixes as $configPrefix) {
			$this->backends[$configPrefix] =
				new \OCA\User_LDAP\Group_LDAP($this->getAccess($configPrefix));
			if(is_null($this->refBackend)) {
				$this->refBackend = &$this->backends[$configPrefix];
			}
		}
	}

	/**
	 * Tries the backends one after the other until a positive result is returned from the specified method
	 * @param string $gid the gid connected to the request
	 * @param string $method the method of the group backend that shall be called
	 * @param array $parameters an array of parameters to be passed
	 * @return mixed, the result of the method or false
	 */
	protected function walkBackends($gid, $method, $parameters) {
		$cacheKey = $this->getGroupCacheKey($gid);
		foreach($this->backends as $configPrefix => $backend) {
			try {
				if($result = call_user_func_array(array($backend, $method), $parameters)) {
					$this->writeToCache($cacheKey, $configPrefix);
					return $result;
				}
			} catch (\OC\ServerNotAvailableException $ex) {
				$this->handleServerNotAvailable($configPrefix, $ex);
				continue;
			}
		}
		return false;
	}

	/**
	 * Asks the backend connected to the server that supposely takes care of the gid from the request.
	 * @param string $gid the gid connected to the request
	 * @param string $method the method of the group backend that shall be called
	 * @param array $parameters an array of parameters to be passed
	 * @param mixed $passOnWhen the result matches this variable
	 * @return mixed, the result of the method or false
	 */
	protected function callOnLastSeenOn($gid, $method, $parameters, $passOnWhen) {
		$cacheKey = $this->getGroupCacheKey($gid);;
		$prefix = $this->getFromCache($cacheKey);
		//in case the uid has been found in the past, try this stored connection first
		if(!is_null($prefix)) {
			if(isset($this->backends[$prefix])) {
				try {
					$result = call_user_func_array(array($this->backends[$prefix], $method), $parameters);
					if($result === $passOnWhen) {
						//not found here, reset cache to null if group vanished
						//because sometimes methods return false with a reason
						$groupExists = call_user_func_array(
							array($this->backends[$prefix], 'groupExists'),
							array($gid)
						);
						if(!$groupExists) {
							$this->writeToCache($cacheKey, null);
						}
					}
					return $result;
				} catch (\OC\ServerNotAvailableException $ex) {
					$this->handleServerNotAvailable($prefix, $ex);
					return false;
				}
			}
		}
		return false;
	}

	/**
	 * is user in group?
	 * @param string $uid uid of the user
	 * @param string $gid gid of the group
	 * @return bool
	 *
	 * Checks whether the user is member of a group or not.
	 */
	public function inGroup($uid, $gid) {
		return $this->handleRequest($gid, 'inGroup', array($uid, $gid));
	}

	/**
	 * Get all groups a user belongs to
	 * @param string $uid Name of the user
	 * @return string[] with group names
	 *
	 * This function fetches all groups a user belongs to. It does not check
	 * if the user exists at all.
	 */
	public function getUserGroups($uid) {
		$groups = array();

		foreach($this->backends as $configPrefix => $backend) {
			try {
				$backendGroups = $backend->getUserGroups($uid);
				if (is_array($backendGroups)) {
					$groups = array_merge($groups, $backendGroups);
				}
			} catch (\OC\ServerNotAvailableException $ex) {
				$this->handleServerNotAvailable($configPrefix, $ex);
				continue;
			}
		}

		return $groups;
	}

	/**
	 * get a list of all users in a group
	 * @return string[] with user ids
	 */
	public function usersInGroup($gid, $search = '', $limit = -1, $offset = 0) {
		$users = array();

		foreach($this->backends as $configPrefix => $backend) {
			try {
				$backendUsers = $backend->usersInGroup($gid, $search, $limit, $offset);
				if (is_array($backendUsers)) {
					$users = array_merge($users, $backendUsers);
				}
			} catch (\OC\ServerNotAvailableException $ex) {
				$this->handleServerNotAvailable($configPrefix, $ex);
				continue;
			}
		}

		return $users;
	}

	/**
	 * returns the number of users in a group, who match the search term
	 * @param string $gid the internal group name
	 * @param string $search optional, a search string
	 * @return int|bool
	 */
	public function countUsersInGroup($gid, $search = '') {
		return $this->handleRequest(
			$gid, 'countUsersInGroup', array($gid, $search));
	}

	/**
	 * get a list of all groups
	 * @return string[] with group names
	 *
	 * Returns a list with all groups
	 */
	public function getGroups($search = '', $limit = -1, $offset = 0) {
		$groups = array();

		foreach($this->backends as $configPrefix => $backend) {
			try {
				$backendGroups = $backend->getGroups($search, $limit, $offset);
				if (is_array($backendGroups)) {
					$groups = array_merge($groups, $backendGroups);
				}
			} catch (\OC\ServerNotAvailableException $ex) {
				$this->handleServerNotAvailable($configPrefix, $ex);
				continue;
			}
		}

		return $groups;
	}

	/**
	 * check if a group exists
	 * @param string $gid
	 * @return bool
	 */
	public function groupExists($gid) {
		return $this->handleRequest($gid, 'groupExists', array($gid));
	}

	/**
	 * Check if backend implements actions
	 * @param int $actions bitwise-or'ed actions
	 * @return boolean
	 *
	 * Returns the supported actions as int to be
	 * compared with OC_USER_BACKEND_CREATE_USER etc.
	 */
	public function implementsActions($actions) {
		//it's the same across all our user backends obviously
		return $this->refBackend->implementsActions($actions);
	}

	/**
	 * Show a log message only once per configuration prefix in order to prevent log flooding
	 * @param string $configPrefix the configuration prefix for the LDAP connection
	 * @param \Exception $ex the exception thrown
	 */
	protected function handleServerNotAvailable($configPrefix, $ex) {
		static $messages = array();
		if (!isset($messages[$configPrefix])) {
			$badConnection = $this->getAccess($configPrefix)->getConnection();
			$ldapHost = $badConnection->ldapHost;
			$ldapPort = $badConnection->ldapPort;
			\OCP\Util::writeLog('user_ldap', "can't access to group information in $ldapHost:$ldapPort ($configPrefix) : " . $ex->getMessage() . " ; jumping to the next backend", \OCP\Util::ERROR);
			// remove the backend to not query it again
			unset($this->backends[$configPrefix]);
			// mark config prefix as logged
			$messages[$configPrefix] = true;
		}
	}
}
