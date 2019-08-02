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

use OCA\User_LDAP\Config\Config;
use OCA\User_LDAP\Config\ConfigMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetConfig extends Command {

	/** @var ConfigMapper */
	protected $mapper;

	/**
	 * @param IConfig $config
	 */
	public function __construct(ConfigMapper $configMapper) {
		parent::__construct();
		$this->mapper = $configMapper;
	}

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
		$configId = $input->getArgument('configID');
		try {
			$config = $this->mapper->find($configId);
		} catch (DoesNotExistException $e) {
			$output->writeln("Configuration with configID '$configId' does not exist");
			return;
		}

		$this->setValue(
			$config,
			$input->getArgument('configKey'),
			$input->getArgument('configValue')
		);

		$this->mapper->update($config);
	}

	/**
	 * save the configuration value as provided
	 * @param Config $config
	 * @param string $key
	 * @param string $value
	 */
	protected function setValue($config, $key, $value) {
		$config->$key = $value;
	}
}
