<?php
/**
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Copyright (c) 2019, ownCloud GmbH
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

use OCP\IDBConnection;
use OCP\IConfig;

class LegacyConfig {
	const LEGACY_PREFIX = 'conn-';

	/** @var IDBConnection */
	private $dbConnection;

	/** @var IConfig */
	private $coreConfig;

	/** @var array an associative array with the default values.
	 */
	private $defaults = [
		'ldapHost'                       => '',
		'ldapPort'                       => '',
		'ldapBackupHost'                 => '',
		'ldapBackupPort'                 => '',
		'ldapOverrideMainServer'         => '',
		'ldapAgentName'                  => '',
		'ldapAgentPassword'              => '',
		'ldapBase'                       => '',
		'ldapBaseUsers'                  => '',
		'ldapBaseGroups'                 => '',
		'ldapUserFilter'                 => '',
		'ldapUserFilterMode'             => 0,
		'ldapUserFilterObjectclass'      => '',
		'ldapUserFilterGroups'           => '',
		'ldapLoginFilter'                => '',
		'ldapLoginFilterMode'            => 0,
		'ldapLoginFilterEmail'           => 0,
		'ldapLoginFilterUsername'        => 1,
		'ldapLoginFilterAttributes'      => '',
		'ldapGroupFilter'                => '',
		'ldapGroupFilterMode'            => 0,
		'ldapGroupFilterObjectclass'     => '',
		'ldapGroupFilterGroups'          => '',
		'ldapUserName'                   => 'samaccountname',
		'ldapUserDisplayName'            => 'displayName',
		'ldapUserDisplayName2'           => '',
		'ldapGroupDisplayName'           => 'cn',
		'ldapTLS'                        => 0,
		'ldapQuotaDefault'               => '',
		'ldapQuotaAttribute'             => '',
		'ldapEmailAttribute'             => '',
		'ldapGroupMemberAssocAttr'       => 'uniqueMember',
		'ldapCacheTTL'                   => 600,
		'ldapNetworkTimeout'             => 2,
		'ldapUuidUserAttribute'          => 'auto',
		'ldapUuidGroupAttribute'         => 'auto',
		'homeFolderNamingRule'           => '',
		'turnOffCertCheck'               => 0,
		'ldapConfigurationActive'        => 0,
		'ldapAttributesForUserSearch'    => '',
		'ldapAttributesForGroupSearch'   => '',
		'ldapExpertUsernameAttr'         => '',
		'ldapExpertUUIDUserAttr'         => '',
		'ldapExpertUUIDGroupAttr'        => '',
		'hasMemberOfFilterSupport'       => 0,
		'useMemberOfToDetectMembership'  => 1,
		'lastJpegPhotoLookup'            => 0,
		'ldapNestedGroups'               => 0,
		'ldapPagingSize'                 => 500,
		'ldapExperiencedAdmin'           => 0,
		'ldapDynamicGroupMemberURL'      => '',
		'ldapIgnoreNamingRules'          => false
	];

	private $data = [];

	public function __construct(IDBConnection $dbConnection, IConfig $coreConfig) {
		$this->dbConnection = $dbConnection;
		$this->coreConfig = $coreConfig;
	}

	/**
	 * Get all available config prefixes
	 */
	public function getAllPrefixes() {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->select('configkey', 'configvalue')
			->from('appconfig')
			->where(
				$qb->expr()->eq('appid', $qb->expr()->literal('user_ldap'))
			)
			->andWhere(
				$qb->expr()->like('configkey', $qb->expr()->literal(self::LEGACY_PREFIX . '%'))
			);
		$result = $qb->execute();
		$configs = $result->fetchAll();
		$prefixes = [];
		foreach ($configs as $config) {
			$prefixes[] = \substr($config['configkey'], \strlen(self::LEGACY_PREFIX));
		}
		return $prefixes;
	}

	/**
	 * Get a legacy config by its prefix
	 *
	 * @param string $prefix
	 *
	 * @return string[]
	 */
	public function getConfig($prefix) {
		$conn = $this->coreConfig->getAppValue(
			'user_ldap', self::LEGACY_PREFIX . $prefix, '{}'
		);
		$rawConnData = \json_decode($conn, true);

		$data = $this->defaults;
		foreach ($rawConnData as $key => $val) {
			if (!\array_key_exists($key, $this->defaults)) {
				//some are determined
				continue;
			}
			$value = $rawConnData[$key];
			switch ($key) {
				case 'ldapIgnoreNamingRules':
					//$readMethod = 'getSystemValue';
					//$dbKey = $key;
					break;
				case 'ldapAgentPassword':
					// password
					$value = \base64_decode($value);
					break;
				case 'ldapUserDisplayName2':
				case 'ldapGroupDisplayName':
					// lowercase
					$value = \mb_strtolower($value, 'UTF-8');
					break;
				case 'ldapUserDisplayName':
				default:
					// user display name does not lower case because
					// we rely on an upper case N as indicator whether to
					// auto-detect it or not. FIXME
					break;
			}
			$data[$key] = $value;
		}
		return $data;
	}
}
