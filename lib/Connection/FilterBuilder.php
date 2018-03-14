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

namespace OCA\User_LDAP\Connection;

use OCA\User_LDAP\Config\GroupTree;
use OCA\User_LDAP\Config\UserTree;
use OCP\IConfig;

class FilterBuilder {

	/**
	 * @var IConfig
	 */
	protected $config;

	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	/**
	 * combines the input filters with AND
	 * @param string[] $filters the filters to connect
	 * @return string the combined filter
	 */
	public function combineFilterWithAnd($filters) {
		return $this->combineFilter($filters, '&');
	}

	/**
	 * combines the input filters with OR
	 * @param string[] $filters the filters to connect
	 * @return string the combined filter
	 * Combines Filter arguments with OR
	 */
	public function combineFilterWithOr($filters) {
		return $this->combineFilter($filters, '|');
	}

	/**
	 * combines the input filters with given operator
	 * @param string[] $filters the filters to connect
	 * @param string $operator either & or |
	 * @return string the combined filter
	 */
	private function combineFilter($filters, $operator) {
		$combinedFilter = "($operator";
		foreach ($filters as $filter) {
			if ($filter !== '' && $filter[0] !== '(') {
				$filter = "($filter)";
			}
			$combinedFilter .= $filter;
		}
		return "$combinedFilter)";
	}

	/**
	 * returns the search term depending on whether we are allowed
	 * list users found by ldap with the current input appended by
	 * a *
	 *
	 * @param $term
	 * @return string
	 */
	private function prepareSearchTerm($term) {
		$allowEnum = $this->config->getAppValue('core', 'shareapi_allow_share_dialog_user_enumeration', 'yes');
		$allowMedialSearches = $this->config->getSystemValue('user_ldap.enable_medial_search', false);

		$result = $term;
		if ($term === '') {
			$result = '*';
		} elseif ($allowEnum !== 'no') {
			if ($allowMedialSearches) {
				$result = "*$term*";
			} else {
				$result = "$term*";
			}
		}
		return $result;
	}

	/**
	 * creates a filter part for searches by splitting up the given search
	 * string into single words
	 * @param string $search the search term
	 * @param string[] $searchAttributes needs to have at least two attributes,
	 * otherwise it does not make sense :)
	 * @return string the final filter part to use in LDAP searches
	 * @throws \InvalidArgumentException
	 */
	private function getAdvancedFilterPartForSearch($search, $searchAttributes) {
		if (!\is_array($searchAttributes) || \count($searchAttributes) < 2) {
			throw new \InvalidArgumentException('searchAttributes must be an array with at least two string');
		}
		$searchWords = \explode(' ', \trim($search));
		$wordFilters = [];
		foreach ($searchWords as $word) {
			$word = $this->prepareSearchTerm($word);
			//every word needs to appear at least once
			$wordMatchOneAttrFilters = [];
			foreach ($searchAttributes as $attr) {
				$wordMatchOneAttrFilters[] = "$attr=$word";
			}
			$wordFilters[] = $this->combineFilterWithOr($wordMatchOneAttrFilters);
		}
		return $this->combineFilterWithAnd($wordFilters);
	}

	/**
	 * creates a filter part for searches
	 * @param string $search the search term
	 * @param string[]|null $searchAttributes
	 * @param string $fallbackAttribute a fallback attribute in case the user
	 * did not define search attributes. Typically the display name attribute.
	 * @return string the final filter part to use in LDAP searches
	 */
	public function getFilterPartForSearch($search, $searchAttributes, $fallbackAttribute) {
		$filter = [];
		$haveMultiSearchAttributes = (\is_array($searchAttributes) && \count($searchAttributes) > 0);
		if ($haveMultiSearchAttributes && \strpos(\trim($search), ' ') !== false) {
			try {
				return $this->getAdvancedFilterPartForSearch($search, $searchAttributes);
			} catch (\Exception $e) {
				\OC::$server->getLogger()->info(
					'Creating advanced filter for search failed, falling back to simple method.',
					['app' => 'user_ldap']);
			}
		}

		$search = $this->prepareSearchTerm($search);
		if (!\is_array($searchAttributes) || \count($searchAttributes) === 0) {
			if ($fallbackAttribute === '') {
				return '';
			}
			$filter[] = "$fallbackAttribute=$search";
		} else {
			foreach ($searchAttributes as $attribute) {
				$filter[] = "$attribute=$search";
			}
		}
		if (\count($filter) === 1) {
			return "($filter[0])";
		}
		return $this->combineFilterWithOr($filter);
	}

	/**
	 * creates a filter part for to perform search for users
	 * @param UserTree $tree
	 * @param string $search the search term
	 * @return string the final filter part to use in LDAP searches
	 */
	public function getFilterPartForUserSearch(UserTree $tree, $search) {
		return $this->getFilterPartForSearch($search,
			$tree->getAdditionalSearchAttributes(),
			$tree->getDisplayNameAttribute());
	}

	/**
	 * creates a filter part for to perform search for groups
	 * @param GroupTree $tree
	 * @param string $search the search term
	 * @return string the final filter part to use in LDAP searches
	 */
	public function getFilterPartForGroupSearch(GroupTree $tree, $search) {
		return $this->getFilterPartForSearch($search,
			$tree->getAdditionalSearchAttributes(),
			$tree->getDisplayNameAttribute());
	}

	/**
	 * returns the filter used for counting users
	 * @param UserTree $tree
	 * @return string
	 */
	public function getFilterForUserCount(UserTree $tree) {
		return $this->combineFilterWithAnd([
			$tree->getFilter(),
			"{$tree->getDisplayNameAttribute()}=*" // make sure displayname is set TODO this might cause differences when counting and one of the users has no displayname set
		]);
	}
}
