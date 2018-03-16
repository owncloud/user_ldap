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
	 * @When the admin creates the group :group in the ldap ou :ou
	 * 
	 * @param string $group
	 * @param string $ou if null ldapGroupsOU from behat.yml will be used
	 * 
	 * @return void
	 */
	public function createLdapGroup($group, $ou = null) {
		if ($ou === null) {
			$ou = $this->userLdapGeneralContext->getLdapGroupsOU();
		}
		
		$newDN = 'cn=' . $group . ',ou=' . $ou . ',' .
				 $this->userLdapGeneralContext->getLdapBaseDN();
		
		$entry = array();
		$entry['cn'] = $group;
		$entry['objectclass'][] = 'posixGroup';
		$entry['objectclass'][] = 'top';
		$entry['gidNumber'] = 5000;
		
		$this->userLdapGeneralContext->getLdap()->add($newDN, $entry);
	}
	
	/**
	 * @When the admin adds the user :user to the ldap group :group
	 * @When the admin adds the user :user to the group :group in the ldap OU :ou
	 * 
	 * @param string $user
	 * @param string $group
	 * @param string $ou if null ldapGroupsOU from behat.yml will be used
	 * 
	 * @return void
	 */
	public function addUserToLdapGroup($user, $group, $ou = null) {
		if ($ou === null) {
			$ou = $this->userLdapGeneralContext->getLdapGroupsOU();
		}
		
		$this->userLdapGeneralContext->addValueToLdapAttributeOfTheEntry(
			$user, "memberUid", "cn=$group,ou=$ou"
		);
	}
	
	/**
	 * @When the admin removes user :user from the ldap group :group
	 * @When the admin removes user :user from the group :group in the ldap OU :ou
	 * 
	 * @param string $user
	 * @param string $group
	 * @param string $ou if null ldapGroupsOU from behat.yml will be used
	 * 
	 * @return void
	 */
	public function removeUserFromLdapGroup($user, $group, $ou = null) {
		if ($ou === null) {
			$ou = $this->userLdapGeneralContext->getLdapGroupsOU();
		}
		$this->userLdapGeneralContext->deleteValueFromLdapAttribute(
			$user, "memberUid", "cn=$group,ou=$ou"
		);
	}

	/**
	 * @When the admin deletes the ldap group :group
	 * @When the admin deletes the group :group in the ldap ou :ou
	 * 
	 * @param string $group
	 * @param string $ou if null ldapGroupsOU from behat.yml will be used
	 * 
	 * @return void
	 */
	public function deleteLdapGroup($group, $ou = null) {
		if ($ou === null) {
			$ou = $this->userLdapGeneralContext->getLdapGroupsOU();
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
	}
}