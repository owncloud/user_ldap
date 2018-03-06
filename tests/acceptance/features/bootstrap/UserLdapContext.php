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
 * LDAP context.
 */
class UserLdapContext extends RawMinkContext implements Context {

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
	
	/**
	 * @Given the LDAP config :configId has the key :configKey set to :configValue
	 * 
	 * @param string $configId
	 * @param string $configKey
	 * @param string $configValue
	 * 
	 * @return void
	 */
	public function theLdapConfigHasTheKeySetTo(
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
		$originalConfig = json_decode($occResult['stdOut'], true);
		if (isset($originalConfig[$configKey])) {
			$this->oldConfig[$configId][$configKey] = $originalConfig[$configKey];
		} else {
			$this->oldConfig[$configId][$configKey] = "";
		}
		
		$this->setLdapSetting($configId, $configKey, $configValue);
	}

	/**
	 * @Given the LDAP config :configId has these settings:
	 * 
	 * @param string $configId
	 * @param TableNode $table with the headings |key | value |
	 * 
	 * @return void
	 */
	public function theLdapConfigHasTheseSettings($configId, TableNode $table) {
		foreach ($table as $line) {
			$this->theLdapConfigHasTheKeySetTo(
				$configId, $line['key'], $line['value']
			);
		}
	}

	/**
	 * @Given the LDAP users have been resynced
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
	 * @When the admin sets the ldap attribute :attribute of the entry :entry to :value
	 * 
	 * @param string $attribute
	 * @param string $entry
	 * @param string $value
	 * 
	 * @return void
	 */
	public function theLdapAttributeOfTheEntryTo($attribute, $entry, $value) {
		$ldapEntry = $this->ldap->getEntry($entry . "," . $this->ldapBaseDN);
		Zend\Ldap\Attribute::setAttribute($ldapEntry, $attribute, $value);
		$this->ldap->update($entry . "," . $this->ldapBaseDN, $ldapEntry);
	}

	/**
	 * @When the admin sets the ldap attribute :attribute of the entry :entry to the content of the file :filename
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
		$value = file_get_contents(getenv("FILES_FOR_UPLOAD") . $filename);
		
		$this->theLdapAttributeOfTheEntryTo(
			$attribute, $entry, $value
		);
	}

	/**
	 * @When the admin adds :value to the ldap attribute :attribute of the entry :entry
	 * 
	 * @param string $value
	 * @param string $attribute
	 * @param string $entry
	 * 
	 * @return void
	 */
	public function addValueToLdapAttributeOfTheEntry($value, $attribute, $entry) {
		$ldapEntry = $this->ldap->getEntry($entry . "," . $this->ldapBaseDN);
		Zend\Ldap\Attribute::setAttribute($ldapEntry, $attribute, $value, true);
		$this->ldap->update($entry . "," . $this->ldapBaseDN, $ldapEntry);
	}

	/**
	 * @When the admin imports this ldif data:
	 * 
	 * @param PyStringNode $ldifData
	 * 
	 * @return void
	 */
	public function theAdminImportsThisLdifData(PyStringNode $ldifData) {
		$this->importLdifData($ldifData->getRaw());
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
		$ci = getenv("CI");
		if ($ci === "drone") {
			$this->ldapHost = (string)$suiteParameters['ldapHostDrone'];
		} else {
			$this->ldapHost = (string)$suiteParameters['ldapHost'];
		}
		
		$options = array(
			'host' => $this->ldapHost,
			'password' => $this->ldapAdminPassword,
			'bindRequiresDn' => true,
			'baseDn' => $this->ldapBaseDN,
			'username' => $this->ldapAdminUser
		);
		$this->ldap = new Zend\Ldap\Ldap($options);
		$this->ldap->bind();
		SetupHelper::init(
			"admin", (string)$suiteParameters['adminPassword'],
			$this->getMinkParameter('base_url'), $suiteParameters['ocPath']
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
		$ldifData = file_get_contents($path);
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
		foreach ($this->oldConfig as $configId => $settings ) {
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