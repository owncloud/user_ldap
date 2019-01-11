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
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\RawMinkContext;
use TestHelpers\SetupHelper;

require_once 'bootstrap.php';

/**
 * context that holds all general steps for the user_ldap app
 */
class UserLdapGeneralContext extends RawMinkContext implements Context {
	private $oldConfig = [];
	/**
	 *
	 * @var Zend\Ldap\Ldap
	 */
	private $ldap;
	private $ldapAdminUser;
	private $ldapAdminPassword;
	private $ldapBaseDN;
	private $ldapHost;
	private $ldapUsersOU;
	private $ldapGroupsOU;
	private $toDeleteDNs = [];

	/**
	 *
	 * @var FeatureContext
	 */
	private $featureContext;
	
	/**
	 * @Given LDAP config :configId has key :configKey set to :configValue
	 *
	 * @param string $configId
	 * @param string $configKey
	 * @param string $configValue
	 *
	 * @return void
	 */
	public function ldapConfigHasKeySetTo(
		$configId, $configKey, $configValue
	) {
		//remember old settings to be able to set them back after test run
		$occResult = SetupHelper::runOcc(
			['ldap:show-config', $configId, "--output=json"]
		);
		if ($occResult['code'] !== "0") {
			throw new Exception(
				"could not read LDAP settings " . $occResult['stdErr']
			);
		}
		$originalConfig = \json_decode($occResult['stdOut'], true);
		if (isset($originalConfig[$configKey])) {
			$this->oldConfig[$configId][$configKey] = $originalConfig[$configKey];
		} else {
			$this->oldConfig[$configId][$configKey] = "";
		}
		
		$this->setLdapSetting($configId, $configKey, $configValue);
	}

	/**
	 * @Given LDAP config :configId has these settings:
	 *
	 * @param string $configId
	 * @param TableNode $table with the headings |key | value |
	 *
	 * @return void
	 */
	public function ldapConfigHasTheseSettings($configId, TableNode $table) {
		foreach ($table as $line) {
			$this->ldapConfigHasKeySetTo(
				$configId, $line['key'], $line['value']
			);
		}
	}

	/**
	 * @Given the LDAP users have been resynced
	 * @When the LDAP users are resynced
	 *
	 * @throws Exception
	 * @return void
	 */
	public function theLdapUsersHaveBeenResynced() {
		$occResult = SetupHelper::runOcc(
			['user:sync', 'OCA\User_LDAP\User_Proxy', '-m', 'remove']
		);
		if ($occResult['code'] !== "0") {
			throw new Exception("could not sync LDAP users " . $occResult['stdErr']);
		}
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
	 */
	public function setTheLdapAttributeOfTheEntryTo(
		$attribute, $entry, $value, $append=false
	) {
		$ldapEntry = $this->ldap->getEntry($entry . "," . $this->ldapBaseDN);
		Zend\Ldap\Attribute::setAttribute($ldapEntry, $attribute, $value, $append);
		$this->ldap->update($entry . "," . $this->ldapBaseDN, $ldapEntry);
	}
	
	/**
	 * @When the administrator sets the ldap attribute :attribute of the entry :entry to
	 *
	 * @param string $attribute
	 * @param string $entry
	 * @param TableNode $table
	 *
	 * @return void
	 */
	public function theLdapAttributeOfTheEntryToTable($attribute, $entry, $table) {
		$first = true;
		foreach ($table as $row) {
			if ($first) {
				$this->setTheLdapAttributeOfTheEntryTo(
					$attribute, $entry, $row
				);
				$first = false;
			} else {
				$this->addValueToLdapAttributeOfTheEntry(
					$attribute, $entry, $row
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
	 */
	public function theLdapAttributeOfTheEntryToContentOfFile(
		$attribute, $entry, $filename
	) {
		$value = \file_get_contents(\getenv("FILES_FOR_UPLOAD") . $filename);
		
		$this->setTheLdapAttributeOfTheEntryTo(
			$attribute, $entry, $value
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
	 */
	public function addValueToLdapAttributeOfTheEntry($value, $attribute, $entry) {
		$this->setTheLdapAttributeOfTheEntryTo($attribute, $entry, $value, true);
	}

	/**
	 * @When the administrator deletes the ldap entry :entry
	 *
	 * @param string $entry
	 *
	 * @return void
	 */
	public function deleteTheLdapEntry($entry) {
		$this->ldap->delete($entry . "," . $this->ldapBaseDN);
	}

	/**
	 * @When the administrator deletes the value :value from the attribute :attribute of the ldap entry :entry
	 *
	 * @param string $value
	 * @param string $attribute
	 * @param string $entry DN, not containing baseDN
	 *
	 * @return void
	 */
	public function deleteValueFromLdapAttribute($value, $attribute, $entry) {
		$this->ldap->deleteAttributes(
			$entry . "," . $this->ldapBaseDN, [$attribute => [$value]]
		);
	}

	/**
	 * @When the administrator imports this ldif data:
	 *
	 * @param PyStringNode $ldifData
	 *
	 * @return void
	 */
	public function theAdminImportsThisLdifData(PyStringNode $ldifData) {
		$this->importLdifData($ldifData->getRaw());
	}

	/**
	 * @return \Zend\Ldap\Ldap
	 */
	public function getLdap() {
		return $this->ldap;
	}

	/**
	 * @return string
	 */
	public function getLdapAdminUser() {
		return $this->ldapAdminUser;
	}

	/**
	 * @return string
	 */
	public function getLdapAdminPassword() {
		return $this->ldapAdminPassword;
	}

	/**
	 * @return string
	 */
	public function getLdapBaseDN() {
		return $this->ldapBaseDN;
	}

	/**
	 * @return string
	 */
	public function getLdapHost() {
		return $this->ldapHost;
	}

	/**
	 * @return string
	 */
	public function getLdapUsersOU() {
		return $this->ldapUsersOU;
	}

	/**
	 * @return string
	 */
	public function getLdapGroupsOU() {
		return $this->ldapGroupsOU;
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
	 */
	public function createLDAPUsers($amount, $prefix, $ou) {
		$uidNumberSearch = $this->ldap->searchEntries(
			'objectClass=posixAccount', null, 0, ['uidNumber']
		);
		$maxUidNumber = 0;
		foreach ($uidNumberSearch as $searchResult) {
			if ((int)$searchResult['uidnumber'][0] > $maxUidNumber) {
				$maxUidNumber = (int)$searchResult['uidnumber'][0];
			}
		}
		$entry = [];
		$ouDN = 'ou=' . $ou . ',' . $this->ldapBaseDN;
		$ouExists = $this->ldap->exists($ouDN);
		if (!$ouExists) {
			$entry['objectclass'][] = 'top';
			$entry['objectclass'][] = 'organizationalunit';
			$entry['ou'] = $ou;
			$this->ldap->add($ouDN, $entry);
			$this->toDeleteDNs[] = $ouDN;
		}
		
		$lenOfSuffix = \strlen((string)$amount);
		for ($i = 0; $i < $amount; $i++) {
			$uid = $prefix . \str_pad($i, $lenOfSuffix, '0', STR_PAD_LEFT);
			$newDN = 'uid=' . $uid . ',ou=' . $ou . ',' . $this->ldapBaseDN;
			
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
			
			$this->ldap->add($newDN, $entry);
			$this->featureContext->addUserToCreatedUsersList(
				$uid, $uid, $uid, null, false
			);

			if ($ouExists) {
				//if the OU did not exist, we have created it,
				//and we will delete the OU recursive.
				//No need to remember the entries to delete
				$this->toDeleteDNs[] = $newDN;
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
	 */
	public function setUpBeforeScenario(BeforeScenarioScope $scope) {
		$environment = $scope->getEnvironment();
		// Get all the contexts you need in this context
		$this->featureContext = $environment->getContext('FeatureContext');
		
		$suiteParameters = SetupHelper::getSuiteParameters($scope);
		$this->connectToLdap($suiteParameters);
		$this->importLdifFile(
			__DIR__ . (string)$suiteParameters['ldapInitialUserFilePath']
		);
		$this->theLdapUsersHaveBeenResynced();
	}

	/**
	 * @param array $suiteParameters
	 *
	 * @return void
	 */
	public function connectToLdap($suiteParameters) {
		$this->ldapAdminUser = (string)$suiteParameters['ldapAdminUser'];
		$this->ldapAdminPassword = (string)$suiteParameters['ldapAdminPassword'];
		$this->ldapBaseDN = (string)$suiteParameters['ldapBaseDN'];
		$this->ldapUsersOU = (string)$suiteParameters['ldapUsersOU'];
		$this->ldapGroupsOU = (string)$suiteParameters['ldapGroupsOU'];
		$ci = \getenv("CI");
		if ($ci === "drone") {
			$this->ldapHost = (string)$suiteParameters['ldapHostDrone'];
		} else {
			$this->ldapHost = (string)$suiteParameters['ldapHost'];
		}
		
		$options = [
			'host' => $this->ldapHost,
			'password' => $this->ldapAdminPassword,
			'bindRequiresDn' => true,
			'baseDn' => $this->ldapBaseDN,
			'username' => $this->ldapAdminUser
		];
		$this->ldap = new Zend\Ldap\Ldap($options);
		$this->ldap->bind();
		SetupHelper::init(
			$this->featureContext->getAdminUsername(),
			$this->featureContext->getAdminPassword(),
			$this->featureContext->getBaseUrl(),
			$this->featureContext->getOcPath()
		);
	}

	/**
	 * imports an ldif string
	 *
	 * @param string $ldifData
	 *
	 * @return void
	 */
	public function importLdifData($ldifData) {
		$items = Zend\Ldap\Ldif\Encoder::decode($ldifData);
		
		if (isset($items['dn'])) {
			//only one item in the ldif data
			$this->ldap->add($items['dn'], $items);
		} else {
			foreach ($items as $item) {
				$this->ldap->add($item['dn'], $item);
			}
		}
	}

	/**
	 *
	 * @param string $path
	 *
	 * @return void
	 */
	public function importLdifFile($path) {
		$ldifData = \file_get_contents($path);
		$this->importLdifData($ldifData);
	}

	/**
	 * @AfterScenario
	 * @return void
	 */
	public function deleteUserAndGroups() {
		$this->ldap->delete(
			"ou=" . $this->ldapUsersOU . "," . $this->ldapBaseDN, true
		);
		$this->ldap->delete(
			"ou=" . $this->ldapGroupsOU . "," . $this->ldapBaseDN, true
		);
		foreach ($this->toDeleteDNs as $dn) {
			$this->ldap->delete($dn, true);
		}
		$this->theLdapUsersHaveBeenResynced();
	}

	/**
	 * After Scenario. Sets back old settings
	 *
	 * @AfterScenario
	 *
	 * @return void
	 */
	public function resetOldConfig() {
		foreach ($this->oldConfig as $configId => $settings) {
			foreach ($settings as $configKey => $configValue) {
				$this->setLdapSetting($configId, $configKey, $configValue);
			}
		}
	}

	/**
	 *
	 * @param string $configId
	 * @param string $configKey
	 * @param string $configValue
	 *
	 * @throws Exception
	 * @return void
	 */
	public function setLdapSetting($configId, $configKey, $configValue) {
		if ($configValue === "") {
			$configValue = "''";
		}
		$occResult = SetupHelper::runOcc(
			['ldap:set-config', $configId, $configKey, $configValue]
		);
		if ($occResult['code'] !== "0") {
			throw new Exception(
				"could not set LDAP setting " . $occResult['stdErr']
			);
		}
	}
}
