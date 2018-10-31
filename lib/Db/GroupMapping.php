<?php

namespace OCA\User_LDAP\Db;


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

}