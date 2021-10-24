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
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\RawMinkContext;
use Laminas\Ldap\Exception\LdapException;
use TestHelpers\OcsApiHelper;

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
	 * @var OCSContext
	 */
	private $ocsContext;

	/**
	 * @When the administrator creates group :group in ldap OU :ou
	 *
	 * @param string $group
	 * @param string|null $ou if null ldapGroupsOU from behat.yml will be used
	 *
	 * @return void
	 * @throws LdapException
	 */
	public function createLdapGroup(
		string $group,
		?string $ou = null
	):void {
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
	 * @param string|null $ou if null ldapGroupsOU from behat.yml will be used
	 *
	 * @return void
	 * @throws LdapException
	 */
	public function addUserToLdapGroup(
		string $user,
		string $group,
		?string $ou = null
	):void {
		if ($ou === null) {
			$ou = $this->featureContext->getLdapGroupsOU();
		}

		$this->userLdapGeneralContext->addValueToLdapAttributeOfTheEntry(
			$user,
			"memberUid",
			"cn=$group,ou=$ou"
		);
		$this->featureContext->theLdapUsersHaveBeenReSynced();
		// To sync new ldap groups
		$this->featureContext->runOcc(['group:list -vvv']);
	}

	/**
	 * @When the administrator removes user :user from ldap group :group
	 * @When the administrator removes user :user from group :group in ldap OU :ou
	 *
	 * @param string $user
	 * @param string $group
	 * @param string|null $ou if null ldapGroupsOU from behat.yml will be used
	 *
	 * @return void
	 * @throws LdapException
	 */
	public function removeUserFromLdapGroup(
		string $user,
		string $group,
		?string $ou = null
	):void {
		if ($ou === null) {
			$ou = $this->featureContext->getLdapGroupsOU();
		}
		$this->userLdapGeneralContext->deleteValueFromLdapAttribute(
			$user,
			"memberUid",
			"cn=$group,ou=$ou"
		);
	}

	/**
	 * @When the administrator deletes ldap group :group
	 * @When the administrator deletes group :group in ldap OU :ou
	 *
	 * @param string $group
	 * @param string|null $ou if null ldapGroupsOU from behat.yml will be used
	 *
	 * @return void
	 * @throws LdapException
	 */
	public function deleteLdapGroup(
		string $group,
		?string $ou = null
	):void {
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
	public function setUpBeforeScenario(BeforeScenarioScope $scope):void {
		$environment = $scope->getEnvironment();
		// Get all the contexts you need in this context
		$this->userLdapGeneralContext = $environment->getContext(
			'UserLdapGeneralContext'
		);
		$this->featureContext = $environment->getContext(
			'FeatureContext'
		);
		$this->ocsContext = $environment->getContext(
			'OCSContext'
		);
	}

	/**
	 * @When the administrator sends a user creation request with the following attributes using the provisioning API and LDAP:
	 *
	 * @param TableNode $table
	 *
	 * @return void
	 * @throws Exception
	 */
	public function adminSendsUserCreationRequestLdap(TableNode $table):void {
		echo "adminSendsUserCreationRequestLdap start\n";
		$this->featureContext->verifyTableNodeRows($table, ["username", "password"], ["email", "displayname"]);
		echo "adminSendsUserCreationRequestLdap getRowsHash\n";
		$table = $table->getRowsHash();
		echo "adminSendsUserCreationRequestLdap end getRowsHash\n";
		$username = $this->featureContext->getActualUsername($table["username"]);
		$password = $this->featureContext->getActualPassword($table["password"]);
		$displayname = \array_key_exists("displayname", $table) ? $table["displayname"] : null;
		$email = \array_key_exists("email", $table) ? $table["email"] : null;
		$userAttributes = [
			"userid" => $username,
			"password" => $password,
			"displayname" => $displayname,
			"email" => $email
		];

		echo "adminSendsUserCreationRequestLdap userSendsHTTPMethodToOcsApiEndpointWithBody\n";
		$this->userSendsHTTPMethodToOcsApiEndpointWithBody(
			$this->featureContext->getAdminUsername(),
			"POST",
			"/cloud/users",
			new TableNode($userAttributes)
		);
		echo "adminSendsUserCreationRequestLdap addUserToCreatedUsersList\n";
		$this->featureContext->addUserToCreatedUsersList(
			$username,
			$password,
			$displayname,
			$email,
			$this->featureContext->theHTTPStatusCodeWasSuccess()
		);
		echo "adminSendsUserCreationRequestLdap end\n";
	}

	/**
	 * @param string $user
	 * @param string $verb
	 * @param string $url
	 * @param TableNode|null $body
	 * @param string|null $password
	 * @param array|null $headers
	 *
	 * @return void
	 */
	public function userSendsHTTPMethodToOcsApiEndpointWithBody(
		string $user,
		string $verb,
		string $url,
		?TableNode $body = null,
		?string $password = null,
		?array $headers = null
	):void {
		echo "userSendsHTTPMethodToOcsApiEndpointWithBody start\n";
		/**
		 * array of the data to be sent in the body.
		 * contains $body data converted to an array
		 *
		 * @var array $bodyArray
		 */
		$bodyArray = [];
		if ($body instanceof TableNode) {
			$bodyArray = $body->getRowsHash();
		} elseif ($body !== null && \is_array($body)) {
			$bodyArray = $body;
		}

		if ($user !== 'UNAUTHORIZED_USER') {
			echo "userSendsHTTPMethodToOcsApiEndpointWithBody a\n";
			if ($password === null) {
				$password = $this->featureContext->getPasswordForUser($user);
			}
			$user = $this->featureContext->getActualUsername($user);
		} else {
			echo "userSendsHTTPMethodToOcsApiEndpointWithBody not a\n";
			$user = null;
			$password = null;
		}
		echo "userSendsHTTPMethodToOcsApiEndpointWithBody OcsApiHelper::sendRequest\n";
		$response = OcsApiHelper::sendRequest(
			$this->featureContext->getBaseUrl(),
			$user,
			$password,
			$verb,
			$url,
			$this->featureContext->getStepLineRef(),
			$bodyArray,
			$this->featureContext->getOcsApiVersion(),
			$headers
		);
		echo "userSendsHTTPMethodToOcsApiEndpointWithBody setResponse\n";
		$this->featureContext->setResponse($response);
	}
}
