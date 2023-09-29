<?php
/**
 * @copyright Copyright (c) 2023, ownCloud GmbH.
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

use OC\ServerNotAvailableException;
use OCA\User_LDAP\Exceptions\BindFailedException;
use OCA\User_LDAP\User_Proxy;
use OCP\UserInterface;
use OCP\Sync\User\IUserSyncBackend;
use OCP\Sync\User\SyncingUser;
use OCP\Sync\User\SyncBackendUserFailedException;
use OCP\Sync\User\SyncBackendBrokenException;

class UserSyncLDAPBackend implements IUserSyncBackend {
	/** @var User_Proxy */
	private $userProxy;

	private $connectionTested = false;
	private $pointer = 0;
	private $cachedUserData = ['min' => 0, 'max' => 0, 'last' => false];

	public function __construct(User_Proxy $userProxy) {
		$this->userProxy = $userProxy;
	}

	/**
	 * @inheritDoc
	 */
	public function resetPointer() {
		$this->connectionTested = false;
		$this->pointer = 0;
		$this->cachedUserData = ['min' => 0, 'max' => 0, 'last' => false];
	}

	/**
	 * @inheritDoc
	 */
	public function getNextUser(): ?SyncingUser {
		$chunk = 500; // TODO: this should depend on the actual configuration
		$minPointer = $this->cachedUserData['min'];
		if (!isset($this->cachedUserData['users'][$this->pointer - $minPointer])) {
			if ($this->cachedUserData['last']) {
				// we've reached the end
				return null;
			}

			try {
				if (!$this->connectionTested) {
					$test = $this->userProxy->testConnection();
					$this->connectionTested = true;
				}
				$ldap_entries = $this->userProxy->getRawUsersEntriesWithPrefix('', $chunk, $this->pointer);
			} catch (ServerNotAvailableException | BindFailedException $ex) {
				throw new SyncBackendBrokenException('Failed to get user entries', 1, $ex);
			}

			$minPointer = $this->pointer;
			$this->cachedUserData = [
				'min' => $this->pointer,
				'max' => $this->pointer + \count($ldap_entries),
				'last' => empty($ldap_entries),
				'users' => $ldap_entries,
			];
		}

		$syncingUser = null;
		if (isset($this->cachedUserData['users'][$this->pointer - $minPointer])) {
			$ldapEntryData = $this->cachedUserData['users'][$this->pointer - $minPointer];
			$this->pointer++;
			try {
				$userEntry = $this->userProxy->getUserEntryFromRawWithPrefix($ldapEntryData['prefix'], $ldapEntryData['entry']);
			} catch (\OutOfBoundsException $ex) {
				throw new SyncBackendUserFailedException("Failed to get user with dn {$ldapEntryData['entry']['dn'][0]}", 1, $ex);
			}

			try {
				$uid = $userEntry->getOwnCloudUID();
				$displayname = $userEntry->getDisplayName();
				$quota = $userEntry->getQuota();
				$email = $userEntry->getEMailAddress();
				$home = $userEntry->getHome();
				$searchTerms = $userEntry->getSearchTerms();
			} catch (\Exception $e) {
				throw new SyncBackendUserFailedException("Can't sync user with dn {$userEntry->getDN()}", 1, $ex);
			}

			$syncingUser = new SyncingUser($uid);
			$syncingUser->setDisplayName($displayname);
			if ($email !== null) {
				$syncingUser->setEmail($email);
			}
			if ($home !== null) {
				$syncingUser->setHome($home);
			}
			if ($searchTerms !== null) {
				$syncingUser->setSearchTerms($searchTerms);
			}
			if ($quota !== false) {
				$syncingUser->setQuota($quota);
			}
		} else {
			$this->pointer++;
		}
		return $syncingUser;
	}

	/**
	 * @inheritDoc
	 */
	public function getSyncingUser(string $id): ?SyncingUser {
		$syncingUser = null;

		try {
			$userEntry = $this->userProxy->getUserEntry($id);
		} catch (ServerNotAvailableException | BindFailedException $ex) {
			throw new SyncBackendBrokenException('Failed to get the user entry', 1, $ex);
		}

		if ($userEntry !== null) {
			try {
				$uid = $userEntry->getOwnCloudUID();
				$displayname = $userEntry->getDisplayName();
				$quota = $userEntry->getQuota();
				$email = $userEntry->getEMailAddress();
				$home = $userEntry->getHome();
				$searchTerms = $userEntry->getSearchTerms();
			} catch (\Exception $e) {
				throw new SyncBackendUserFailedException("Can't sync user with dn {$userEntry->getDN()}", 1, $ex);
			}

			$syncingUser = new SyncingUser($uid);
			$syncingUser->setDisplayName($displayname);
			if ($email !== null) {
				$syncingUser->setEmail($email);
			}
			if ($home !== null) {
				$syncingUser->setHome($home);
			}
			if ($searchTerms !== null) {
				$syncingUser->setSearchTerms($searchTerms);
			}
			if ($quota !== false) {
				$syncingUser->setQuota($quota);
			}
		}
		return $syncingUser;
	}

	/**
	 * @inheritDoc
	 */
	public function userCount(): ?int {
		$nUsers = $this->userProxy->countUsers();
		if ($nUsers !== false) {
			return $nUsers;
		}
		return null;
	}

	/**
	 * @inheritDoc
	 */
	public function getUserInterface(): UserInterface {
		return $this->userProxy;
	}
}
