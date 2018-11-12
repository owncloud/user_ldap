<?php
/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
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

namespace OCA\User_LDAP\User;

use OC\Cache\CappedMemoryCache;
use OCA\User_LDAP\Access;
use OCA\User_LDAP\Config\UserTree;
use OCA\User_LDAP\Connection;
use OCA\User_LDAP\Exceptions\DoesNotExistOnLDAPException;
use OCA\User_LDAP\FilesystemHelper;
use OCA\User_LDAP\Mapping\AbstractMapping;
use OCA\User_LDAP\Mapping\UserMapping;
use OCP\IAvatarManager;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\ILogger;
use OCP\Image;
use OCP\IUserManager;

/**
 * Manager of UserEntries
 *
 * upon request, returns a UserEntry object either by creating or from run-time
 * cache
 */
class Manager {
	/**
	 * DB config keys for user preferences
	 */
	const USER_PREFKEY_FIRSTLOGIN  = 'firstLoginAccomplished';

	/** @var IConfig */
	protected $ocConfig;

	/** @var IDBConnection */
	protected $db;

	/** @var FilesystemHelper */
	protected $ocFilesystem;

	/** @var ILogger */
	protected $logger;

	/** @param \OCP\IAvatarManager */
	protected $avatarManager;

	/** @param IUserManager */
	protected $userManager;

	/**
	 * @var Access
	 */
	protected $access;

	/**
	 * @var Connection\FilterBuilder
	 */
	protected $filterBuilder;

	/** @var UserTree */
	protected $userTree;

	/**
	 * @var CappedMemoryCache $usersByDN
	 */
	protected $usersByDN;

	/**
	 * @var CappedMemoryCache $usersByUid
	 */
	protected $usersByUid;

	/**
	 * @param IConfig $ocConfig
	 * @param \OCA\User_LDAP\FilesystemHelper $ocFilesystem object that
	 * gives access to necessary functions from the OC filesystem
	 * @param ILogger $logger
	 * @param IAvatarManager $avatarManager
	 * @param IDBConnection $db
	 * @param IUserManager $userManager
	 * @param Access $access
	 * @param Connection\FilterBuilder $filterBuilder
	 * @param UserTree $userTree
	 */
	public function __construct(IConfig $ocConfig,
								FilesystemHelper $ocFilesystem, ILogger $logger,
								IAvatarManager $avatarManager,
								IDBConnection $db, IUserManager $userManager,
								Access $access,
								Connection\FilterBuilder $filterBuilder,
								UserTree $userTree
	) {
		$this->ocConfig      = $ocConfig;
		$this->ocFilesystem  = $ocFilesystem;
		$this->logger        = $logger;
		$this->avatarManager = $avatarManager;
		$this->db            = $db;
		$this->userManager   = $userManager;
		$this->access        = $access;
		$this->filterBuilder = $filterBuilder;
		$this->userTree    = $userTree;
		$this->usersByDN     = new CappedMemoryCache();
		$this->usersByUid    = new CappedMemoryCache();
	}

	/**
	 * @return Connection
	 */
	private function getConnection() {
		return $this->access->getConnection();
	}
	/**
	 * returns a list of attributes that will be processed further, e.g. quota,
	 * email, displayname, or others.
	 * @param bool $minimal - optional, set to true to skip attributes with big
	 * payload
	 * @return string[]
	 */
	public function getAttributes($minimal = false) {
		$attributes = ['dn' => true, 'uid' => true, 'samaccountname' => true];
		$lookupUUIDAttribute = false;
		$attributes[$this->userTree->getEmailAttribute()] = true;
		$attributes[$this->userTree->getDisplayNameAttribute()] = true;
		$attributes[$this->userTree->getDisplayName2Attribute()] = true;
		$attributes[$this->userTree->getUsernameAttribute()] = true;
		$attributes[$this->userTree->getExpertUsernameAttr()] = true;
		$attributes[$this->userTree->getQuotaAttribute()] = true;
		foreach ($this->userTree->getAdditionalSearchAttributes() as $attr) {
			$attributes[$attr] = true;
		}
		$homeRule = $this->userTree->getHomeFolderNamingRule();
		if (\strpos($homeRule, 'attr:') === 0) {
			$attributes[\substr($homeRule, \strlen('attr:'))] = true;
		}
		$uuidAttribute = $this->userTree->getUuidAttribute();
		if ($uuidAttribute !== 'auto' && $uuidAttribute !== '') {
			// if uuidAttribute is specified use that
			$attributes[$uuidAttribute] = true;
		} else {
			$lookupUUIDAttribute = true;
		}
		if ($lookupUUIDAttribute) {
			// get known possible uuidAttributes
			foreach (Access::$uuidAttributes as $attr) {
				$attributes[$attr] = true;
			}
		}

		if ($this->ocConfig->getSystemValue('enable_avatars', true) === true && !$minimal) {
			// attributes may come with big payload.

			$attributes['jpegphoto'] = true;
			$attributes['thumbnailphoto'] = true;
		}

		// we don't need the empty attribute
		unset($attributes['']);

		return \array_keys($attributes);
	}

	/**
	 * Gets an UserEntry from the LDAP server from a distinguished name
	 * @param $dn
	 * @return UserEntry
	 * @throws DoesNotExistOnLDAPException when the dn supplied cannot be found on LDAP
	 */
	public function getUserEntryByDn($dn) {
		$result = $this->access->executeRead(
			$this->getConnection()->getConnectionResource(),
			$dn,
			$this->getAttributes(),
			$this->userTree->getFilter(),
			20); // TODO why 20? why is 1 not sufficient? hm maybe if multiple servers are asked at the same time? so this is a limit to 20 servers??
		if ($result === false || $result['count'] === 0) {
			// FIXME the ldap error ($result = false) should bubble up ... and not be converted to a DoesNotExistOnLDAPException
			throw new DoesNotExistOnLDAPException($dn);
		}
		// Try to convert entry into a UserEntry
		return $this->getFromEntry($result);
	}

	/**
	 * @brief returns a User object by the DN in an ldap entry
	 * @param array $ldapEntry the ldap entry used to prefill the user properties
	 * @return \OCA\User_LDAP\User\UserEntry
	 * @throws \Exception when connection could not be established
	 * @throws \InvalidArgumentException if entry does not contain a dn
	 * @throws \OutOfBoundsException when username could not be determined
	 */
	public function getFromEntry($ldapEntry) {
		$userEntry = new UserEntry($this->ocConfig, $this->logger, $this->userTree, $ldapEntry);
		$dn = $userEntry->getDN();

		if (!$this->access->isDNPartOfUserBases($dn)) {
			throw new \OutOfBoundsException("DN <$dn> outside configured base domains: ".\print_r($this->userTree->getBaseDN(), true));
		}

		$uid = $this->resolveUID($userEntry);
		$userEntry->setOwnCloudUID($uid);

		// cache entries
		$this->usersByDN[$dn] = $userEntry;
		$this->usersByUid[$uid] = $userEntry;

		return $userEntry;
	}

	/**
	 * @param $uid
	 * @return UserEntry
	 */
	public function getCachedEntry($uid) {
		if (isset($this->usersByUid[$uid])) {
			return $this->usersByUid[$uid];
		}

		try {
			$dn = $this->username2dn($uid);
			if ($dn) {
				return $this->getUserEntryByDn($dn);
			}
		} catch (DoesNotExistOnLDAPException $e) {
			return null;
		} catch (\OutOfBoundsException $e) {
			// dn might be outside of the base
			return null;
		}

		// should have been cached during login or while iterating over multiple users, eg during sync
		$this->logger->warning('No cached ldap result found for '. $uid, ['app' => self::class]);
		return null;
	}

	/**
	 * checks whether a user is still available on LDAP
	 *
	 * @param string $dn
	 * @return bool
	 * @throws \Exception
	 * @throws \OC\ServerNotAvailableException
	 */
	public function dnExistsOnLDAP($dn) {

		//check if user really still exists by reading its entry
		if (!\is_array($this->access->readAttribute($dn, '', $this->userTree->getFilter()))) {
			$lcr = $this->getConnection()->getConnectionResource();
			if ($lcr === null) {
				throw new \Exception('No LDAP Connection to server ' /*. $this->getConnection()->ldapHost*/);
			}

			try {
				$uuid = $this->access->getUserMapper()->getUUIDByDN($dn);
				if (!$uuid) {
					return false;
				}
				$newDn = $this->getUserDnByUuid($uuid);
				//check if renamed user is still valid by reapplying the ldap filter
				if (!\is_array($this->access->readAttribute($newDn, '', $this->userTree->getFilter()))) {
					return false;
				}
				$this->access->getUserMapper()->setDNbyUUID($newDn, $uuid);
				return true;
			} catch (\Exception $e) {
				$this->logger->logException($e, ['app' => self::class]);
				return false;
			}
		}

		return true;
	}

	/**
	 * reverse lookup of a DN given a known UUID
	 *
	 * @param string $uuid
	 * @return string
	 * @throws \OutOfBoundsException
	 * @throws \OC\ServerNotAvailableException
	 * FIXME check config has proper uuid attribute before activating a config? wizard stuff
	 */
	public function getUserDnByUuid($uuid) {
		$uuidAttribute = $this->userTree->getUuidAttribute();

		if ($uuidAttribute === 'guid' || $uuidAttribute === 'objectguid') {
			$uuid = $this->access->formatGuid2ForFilterUser($uuid);
		}

		$filter = $uuidAttribute . '=' . $uuid;
		$result = $this->access->searchUsers($filter, ['dn'], 2);
		if (isset($result[0]['dn']) && \count($result) === 1) {
			// we put the count into account to make sure that this is
			// really unique
			return $result[0]['dn'][0];
		}
		return false;

	}
	/**
	 * returns an internal ownCloud name for the given LDAP DN, false on DN outside of search DN
	 * @param UserEntry $userEntry user entry object backed by an ldap entry
	 * @return string with with the uid to use in ownCloud
	 * @throws \OutOfBoundsException when uid could not be determined
	 */
	public function resolveUID(UserEntry $userEntry) {
		/** @var UserMapping $mapper */
		$mapper = $this->access->getUserMapper();

		$dn = $userEntry->getDN();

		//let's try to retrieve the ownCloud name from the mappings table
		$ocName = \trim($mapper->getNameByDN($dn));
		if (\is_string($ocName) && $ocName !== '') {
			return $ocName;
		}

		//second try: get the UUID and check if it is known. Then, update the DN and return the name.
		$uuid = $userEntry->getUUID();
		$ocName = \trim($mapper->getNameByUUID($uuid));
		if (\is_string($ocName) && $ocName !== '') {
			$mapper->setDNbyUUID($dn, $uuid);
			return $ocName;
		}

		$intName = \trim($this->access->sanitizeUsername($userEntry->getUsername()));

		//a new user/group! Add it only if it doesn't conflict with other backend's users or existing groups
		if ($intName !== '' && !\OCP\User::userExists($intName)) {
			if ($mapper->map($dn, $intName, $uuid)) {
				return $intName;
			}
		}

		// FIXME move to a better place, naming related. eg DistinguishedNameUtils
		$altName = $this->access->createAltInternalOwnCloudName($intName, true);
		if (\is_string($altName) && $mapper->map($dn, $altName, $uuid)) {
			return $altName;
		}

		throw new \OutOfBoundsException("Could not create unique name for $dn.");
	}

	/**
	 * the call to the method that saves the avatar in the file
	 * system must be postponed after the login. It is to ensure
	 * external mounts are mounted properly (e.g. with login
	 *  credentials from the session).
	 * @param UserEntry $userEntry
	 */
	public function registerAvatarHook($userEntry) {
		$avatarImage = $userEntry->getAvatarImage();
		if ($avatarImage !== null) {
			// TODO avatar won't be shown on first login because post_login is too late
			\OCP\Util::connectHook('OC_User', 'post_login', $this, 'updateAvatarPostLogin');
		}
	}

	/**
	 * called by a post_login hook to save the avatar picture
	 *
	 * @param array $params
	 */
	public function updateAvatarPostLogin($params) {
		if (isset($params['uid'])) {
			$this->updateAvatar($params['uid']);
		}
	}

	/**
	 * FIXME fails if data/avatars does not exist?
	 * @brief attempts to get an image from LDAP and sets it as ownCloud avatar
	 * @param string $uid
	 */
	public function updateAvatar($uid) {
		$userEntry = $this->getCachedEntry($uid);
		$avatarImage = $userEntry->getAvatarImage();
		if ($avatarImage === null) {
			//not set, nothing left to do;
			return;
		}
		$image = new Image();
		$image->loadFromBase64(\base64_encode($avatarImage));
		$this->setOwnCloudAvatar($userEntry, $image);
	}

	/**
	 * @brief sets an image as ownCloud avatar
	 * @param UserEntry $userEntry
	 * @param Image $image
	 */
	private function setOwnCloudAvatar(UserEntry $userEntry, Image $image) {
		if (!$image->valid()) {
			$this->logger->error('jpegPhoto data invalid for '.$userEntry->getDN(), ['app' => self::class]);
			return;
		}
		//make sure it is a square and not bigger than 128x128
		$size = \min([$image->width(), $image->height(), 128]);
		if (!$image->centerCrop($size)) {
			$this->logger->error('croping image for avatar failed for '.$userEntry->getDN(), ['app' => self::class]);
			return;
		}

		if (!$this->ocFilesystem->isLoaded()) {
			$this->ocFilesystem->setup($userEntry->getOwnCloudUID());
		}

		try {
			$avatar = $this->avatarManager->getAvatar($userEntry->getOwnCloudUID());
			$avatar->set($image);
		} catch (\Exception $e) {
			$this->logger->logException($e, ['app' => self::class]);
		}
	}

	/**
	 * @brief marks the user as having logged in at least once
	 * @param string $uid
	 */
	public function markLogin($uid) {
		$this->ocConfig->setUserValue(
			$uid, 'user_ldap', self::USER_PREFKEY_FIRSTLOGIN, 1);
	}

	/**
	 * returns an LDAP record based on a given login name
	 *
	 * @param string $loginName
	 * @return UserEntry
	 * @throws DoesNotExistOnLDAPException
	 * @throws \OC\ServerNotAvailableException
	 */
	public function getLDAPUserByLoginName($loginName) {
		//find out dn of the user name
		$attrs = $this->getAttributes();
		$users = $this->access->fetchUsersByLoginName($this->userTree, $loginName, $attrs);
		if (\count($users) < 1) {
			throw new DoesNotExistOnLDAPException('No user available for the given login name on '/* .
				$this->getConnection()->ldapHost . ':' . $this->getConnection()->ldapPort*/);
		}
		return $this->getFromEntry($users[0]);
	}

	/**
	 * Get a list of all users
	 *
	 * @param string $search
	 * @param integer $limit
	 * @param integer $offset
	 * @return string[] an array of all uids
	 * @throws \OC\ServerNotAvailableException
	 */
	public function getUsers($search = '', $limit = 10, $offset = 0) {
		$search = Access::escapeFilterPart($search, true);

		// if we'd pass -1 to LDAP search, we'd end up in a Protocol
		// error. With a limit of 0, we get 0 results. So we pass null.
		if ($limit <= 0) {
			$limit = null;
		}
		$filter = $this->filterBuilder->combineFilterWithAnd([
			$this->userTree->getFilter(),
			$this->userTree->getDisplayNameAttribute() . '=*', // TODO why do we need this? =* basically selects all? A: it requires the attribute to be not empty
			$this->filterBuilder->getFilterPartForUserSearch($this->userTree, $search)
		]);

		$this->logger->debug('getUsers: Options: search '.$search
			.' limit '.$limit
			.' offset ' .$offset
			.' Filter: '.$filter,
			['app' => self::class]);

		//do the search and translate results to owncloud names
		$ldap_users = $this->fetchListOfUsers(
			$filter,
			$this->getAttributes(),
			$limit, $offset);
		$ownCloudUserNames = [];
		foreach ($ldap_users as $ldapEntry) {
			try {
				$userEntry = $this->getFromEntry($ldapEntry);
				$this->logger->debug(
					"Caching ldap entry for <{$ldapEntry['dn'][0]}>:".\json_encode($ldapEntry),
					['app' => self::class]
				);
				$ownCloudUserNames[] = $userEntry->getOwnCloudUID();
			} catch (\OutOfBoundsException $e) {
				// tell the admin why we skip the user
				$this->logger->logException($e, ['app' => self::class]);
			}
		}

		$this->logger->debug('getUsers: '.\count($ownCloudUserNames). ' Users found', ['app' => self::class]);

		return $ownCloudUserNames;
	}

	// TODO find better places for the delegations to Access

	/**
	 * @param string $name
	 * @param string $password
	 * @return bool
	 * @throws \OC\ServerNotAvailableException
	 */
	public function areCredentialsValid($name, $password) {
		return $this->access->areCredentialsValid($name, $password);
	}
	/**
	 * returns the LDAP DN for the given internal ownCloud name of the user
	 * @param string $name the ownCloud name in question
	 * @return string|false with the LDAP DN on success, otherwise false
	 * TODO move code here or to a utility class, also move Access::sanitizeDN there
	 */
	public function username2dn($name) {
		return $this->access->username2dn($name);
	}

	/**
	 * @param string $filter
	 * @param string|string[] $attr
	 * @param int $limit
	 * @param int $offset
	 * @return array
	 * @throws \OC\ServerNotAvailableException
	 */
	private function fetchListOfUsers($filter, $attr, $limit = null, $offset = null) {
		return $this->access->fetchListOfUsers([$this->userTree], $filter, $attr, $limit, $offset);
	}

	/**
	 * returns the User Mapper
	 * @throws \Exception when UserMapper was not assigned to the Access instance, yet
	 * @return AbstractMapping
	 */
	public function getUserMapper() {
		return $this->access->getUserMapper();
	}

	/**
	 * @param string $filter
	 * @param string|string[] $attr
	 * @param int $limit
	 * @param int $offset
	 * @return false|int
	 * @throws \OC\ServerNotAvailableException
	 */
	public function countUsers($filter, $attr = ['dn'], $limit = null, $offset = null) {
		return $this->access->countUsers([$this->userTree], $filter, $attr, $limit, $offset);
	}

	/**
	 * returns the filter used for counting users
	 * @return string
	 */
	public function getFilterForUserCount() {
		return $this->filterBuilder->getFilterForUserCount($this->userTree);
	}
}
