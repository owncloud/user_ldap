<?php
/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use OCA\User_LDAP\User_Proxy;
use OCA\User_LDAP\Group_Proxy;
use OCP\IConfig;

class Search extends Command {
	/** @var \OCP\IConfig */
	protected $ocConfig;
	/** @var User_Proxy */
	protected $userProxy;
	/** @var Group_Proxy */
	protected $groupProxy;

	/**
	 * @param \OCP\IConfig $ocConfig
	 * @param User_Proxy $userProxy
	 * @param Group_Proxy $groupProxy
	 */
	public function __construct(IConfig $ocConfig, User_Proxy $userProxy, Group_Proxy $groupProxy) {
		parent::__construct();
		$this->ocConfig = $ocConfig;
		$this->userProxy = $userProxy;
		$this->groupProxy = $groupProxy;
	}

	protected function configure() {
		$this
			->setName('ldap:search')
			->setDescription('executes a user or group search')
			->addArgument(
					'search',
					InputArgument::REQUIRED,
					'the search string (can be empty)'
					 )
			->addOption(
					'group',
					null,
					InputOption::VALUE_NONE,
					'searches groups instead of users'
					 )
			->addOption(
					'offset',
					null,
					InputOption::VALUE_REQUIRED,
					'The offset of the result set. Needs to be a multiple of limit. defaults to 0.',
					0
					 )
			->addOption(
					'limit',
					null,
					InputOption::VALUE_REQUIRED,
					'limit the results. 0 means no limit, defaults to 15',
					15
					 )
		;
	}

	/**
	 * Tests whether the offset and limit options are valid
	 * @param int $offset
	 * @param int $limit
	 * @throws \InvalidArgumentException
	 */
	protected function validateOffsetAndLimit($offset, $limit) {
		if ($limit < 0) {
			throw new \InvalidArgumentException('limit must be  0 or greater');
		}
		if ($offset  < 0) {
			throw new \InvalidArgumentException('offset must be 0 or greater');
		}
		if ($limit === 0 && $offset !== 0) {
			throw new \InvalidArgumentException('offset must be 0 if limit is also set to 0');
		}
		if ($offset > 0 && ($offset % $limit !== 0)) {
			throw new \InvalidArgumentException('offset must be a multiple of limit');
		}
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$offset = (int)$input->getOption('offset');
		$limit = (int)$input->getOption('limit');
		$this->validateOffsetAndLimit($offset, $limit);

		if ($input->getOption('group')) {
			$result = $this->groupProxy->getGroups($input->getArgument('search'), $limit, $offset);
			$printID = false;
			// convert the limit of groups to null. This will show all the groups available instead of
			// nothing, and will match the same behaviour the search for users has.
			if ($limit === 0) {
				$limit = null;
			}
		} else {
			$result = $this->userProxy->getDisplayNames($input->getArgument('search'), $limit, $offset);
			$printID = true;
		}

		foreach ($result as $id => $name) {
			$line = $name . ($printID ? ' ('.$id.')' : '');
			$output->writeln($line);
		}
	}
}
