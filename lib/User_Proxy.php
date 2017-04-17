<?php
/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

use OCA\User_LDAP\User\User;
use OCP\IConfig;

class User_Proxy extends Proxy implements \OCP\IUserBackend, \OCP\UserInterface {
	private $backends = array();
	private $refBackend = null;

	/**
	 * Constructor
	 * @param array $serverConfigPrefixes array containing the config Prefixes
	 */
	public function __construct(array $serverConfigPrefixes, ILDAPWrapper $ldap, IConfig $ocConfig) {
		parent::__construct($ldap);
		foreach($serverConfigPrefixes as $configPrefix) {
			$this->backends[$configPrefix] =
				new User_LDAP($this->getAccess($configPrefix), $ocConfig);
			if(is_null($this->refBackend)) {
				$this->refBackend = &$this->backends[$configPrefix];
			}
		}
	}

	/**
	 * Tries the backends one after the other until a positive result is returned from the specified method
	 * @param string $uid the uid connected to the request
	 * @param string $method the method of the user backend that shall be called
	 * @param array $parameters an array of parameters to be passed
	 * @return mixed the result of the method or false
	 */
	protected function walkBackends($uid, $method, $parameters) {
		$cacheKey = $this->getUserCacheKey($uid);
		foreach($this->backends as $configPrefix => $backend) {
			$instance = $backend;
			if(!method_exists($instance, $method)
				&& method_exists($this->getAccess($configPrefix), $method)) {
				$instance = $this->getAccess($configPrefix);
			}
			try {
				if($result = call_user_func_array(array($instance, $method), $parameters)) {
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
	 * Asks the backend connected to the server that supposely takes care of the uid from the request.
	 * @param string $uid the uid connected to the request
	 * @param string $method the method of the user backend that shall be called
	 * @param array $parameters an array of parameters to be passed
	 * @param mixed $passOnWhen the result matches this variable
	 * @return mixed the result of the method or false
	 */
	protected function callOnLastSeenOn($uid, $method, $parameters, $passOnWhen) {
		$cacheKey = $this->getUserCacheKey($uid);
		$prefix = $this->getFromCache($cacheKey);
		//in case the uid has been found in the past, try this stored connection first
		if(!is_null($prefix)) {
			if(isset($this->backends[$prefix])) {
				$instance = $this->backends[$prefix];
				if(!method_exists($instance, $method)
					&& method_exists($this->getAccess($prefix), $method)) {
					$instance = $this->getAccess($prefix);
				}
				try {
					$result = call_user_func_array(array($instance, $method), $parameters);
					if($result === $passOnWhen) {
						//not found here, reset cache to null if user vanished
						//because sometimes methods return false with a reason
						$userExists = call_user_func_array(
							array($this->backends[$prefix], 'userExists'),
							array($uid)
						);
						if(!$userExists) {
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
	 * Backend name to be shown in user management
	 * @return string the name of the backend to be shown
	 */
	public function getBackendName() {
		return $this->refBackend->getBackendName();
	}

	/**
	 * Get a list of all users
	 *
	 * @param string $search
	 * @param null|int $limit
	 * @param null|int $offset
	 * @return string[] an array of all uids
	 */
	public function getUsers($search = '', $limit = 10, $offset = 0) {
		//we do it just as the /OC_User implementation: do not play around with limit and offset but ask all backends
		$users = array();
		foreach($this->backends as $configPrefix => $backend) {
			try {
			$backendUsers = $backend->getUsers($search, $limit, $offset);
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
	 * check if a user exists
	 * @param string $uid the username
	 * @return boolean
	 */
	public function userExists($uid) {
		return $this->handleRequest($uid, 'userExists', array($uid));
	}

	/**
	 * check if a user exists on LDAP
	 * @param string|\OCA\User_LDAP\User\User $user either the ownCloud user
	 * name or an instance of that user
	 * @return boolean
	 */
	public function userExistsOnLDAP($user) {
		$id = ($user instanceof User) ? $user->getUsername() : $user;
		return $this->handleRequest($id, 'userExistsOnLDAP', array($user));
	}

	/**
	 * Check if the password is correct
	 * @param string $uid The username
	 * @param string $password The password
	 * @return bool
	 *
	 * Check if the password is correct without logging in the user
	 */
	public function checkPassword($uid, $password) {
		return $this->handleRequest($uid, 'checkPassword', array($uid, $password));
	}

	/**
	 * returns the username for the given login name, if available
	 *
	 * @param string $loginName
	 * @return string|false
	 */
	public function loginName2UserName($loginName) {
		$id = 'LOGINNAME,' . $loginName;
		return $this->handleRequest($id, 'loginName2UserName', array($loginName));
	}

	/**
	 * get the user's home directory
	 * @param string $uid the username
	 * @return boolean
	 */
	public function getHome($uid) {
		return $this->handleRequest($uid, 'getHome', array($uid));
	}

	/**
	 * get display name of the user
	 * @param string $uid user ID of the user
	 * @return string display name
	 */
	public function getDisplayName($uid) {
		return $this->handleRequest($uid, 'getDisplayName', array($uid));
	}

	/**
	 * checks whether the user is allowed to change his avatar in ownCloud
	 * @param string $uid the ownCloud user name
	 * @return boolean either the user can or cannot
	 */
	public function canChangeAvatar($uid) {
		return $this->handleRequest($uid, 'canChangeAvatar', array($uid), true);
	}

	/**
	 * Get a list of all display names and user ids.
	 * @param string $search
	 * @param string|null $limit
	 * @param string|null $offset
	 * @return array an array of all displayNames (value) and the corresponding uids (key)
	 */
	public function getDisplayNames($search = '', $limit = null, $offset = null) {
		//we do it just as the /OC_User implementation: do not play around with limit and offset but ask all backends
		$users = array();
		foreach($this->backends as $configPrefix => $backend) {
			try {
				$backendUsers = $backend->getDisplayNames($search, $limit, $offset);
				if (is_array($backendUsers)) {
					$users = $users + $backendUsers;
				}
			} catch (\OC\ServerNotAvailableException $ex) {
				$this->handleServerNotAvailable($configPrefix, $ex);
				continue;
			}
		}
		return $users;
	}

	/**
	 * delete a user
	 * @param string $uid The username of the user to delete
	 * @return bool
	 *
	 * Deletes a user
	 */
	public function deleteUser($uid) {
		return $this->handleRequest($uid, 'deleteUser', array($uid));
	}

	/**
	 * @return bool
	 */
	public function hasUserListings() {
		return $this->refBackend->hasUserListings();
	}

	/**
	 * Count the number of users
	 * @return int|bool
	 */
	public function countUsers() {
		$users = false;
		foreach($this->backends as $configPrefix => $backend) {
			try {
				$backendUsers = $backend->countUsers();
				if ($backendUsers !== false) {
					$users += $backendUsers;
				}
			} catch (\OC\ServerNotAvailableException $ex) {
				$this->handleServerNotAvailable($configPrefix, $ex);
				continue;
			}
		}
		return $users;
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
			\OCP\Util::writeLog('user_ldap', "can't access to user information in $ldapHost:$ldapPort ($configPrefix) : " . $ex->getMessage() . " ; jumping to the next backend", \OCP\Util::ERROR);
			// remove the backend to not query it again
			unset($this->backends[$configPrefix]);
			// mark config prefix as logged
			$messages[$configPrefix] = true;
		}
	}

}
