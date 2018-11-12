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


class GroupTree extends Tree {

	/**
	 * @var string
	 */
	private $memberAttribute;
	/**
	 * @var string|null
	 */
	private $dynamicGroupMemberURL;
	/**
	 * @var bool
	 */
	private $nestedGroups;

	public function __construct(array $data) {
		parent::__construct($data);

		$this->filterObjectclass =     isset($data['filterObjectclass'])     ?         $data['filterObjectclass']     : ['objectclass=group'];
		$this->memberAttribute =       isset($data['memberAttribute'])       ? (string)$data['memberAttribute']       : 'uniquemember';
		$this->dynamicGroupMemberURL = isset($data['dynamicGroupMemberURL']) ?         $data['dynamicGroupMemberURL'] : null;
		$this->nestedGroups =          isset($data['nestedGroups'])          ?   (bool)$data['nestedGroups']          : false;

	}
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
	 * @return string|null
	 */
	public function getDynamicGroupMemberURL() {
		return $this->dynamicGroupMemberURL;
	}

	/**
	 * @param string|null $dynamicGroupMemberURL
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