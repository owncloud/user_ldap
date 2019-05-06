<?php
/**
 * @author Alexander Bergolth <leo@strike.wu.ac.at>
 * @author Alex Weirig <alex.weirig@technolink.lu>
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lennart Rosam <hello@takuto.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
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
use OCP\IConfig;

/**
 * @property int ldapPagingSize holds an integer
 */
class Configuration {
	const CONFIG_PREFIX = 'conn-';

	/** @var IConfig */
	protected $coreConfig;

	/** @var string */
	protected $prefix;

	/** @var bool */
	protected $read = false;

	/** @var array */
	protected $data = [];

	protected $rawConnData;

	/**
	 * @param IConfig $coreConfig
	 * @param string $prefix a string with the prefix for the configkey column (appconfig table)
	 * @param bool $autoRead
	 */
	public function __construct(IConfig $coreConfig, $prefix, $autoRead = true) {
		$this->coreConfig = $coreConfig;
		$this->prefix = $prefix;
		$this->data = $this->getDefaults();
		if ($autoRead) {
			$this->readConfiguration();
		}
	}

	/**
	 * @return IConfig
	 */
	public function getCoreConfig() {
		return $this->coreConfig;
	}
	/**
	 * @return string the configuration prefix
	 */
	public function getPrefix() {
		return $this->prefix;
	}

	/**
	 * @return bool
	 */
	public function isRead() {
		return $this->read;
	}

	/**
	 * @param string $name
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
	 * @param mixed $value
	 */
	public function __set($name, $value) {
		$this->setConfiguration([$name => $value]);
	}

	/**
	 * @return array
	 */
	public function getConfiguration() {
		return $this->data;
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
						$val = 'attr:'.$trimmedVal;
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

	public function readConfiguration() {
		if ($this->prefix !== null && !$this->isRead()) {
			$conn = $this->coreConfig->getAppValue(
				'user_ldap', self::CONFIG_PREFIX . $this->prefix, '{}'
			);
			$this->rawConnData = \json_decode($conn, true);

			$defaults = $this->getDefaults();
			foreach ($this->data as $key => $val) {
				if (!\array_key_exists($key, $defaults)) {
					//some are determined
					continue;
				}
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
					case 'ldapUserDisplayName':
					default:
						// user display name does not lower case because
						// we rely on an upper case N as indicator whether to
						// auto-detect it or not. FIXME
						$readMethod = 'getValue';
						break;
				}
				$this->data[$key] = $this->$readMethod($key);
			}
			$this->read = true;
		}
	}

	private function getTranslatedConfig() {
		$result = [];
		foreach ($this->data as $key => $value) {
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
			$result[$key] = $value;
		}
		return $result;
	}
	/**
	 * saves the current Configuration in the database
	 */
	public function saveConfiguration() {
		$this->coreConfig->setAppValue(
			'user_ldap',
			self::CONFIG_PREFIX . $this->prefix,
			\json_encode($this->getTranslatedConfig())
		);
	}

	/**
	 * Sets multi-line values as arrays
	 *
	 * @param string $varName name of config-key
	 * @param array|string $value to set
	 */
	protected function setMultiLine($varName, $value) {
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
	protected function getPwd($varName) {
		return \base64_decode($this->getValue($varName));
	}

	/**
	 * @param string $varName
	 * @return string
	 */
	protected function getLcValue($varName) {
		return \mb_strtolower($this->getValue($varName), 'UTF-8');
	}

	/**
	 * @param string $varName
	 * @return string
	 */
	protected function getSystemValue($varName) {
		//FIXME: if another system value is added, softcode the default value
		return $this->coreConfig->getSystemValue($varName, false);
	}

	/**
	 * @param string $varName
	 * @return string
	 */
	protected function getValue($varName) {
		if (isset($this->rawConnData[$varName])) {
			return $this->rawConnData[$varName];
		}
		$defaults = $this->getDefaults();
		return $defaults[$varName];
	}

	/**
	 * Sets a scalar value.
	 *
	 * @param string $varName name of config key
	 * @param mixed $value to set
	 */
	protected function setValue($varName, $value) {
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
	protected function setRawValue($varName, $value) {
		$this->data[$varName] = $value;
	}

	/**
	 * @return array an associative array with the default values. Keys are correspond
	 * to config-value entries in the database table
	 */
	public function getDefaults() {
		return [
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
	}
}
