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


class GroupMapping extends Mapping {

	/**
	 * @var string
	 */
	private $memberAttribute;
	/**
	 * @var string
	 */
	private $dynamicGroupMemberURL;
	/**
	 * @var bool
	 */
	private $nestedGroups;

	/**
	 * @return string
	 */
	public function getMemberAttribute() {
		return $this->memberAttribute;
	}

	/**
	 * @param string $memberAttribute
	 */
	public function setMemberAttribute($memberAttribute) {
		$this->memberAttribute = $memberAttribute;
	}

	/**
	 * @return string
	 */
	public function getDynamicGroupMemberURL() {
		return $this->dynamicGroupMemberURL;
	}

	/**
	 * @param string $dynamicGroupMemberURL
	 */
	public function setDynamicGroupMemberURL($dynamicGroupMemberURL) {
		$this->dynamicGroupMemberURL = $dynamicGroupMemberURL;
	}

	/**
	 * @return bool
	 */
	public function isNestedGroups() {
		return $this->nestedGroups;
	}

	/**
	 * @param bool $nestedGroups
	 */
	public function setNestedGroups($nestedGroups) {
		$this->nestedGroups = $nestedGroups;
	}

	// TODO add hasPrimaryGroups as config, only detect it once


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