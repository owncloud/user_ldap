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


class UserTree extends Tree {

	const LOGIN_FILTER_MODE_AUTO = 0;
	const LOGIN_FILTER_MODE_MANUAL = 1;

	/**
	 * @var string
	 */
	private $loginFilter; // generated, based on filter and login attributes
	/**
	 * @var int
	 */
	private $loginFilterMode; // 0 = generated, 1 = manual

	/**
	 * @var bool
	 */
	private $loginFilterEmail;
	/**
	 * @var bool
	 */
	private $loginFilterUsername;
	/**
	 * @var string[]
	 */
	private $loginFilterAttributes;

	/**
	 * @var string
	 */
	private $usernameAttribute; // this is the username that is used for wnd
	/**
	 * @var string
	 */
	private $expertUsernameAttr; // this is the internal oc username ...

	/**
	 * @var string
	 */
	private $displayName2Attribute;

	/**
	 * @var string
	 */
	private $emailAttribute;
	/**
	 * @var string
	 */
	private $homeFolderNamingRule;
	/**
	 * @var string
	 */
	private $quotaAttribute;
	/**
	 * @var string
	 */
	private $quotaDefault;

	public function __construct(array $data) {
		parent::__construct($data);

		$this->filterObjectclass =     isset($data['filterObjectclass'])     ?         $data['filterObjectclass']          : ['objectclass=person'];

		$this->loginFilterMode =       isset($data['loginFilterMode'])       ?    (int)$data['loginFilterMode']       : self::LOGIN_FILTER_MODE_AUTO;
		$this->loginFilterEmail =      isset($data['loginFilterEmail'])      ?   (bool)$data['loginFilterEmail']      : false;
		$this->loginFilterUsername =   isset($data['loginFilterUsername'])   ?   (bool)$data['loginFilterUsername']   : true;
		$this->loginFilterAttributes = isset($data['loginFilterAttributes']) ?         $data['loginFilterAttributes'] : [];
		$this->usernameAttribute =     isset($data['usernameAttribute'])     ? (string)$data['usernameAttribute']     : 'samaccountname';
		$this->expertUsernameAttr =    isset($data['expertUsernameAttr'])    ? (string)$data['expertUsernameAttr']    : 'auto';
		$this->displayName2Attribute = isset($data['displayName2Attribute']) ? (string)$data['displayName2Attribute'] : 'displayName';
		$this->emailAttribute =        isset($data['emailAttribute'])        ? (string)$data['emailAttribute']        : 'mail';
		$this->homeFolderNamingRule =  isset($data['homeFolderNamingRule'])  ?         $data['homeFolderNamingRule']  : null;
		$this->quotaAttribute =        isset($data['quotaAttribute'])        ?         $data['quotaAttribute']        : null;
		$this->quotaDefault =          isset($data['quotaDefault'])          ?         $data['quotaDefault']          : null;

		if ($this->loginFilterMode === self::LOGIN_FILTER_MODE_AUTO) {
			$this->loginFilter = '(&('.implode(')(', $this->filterObjectclass).")({$this->usernameAttribute}=%uid))";
		} else {
			$this->loginFilter =           isset($data['loginFilter'])           ? (string)$data['loginFilter']           : null;
		}

		// avatar attribute?
	}
	/**
	 * @return string
	 */
	public function getLoginFilter() {
		return $this->loginFilter;
	}

	/**
	 * @param string $loginFilter
	 */
	public function setLoginFilter($loginFilter) {
		$this->loginFilter = $loginFilter;
	}

	/**
	 * @return int
	 */
	public function getLoginFilterMode() {
		return $this->loginFilterMode;
	}

	/**
	 * @param int $loginFilterMode
	 */
	public function setLoginFilterMode($loginFilterMode) {
		$this->loginFilterMode = $loginFilterMode;
	}

	/**
	 * @return bool
	 */
	public function isLoginFilterEmail() {
		return $this->loginFilterEmail;
	}

	/**
	 * @param bool $loginFilterEmail
	 */
	public function setLoginFilterEmail($loginFilterEmail) {
		$this->loginFilterEmail = $loginFilterEmail;
	}

	/**
	 * @return bool
	 */
	public function isLoginFilterUsername() {
		return $this->loginFilterUsername;
	}

	/**
	 * @param bool $loginFilterUsername
	 */
	public function setLoginFilterUsername($loginFilterUsername) {
		$this->loginFilterUsername = $loginFilterUsername;
	}

	/**
	 * @return string[]
	 */
	public function getLoginFilterAttributes() {
		return $this->loginFilterAttributes;
	}

	/**
	 * @param string[] $loginFilterAttributes
	 */
	public function setLoginFilterAttributes($loginFilterAttributes) {
		$this->loginFilterAttributes = $loginFilterAttributes;
	}

	/**
	 * @return string
	 */
	public function getUsernameAttribute() {
		return $this->usernameAttribute;
	}

	/**
	 * @param string $usernameAttribute
	 */
	public function setUsernameAttribute($usernameAttribute) {
		$this->usernameAttribute = $usernameAttribute;
	}

	/**
	 * @return string
	 */
	public function getExpertUsernameAttr() {
		return $this->expertUsernameAttr;
	}

	/**
	 * @param string $expertUsernameAttr
	 */
	public function setExpertUsernameAttr($expertUsernameAttr) {
		$this->expertUsernameAttr = $expertUsernameAttr;
	}

	/**
	 * @return string
	 */
	public function getDisplayName2Attribute() {
		return $this->displayName2Attribute;
	}

	/**
	 * @param string $displayName2Attribute
	 */
	public function setDisplayName2Attribute($displayName2Attribute) {
		$this->displayName2Attribute = $displayName2Attribute;
	}

	/**
	 * @return string
	 */
	public function getEmailAttribute() {
		return $this->emailAttribute;
	}

	/**
	 * @param string $emailAttribute
	 */
	public function setEmailAttribute($emailAttribute) {
		$this->emailAttribute = $emailAttribute;
	}

	/**
	 * @return string|null
	 */
	public function getHomeFolderNamingRule() {
		return $this->homeFolderNamingRule;
	}

	/**
	 * @param string|null $homeFolderNamingRule
	 */
	public function setHomeFolderNamingRule($homeFolderNamingRule) {
		$this->homeFolderNamingRule = $homeFolderNamingRule;
	}

	/**
	 * @return string|null
	 */
	public function getQuotaAttribute() {
		return $this->quotaAttribute;
	}

	/**
	 * @param string|null $quotaAttribute
	 */
	public function setQuotaAttribute($quotaAttribute) {
		$this->quotaAttribute = $quotaAttribute;
	}

	/**
	 * @return string|null
	 */
	public function getQuotaDefault() {
		return $this->quotaDefault;
	}

	/**
	 * @param string|null $quotaDefault
	 */
	public function setQuotaDefault($quotaDefault) {
		$this->quotaDefault = $quotaDefault;
	}


	/**
	 * Specify data which should be serialized to JSON
	 *
	 * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return mixed data which can be serialized by <b>json_encode</b>,
	 * which is a value of any type other than a resource.
	 * @since 5.4.0
	 */
	public function jsonSerialize() {
		$data = [];
		// maybe using an array to store the properties makes more sense ... but please with explicit getters and setters
		foreach ($this as $key => $value) {
			$data[$key] = $value;
		}
		return $data;
	}
}