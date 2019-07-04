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

abstract class Tree implements \JsonSerializable {
	const FILTER_MODE_AUTO = 0;
	const FILTER_MODE_MANUAL = 1;

	/**
	 * @var string
	 */
	protected $baseDN;

	/**
	 * @var string[]
	 */
	protected $filterObjectclass;

	/**
	 * @var string[]
	 */
	protected $filterGroups;

	/**
	 * @var string
	 */
	protected $filter; // generated, based on objectclass and groups

	/**
	 * @var bool
	 */
	protected $filterMode; // 0 = generated, 1 = manual

	/**
	 * @var string
	 */
	protected $uuidAttribute = 'auto'; // used to find the dn when the user was renamed

	/**
	 * @var string
	 */
	protected $displayNameAttribute;

	/**
	 * @var string[]
	 */
	protected $additionalSearchAttributes;

	public function __construct(array $data) {
		$this->baseDN =                     isset($data['baseDN'])                     ? (string)$data['baseDN']                     : '';
		$this->filterObjectclass =          isset($data['filterObjectclass'])          ?         $data['filterObjectclass']          : [];
		$this->filterGroups =               isset($data['filterGroups'])               ?         $data['filterGroups']               : [];
		$this->filter =                     isset($data['filter'])                     ? (string)$data['filter']                     : '';
		$this->filterMode =                 isset($data['filterMode'])                 ?    (int)$data['filterMode']                 : 0;
		$this->uuidAttribute =              isset($data['uuidAttribute'])              ? (string)$data['uuidAttribute']              : 'auto';
		$this->displayNameAttribute =       isset($data['displayNameAttribute'])       ? (string)$data['displayNameAttribute']       : 'displayName';
		$this->additionalSearchAttributes = isset($data['additionalSearchAttributes']) ?         $data['additionalSearchAttributes'] : [];
	}

	// FIXME use Filter builder?
	protected function updateFilter() {
		if ($this->filterMode === self::FILTER_MODE_AUTO) {
			$this->filter = '(&(objectclass='.\implode(')(objectclass=', $this->filterObjectclass).'))';
		}
	}

	/**
	 * @return string
	 */
	public function getBaseDN() {
		return $this->baseDN;
	}

	/**
	 * @param string $baseDN
	 */
	public function setBaseDN($baseDN) {
		$this->baseDN = $baseDN;
	}

	/**
	 * @return string[]
	 */
	public function getFilterObjectclass() {
		return $this->filterObjectclass;
	}

	/**
	 * @param string[] $filterObjectclass
	 */
	public function setFilterObjectclass($filterObjectclass) {
		$this->filterObjectclass = $filterObjectclass;
	}

	/**
	 * @return string[]
	 */
	public function getFilterGroups() {
		return $this->filterGroups;
	}

	/**
	 * @param string[] $filterGroups
	 */
	public function setFilterGroups($filterGroups) {
		$this->filterGroups = $filterGroups;
	}

	/**
	 * @return string
	 */
	public function getFilter() {
		return $this->filter;
	}

	/**
	 * @param string $filter
	 */
	public function setFilter($filter) {
		$this->filter = $filter;
	}

	/**
	 * @return bool
	 */
	public function isFilterMode() {
		return $this->filterMode;
	}

	/**
	 * @param bool $filterMode
	 */
	public function setFilterMode($filterMode) {
		$this->filterMode = $filterMode;
	}

	/**
	 * @return string
	 */
	public function getUuidAttribute() {
		return $this->uuidAttribute;
	}

	/**
	 * @param string $uuidAttribute
	 */
	public function setUuidAttribute($uuidAttribute) {
		$this->uuidAttribute = $uuidAttribute;
	}

	/**
	 * @return string
	 */
	public function getDisplayNameAttribute() {
		return $this->displayNameAttribute;
	}

	/**
	 * @param string $displayNameAttribute
	 */
	public function setDisplayNameAttribute($displayNameAttribute) {
		$this->displayNameAttribute = $displayNameAttribute;
	}

	/**
	 * @return string[]
	 */
	public function getAdditionalSearchAttributes() {
		return $this->additionalSearchAttributes;
	}

	/**
	 * @param string[] $additionalSearchAttributes
	 */
	public function setAdditionalSearchAttributes($additionalSearchAttributes) {
		$this->additionalSearchAttributes = $additionalSearchAttributes;
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
