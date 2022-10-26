<?php
/**
 * @copyright Copyright (c) 2022, ownCloud GmbH.
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
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\Helper as LDAPHelper;
use OCA\User_LDAP\User_Proxy;

class RemapUser extends Command {
	/** @var \OCA\User_LDAP\User_Proxy */
	protected $backend;

	/** @var \OCA\User_LDAP\Helper */
	protected $helper;

	/** @var \OCA\User_LDAP\Mapping\UserMapping */
	protected $mapping;

	/**
	 * @param User_Proxy $uBackend
	 * @param LDAPHelper $helper
	 * @param UserMapping $mapping
	 */
	public function __construct(User_Proxy $uBackend, LDAPHelper $helper, UserMapping $mapping) {
		$this->backend = $uBackend;
		$this->helper = $helper;
		$this->mapping = $mapping;
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('ldap:remap-user')
			->setDescription('checks whether a user exists on LDAP')
			->addArgument(
				'ocName',
				InputArgument::REQUIRED,
				'the user name as used in ownCloud'
			)
			->addOption(
				'force',
				null,
				InputOption::VALUE_NONE,
				'ignores disabled LDAP configuration'
			)
		;
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|void|null
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$uid = $input->getArgument('ocName');
		$this->isAllowed($input->getOption('force'));

		$mappedData = $this->getMappedUUIDAndDN($uid);
		if ($mappedData['mappedDN'] === false || $mappedData['mappedUUID'] === false) {
			$output->writeln('User not mapped yet. Try to sync it with the user:sync command');
			return -1;
		}

		$output->writeln('Mapped user found in the DB:');
		$table1 = new Table($output);
		$table1->setHeaders(['username', 'uuid', 'dn']);
		$table1->addRow([$uid, $mappedData['mappedUUID'], $mappedData['mappedDN']]);
		$table1->render();

		$entries = $this->backend->findUsername($uid);

		$output->writeln('');
		$output->writeln('Candidates found in LDAP:');
		$table2 = new Table($output);
		$table2->setHeaders(['username', 'uuid', 'dn']);
		foreach ($entries as $entry) {
			$table2->addRow([$entry['owncloud_name'], $entry['directory_uuid'], $entry['dn']]);
		}
		$table2->render();

		try {
			$message = $this->remapUser($uid, $mappedData, $entries);
			$output->writeln($message);
		} catch (\UnexpectedValueException $e) {
			$output->writeln("<error>{$e->getMessage()}</error>");
			return $e->getCode();
		}
	}

	private function getMappedUUIDAndDN($username) {
		$dn = $this->mapping->getDNByName($username);
		$uuid = $this->mapping->getUUIDByName($username);
		return [
			'mappedDN' => $dn,
			'mappedUUID' => $uuid,
		];
	}

	/**
	 * checks whether the setup allows reliable checking of LDAP user existence
	 * @throws \Exception
	 * @return true
	 */
	private function isAllowed($force) {
		if ($this->helper->haveDisabledConfigurations() && !$force) {
			throw new \Exception('Cannot check user existence, because '
				. 'disabled LDAP configurations are present.');
		}

		return true;
	}

	private function remapUser($uid, $mappedData, $entries) {
		$entryCount = \count($entries);
		if ($entryCount > 1) {
			throw new \UnexpectedValueException('Found too many candidates in LDAP for the target user, remapping isn\'t possible', 1);
		} elseif ($entryCount < 1) {
			throw new \UnexpectedValueException('User not found in LDAP. Consider removing the ownCloud\'s account', 2);
		}

		if ($mappedData['mappedDN'] === $entries[0]['dn'] && $mappedData['mappedUUID'] === $entries[0]['directory_uuid']) {
			return 'The same user is already mapped. Nothing to do';
		}

		$result = $this->mapping->replaceUUIDAndDN($uid, $entries[0]['dn'], $entries[0]['directory_uuid']);
		if ($result === false) {
			throw new \UnexpectedValueException("Failed to replace mapping data for user {$uid}", 3);
		}
		return 'Mapping data replaced';
	}
}
