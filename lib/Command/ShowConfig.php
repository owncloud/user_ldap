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

use OCA\User_LDAP\Config\LegacyConfig;
use OCA\User_LDAP\Config\ServerMapper;
use OC\Core\Command\Base;
use OCP\AppFramework\Db\DoesNotExistException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ShowConfig extends Base {

	/** @var ServerMapper */
	protected $mapper;

	/** @var LegacyConfig  */
	protected $legacyConfig;

	/** @var bool */
	protected $showPassword;

	/**
	 * @param ServerMapper $mapper
	 * @param LegacyConfig $legacyConfig
	 */
	public function __construct(ServerMapper $mapper, LegacyConfig $legacyConfig) {
		parent::__construct();
		$this->mapper = $mapper;
		$this->legacyConfig = $legacyConfig;
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
			->addOption(
				'legacy',
				null,
				InputOption::VALUE_NONE,
				'show legacy config'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->showPassword = $input->getOption('show-password');
		$configId = $input->getArgument('configID');
		$legacy = $input->getOption('legacy');
		if ($legacy) {
			$allPrefixes = $this->legacyConfig->getAllPrefixes();
			if ($configId && !\in_array($configId, $allPrefixes)) {
				$output->writeln("prefix $configId does not exist");
				return;
			}
			$configIds = $configId ? [$configId] : $allPrefixes;
			foreach ($configIds as $configId) {
				$this->showLegacyConfig($configId, $input, $output);
			}
		} else {
			try {
				$configIds = $configId ? [$this->mapper->find($configId)] : $this->mapper->listAll();
				foreach ($configIds as $config) {
					$this->showConfig($config->getId(), $input, $output);
				}
			} catch (DoesNotExistException $e) {
				$output->writeln("Configuration with configID '$configId' does not exist");
			}
		}
	}

	protected function showConfig($configId, InputInterface $input, OutputInterface $output) {
		$config = $this->mapper->find($configId);
		if ($this->showPassword === false) {
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
					if ($key === 'groupTrees' || $key === 'userTrees') {
						foreach ($value as $mappingDN => $mapping) {
							foreach ($mapping as $mappingKey => $mappingValue) {
								if (\is_array($mappingValue)) {
									$mappingValue = \implode(';', $mappingValue);
								}
								$rows[] = [$mappingKey, $mappingValue];
							}
						}
						continue;
					}
					if (\is_array($value)) {
						$value = \implode(';', $value);
					} elseif (\is_bool($value)) {
						$value = \var_export($value, true);
					}
					$rows[] = [$key, $value];
				}
				$table->setRows($rows);
				$table->render();
		}
	}

	/**
	 * Prints legacy LDAP configuration(s)
	 *
	 * @param string $prefix
	 * @param string[] $config
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	protected function showLegacyConfig($prefix, InputInterface $input, OutputInterface $output) {
		$config = $this->legacyConfig->getConfig($prefix);
		\ksort($config);
		if ($this->showPassword === false) {
			$config['ldapAgentPassword'] = '***';
		}
		if ($input->getOption('output') === self::OUTPUT_FORMAT_PLAIN) {
			$table = new Table($output);
			$table->setHeaders(['Configuration', $prefix]);
			$rows = [];
			foreach ($config as $key => $value) {
				if (\is_array($value)) {
					$value = \implode(';', $value);

				}
				$rows[] = [$key, $value];
			}

			$table->setRows($rows);
			$table->render();
		} else {
			parent::writeArrayInOutputFormat(
				$input,
				$output,
				$config,
				self::DEFAULT_OUTPUT_PREFIX,
				true
			);
		}
	}
}
