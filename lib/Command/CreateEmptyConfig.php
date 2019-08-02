<?php
/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Martin Konrad <konrad@frib.msu.edu>
 * @author Morris Jobke <hey@morrisjobke.de>
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

class CreateEmptyConfig extends Command {
	/** @var ConfigMapper */
	protected $mapper;

	/**
	 * @param ConfigMapper $mapper
	 */
	public function __construct(ConfigMapper $mapper) {
		parent::__construct();
		$this->mapper = $mapper;
	}

	protected function configure() {
		$this
			->setName('ldap:create-empty-config')
			->setDescription('creates an empty LDAP configuration')
			->addArgument(
				'configID',
				InputArgument::OPTIONAL,
				'create a configuration with the specified id'
			)
		;
	}

	/**
	 * Executes the current command.
	 *
	 * @return null|int null or 0 if everything went fine, or an error code
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$configID = $input->getArgument('configID');
		if ($configID === null) {
			$configID = $this->mapper->nextPossibleConfigurationPrefix();
		} else {
			// Check we are not trying to create an empty configid
			if ($configID === '') {
				$output->writeln('configID cannot be empty');
				return 1;
			}
		}
		$newConfig = new Config(['id' => $configID]);

		try {
			// Check if we are not already using this configid
			$this->mapper->find($newConfig->getId());
			$output->writeln("configID '$configID' already exists");
			return 1;
		} catch (DoesNotExistException $e) {
			$this->mapper->insert($newConfig);
			$output->writeln("Created new configuration with configID '{$configID}'");
		}
		return 0;
	}
}
