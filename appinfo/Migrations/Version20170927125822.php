<?php
namespace OCA\User_LDAP\Migrations;

use OCA\User_LDAP\Helper;
use OCP\Migration\ISimpleMigration;
use OCP\Migration\IOutput;
use \OCA\User_LDAP\Configuration;

/**
 * Migration to move the ldap config IDs to all use a prefix for consistency
 */
class Version20170927125822 implements ISimpleMigration {

	/**
	 * @var Helper
	 */
	protected $helper;

	public function __construct(Helper $helper) {
		$this->helper = $helper;
	}

	/**
     * @param IOutput $out
     */
    public function run(IOutput $out) {
        // Find out what configurations we are using and modify them

		$configPrefixes = $this->helper->getServerConfigurationPrefixes();
		foreach($configPrefixes as $prefix) {
			if($prefix === '') {
				// Found an empty prefix - we need to modify this one
				$newPrefix = $this->helper->getNewConfigPrefix();
				$oldConfig = new Configuration('');
				// Migrate the config
				$newConfig = new Configuration($newPrefix, false);
				foreach($oldConfig->getDefaults() as $configKey) {
					// Set the new config equal to the old config, or default
					$newConfig->$configKey = $oldConfig->$configKey;
				}
				$newConfig->saveConfiguration();
				$this->helper->deleteServerConfiguration('');
			}
		}
    }
}
