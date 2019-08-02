<?php
/**
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Copyright (c) 2019, ownCloud GmbH.
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

namespace OCA\User_LDAP\Migrations;

use OCA\User_LDAP\Config\ConfigMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\ISimpleMigration;

/**
 * Migrate configs that are split across records
 * into a single json-encoded configuration
 */
class Version20190315192717 implements ISimpleMigration {
	const REFERENCE_KEY = 'ldap_configuration_active';

	/**
	 * @var array - known DB configkeys and their defaults
	 */
	private $knownLegacyKeys = [
		'has_memberof_filter_support' => 0,
		'home_folder_naming_rule' => '',
		'last_jpegPhoto_lookup' => 0,
		'ldap_agent_password' => '',
		'ldap_attributes_for_group_search' => '',
		'ldap_attributes_for_user_search' => '',
		'ldap_backup_host' => '',
		'ldap_backup_port' => '',
		'ldap_base' => '',
		'ldap_base_groups' => '',
		'ldap_base_users' => '',
		'ldap_cache_ttl' => 600,
		'ldap_configuration_active' => 0,
		'ldap_display_name' => 'displayName',
		'ldap_dn'  => '',
		'ldap_dynamic_group_member_url'  => '',
		'ldap_email_attr'  => '',
		'ldap_experienced_admin'  => 0,
		'ldap_expert_username_attr'  => '',
		'ldap_expert_uuid_group_attr' => '',
		'ldap_expert_uuid_user_attr' => '',
		'ldap_group_display_name' => 'cn',
		'ldap_group_filter'  => '',
		'ldap_group_filter_mode' => 0,
		'ldap_group_member_assoc_attribute'  => 'uniqueMember',
		'ldap_groupfilter_groups'  => '',
		'ldap_groupfilter_objectclass'  => '',
		'ldap_host'  => '',
		'ldap_login_filter'  => '',
		'ldap_login_filter_mode' => 0,
		'ldap_loginfilter_attributes' => '',
		'ldap_loginfilter_email'  => 0,
		'ldap_loginfilter_username' => 1,
		'ldap_nested_groups' => 0,
		'ldap_network_timeout' => 2,
		'ldap_override_main_server' => '',
		'ldap_paging_size' => 500,
		'ldap_port' => '',
		'ldap_quota_attr' => '',
		'ldap_quota_def' => '',
		'ldap_tls' => 0,
		'ldap_turn_off_cert_check' => 0,
		'ldap_user_display_name_2' => '',
		'ldap_user_filter_mode' => 0,
		'ldap_user_name' => 'samaccountname',
		'ldap_userfilter_groups' => '',
		'ldap_userfilter_objectclass' => '',
		'ldap_userlist_filter' => '',
		'use_memberof_to_detect_membership' => 1
	];

	private $translations = [
		'ldap_host'                         => 'ldapHost',
		'ldap_port'                         => 'ldapPort',
		'ldap_backup_host'                  => 'ldapBackupHost',
		'ldap_backup_port'                  => 'ldapBackupPort',
		'ldap_override_main_server'         => 'ldapOverrideMainServer',
		'ldap_dn'                           => 'ldapAgentName',
		'ldap_agent_password'               => 'ldapAgentPassword',
		'ldap_base'                         => 'ldapBase',
		'ldap_base_users'                   => 'ldapBaseUsers',
		'ldap_base_groups'                  => 'ldapBaseGroups',
		'ldap_userfilter_objectclass'       => 'ldapUserFilterObjectclass',
		'ldap_userfilter_groups'            => 'ldapUserFilterGroups',
		'ldap_userlist_filter'              => 'ldapUserFilter',
		'ldap_user_filter_mode'             => 'ldapUserFilterMode',
		'ldap_login_filter'                 => 'ldapLoginFilter',
		'ldap_login_filter_mode'            => 'ldapLoginFilterMode',
		'ldap_loginfilter_email'            => 'ldapLoginFilterEmail',
		'ldap_loginfilter_username'         => 'ldapLoginFilterUsername',
		'ldap_loginfilter_attributes'       => 'ldapLoginFilterAttributes',
		'ldap_group_filter'                 => 'ldapGroupFilter',
		'ldap_group_filter_mode'            => 'ldapGroupFilterMode',
		'ldap_groupfilter_objectclass'      => 'ldapGroupFilterObjectclass',
		'ldap_groupfilter_groups'           => 'ldapGroupFilterGroups',
		'ldap_user_name'                    => 'ldapUserName',
		'ldap_display_name'                 => 'ldapUserDisplayName',
		'ldap_user_display_name_2'          => 'ldapUserDisplayName2',
		'ldap_group_display_name'           => 'ldapGroupDisplayName',
		'ldap_tls'                          => 'ldapTLS',
		'ldap_quota_def'                    => 'ldapQuotaDefault',
		'ldap_quota_attr'                   => 'ldapQuotaAttribute',
		'ldap_email_attr'                   => 'ldapEmailAttribute',
		'ldap_group_member_assoc_attribute' => 'ldapGroupMemberAssocAttr',
		'ldap_cache_ttl'                    => 'ldapCacheTTL',
		'ldap_network_timeout'              => 'ldapNetworkTimeout',
		'home_folder_naming_rule'           => 'homeFolderNamingRule',
		'ldap_turn_off_cert_check'          => 'turnOffCertCheck',
		'ldap_configuration_active'         => 'ldapConfigurationActive',
		'ldap_attributes_for_user_search'   => 'ldapAttributesForUserSearch',
		'ldap_attributes_for_group_search'  => 'ldapAttributesForGroupSearch',
		'ldap_expert_username_attr'         => 'ldapExpertUsernameAttr',
		'ldap_expert_uuid_user_attr'        => 'ldapExpertUUIDUserAttr',
		'ldap_expert_uuid_group_attr'       => 'ldapExpertUUIDGroupAttr',
		'has_memberof_filter_support'       => 'hasMemberOfFilterSupport',
		'use_memberof_to_detect_membership' => 'useMemberOfToDetectMembership',
		'last_jpegPhoto_lookup'             => 'lastJpegPhotoLookup',
		'ldap_nested_groups'                => 'ldapNestedGroups',
		'ldap_paging_size'                  => 'ldapPagingSize',
		'ldap_experienced_admin'            => 'ldapExperiencedAdmin',
		'ldap_dynamic_group_member_url'     => 'ldapDynamicGroupMemberURL',
		'ldap_uuid_user_attribute'          => 'ldapUuidUserAttribute',
		'ldap_uuid_group_attribute'         => 'ldapUuidGroupAttribute'
	];

	/**
	 * @var string[] - config keys that should be converted into an array
	 */
	private $arrayConfigKeys = [
		'ldapBase',
		'ldapBaseUsers',
		'ldapBaseGroups',
		'ldapAttributesForUserSearch',
		'ldapAttributesForGroupSearch',
		'ldapUserFilterObjectclass',
		'ldapUserFilterGroups',
		'ldapGroupFilterObjectclass',
		'ldapGroupFilterGroups',
		'ldapLoginFilterAttributes'
	];

	/**
	 * @var IConfig
	 */
	private $config;

	/**
	 * @var IDBConnection
	 */
	private $dbConnection;

	/**
	 * Version20190315192717 constructor.
	 *
	 * @param IConfig $config
	 * @param IDBConnection $dbConnection
	 */
	public function __construct(IConfig $config, IDBConnection $dbConnection) {
		$this->config = $config;
		$this->dbConnection = $dbConnection;
	}

	/**
	 * Json encode all existing ldap server configurations
	 *
	 * @param IOutput $out
	 */
	public function run(IOutput $out) {
		$configPrefixes = $this->getLegacyConfigPrefixes();
		$out->info('Converting LDAP configurations');
		$out->startProgress(\count($configPrefixes));
		$converted = 0;
		foreach ($configPrefixes as $prefix) {
			$config = $this->getTranslatedLegacyConfig($prefix);
			$this->storeConfig($prefix, $config);
			$converted++;
			$out->advance($converted, $prefix);
			$this->deleteLegacyConfig($prefix);
		}
		$out->finishProgress();
		$out->info('');
		$out->info('Done');
	}

	/**
	 * Get all legacy configuration prefixes  by enumerating
	 * whateverldap_configuration_active config keys in appconfig table
	 *
	 * @return string[]
	 */
	private function getLegacyConfigPrefixes() {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->selectDistinct('configkey')
			->from('appconfig')
			->where(
				$qb->expr()->eq('appid', $qb->expr()->literal('user_ldap'))
			)
			->andWhere(
				$qb->expr()->like('configkey', $qb->expr()->literal('%' . self::REFERENCE_KEY))
			);
		$result = $qb->execute();
		$configs = $result->fetchAll();
		$prefixes = [];
		foreach ($configs as $config) {
			$len = \strlen($config['configkey']) - \strlen(self::REFERENCE_KEY);
			$prefixes[] = \substr($config['configkey'], 0, $len);
		}
		return $prefixes;
	}

	/**
	 * Collects all known fields into a single array
	 *
	 * @param string $prefix
	 *
	 * @return array
	 */
	private function getTranslatedLegacyConfig($prefix) {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->select('configkey', 'configvalue')
			->from('appconfig')
			->where(
				$qb->expr()->eq('appid', $qb->expr()->literal('user_ldap'))
			)
			->andWhere(
				$qb->expr()->in(
					'configkey',
					$qb->createParameter('keys'),
					IQueryBuilder::PARAM_STR_ARRAY
				)
			)
			->setParameter('keys', $this->getPrefixedLegacyKeys($prefix), IQueryBuilder::PARAM_STR_ARRAY);

		$result = $qb->execute();
		$configValues = $result->fetchAll();

		$current = [];
		foreach ($configValues as $row) {
			// if there is a prefix we need to remove its first occurrence in key
			$key = $prefix === ''
				? $row['configkey']
				: \implode('', \explode($prefix, $row['configkey'], 2));
			$translatedKey = $this->translations[$key];
			$current[$translatedKey] = $row['configvalue'];
		}
		foreach ($this->translations as $legacyKey => $newKey) {
			if (!isset($current[$newKey])) {
				$current[$newKey] = $this->knownLegacyKeys[$legacyKey];
			}
		}

		foreach ($this->arrayConfigKeys as $arrayConfigKey) {
			if (isset($current[$arrayConfigKey])) {
				$current[$arrayConfigKey] = $this->convertToArray($current[$arrayConfigKey]);
			}
		}

		return $current;
	}

	/**
	 * @param  mixed $value
	 * @return string | string[]
	 */
	private function convertToArray($value) {
		// try to split the value by all known separators
		if (empty($value)) {
			$value = '';
		} elseif (!\is_array($value)) {
			$value = \preg_split('/\r\n|\r|\n|;/', $value);
			if ($value === false) {
				$value = '';
			}
		}

		if (!\is_array($value)) {
			// if the value is not an array - store it as is
			$finalValue = \trim($value);
		} else {
			// if the value is an array - clean all empty values
			$finalValue = [];
			foreach ($value as $key => $val) {
				if (\is_string($val)) {
					$val = \trim($val);
					if ($val !== '') {
						//accidental line breaks are not wanted and can cause
						// odd behaviour. Thus, away with them.
						$finalValue[] = $val;
					}
				} else {
					$finalValue[] = $val;
				}
			}
		}

		return $finalValue;
	}

	/**
	 * Store config as json
	 *
	 * @param string $prefix
	 * @param array $configData
	 */
	private function storeConfig($prefix, $configData) {
		$this->config->setAppValue(
			'user_ldap',
			ConfigMapper::PREFIX . $prefix,
			\json_encode($configData)
		);
	}

	/**
	 * Deletes a given legacy LDAP/AD server configuration.
	 *
	 * @param string $prefix the configuration prefix of the config to delete
	 *
	 */
	private function deleteLegacyConfig($prefix) {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->delete('appconfig')
			->where(
				$qb->expr()->eq('appid', $qb->expr()->literal('user_ldap'))
			)
			->andWhere(
				$qb->expr()->in(
					'configkey',
					$qb->createParameter('keys'),
					IQueryBuilder::PARAM_STR_ARRAY
				)
			)
			->setParameter('keys', $this->getPrefixedLegacyKeys($prefix), IQueryBuilder::PARAM_STR_ARRAY);
		$qb->execute();
	}

	/**
	 * Get all possible configkey names prefixed with a given string
	 *
	 * @param string $prefix
	 *
	 * @return array
	 */
	private function getPrefixedLegacyKeys($prefix) {
		$prefixed = [];
		foreach ($this->knownLegacyKeys as $key => $value) {
			$prefixedKey = "{$prefix}{$key}";
			$prefixed[] = $prefixedKey;
		}
		return $prefixed;
	}
}
