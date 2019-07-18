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

class Config implements \JsonSerializable {
	private $rawData = [];

	private $data = [
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

	public function __construct($data) {
		$this->rawData = $data;
		$this->parseData();
	}

	/**
	 * @return string the configuration prefix
	 */
	public function getId() {
		return $this->rawData['id'];
	}

	public function setId($id) {
		$this->rawData['id'] = $id;
	}

	public function getData() {
		return $this->data;
	}

	/**
	 * @param string $name
	 *
	 * @return mixed|null
	 */
	public function __get($name) {
		if (isset($this->data[$name])) {
			return $this->data[$name];
		}
		return null;
	}

	/**
	 * @param string $name
	 *
	 * @param mixed $value
	 */
	public function __set($name, $value) {
		$this->setConfiguration([$name => $value]);
	}

	public function jsonSerialize() {
		$data = [];
		foreach ($this->data as $key=>$value) {
			switch ($key) {
				case 'ldapAgentPassword':
					$value = \base64_encode($value);
					break;
				//following options are not stored but detected, skip them
				case 'ldapIgnoreNamingRules':
				case 'hasPagedResultSupport':
				case 'ldapUuidUserAttribute':
				case 'ldapUuidGroupAttribute':
					continue 2;
			}
			if ($value === null) {
				$value = '';
			}
			$data[$key] = $value;
		}
		return $data;
	}

	/**
	 * set LDAP configuration with values delivered by an array, not read
	 * from configuration. It does not save the configuration! To do so, you
	 * must call saveConfiguration afterwards.
	 * @param array $config array that holds the config parameters in an associated
	 * array
	 * @param array &$applied optional; array where the set fields will be given to
	 * @return false|null
	 */
	public function setConfiguration($config, &$applied = null) {
		if (!\is_array($config)) {
			return false;
		}

		foreach ($config as $inputKey => $val) {
			if (\array_key_exists($inputKey, $this->data)) {
				$key = $inputKey;
			} else {
				continue;
			}

			$setMethod = 'setValue';
			switch ($key) {
				case 'ldapAgentPassword':
					$setMethod = 'setRawValue';
					break;
				case 'homeFolderNamingRule':
					$trimmedVal = \trim($val);
					if ($trimmedVal !== '' && \strpos($val, 'attr:') === false) {
						$val = 'attr:' . $trimmedVal;
					}
					break;
				case 'ldapBase':
				case 'ldapBaseUsers':
				case 'ldapBaseGroups':
				case 'ldapAttributesForUserSearch':
				case 'ldapAttributesForGroupSearch':
				case 'ldapUserFilterObjectclass':
				case 'ldapUserFilterGroups':
				case 'ldapGroupFilterObjectclass':
				case 'ldapGroupFilterGroups':
				case 'ldapLoginFilterAttributes':
					$setMethod = 'setMultiLine';
					break;
			}
			$this->$setMethod($key, $val);
			if (\is_array($applied)) {
				$applied[] = $inputKey;
			}
		}
		return null;
	}

	public function getDefaults() {
		$defaultConfig = new self([]);
		return $defaultConfig->getData();
	}

	/**
	 * Sets multi-line values as arrays
	 *
	 * @param string $varName name of config-key
	 * @param array|string $value to set
	 */
	private function setMultiLine($varName, $value) {
		if (empty($value)) {
			$value = '';
		} elseif (!\is_array($value)) {
			$value = \preg_split('/\r\n|\r|\n|;/', $value);
			if ($value === false) {
				$value = '';
			}
		}

		if (!\is_array($value)) {
			$finalValue = \trim($value);
		} else {
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

		$this->setRawValue($varName, $finalValue);
	}

	/**
	 * @param string $varName
	 * @return string
	 */
	private function getPwd($varName) {
		return \base64_decode($this->getValue($varName));
	}

	/**
	 * @param string $varName
	 * @return string
	 */
	private function getLcValue($varName) {
		return \mb_strtolower($this->getValue($varName), 'UTF-8');
	}

	/**
	 * @param string $varName
	 * @return string
	 */
	private function getSystemValue($varName) {
		//FIXME: if another system value is added, softcode the default value
		return $this->getCoreConfig()->getSystemValue($varName, false);
	}

	/**
	 * @param string $varName
	 * @return mixed
	 */
	private function getValue($varName) {
		if (isset($this->rawData[$varName])) {
			return $this->rawData[$varName];
		}
		return null;
	}

	/**
	 * Sets a scalar value.
	 *
	 * @param string $varName name of config key
	 * @param mixed $value to set
	 */
	private function setValue($varName, $value) {
		if (\is_string($value)) {
			$value = \trim($value);
		}
		$this->data[$varName] = $value;
	}

	/**
	 * Sets a scalar value without trimming.
	 *
	 * @param string $varName name of config key
	 * @param mixed $value to set
	 */
	private function setRawValue($varName, $value) {
		$this->data[$varName] = $value;
	}

	private function parseData() {
		if (!isset($this->rawData['id'])) {
			$this->rawData['id'] = null;
		}
		foreach (\array_keys($this->data) as $key) {
			if (isset($this->rawData[$key])) {
				switch ($key) {
					case 'ldapIgnoreNamingRules':
						$readMethod = 'getSystemValue';
						break;
					case 'ldapAgentPassword':
						$readMethod = 'getPwd';
						break;
					case 'ldapUserDisplayName2':
					case 'ldapGroupDisplayName':
						$readMethod = 'getLcValue';
						break;
					case 'ldapUserDisplayName':
					case 'ldapBase':
					case 'ldapBaseUsers':
					case 'ldapBaseGroups':
					case 'ldapAttributesForUserSearch':
					case 'ldapAttributesForGroupSearch':
					case 'ldapUserFilterObjectclass':
					case 'ldapUserFilterGroups':
					case 'ldapGroupFilterObjectclass':
					case 'ldapGroupFilterGroups':
					case 'ldapLoginFilterAttributes':
					default:
						// user display name does not lower case because
						// we rely on an upper case N as indicator whether to
						// auto-detect it or not. FIXME
						$readMethod = 'getValue';
						break;
				}
				$this->data[$key] = $this->$readMethod($key);
			}
		}
	}

	/**
	 * @return \OCP\IConfig
	 */
	private function getCoreConfig() {
		return \OC::$server->getConfig();
	}
}
