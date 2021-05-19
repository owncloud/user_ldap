<?php
/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
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

use OCP\ICache;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\User_Proxy;

class InvalidateCache extends Command {
	/** @var User_Proxy */
	protected $backend;

	/** @var UserMapping */
	protected $mapping;
	/**
	 * @var ICache
	 */
	private $cache;

	public function __construct(User_Proxy $uBackend, UserMapping $mapping) {
		$this->backend = $uBackend;
		$this->mapping = $mapping;
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('ldap:invalidate-cache')
			->setDescription('invalidates the cache for given users or all users.')
			->addArgument(
					'user-id',
					InputArgument::IS_ARRAY,
					'the user name as used in ownCloud'
					 )
			->addOption(
					'all',
					null,
					InputOption::VALUE_NONE,
					'invalidates cache for all users'
					 )
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		# no cache -> nothing to do
		$memcache = \OC::$server->getMemCacheFactory();
		if (!$memcache->isAvailable()) {
			$output->writeln('No cache available - nothing to do ....');
			return;
		}
		$this->cache = $memcache->create();

		$all = $input->getOption('all');
		if ($all) {
			$this->invalidateForAllUsers();
			return;
		}

		$users = $input->getArgument('user-id');
		if (!\is_array($users)) {
			$users = [$users];
		}
		$progress = new ProgressBar($output, \count($users));
		foreach ($users as $user) {
			$progress->advance();
			$this->invalidateForUser($user);
		}
	}

	protected function confirmUserIsMapped($ocName): bool {
		$dn = $this->mapping->getDNByName($ocName);
		return !($dn === false);
	}

	private function invalidateForAllUsers(): void {
		# this is the cache key as used in Group_LDAP::getUserGroups - we might want to add more keys
		$this->cache->clear('getUserGroups');
	}

	private function invalidateForUser(string $uid): void {
		if (!$this->confirmUserIsMapped($uid)) {
			return;
		}
		if (!$this->backend->userExists($uid)) {
			return;
		}
		# this is the cache key as used in Group_LDAP::getUserGroups - we might want to add more keys
		$cacheKey = 'getUserGroups'.$uid;
		$this->cache->remove($cacheKey);
	}
}
