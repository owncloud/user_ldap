<?php declare(strict_types=1);
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
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\RawMinkContext;
use TestHelpers\SetupHelper;
use Laminas\Ldap\Exception\LdapException;
use PHPUnit\Framework\Assert;

require_once 'bootstrap.php';

/**
 * context that holds all general steps for the user_ldap app
 */
class UserLdapGeneralContext extends RawMinkContext implements Context {
	/**
	 *
	 * @var FeatureContext
	 */
	private $featureContext;

	/**
	 * @Given a new LDAP config with the name :configId has been created
	 *
	 * @param string $configId
	 *
	 * @return void
	 * @throws Exception
	 */
	public function createNewLdapConfig(string $configId):void {
		$occResult = SetupHelper::runOcc(
			['ldap:create-empty-config', $configId]
		);
		if ($occResult['code'] !== "0") {
			throw new \Exception(
				"could not create empty LDAP config " . $occResult['stdErr']
			);
		}
		$this->featureContext->setToDeleteLdapConfigs($configId);
	}

	/**
	 * @Given LDAP config :configId has key :configKey set to :configValue
	 * @When the administrator sets the LDAP config :configId key :configKey to :configValue using the occ command
	 *
	 * @param string $configId
	 * @param string $configKey
	 * @param string $configValue
	 *
	 * @return void
	 * @throws Exception
	 */
	public function ldapConfigHasKeySetTo(
		string $configId,
		string $configKey,
		string $configValue
	):void {
		$oldConfig = $this->featureContext->getOldLdapConfig();
		if (!isset($oldConfig[$configId][$configKey])) {
			//remember old settings to be able to set them back after test run
			$occResult = SetupHelper::runOcc(
				['ldap:show-config', $configId, "--output=json"]
			);
			if ($occResult['code'] !== "0") {
				throw new \Exception(
					"could not read LDAP settings " . $occResult['stdErr']
				);
			}
			$originalConfig = \json_decode($occResult['stdOut'], true);
			if (isset($originalConfig[$configKey])) {
				$this->featureContext->setOldLdapConfig(
					$configId,
					$configKey,
					$originalConfig[$configKey]
				);
			} else {
				$this->featureContext->setOldLdapConfig(
					$configId,
					$configKey,
					""
				);
			}
		}
		$this->featureContext->setLdapSetting($configId, $configKey, $configValue);
	}

	/**
	 * @Given LDAP config :configId has these settings:
	 * @When the administrator sets these settings of LDAP config :configId using the occ command
	 *
	 * @param string $configId
	 * @param TableNode $table with the headings |key | value |
	 *
	 * @return void
	 * @throws Exception
	 */
	public function ldapConfigHasTheseSettings(
		string $configId,
		TableNode $table
	):void {
		foreach ($table as $line) {
			$this->ldapConfigHasKeySetTo(
				$configId,
				$line['key'],
				$line['value']
			);
		}
	}

	/**
	 * @When LDAP user :user is resynced
	 *
	 * @param string $user
	 *
	 * @throws Exception
	 * @return void
	 */
	public function ldapUserIsSynced(string $user):void {
		$this->featureContext->setOccLastCode(
			$this->featureContext->runOcc(
				['user:sync', 'OCA\User_LDAP\User_Proxy', '-u', $user, '-m', 'remove']
			)
		);
		if ($this->featureContext->getExitStatusCodeOfOccCommand() !== 0) {
			throw new \Exception(
				"could not sync LDAP user {$user} " .
				$this->featureContext->getStdErrOfOccCommand()
			);
		}
	}

	/**
	 * @When the admin lists the enabled user backends using the occ command
	 *
	 * @return void
	 * @throws Exception
	 */
	public function theAdminListsTheEnabledBackendsUsingTheOccCommand():void {
		$this->featureContext->setOccLastCode(
			$this->featureContext->runOcc(["user:sync -l"])
		);
	}

	/**
	 * @When the administrator sets the ldap attribute :attribute of the entry :entry to :value
	 *
	 * @param string $attribute
	 * @param string $entry
	 * @param string $value
	 * @param bool $append
	 *
	 * @return void
	 * @throws LdapException
	 */
	public function setTheLdapAttributeOfTheEntryTo(
		string $attribute,
		string $entry,
		string $value,
		bool $append = false
	):void {
		$ldap = $this->featureContext->getLdap();
		$ldapEntry = $ldap->getEntry($entry . "," . $this->featureContext->getLdapBaseDN());
		Laminas\Ldap\Attribute::setAttribute($ldapEntry, $attribute, $value, $append);
		$ldap->update($entry . "," . $this->featureContext->getLdapBaseDN(), $ldapEntry);
	}

	/**
	 * @When the administrator sets the ldap attribute :attribute of the entry :entry to
	 *
	 * @param string $attribute
	 * @param string $entry
	 * @param TableNode $table
	 *
	 * @return void
	 * @throws LdapException
	 */
	public function theLdapAttributeOfTheEntryToTable(
		string $attribute,
		string $entry,
		TableNode $table
	):void {
		$first = true;
		foreach ($table as $row) {
			if ($first) {
				$this->setTheLdapAttributeOfTheEntryTo(
					$attribute,
					$entry,
					$row
				);
				$first = false;
			} else {
				$this->addValueToLdapAttributeOfTheEntry(
					$attribute,
					$entry,
					$row
				);
			}
		}
	}

	/**
	 * @When the administrator sets the ldap attribute :attribute of the entry :entry to the content of the file :filename
	 *
	 * @param string $attribute
	 * @param string $entry
	 * @param string $filename
	 *
	 * @return void
	 * @throws LdapException
	 */
	public function theLdapAttributeOfTheEntryToContentOfFile(
		string $attribute,
		string $entry,
		string $filename
	):void {
		$value = \file_get_contents(\getenv("FILES_FOR_UPLOAD") . $filename);

		$this->setTheLdapAttributeOfTheEntryTo(
			$attribute,
			$entry,
			$value
		);
	}

	/**
	 * @When the administrator adds :value to the ldap attribute :attribute of the entry :entry
	 *
	 * @param string $value
	 * @param string $attribute
	 * @param string $entry
	 *
	 * @return void
	 * @throws LdapException
	 */
	public function addValueToLdapAttributeOfTheEntry(
		string $value,
		string $attribute,
		string $entry
	):void {
		$this->setTheLdapAttributeOfTheEntryTo($attribute, $entry, $value, true);
	}

	/**
	 * @When the administrator deletes the ldap entry :entry
	 *
	 * @param string $entry
	 *
	 * @return void
	 * @throws LdapException
	 */
	public function deleteTheLdapEntry(string $entry):void {
		$ldap = $this->featureContext->getLdap();
		$ldap->delete($entry . "," . $this->featureContext->getLdapBaseDN());
	}

	/**
	 * @When the administrator deletes the value :value from the attribute :attribute of the ldap entry :entry
	 *
	 * @param string $value
	 * @param string $attribute
	 * @param string $entry DN, not containing baseDN
	 *
	 * @return void
	 * @throws LdapException
	 */
	public function deleteValueFromLdapAttribute(
		string $value,
		string $attribute,
		string $entry
	):void {
		$ldap = $this->featureContext->getLdap();
		$ldap->deleteAttributes(
			$entry . "," . $this->featureContext->getLdapBaseDN(),
			[$attribute => [$value]]
		);
	}

	/**
	 * @When the administrator imports this ldif data:
	 * @Given the administrator has imported this ldif data:
	 *
	 * @param PyStringNode $ldifData
	 *
	 * @return void
	 */
	public function theAdminImportsThisLdifData(PyStringNode $ldifData):void {
		$this->featureContext->importLdifData($ldifData->getRaw());
	}

	/**
	 * creates users in LDAP named: "<prefix>-0000" till "<prefix>-(<amount>-1)"
	 * e.g.with $amount=2000; and $prefix="my-user-"; "my-user-0000" till "my-user-1999"
	 * password is the username
	 *
	 * @Given the administrator has created :amount LDAP users with the prefix :prefix in the OU :ou
	 *
	 * @param int $amount
	 * @param string $prefix
	 * @param string $ou
	 *
	 * @return void
	 * @throws LdapException
	 */
	public function createLDAPUsers(
		int $amount,
		string $prefix,
		string $ou
	):void {
		$ldap = $this->featureContext->getLdap();
		$uidNumberSearch = $ldap->searchEntries(
			'objectClass=posixAccount',
			null,
			0,
			['uidNumber']
		);
		$maxUidNumber = 0;
		foreach ($uidNumberSearch as $searchResult) {
			if ((int)$searchResult['uidnumber'][0] > $maxUidNumber) {
				$maxUidNumber = (int)$searchResult['uidnumber'][0];
			}
		}
		$entry = [];
		$ouDN = 'ou=' . $ou . ',' . $this->featureContext->getLdapBaseDN();
		$ouExists = $ldap->exists($ouDN);
		if (!$ouExists) {
			$entry['objectclass'][] = 'top';
			$entry['objectclass'][] = 'organizationalunit';
			$entry['ou'] = $ou;
			$ldap->add($ouDN, $entry);
			$this->featureContext->setToDeleteDNs($ouDN);
		}

		$lenOfSuffix = \strlen((string)$amount);
		for ($i = 0; $i < $amount; $i++) {
			$uid = $prefix . \str_pad((string)$i, $lenOfSuffix, '0', STR_PAD_LEFT);
			$newDN = 'uid=' . $uid . ',ou=' . $ou . ',' . $this->featureContext->getLdapBaseDN();

			$entry = [];
			$entry['cn'] = $uid;
			$entry['sn'] = $i;
			$entry['homeDirectory'] = '/home/openldap/' . $uid;
			$entry['objectclass'][] = 'posixAccount';
			$entry['objectclass'][] = 'inetOrgPerson';
			$entry['userPassword'] = $uid;
			$entry['displayName'] = $uid;
			$entry['gidNumber'] = 5000;
			$entry['uidNumber'] = $maxUidNumber + $i + 1;

			$ldap->add($newDN, $entry);
			$this->featureContext->addUserToCreatedUsersList(
				$uid,
				$uid,
				$uid,
				null,
				null,
				false
			);

			if ($ouExists) {
				//if the OU did not exist, we have created it,
				//and we will delete the OU recursive.
				//No need to remember the entries to delete
				$this->featureContext->setToDeleteDNs($newDN);
			}
		}
	}

	/**
	 * We need to make sure that the setup routines are called in the correct order
	 * So this is the main function for setUp
	 *
	 * @BeforeScenario
	 *
	 * @param BeforeScenarioScope $scope
	 *
	 * @return void
	 * @throws Exception
	 */
	public function setUpBeforeScenario(BeforeScenarioScope $scope):void {
		$environment = $scope->getEnvironment();
		// Get all the contexts you need in this context
		$this->featureContext = $environment->getContext('FeatureContext');
	}

	/**
	 * @Then /^the users returned by the API should have$/
	 *
	 * @param TableNode $usersList
	 *
	 * @return void
	 * @throws Exception
	 */
	public function theUsersShouldHave(TableNode $usersList):void {
		$this->featureContext->verifyTableNodeColumnsCount($usersList, 1);
		$users = $usersList->getRows();
		$usersSimplified = $this->featureContext->simplifyArray($users);
		$respondedArray = $this->featureContext->getArrayOfUsersResponded($this->featureContext->getResponse());
		foreach ($usersSimplified as $user) {
			Assert::assertTrue(\in_array($user, $respondedArray));
		}
	}
}
