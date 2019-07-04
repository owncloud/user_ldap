<?php
/**
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
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
use OCP\AppFramework\Db\DoesNotExistException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportConfig extends Command {

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
			->setName('ldap:import-config')
			->setDescription('imports an LDAP configuration from stdin')
		;
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|null|void
	 * @throws \Doctrine\DBAL\Exception\UniqueConstraintViolationException
	 * @throws \OCA\User_LDAP\Exceptions\ConfigException
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$json = $this->getFromStdin();
		$config = \json_decode($json, true);
		if (!\is_array($config) || empty($config)) {
			throw new \UnexpectedValueException('The config must contain a valid json array: ('.\json_last_error().') '.\json_last_error_msg());
		}
		$newServer = new Server($config);

		try {
			$this->mapper->find($newServer->getId());
			$this->mapper->update($newServer);
		} catch (DoesNotExistException $e) {
			$this->mapper->insert($newServer);
		}
	}

	/**
	 * Get the content from stdin ("config:import < file.json")
	 *
	 * @return string
	 */
	protected function getFromStdin() {
		// Read from stdin. stream_set_blocking is used to prevent blocking
		// when nothing is passed via stdin.
		\stream_set_blocking(STDIN, 0);
		$content = \file_get_contents('php://stdin');
		\stream_set_blocking(STDIN, 1);
		return $content;
	}
}
