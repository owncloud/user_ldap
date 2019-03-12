<?php
/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Laurens Post <Crote@users.noreply.github.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Copyright (c) 2019, ownCloud GmbH.
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

use OCA\User_LDAP\Config\ServerMapper;
use OC\Core\Command\Base;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ShowConfig extends Base {

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
		parent::configure();

		$this
			->setName('ldap:show-config')
			->setDescription('shows the LDAP configuration')
			->addArgument(
					'configID',
					InputArgument::OPTIONAL,
					'will show the configuration of the specified id'
					 )
			->addOption(
					'show-password',
					null,
					InputOption::VALUE_NONE,
					'show ldap bind password'
					 )
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$configId = $input->getArgument('configID');
		if ($configId) {
			$this->showConfig($configId, $input, $output);
		} else {
			// show all configs
			$allConfigs = $this->mapper->listAll();
			foreach ($allConfigs as $config) {
				$this->showConfig($config->getId(), $input, $output);
			}
		}
	}

	protected function showConfig($configId, InputInterface $input, OutputInterface $output) {
		$config = $this->mapper->find($configId);
		$showPassword = $input->getOption('show-password');
		if ($showPassword === false) {
			$config->setPassword('***');
		}
		switch ($input->getOption('output')) {
			case self::OUTPUT_FORMAT_JSON:
				$output->writeln(\json_encode($config));
				break;
			case self::OUTPUT_FORMAT_JSON_PRETTY:
				$output->writeln(\json_encode($config, JSON_PRETTY_PRINT));
				break;
			default:
				$table = new Table($output);
				$table->setHeaders(['Configuration', $configId]);
				$rows = [];
				foreach ($config->jsonSerialize() as $key => $value) {
					if (\is_array($value)) {
						$value = \implode(';', $value);
					}
					$rows[] = [$key, $value];
				}

				$table->setRows($rows);
				$table->render();
		}
	}

	/**
	 * Prints LDAP configuration
	 *
	 * @param string $configId
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @param bool $withPassword      Set to TRUE to show plaintext passwords in output
	 * FIXME reenable this legacy rendering
	 */
	protected function showConfig($configId, InputInterface $input, OutputInterface $output) {
		$config = $this->mapper->find($configId);
		if ($this->showPassword === false) {
			$config->ldapAgentPassword = '***';
		}
		$configData = $config->jsonSerialize();
		if ($input->getOption('output') === self::OUTPUT_FORMAT_PLAIN) {
			$table = new Table($output);
			$table->setHeaders(['Configuration', $configId]);
			$rows = [];
			foreach ($configData as $key => $value) {
				if (\is_array($value)) {
					$value = \implode(';', $value);
				} elseif ($key === 'ldapAgentPassword') {
					$value = $config->ldapAgentPassword;
				}
				$rows[] = [$key, $value];
			}

			$table->setRows($rows);
			$table->render();
		} else {
			parent::writeArrayInOutputFormat(
				$input,
				$output,
				\array_merge($configData, ['id' => $configId]),
				self::DEFAULT_OUTPUT_PREFIX,
				true
			);
		}
	}
}
