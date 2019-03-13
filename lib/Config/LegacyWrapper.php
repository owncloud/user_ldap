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

namespace OCA\User_LDAP\Config;

class LegacyWrapper {

	/** @var Server */
	private $server;

	/** @var LegacyConfig  */
	private $legacyConfig;

	public function __construct(Server $server, LegacyConfig $legacyConfig) {
		$this->server = $server;
		$this->legacyConfig = $legacyConfig;
	}

	public function __set($name, $value) {
		switch ($name) {
			// server config
			case 'ldapHost':
				$this->server->setHost($value);
				break;
			case 'ldapPort':
				$this->server->setPort($value);
				break;
			case 'ldapBackupHost':
				$this->server->setBackupHost($value);
				break;
			case 'ldapBackupPort':
				$this->server->setBackupPort($value);
				break;
			case 'ldapOverrideMainServer':
				$this->server->setOverrideMainServer($value);
				break;
			case 'ldapAgentName':
				$this->server->setBindDN($value);
				break;
			case 'ldapAgentPassword':
				$this->server->setPassword($value);
				break;
			case 'ldapCacheTTL':
				$this->server->setCacheTTL($value);
				break;
			case 'ldapTLS':
				$this->server->setTls($value);
				break;
			case 'turnOffCertCheck':
				$this->server->setTurnOffCertCheck($value);
				break;
			case 'ldapConfigurationActive':
				$this->server->setActive($value);
				break;
			// memberof is handled by a single flag
			case 'hasMemberOfFilterSupport':
				$this->server->setSupportsMemberOf($value);
				break;
			case 'useMemberOfToDetectMembership':
				$this->server->setSupportsMemberOf($value);
				break;
			// supports paging?
			case 'ldapPagingSize':
				$this->server->setPageSize($value);
				break;

			// user tree
			case 'ldapBaseUsers':
				$tree = \array_values($this->getUserTrees())[0];
				$this->server->setUserTrees([]);
				// copy first tree config to all base dns
				foreach ($this->getMultiValues($value) as $base) {
					$newTree = clone $tree;
					$newTree->setBaseDN($base);
					$this->server->setUserTree($base, $newTree);
				}
				break;
			case 'ldapUserFilter':
				foreach ($this->getUserTrees() as $ut) {
					$ut->setFilter($value);
				}
				break;
			case 'ldapUserFilterMode':
				foreach ($this->getUserTrees() as $ut) {
					$ut->setFilterMode($value);
				}
				break;
			case 'ldapUserFilterObjectclass':
				foreach ($this->getUserTrees() as $ut) {
					$ut->setFilterObjectclass($this->getMultiValues($value));
				}
				break;
			case 'ldapUserFilterGroups':
				foreach ($this->getUserTrees() as $ut) {
					$ut->setFilterGroups($this->getMultiValues($value));
				}
				break;
			case 'ldapUserDisplayName':
				foreach ($this->getUserTrees() as $ut) {
					$ut->setDisplayNameAttribute($value);
				}
				break;
			case 'ldapAttributesForUserSearch':
				foreach ($this->getUserTrees() as $ut) {
					$ut->setAdditionalSearchAttributes($this->getMultiValues($value));
				}
				break;
			case 'ldapExpertUUIDUserAttr':
				foreach ($this->getUserTrees() as $ut) {
					$ut->setUuidAttribute($value);
				}
				break;
			case 'ldapLoginFilter':
				foreach ($this->getUserTrees() as $ut) {
					$ut->setLoginFilter($value);
				}
				break;
			case 'ldapLoginFilterMode':
				foreach ($this->getUserTrees() as $ut) {
					$ut->setLoginFilterMode($value);
				}
				break;
			case 'ldapLoginFilterEmail':
				foreach ($this->getUserTrees() as $ut) {
					$ut->setLoginFilterEmail($value);
				}
				break;
			case 'ldapLoginFilterUsername':
				foreach ($this->getUserTrees() as $ut) {
					$ut->setLoginFilterUsername($value);
				}
				break;
			case 'ldapLoginFilterAttributes':
				foreach ($this->getUserTrees() as $ut) {
					$ut->setLoginFilterAttributes($this->getMultiValues($value));
				}
				break;
			case 'ldapUserDisplayName2':
				foreach ($this->getUserTrees() as $ut) {
					$ut->setDisplayName2Attribute($value);
				}
				break;
			case 'ldapQuotaDefault':
				foreach ($this->getUserTrees() as $ut) {
					$ut->setQuotaDefault($value);
				}
				break;
			case 'ldapQuotaAttribute':
				foreach ($this->getUserTrees() as $ut) {
					$ut->setQuotaAttribute($value);
				}
				break;
			case 'ldapEmailAttribute':
				foreach ($this->getUserTrees() as $ut) {
					$ut->setEmailAttribute($value);
				}
				break;
			case 'homeFolderNamingRule':
				foreach ($this->getUserTrees() as $ut) {
					$ut->setHomeFolderNamingRule($value);
				}
				break;
			case 'ldapExpertUsernameAttr':
				foreach ($this->getUserTrees() as $ut) {
					$ut->setExpertUsernameAttr($value);
				}
				break;

			//group tree
			case 'ldapBaseGroups':
				$tree = \array_values($this->getGroupTrees())[0];
				$this->server->setGroupTrees([]);
				// copy first tree config to all base dns
				foreach ($this->getMultiValues($value) as $base) {
					$newTree = clone $tree;
					$newTree->setBaseDN($base);
					$this->server->setGroupTree($base, $newTree);
				}
				break;
			case 'ldapGroupFilter':
				foreach ($this->getGroupTrees() as $ut) {
					$ut->setFilter($value);
				}
				break;
			case 'ldapGroupFilterMode':
				foreach ($this->getGroupTrees() as $ut) {
					$ut->setFilterMode($value);
				}
				break;
			case 'ldapGroupFilterObjectclass':
				foreach ($this->getGroupTrees() as $ut) {
					$ut->setFilterObjectclass($this->getMultiValues($value));
				}
				break;
			case 'ldapGroupFilterGroups':
				foreach ($this->getGroupTrees() as $ut) {
					$ut->setFilterGroups($this->getMultiValues($value));
				}
				break;
			case 'ldapGroupDisplayName':
				foreach ($this->getGroupTrees() as $ut) {
					$ut->setDisplayNameAttribute($value);
				}
				break;
			case 'ldapAttributesForGroupSearch':
				foreach ($this->getGroupTrees() as $ut) {
					$ut->setAdditionalSearchAttributes($this->getMultiValues($value));
				}
				break;
			case 'ldapExpertUUIDGroupAttr':
				foreach ($this->getGroupTrees() as $ut) {
					$ut->setUuidAttribute($value);
				}
				break;
			case 'ldapGroupMemberAssocAttr':
				foreach ($this->getGroupTrees() as $ut) {
					$ut->setMemberAttribute($value);
				}
				break;
			case 'ldapNestedGroups':
				foreach ($this->getGroupTrees() as $ut) {
					$ut->setNestedGroups($value);
				}
				break;
			case 'ldapDynamicGroupMemberURL':
				foreach ($this->getGroupTrees() as $ut) {
					$ut->setDynamicGroupMemberURL($value);
				}
				break;

			//case 'ldap_base'                         : break;
			//case 'last_jpegPhoto_lookup'             : break;
			//case 'ldap_experienced_admin'            : break;
		}
	}

	private function getUserTrees() {
		$trees = $this->server->getUserTrees();
		if (empty($trees)) {
			// create tree on the fly
			$this->server->setUserTree('', new UserTree([]));
		}
		return $this->server->getUserTrees();
	}

	private function getGroupTrees() {
		$trees = $this->server->getGroupTrees();
		if (empty($trees)) {
			// create tree on the fly
			$this->server->setGroupTree('', new GroupTree([]));
		}
		return $this->server->getGroupTrees();
	}

	private function getMultiValues($value) {
		return \preg_split('/\r\n|\r|\n|;/', $value);
	}
}
