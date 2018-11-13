<?php
/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH.
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

use OCA\User_LDAP\Config\ServerMapper;
use OCA\User_LDAP\Config\UserTree;
use OCA\User_LDAP\Connection\BackendManager;
use OCP\IConfig;
use OCP\IUserBackend;
use OCP\User\IProvidesEMailBackend;
use OCP\User\IProvidesExtendedSearchBackend;
use OCP\User\IProvidesQuotaBackend;
use OCP\UserInterface;

class User_Proxy extends Proxy implements IUserBackend, UserInterface, IProvidesQuotaBackend, IProvidesExtendedSearchBackend, IProvidesEMailBackend {

	private $refBackend;

	/**
	 * @param ServerMapper $config
	 * @param BackendManager $manager
	 * @param IConfig $ocConfig
	 */
	public function __construct(ServerMapper $config, BackendManager $manager, IConfig $ocConfig) {
		parent::__construct($manager);
		foreach ($config->listAll() as $server) {
			$manager->registerServer($server);
			foreach ($server->getUserTrees() as $tree) {
                $backend = $manager->createUserBackend($server, $tree);
                // first backend is used for reference
                if ($this->refBackend === null) {
                    $this->refBackend = $backend;
                }
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
		foreach ($this->manager->getUserBackends() as $id => $backend) {
			if ($result = \call_user_func_array([$backend, $method], $parameters)) {
				$this->writeToCache($cacheKey, $id);
				return $result;
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
		// FIXME remove caching here ...
		$cacheKey = $this->getUserCacheKey($uid);
		$id = $this->getFromCache($cacheKey);
		//in case the uid has been found in the past, try this stored connection first
		if ($id !== null) {
			$backend = $this->manager->getUserBackend($id);
			if ($backend !== null) {
				$result = \call_user_func_array([$backend, $method], $parameters);
				if ($result === $passOnWhen) {
					//not found here, reset cache to null if user vanished
					//because sometimes methods return false with a reason
					$userExists = \call_user_func_array(
						[$backend, 'userExists'],
						[$uid]
					);
					if (!$userExists) {
						$this->writeToCache($cacheKey, null);
					}
				}
				return $result;
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
	public function getUsers($search = '', $limit = 10, $offset = 0, $options = []) {
		//we do it just as the /OC_User implementation: do not play around with limit and offset but ask all backends
		$users = [];
		foreach ($this->manager->getUserBackends() as $backend) {
			$backendUsers = $backend->getUsers($search, $limit, $offset);
			if (\is_array($backendUsers)) {
				$users = \array_merge($users, $backendUsers);
			}
		}
		return $users;
	}

	/**
	 * check if a user exists
	 * @param string $uid the username
	 * @return bool
	 */
	public function userExists($uid) {
		return $this->handleRequest($uid, 'userExists', [$uid]);
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
		return $this->handleRequest($uid, 'checkPassword', [$uid, $password]);
	}

	/**
	 * returns the username for the given login name, if available
	 * needs to check all ldap servers
	 * TODO this implies login != owncloud internal username
	 *
	 * @param string $loginName
	 * @return string|false
	 */
	public function loginName2UserName($loginName) {
		$id = 'LOGINNAME,' . $loginName;
		return $this->handleRequest($id, 'loginName2UserName', [$loginName]);
	}

	/**
	 * get the user's home directory
	 * @param string $uid the username
	 * @return string|null
	 */
	public function getHome($uid) {
		$result = $this->handleRequest($uid, 'getHome', [$uid]);
		if ($result === false) { // false means no quota for user found
			$result = null;
		}
		return $result;
	}

	/**
	 * get display name of the user
	 * @param string $uid user ID of the user
	 * @return string display name
	 */
	public function getDisplayName($uid) {
		return $this->handleRequest($uid, 'getDisplayName', [$uid]);
	}

	/**
	 * checks whether the user is allowed to change his avatar in ownCloud
	 * @param string $uid the ownCloud user name
	 * @return bool either the user can or cannot
	 */
	public function canChangeAvatar($uid) {
		return $this->handleRequest($uid, 'canChangeAvatar', [$uid]);
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
		$users = [];
		foreach ($this->manager->getUserBackends() as $backend) {
			$backendUsers = $backend->getDisplayNames($search, $limit, $offset);
			if (\is_array($backendUsers)) {
				$users = $users + $backendUsers;
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
		return $this->handleRequest($uid, 'deleteUser', [$uid]);
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
		foreach ($this->manager->getUserBackends() as $backend) {
			$backendUsers = $backend->countUsers();
			if ($backendUsers !== false) {
				$users += $backendUsers;
			}
		}
		return $users;
	}

	/**
	 * Get a users email address
	 *
	 * @param string $uid The username
	 * @return string|null
	 * @since 10.0
	 */
	public function getEMailAddress($uid) {
		$result = $this->handleRequest($uid, 'getEMailAddress', [$uid]);
		if ($result === false) { // false means no quota for user found
			$result = null;
		}
		return $result;
	}

	/**
	 * Get a users quota
	 *
	 * @param string $uid The username
	 * @return string|null
	 * @since 10.0
	 */
	public function getQuota($uid) {
		$result = $this->handleRequest($uid, 'getQuota', [$uid]);
		if ($result === false) { // false means no quota for user found
			$result = null;
		}
		return $result;
	}

	/**
	 * @param string $uid
	 * @return string[]
	 */
	public function getSearchTerms($uid) {
		$terms = $this->handleRequest($uid, 'getSearchTerms', [$uid]);
		return \is_array($terms) ? $terms : [];
	}
}
