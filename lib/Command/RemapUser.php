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
		//$this->confirmUserIsMapped($uid);

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
		$entryCount = \count($entries);

		$output->writeln('');
		$output->writeln('Candidates found in LDAP:');
		$table2 = new Table($output);
		$table2->setHeaders(['username', 'uuid', 'dn']);
		foreach ($entries as $entry) {
			$table2->addRow([$entry['owncloud_name'], $entry['directory_uuid'], $entry['dn']]);
		}
		$table2->render();

		if ($entryCount > 1) {
			$output->writeln('<error>Found too many candidates in LDAP for the target user, remapping isn\'t possible</error>');
			return 1;
		} elseif ($entryCount < 1) {
			$output->writeln('<error>User not found in LDAP. Consider removing the ownCloud\'s account</error>');
			return 2;
		}

		if ($mappedData['mappedDN'] === $entries[0]['dn'] && $mappedData['mappedUUID'] === $entries[0]['directory_uuid']) {
			$output->writeln('The same user is already mapped. Nothing to do');
			return 0;  // just show a message and return a success code
		}

		$result = $this->mapping->replaceUUIDAndDN($uid, $entries[0]['dn'], $entries[0]['directory_uuid']);
		if ($result === false) {
			$output->writeln("<error>Failed to replace mapping data for user {$uid}</error>");
			return 3;
		}
		$output->writeln('Mapping data replaced');
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
	 * checks whether a user is actually mapped
	 * @param string $ocName the username as used in ownCloud
	 * @throws \Exception
	 * @return true
	 */
	private function confirmUserIsMapped($ocName) {
		$dn = $this->mapping->getDNByName($ocName);
		if ($dn === false) {
			throw new \Exception('The given user is not a recognized LDAP user.');
		}

		return true;
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
}
