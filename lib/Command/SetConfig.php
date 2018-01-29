<?php
/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud GmbH.
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

namespace OCA\User_LDAP\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use OCA\User_LDAP\Helper;
use OCA\User_LDAP\Configuration;

class SetConfig extends Command {

	protected function configure() {
		$this
			->setName('ldap:set-config')
			->setDescription('modifies an LDAP configuration')
			->addArgument(
					'configID',
					InputArgument::REQUIRED,
					'the configuration ID'
				     )
			->addArgument(
					'configKey',
					InputArgument::REQUIRED,
					'the configuration key'
				     )
			->addArgument(
					'configValue',
					InputArgument::REQUIRED,
					'the new configuration value'
				     )
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$helper = new Helper();
		$availableConfigs = $helper->getServerConfigurationPrefixes();
		$configID = $input->getArgument('configID');
		if(!in_array($configID, $availableConfigs)) {
			var_dump($availableConfigs);
			$output->writeln("Invalid configID");
			return;
		}

		$this->setValue(
			$configID,
			$input->getArgument('configKey'),
			$input->getArgument('configValue')
		);
	}

	/**
	 * save the configuration value as provided
	 * @param string $configID
	 * @param string $configKey
	 * @param string $configValue
	 */
	protected function setValue($configID, $key, $value) {
		$configHolder = new Configuration($configID);
		$configHolder->$key = $value;
		$configHolder->saveConfiguration();
	}
}
