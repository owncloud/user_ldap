<?php
/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Dominik Schmidt <dev@dominik-schmidt.de>
 * @author felixboehm <felix@webhippie.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Renaud Fortier <Renaud.Fortier@fsaa.ulaval.ca>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Tom Needham <tom@owncloud.com>
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

use OC\User\NoUserException;
use OCA\User_LDAP\User\User;
use OCP\IConfig;
use OCP\IUserBackend;
use OCP\UserInterface;

class User_LDAP extends BackendUtility implements IUserBackend, UserInterface {

	/** @var \OCP\IConfig */
	protected $ocConfig;

	/**
	 * @param Access $access
	 * @param \OCP\IConfig $ocConfig
	 */
	public function __construct(Access $access, IConfig $ocConfig) {
		parent::__construct($access);
		$this->ocConfig = $ocConfig;
	}

	/**
	 * checks whether the user is allowed to change his avatar in ownCloud
	 * @param string $uid the ownCloud user name
	 * @return boolean either the user can or cannot
	 */
	public function canChangeAvatar($uid) {
		$user = $this->access->userManager->get($uid);
		if(!$user instanceof User) {
			return false;
		}
		if($user->getAvatarImage() === false) {
			return true;
		}

		return false;
	}

	/**
	 * returns the username for the given login name, if available
	 *
	 * @param string $loginName
	 * @return string|false
	 */
	public function loginName2UserName($loginName) {
		try {
			$ldapRecord = $this->getLDAPUserByLoginName($loginName);
			$user = $this->access->userManager->get($ldapRecord['dn'][0]);
			return $user->getUsername();
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * returns an LDAP record based on a given login name
	 *
	 * @param string $loginName
	 * @return array
	 * @throws \Exception
	 */
	public function getLDAPUserByLoginName($loginName) {
		//find out dn of the user name
		$attrs = $this->access->userManager->getAttributes();
		$users = $this->access->fetchUsersByLoginName($loginName, $attrs);
		if(count($users) < 1) {
			throw new \Exception('No user available for the given login name on ' .
				$this->access->connection->ldapHost . ':' . $this->access->connection->ldapPort);
		}
		return $users[0];
	}

	/**
	 * Check if the password is correct
	 * @param string $uid The username
	 * @param string $password The password
	 * @return false|string
	 *
	 * Check if the password is correct without logging in the user
	 */
	public function checkPassword($uid, $password) {
		try {
			$ldapRecord = $this->getLDAPUserByLoginName($uid);
		} catch(\Exception $e) {
			\OC::$server->getLogger()->logException($e, ['app' => 'user_ldap']);
			return false;
		}
		$dn = $ldapRecord['dn'][0];
		$user = $this->access->userManager->get($dn);

		if(!$user instanceof User) {
			\OCP\Util::writeLog('user_ldap',
				'LDAP Login: Could not get user object for DN ' . $dn .
				'. Maybe the LDAP entry has no set display name attribute?',
				\OCP\Util::WARN);
			return false;
		}
		if($user->getUsername() !== false) {
			//are the credentials OK?
			if(!$this->access->areCredentialsValid($dn, $password)) {
				return false;
			}

			$this->access->cacheUserExists($user->getUsername());
			$user->processAttributes($ldapRecord);
			$user->markLogin();

			return $user->getUsername();
		}

		return false;
	}

	/**
	 * Get a list of all users
	 *
	 * @param string $search
	 * @param integer $limit
	 * @param integer $offset
	 * @return string[] an array of all uids
	 */
	public function getUsers($search = '', $limit = 10, $offset = 0) {
		$search = $this->access->escapeFilterPart($search, true);
		$cachekey = 'getUsers-'.$search.'-'.$limit.'-'.$offset;

		//check if users are cached, if so return
		$ldap_users = $this->access->connection->getFromCache($cachekey);
		if(!is_null($ldap_users)) {
			return $ldap_users;
		}

		// if we'd pass -1 to LDAP search, we'd end up in a Protocol
		// error. With a limit of 0, we get 0 results. So we pass null.
		if($limit <= 0) {
			$limit = null;
		}
		$filter = $this->access->combineFilterWithAnd(array(
			$this->access->connection->ldapUserFilter,
			$this->access->connection->ldapUserDisplayName . '=*',
			$this->access->getFilterPartForUserSearch($search)
		));

		\OCP\Util::writeLog('user_ldap',
			'getUsers: Options: search '.$search.' limit '.$limit.' offset '.$offset.' Filter: '.$filter,
			\OCP\Util::DEBUG);
		//do the search and translate results to owncloud names
		$ldap_users = $this->access->fetchListOfUsers(
			$filter,
			$this->access->userManager->getAttributes(true),
			$limit, $offset);
		$ldap_users2 = $this->access->ownCloudUserNames($ldap_users);
		\OCP\Util::writeLog('user_ldap', 'getUsers: '.count($ldap_users2). ' Users found', \OCP\Util::DEBUG);

		// sanity check
		$ldap_users_count = count($ldap_users);
		$ldap_users2_count = count($ldap_users2);
		if ($ldap_users_count !== $ldap_users2_count) {
			\OCP\Util::writeLog('user_ldap', "LDAP unable to find user mappings for all the users returned by the search. Returning $ldap_users2_count instead of $ldap_users_count", \OCP\Util::ERROR);
		}

		$this->access->connection->writeToCache($cachekey, $ldap_users2);
		return $ldap_users2;
	}

	/**
	 * checks whether a user is still available on LDAP
	 *
	 * @param string|\OCA\User_LDAP\User\User $user either the ownCloud user
	 * name or an instance of that user
	 * @return bool
	 * @throws \Exception
	 * @throws \OC\ServerNotAvailableException
	 */
	public function userExistsOnLDAP($user) {
		if(is_string($user)) {
			$user = $this->access->userManager->get($user);
		}
		if(is_null($user)) {
			return false;
		}

		$dn = $user->getDN();
		//check if user really still exists by reading its entry
		if(!is_array($this->access->readAttribute($dn, '', $this->access->connection->ldapUserFilter))) {
			$lcr = $this->access->connection->getConnectionResource();
			if(is_null($lcr)) {
				throw new \Exception('No LDAP Connection to server ' . $this->access->connection->ldapHost);
			}

			try {
				$uuid = $this->access->getUserMapper()->getUUIDByDN($dn);
				if(!$uuid) {
					return false;
				}
				$newDn = $this->access->getUserDnByUuid($uuid);
				//check if renamed user is still valid by reapplying the ldap filter
				if(!is_array($this->access->readAttribute($newDn, '', $this->access->connection->ldapUserFilter))) {
					
					return false;
				}
				$this->access->getUserMapper()->setDNbyUUID($newDn, $uuid);
				return true;
			} catch (\Exception $e) {
				return false;
			}
		}

		return true;
	}

	/**
	 * check if a user exists
	 * @param string $uid the username
	 * @return boolean
	 * @throws \Exception when connection could not be established
	 */
	public function userExists($uid) {
		$userExists = $this->access->connection->getFromCache('userExists'.$uid);
		if(!is_null($userExists)) {
			return (bool)$userExists;
		}
		//getting dn, if false the user does not exist. If dn, he may be mapped only, requires more checking.
		$user = $this->access->userManager->get($uid);

		if(is_null($user)) {
			\OCP\Util::writeLog('user_ldap', 'No DN found for '.$uid.' on '.
				$this->access->connection->ldapHost, \OCP\Util::DEBUG);
			$this->access->connection->writeToCache('userExists'.$uid, false);
			return false;
		}

		$result = $this->userExistsOnLDAP($user);
		$this->access->connection->writeToCache('userExists'.$uid, $result);
		return $result;
	}

	/**
	* returns whether a user was deleted in LDAP
	*
	* @param string $uid The username of the user to delete
	* @return bool
	*/
	public function deleteUser($uid) {
		\OC::$server->getLogger()->info('Cleaning up after user ' . $uid,
			array('app' => 'user_ldap'));

		$this->access->getUserMapper()->unmap($uid);

		return true;
	}

	/**
	 * get the user's home directory
	 *
	 * @param string $uid the username
	 * @return bool|string
	 * @throws NoUserException
	 * @throws \Exception
	 */
	public function getHome($uid) {
		// user Exists check required as it is not done in user proxy!
		if(!$this->userExists($uid)) {
			return false;
		}

		$cacheKey = 'getHome'.$uid;
		$path = $this->access->connection->getFromCache($cacheKey);
		if(!is_null($path)) {
			return $path;
		}

		$user = $this->access->userManager->get($uid);
		if(is_null($user)) {
			throw new NoUserException($uid . ' is not a valid user anymore');
		}
		$path = $user->getHomePath();
		$this->access->cacheUserHome($uid, $path);

		return $path;
	}

	/**
	 * get display name of the user
	 * @param string $uid user ID of the user
	 * @return string|false display name
	 */
	public function getDisplayName($uid) {
		if(!$this->userExists($uid)) {
			return false;
		}

		$cacheKey = 'getDisplayName'.$uid;
		if(!is_null($displayName = $this->access->connection->getFromCache($cacheKey))) {
			return $displayName;
		}

		//Check whether the display name is configured to have a 2nd feature
		$additionalAttribute = $this->access->connection->ldapUserDisplayName2;
		$displayName2 = '';
		if ($additionalAttribute !== '') {
			$displayName2 = $this->access->readAttribute(
				$this->access->username2dn($uid),
				$additionalAttribute);
		}

		$displayName = $this->access->readAttribute(
			$this->access->username2dn($uid),
			$this->access->connection->ldapUserDisplayName);

		if($displayName && (count($displayName) > 0)) {
			$displayName = $displayName[0];

			if (is_array($displayName2)){
				$displayName2 = count($displayName2) > 0 ? $displayName2[0] : '';
			}

			$user = $this->access->userManager->get($uid);
			$displayName = $user->composeAndStoreDisplayName($displayName, $displayName2);
			$this->access->connection->writeToCache($cacheKey, $displayName);
			return $displayName;
		}

		return null;
	}

	/**
	 * Get a list of all display names
	 *
	 * @param string $search
	 * @param string|null $limit
	 * @param string|null $offset
	 * @return array an array of all displayNames (value) and the corresponding uids (key)
	 */
	public function getDisplayNames($search = '', $limit = null, $offset = null) {
		$cacheKey = 'getDisplayNames-'.$search.'-'.$limit.'-'.$offset;
		if(!is_null($displayNames = $this->access->connection->getFromCache($cacheKey))) {
			return $displayNames;
		}

		$displayNames = array();
		$users = $this->getUsers($search, $limit, $offset);
		foreach ($users as $user) {
			$displayNames[$user] = $this->getDisplayName($user);
		}
		$this->access->connection->writeToCache($cacheKey, $displayNames);
		return $displayNames;
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
		return (bool)((\OC\User\Backend::CHECK_PASSWORD
			| \OC\User\Backend::GET_HOME
			| \OC\User\Backend::GET_DISPLAYNAME
			| \OC\User\Backend::PROVIDE_AVATAR
			| \OC\User\Backend::COUNT_USERS)
			& $actions);
	}

	/**
	 * @return bool
	 */
	public function hasUserListings() {
		return true;
	}

	/**
	 * counts the users in LDAP
	 *
	 * @return int|bool
	 */
	public function countUsers() {
		$filter = $this->access->getFilterForUserCount();
		$cacheKey = 'countUsers-'.$filter;
		if(!is_null($entries = $this->access->connection->getFromCache($cacheKey))) {
			return $entries;
		}
		$entries = $this->access->countUsers($filter);
		$this->access->connection->writeToCache($cacheKey, $entries);
		return $entries;
	}

	/**
	 * Backend name to be shown in user management
	 * @return string the name of the backend to be shown
	 */
	public function getBackendName(){
		return 'LDAP';
	}

	/**
	 * @param $uid
	 * returns false if no user wes found. Will lead to the proxy to check the next server
	 * @return string[]
	 */
	public function getSearchTerms($uid) {
		$user = $this->access->userManager->get($uid);
		if ($user === null) {
			return [];
		}
		return $user->getSearchTerms();
	}

}
