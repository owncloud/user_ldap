<?php
/**
 * ownCloud
 *
 * @author Artur Neumann <artur@jankaritech.com>
 * @copyright Copyright (c) 2018 Artur Neumann artur@jankaritech.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\MinkExtension\Context\RawMinkContext;

require_once 'bootstrap.php';

/**
 * context that holds steps to manipulate users and groups in the user_ldap app
 */
class UserLdapUsersContext extends RawMinkContext implements Context {
	
	/**
	 *
	 * @var UserLdapGeneralContext
	 */
	private $userLdapGeneralContext;

	/**
	 * @var FeatureContext
	 */
	private $featureContext;

	/**
	 * @When the administrator creates group :group in ldap OU :ou
	 *
	 * @param string $group
	 * @param string $ou if null ldapGroupsOU from behat.yml will be used
	 *
	 * @return void
	 * @throws \Zend\Ldap\Exception\LdapException
	 */
	public function createLdapGroup($group, $ou = null) {
		if ($ou === null) {
			$ou = $this->featureContext->getLdapGroupsOU();
		}
		
		$newDN = 'cn=' . $group . ',ou=' . $ou . ',' .
				 $this->featureContext->getLdapBaseDN();
		
		$entry = [];
		$entry['cn'] = $group;
		$entry['objectclass'][] = 'posixGroup';
		$entry['objectclass'][] = 'top';
		$entry['gidNumber'] = 5000;
		
		$this->featureContext->getLdap()->add($newDN, $entry);
	}

	/**
	 * @When the administrator adds user :user to ldap group :group
	 * @When the administrator adds user :user to group :group in ldap OU :ou
	 *
	 * @param string $user
	 * @param string $group
	 * @param string $ou if null ldapGroupsOU from behat.yml will be used
	 *
	 * @return void
	 * @throws \Zend\Ldap\Exception\LdapException
	 */
	public function addUserToLdapGroup($user, $group, $ou = null) {
		if ($ou === null) {
			$ou = $this->featureContext->getLdapGroupsOU();
		}
		
		$this->userLdapGeneralContext->addValueToLdapAttributeOfTheEntry(
			$user, "memberUid", "cn=$group,ou=$ou"
		);
	}

	/**
	 * @When the administrator removes user :user from ldap group :group
	 * @When the administrator removes user :user from group :group in ldap OU :ou
	 *
	 * @param string $user
	 * @param string $group
	 * @param string $ou if null ldapGroupsOU from behat.yml will be used
	 *
	 * @return void
	 * @throws \Zend\Ldap\Exception\LdapException
	 */
	public function removeUserFromLdapGroup($user, $group, $ou = null) {
		if ($ou === null) {
			$ou = $this->featureContext->getLdapGroupsOU();
		}
		$this->userLdapGeneralContext->deleteValueFromLdapAttribute(
			$user, "memberUid", "cn=$group,ou=$ou"
		);
	}

	/**
	 * @When the administrator deletes ldap group :group
	 * @When the administrator deletes group :group in ldap OU :ou
	 *
	 * @param string $group
	 * @param string $ou if null ldapGroupsOU from behat.yml will be used
	 *
	 * @return void
	 * @throws \Zend\Ldap\Exception\LdapException
	 */
	public function deleteLdapGroup($group, $ou = null) {
		if ($ou === null) {
			$ou = $this->featureContext->getLdapGroupsOU();
		}
		$this->userLdapGeneralContext->deleteTheLdapEntry("cn=$group,ou=$ou");
	}

	/**
	 * @BeforeScenario
	 *
	 * @param BeforeScenarioScope $scope
	 *
	 * @return void
	 */
	public function setUpBeforeScenario(BeforeScenarioScope $scope) {
		$environment = $scope->getEnvironment();
		// Get all the contexts you need in this context
		$this->userLdapGeneralContext = $environment->getContext(
			'UserLdapGeneralContext'
		);
		$this->featureContext = $environment->getContext(
			'FeatureContext'
		);
	}
}
