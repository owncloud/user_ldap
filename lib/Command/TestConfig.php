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

use OCA\User_LDAP\Config\Server;
use OCA\User_LDAP\Config\ServerMapper;
use OCA\User_LDAP\Connection;
use OCA\User_LDAP\LDAP;
use OCP\ICacheFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestConfig extends Command {

	/** @var ICacheFactory */
	protected $cf;

	/** @var ServerMapper */
	protected $mapper;

	/** @var LDAP */
	protected $ldap;

	/**
	 * @param ICacheFactory $cf
	 * @param ServerMapper $mapper
	 * @param LDAP $ldap
	 */
	public function __construct(ICacheFactory $cf, ServerMapper $mapper, LDAP $ldap) {
		parent::__construct();
		$this->cf = $cf;
		$this->mapper = $mapper;
		$this->ldap = $ldap;
	}

	protected function configure() {
		$this
			->setName('ldap:test-config')
			->setDescription('tests an LDAP configuration')
			->addArgument(
					'configID',
					InputArgument::REQUIRED,
					'the configuration ID'
					 )
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$configId = $input->getArgument('configID');
		$config = $this->mapper->find($configId);
		$result = $this->testConfig($config);
		if ($result === 0) {
			$output->writeln('The configuration is valid and the connection could be established!');
		} elseif ($result === 1) {
			$output->writeln('The configuration is invalid. Please have a look at the logs for further details.');
		} elseif ($result === 2) {
			$output->writeln('The configuration is valid, but the Bind failed. Please check the server settings and credentials.');
		} else {
			$output->writeln('Your LDAP server was kidnapped by aliens.');
		}
	}

	/**
	 * tests the specified connection
	 * @param Server $config
	 * @return int
	 * @throws \OCA\User_LDAP\Exceptions\BindFailedException
	 * @throws \OC\ServerNotAvailableException
	 */
	protected function testConfig(Server $config) {
		$config->setActive(true);
		$connection = new Connection($this->cf, $this->ldap, $config);

		//FIXME actually verify config
		//ensure validation is run before we attempt the bind
		//$connection->getConfiguration();

		if ($connection->bind()) {
			return 0;
		}
		return 2;
	}
}
