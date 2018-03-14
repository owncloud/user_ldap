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

use OCA\User_LDAP\Config\LegacyWrapper;
use OCA\User_LDAP\Config\Server;
use OCA\User_LDAP\Config\ServerMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetConfig extends Command {

	/** @var ServerMapper */
	protected $mapper;

	/**
	 * @param ServerMapper $mapper
	 */
	public function __construct(ServerMapper $mapper) {
		parent::__construct();
		$this->mapper = $mapper;
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

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|null|void
	 * @throws DoesNotExistException
	 * @throws \Doctrine\DBAL\Exception\UniqueConstraintViolationException
	 * @throws \OCA\User_LDAP\Exceptions\ConfigException
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$configId = $input->getArgument('configID');

		try {
			$server = $this->mapper->find($configId);
		} catch (DoesNotExistException $e) {
			$server = new Server(['id' => $configId]);
			$this->mapper->insert($server);
		}
		$this->setValue(
			$server,
			$input->getArgument('configKey'),
			$input->getArgument('configValue')
		);

		$this->mapper->update($server);
	}

	/**
	 * save the configuration value as provided
	 * @param Server $server
	 * @param string $key
	 * @param string $value
	 */
	protected function setValue(Server $server, $key, $value) {
		$migration = new LegacyWrapper($server);
		$migration->$key = $value;
	}
}
