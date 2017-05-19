<?php
/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
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

namespace OCA\User_LDAP\User;

use OCA\User_LDAP\Connection;
use OCA\User_LDAP\FilesystemHelper;
use OCA\User_LDAP\LogWrapper;
use OCP\IAvatarManager;
use OCP\IConfig;
use OCP\Image;
use OCP\IUserManager;

/**
 * User
 *
 * represents an LDAP user, gets and holds user-specific information from LDAP
 */
class User {
	/**
	 * @var IUserTools
	 */
	protected $access;
	/**
	 * @var Connection
	 */
	protected $connection;
	/**
	 * @var IConfig
	 */
	protected $config;
	/**
	 * @var FilesystemHelper
	 */
	protected $fs;
	/**
	 * @var Image
	 */
	protected $image;
	/**
	 * @var LogWrapper
	 */
	protected $log;
	/**
	 * @var IAvatarManager
	 */
	protected $avatarManager;
	/**
	 * @var IUserManager
	 */
	protected $userManager;
	/**
	 * @var string
	 */
	protected $dn;
	/**
	 * @var string
	 */
	protected $uid;
	/**
	 * @var string[]
	 */
	protected $refreshedFeatures = array();
	/**
	 * @var string
	 */
	protected $avatarImage;

	/**
	 * DB config keys for user preferences
	 */
	const USER_PREFKEY_FIRSTLOGIN  = 'firstLoginAccomplished';
	const USER_PREFKEY_LASTREFRESH = 'lastFeatureRefresh';

	/**
	 * @brief constructor, make sure the subclasses call this one!
	 * @param string $username the internal username
	 * @param string $dn the LDAP DN
	 * @param IUserTools $access an instance that implements IUserTools for
	 * LDAP interaction
	 * @param IConfig $config
	 * @param FilesystemHelper $fs
	 * @param Image $image any empty instance
	 * @param LogWrapper $log
	 * @param IAvatarManager $avatarManager
	 * @param IUserManager $userManager
	 */
	public function __construct($username, $dn, IUserTools $access,
		IConfig $config, FilesystemHelper $fs, Image $image,
		LogWrapper $log, IAvatarManager $avatarManager, IUserManager $userManager) {

		if ($username === null) {
			throw new \InvalidArgumentException("uid for '$dn' must not be null!");
		} else if ($username === '') {
			throw new \InvalidArgumentException("uid for '$dn' must not be an empty string!");
		}

		$this->access        = $access;
		$this->connection    = $access->getConnection();
		$this->config        = $config;
		$this->fs            = $fs;
		$this->dn            = $dn;
		$this->uid           = $username;
		$this->image         = $image;
		$this->log           = $log;
		$this->avatarManager = $avatarManager;
		$this->userManager   = $userManager;
	}

	/**
	 * @brief updates properties like email, quota or avatar provided by LDAP
	 * @return null
	 */
	public function update() {
		if(is_null($this->dn)) {
			return null;
		}

		$hasLoggedIn = $this->config->getUserValue($this->uid, 'user_ldap',
				self::USER_PREFKEY_FIRSTLOGIN, 0);

		if($this->needsRefresh()) {
			$this->updateEmail();
			$this->updateQuota();
			$this->updateSearchAttributes();
			if($hasLoggedIn !== 0) {
				//we do not need to try it, when the user has not been logged in
				//before, because the file system will not be ready.
				$this->updateAvatar();
				//in order to get an avatar as soon as possible, mark the user
				//as refreshed only when updating the avatar did happen
				$this->markRefreshTime();
			}
		}
	}

	/**
	 * processes results from LDAP for attributes as returned by getAttributesToRead()
	 * @param array $ldapEntry the user entry as retrieved from LDAP
	 */
	public function processAttributes($ldapEntry) {
		$this->markRefreshTime();
		//Quota
		$attr = strtolower($this->connection->ldapQuotaAttribute);
		if(isset($ldapEntry[$attr])) {
			$this->updateQuota($ldapEntry[$attr][0]);
		} else {
			if ($this->connection->ldapQuotaDefault !== '') {
				$this->updateQuota();
			}
		}
		unset($attr);

		//Email
		$attr = strtolower($this->connection->ldapEmailAttribute);
		if(isset($ldapEntry[$attr])) {
			$this->updateEmail($ldapEntry[$attr][0]);
		}
		unset($attr);

		//displayName
		$displayName = $displayName2 = '';
		$attr = strtolower($this->connection->ldapUserDisplayName);
		if(isset($ldapEntry[$attr])) {
			$displayName = strval($ldapEntry[$attr][0]);
		}
		$attr = strtolower($this->connection->ldapUserDisplayName2);
		if(isset($ldapEntry[$attr])) {
			$displayName2 = strval($ldapEntry[$attr][0]);
		}
		if ($displayName !== '') {
			$this->composeAndStoreDisplayName($displayName);
			$this->access->cacheUserDisplayName(
				$this->getUsername(),
				$displayName,
				$displayName2
			);
		}
		unset($attr);

		// search attributes
		$this->updateSearchAttributes($ldapEntry);

		//homePath
		if(strpos($this->connection->homeFolderNamingRule, 'attr:') === 0) {
			$attr = strtolower(substr($this->connection->homeFolderNamingRule, strlen('attr:')));
			if(isset($ldapEntry[$attr])) {
				$this->access->cacheUserHome(
					$this->getUsername(), $this->getHomePath($ldapEntry[$attr][0]));
			}
		}

		//memberOf groups
		$cacheKey = 'getMemberOf'.$this->getUsername();
		$groups = false;
		if(isset($ldapEntry['memberof'])) {
			$groups = $ldapEntry['memberof'];
		}
		$this->connection->writeToCache($cacheKey, $groups);

		//Avatar
		$attrs = array('jpegphoto', 'thumbnailphoto');
		foreach ($attrs as $attr)  {
			if(isset($ldapEntry[$attr])) {
				$this->avatarImage = $ldapEntry[$attr][0];
				// the call to the method that saves the avatar in the file
				// system must be postponed after the login. It is to ensure
				// external mounts are mounted properly (e.g. with login
				// credentials from the session).
				\OCP\Util::connectHook('OC_User', 'post_login', $this, 'updateAvatarPostLogin');
				break;
			}
		}
	}

	/**
	 * @brief returns the LDAP DN of the user
	 * @return string
	 */
	public function getDN() {
		return $this->dn;
	}

	/**
	 * @brief returns the ownCloud internal username of the user
	 * @return string
	 */
	public function getUsername() {
		return $this->uid;
	}

	/**
	 * returns the home directory of the user if specified by LDAP settings
	 * @param string $valueFromLDAP
	 * @return bool|string
	 * @throws \Exception
	 */
	public function getHomePath($valueFromLDAP = null) {
		$path = strval($valueFromLDAP);
		$attr = null;

		if (is_null($valueFromLDAP)
		   && strpos($this->access->connection->homeFolderNamingRule, 'attr:') === 0
		   && $this->access->connection->homeFolderNamingRule !== 'attr:')
		{
			$attr = substr($this->access->connection->homeFolderNamingRule, strlen('attr:'));
			$homedir = $this->access->readAttribute(
				$this->access->username2dn($this->getUsername()), $attr);
			if ($homedir && isset($homedir[0])) {
				$path = $homedir[0];
			}
		}

		if ($path !== '') {
			//if attribute's value is an absolute path take this, otherwise append it to data dir
			//check for / at the beginning or pattern c:\ resp. c:/
			if(   '/' !== $path[0]
			   && !(3 < strlen($path) && ctype_alpha($path[0])
			       && $path[1] === ':' && ('\\' === $path[2] || '/' === $path[2]))
			) {
				$path = $this->config->getSystemValue('datadirectory',
						\OC::$SERVERROOT.'/data' ) . '/' . $path;
			}
			return $path;
		}

		if(    !is_null($attr)
			&& $this->config->getAppValue('user_ldap', 'enforce_home_folder_naming_rule', true)
		) {
			// a naming rule attribute is defined, but it doesn't exist for that LDAP user
			throw new \Exception('Home dir attribute can\'t be read from LDAP for uid: ' . $this->getUsername());
		}

		return false;
	}

	public function getMemberOfGroups() {
		$cacheKey = 'getMemberOf'.$this->getUsername();
		$memberOfGroups = $this->connection->getFromCache($cacheKey);
		if(!is_null($memberOfGroups)) {
			return $memberOfGroups;
		}
		$groupDNs = $this->access->readAttribute($this->getDN(), 'memberOf');
		$this->connection->writeToCache($cacheKey, $groupDNs);
		return $groupDNs;
	}

	/**
	 * @brief reads the image from LDAP that shall be used as Avatar
	 * @return string data (provided by LDAP) | false
	 */
	public function getAvatarImage() {
		if(!is_null($this->avatarImage)) {
			return $this->avatarImage;
		}

		$this->avatarImage = false;
		$attributes = array('jpegPhoto', 'thumbnailPhoto');
		foreach($attributes as $attribute) {
			$result = $this->access->readAttribute($this->dn, $attribute);
			if($result !== false && is_array($result) && isset($result[0])) {
				$this->avatarImage = $result[0];
				break;
			}
		}

		return $this->avatarImage;
	}

	/**
	 * @brief marks the user as having logged in at least once
	 * @return null
	 */
	public function markLogin() {
		$this->config->setUserValue(
			$this->uid, 'user_ldap', self::USER_PREFKEY_FIRSTLOGIN, 1);
	}

	/**
	 * @brief marks the time when user features like email have been updated
	 * @return null
	 */
	public function markRefreshTime() {
		$this->config->setUserValue(
			$this->uid, 'user_ldap', self::USER_PREFKEY_LASTREFRESH, time());
	}

	/**
	 * @brief checks whether user features needs to be updated again by
	 * comparing the difference of time of the last refresh to now with the
	 * desired interval
	 * @return bool
	 */
	private function needsRefresh() {
		$lastChecked = $this->config->getUserValue($this->uid, 'user_ldap',
			self::USER_PREFKEY_LASTREFRESH, 0);

		//TODO make interval configurable
		if((time() - intval($lastChecked)) < 86400 ) {
			return false;
		}
		return  true;
	}

	/**
	 * Composes the display name and stores it in the database. The final
	 * display name is returned.
	 *
	 * @param string $displayName
	 * @param string $displayName2
	 * @returns string the effective display name
	 */
	public function composeAndStoreDisplayName($displayName, $displayName2 = '') {
		$displayName2 = strval($displayName2);
		if($displayName2 !== '') {
			$displayName .= ' (' . $displayName2 . ')';
		}
		return $displayName;
	}

	/**
	 * @brief checks whether an update method specified by feature was run
	 * already. If not, it will marked like this, because it is expected that
	 * the method will be run, when false is returned.
	 * @param string $feature email | quota | avatar (can be extended)
	 * @return bool
	 */
	private function wasRefreshed($feature) {
		if(isset($this->refreshedFeatures[$feature])) {
			return true;
		}
		$this->refreshedFeatures[$feature] = 1;
		return false;
	}

	/**
	 * fetches the email from LDAP and stores it as ownCloud user value
	 * @param string $valueFromLDAP if known, to save an LDAP read request
	 * @return null
	 */
	public function updateEmail($valueFromLDAP = null) {
		if($this->wasRefreshed('email')) {
			return;
		}
		$email = strval($valueFromLDAP);
		if(is_null($valueFromLDAP)) {
			$emailAttribute = $this->connection->ldapEmailAttribute;
			if ($emailAttribute !== '') {
				$aEmail = $this->access->readAttribute($this->dn, $emailAttribute);
				if(is_array($aEmail) && (count($aEmail) > 0)) {
					$email = strval($aEmail[0]);
				}
			}
		}
		if ($email !== '') {
			$user = $this->userManager->get($this->uid);
			if (!is_null($user)) {
				$currentEmail = strval($user->getEMailAddress());
				if ($currentEmail !== $email) {
					try {
						$user->setEMailAddress($email);
					} catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $ex) {
						\OCP\Util::writeLog('user_ldap', 'can\'t set email [' . $email .'] for user ['. $this->uid .']. Trying to set as empty email', \OCP\Util::WARN);
						try {
							$user->setEMailAddress(null);
						} catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $ex) {
							\OCP\Util::writeLog('user_ldap', 'can\'t set email [' . $email .'] nor making it empty for user ['. $this->uid .']', \OCP\Util::ERROR);
						}
					}
				}
			}
		}
	}

	/**
	 * updates the ownCloud accounts table search string as calculated from LDAP
	 * @param string $valueFromLDAP if known, to save an LDAP read request
	 */
	public function updateSearchAttributes(array $ldapEntry = null) {
		if($this->wasRefreshed('searchAttributes')) {
			return;
		}
		$user = $this->userManager->get($this->uid);
		if (!is_null($user)) {
			// Get from LDAP if we don't have it already
			if(is_null($ldapEntry)) {
				$searchTerms = $this->getSearchTerms();
			} else {
				$searchTerms = [];
				$rawAttributes = $this->connection->ldapAttributesForUserSearch;
				$attributes = empty($rawAttributes) ? [] : $rawAttributes;
				foreach($attributes as $attr) {
					$lowerAttr = strtolower($attr);
					if(isset($ldapEntry[$lowerAttr])) {
						foreach ($ldapEntry[$lowerAttr] as $value) {
							$value = trim($value);
							if (!empty($value)) {
								$searchTerms[] = strtolower($value);
							}
						}
					}
				}
			}

			// If we have a value, which is different to the current, then let's update the accounts table
			if (array_diff($searchTerms, $user->getSearchTerms())) {
				$user->setSearchTerms($searchTerms);
			}
		}
	}

	/**
	 * @return string[]
	 */
	public function getSearchTerms() {
		$rawAttributes = $this->connection->ldapAttributesForUserSearch;
		$attributes = empty($rawAttributes) ? [] : $rawAttributes;
		// Get from LDAP if we don't have it already
		$searchTerms = [];
		foreach($attributes as $attr) {
			foreach ($this->access->readAttribute($this->dn, strtolower($attr)) as $value) {
				$value = trim($value);
				if (!empty($value)) {
					$searchTerms[] = strtolower($value);
				}
			}
		}
		return $searchTerms;
	}

	/**
	 * Overall process goes as follow:
	 * 1. fetch the quota from LDAP and check if it's parseable with the "verifyQuotaValue" function
	 * 2. if the value can't be fetched, is empty or not parseable, use the default LDAP quota
	 * 3. if the default LDAP quota can't be parsed, use the ownCloud's default quota (use 'default')
	 * 4. check if the target user exists and set the quota for the user.
	 *
	 * In order to improve performance and prevent an unwanted extra LDAP call, the $valueFromLDAP
	 * parameter can be passed with the value of the attribute. This value will be considered as the
	 * quota for the user coming from the LDAP server (step 1 of the process) It can be useful to
	 * fetch all the user's attributes in one call and use the fetched values in this function.
	 * The expected value for that parameter is a string describing the quota for the user. Valid
	 * values are 'none' (unlimited), 'default' (the ownCloud's default quota), '1234' (quota in
	 * bytes), '1234 MB' (quota in MB - check the \OC_Helper::computerFileSize method for more info)
	 *
	 * fetches the quota from LDAP and stores it as ownCloud user value
	 * @param string $valueFromLDAP the quota attribute's value can be passed,
	 * to save the readAttribute request
	 * @return null
	 */
	public function updateQuota($valueFromLDAP = null) {
		if($this->wasRefreshed('quota')) {
			return;
		}

		$quota = false;
		if(is_null($valueFromLDAP)) {
			$quotaAttribute = $this->connection->ldapQuotaAttribute;
			if ($quotaAttribute !== '') {
				$aQuota = $this->access->readAttribute($this->dn, $quotaAttribute);
				if($aQuota && (count($aQuota) > 0)) {
					if ($this->verifyQuotaValue($aQuota[0])) {
						$quota = $aQuota[0];
					} else {
						$this->log->log('not suitable LDAP quota found for user ' . $this->uid . ': [' . $aQuota[0] . ']', \OCP\Util::WARN);
					}
				}
			}
		} else {
			if ($this->verifyQuotaValue($valueFromLDAP)) {
				$quota = $valueFromLDAP;
			} else {
				$this->log->log('not suitable LDAP quota found for user ' . $this->uid . ': [' . $valueFromLDAP . ']', \OCP\Util::WARN);
			}
		}

		if ($quota === false) {
			// quota not found using the LDAP attribute (or not parseable). Try the default quota
			$defaultQuota = $this->connection->ldapQuotaDefault;
			if ($this->verifyQuotaValue($defaultQuota)) {
				$quota = $defaultQuota;
			}
		}

		$targetUser = $this->userManager->get($this->uid);
		if ($targetUser) {
			if($quota !== false) {
				$targetUser->setQuota($quota);
			} else {
				$this->log->log('not suitable default quota found for user ' . $this->uid . ': [' . $defaultQuota . ']', \OCP\Util::WARN);
				$targetUser->setQuota('default');
			}
		} else {
			$this->log->log('trying to set a quota for user ' . $this->uid . ' but the user is missing', \OCP\Util::ERROR);
		}
	}

	private function verifyQuotaValue($quotaValue) {
		return $quotaValue === 'none' || $quotaValue === 'default' || \OC_Helper::computerFileSize($quotaValue) !== false;
	}

	/**
	 * called by a post_login hook to save the avatar picture
	 *
	 * @param array $params
	 */
	public function updateAvatarPostLogin($params) {
		if(isset($params['uid']) && $params['uid'] === $this->getUsername()) {
			$this->updateAvatar();
		}
	}

	/**
	 * @brief attempts to get an image from LDAP and sets it as ownCloud avatar
	 * @return null
	 */
	public function updateAvatar() {
		if($this->wasRefreshed('avatar')) {
			return;
		}
		$avatarImage = $this->getAvatarImage();
		if($avatarImage === false) {
			//not set, nothing left to do;
			return;
		}
		$this->image->loadFromBase64(base64_encode($avatarImage));
		$this->setOwnCloudAvatar();
	}

	/**
	 * @brief sets an image as ownCloud avatar
	 * @return null
	 */
	private function setOwnCloudAvatar() {
		if(!$this->image->valid()) {
			$this->log->log('jpegPhoto data invalid for '.$this->dn, \OCP\Util::ERROR);
			return;
		}
		//make sure it is a square and not bigger than 128x128
		$size = min(array($this->image->width(), $this->image->height(), 128));
		if(!$this->image->centerCrop($size)) {
			$this->log->log('croping image for avatar failed for '.$this->dn, \OCP\Util::ERROR);
			return;
		}

		if(!$this->fs->isLoaded()) {
			$this->fs->setup($this->uid);
		}

		try {
			$avatar = $this->avatarManager->getAvatar($this->uid);
			$avatar->set($this->image);
		} catch (\Exception $e) {
			\OC::$server->getLogger()->notice(
				'Could not set avatar for ' . $this->dn	. ', because: ' . $e->getMessage(),
				['app' => 'user_ldap']);
		}
	}

}
