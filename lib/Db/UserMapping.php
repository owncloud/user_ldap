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

namespace OCA\User_LDAP\Db;


class UserMapping extends Mapping {

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
	 * @return string
	 */
	public function getHomeFolderNamingRule() {
		return $this->homeFolderNamingRule;
	}

	/**
	 * @param string $homeFolderNamingRule
	 */
	public function setHomeFolderNamingRule($homeFolderNamingRule) {
		$this->homeFolderNamingRule = $homeFolderNamingRule;
	}

	/**
	 * @return string
	 */
	public function getQuotaAttribute() {
		return $this->quotaAttribute;
	}

	/**
	 * @param string $quotaAttribute
	 */
	public function setQuotaAttribute($quotaAttribute) {
		$this->quotaAttribute = $quotaAttribute;
	}

	/**
	 * @return string
	 */
	public function getQuotaDefault() {
		return $this->quotaDefault;
	}

	/**
	 * @param string $quotaDefault
	 */
	public function setQuotaDefault($quotaDefault) {
		$this->quotaDefault = $quotaDefault;
	}

}