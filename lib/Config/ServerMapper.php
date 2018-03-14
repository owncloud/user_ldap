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

namespace OCA\User_LDAP\Config;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OCA\User_LDAP\Exceptions\ConfigException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IConfig;
use OCP\ILogger;

class ServerMapper {
	const PREFIX = 'config-';

	/**
	 * @var IConfig
	 */
	private $config;
	/**
	 * @var $logger
	 */
	private $logger;

	/**
	 * ServerMapper constructor.
	 *
	 * @param IConfig $config
	 * @param ILogger $logger
	 */
	public function __construct(IConfig $config, ILogger $logger) {
		$this->config = $config;
		$this->logger = $logger;
	}

	/**
	 * @param Server $server
	 * @throws UniqueConstraintViolationException
	 */
	public function insert(Server $server) {
		$key = self::PREFIX.$server->getId();
		$keys = $this->config->getAppKeys('user_ldap');
		if (\in_array($key, $keys)) {
			throw new UniqueConstraintViolationException("$key already exists", null);
		}
		$this->config->setAppValue('user_ldap', $key, \json_encode($server));
	}

	/**
	 * @param Server $server
	 * @throws DoesNotExistException
	 */
	public function update(Server $server) {
		$key = self::PREFIX.$server->getId();
		$keys = $this->config->getAppKeys('user_ldap');
		if (!\in_array($key, $keys)) {
			throw new DoesNotExistException("$key does not exist");
		}
		$this->config->setAppValue('user_ldap', $key, \json_encode($server));
	}

	/**
	 * @param $id
	 * @return Server
	 * @throws ConfigException
	 * @throws DoesNotExistException
	 */
	public function find($id) {
		$key = self::PREFIX.$id;
		$json = $this->config->getAppValue('user_ldap', $key, null);
		if ($json === null) {
			throw new DoesNotExistException("$key does not exist");
		}
		$a = \json_decode($json, true);
		return new Server($a);
	}

	/**
	 * @return Server[]
	 */
	public function listAll() {
		$keys = $this->config->getAppKeys('user_ldap');
		$configs = [];
		foreach ($keys as $key) {
			if (\strpos($key, self::PREFIX) === 0) {
				$json = $this->config->getAppValue('user_ldap', $key, null);
				$a = \json_decode($json, true);
				// log broken configs, but continue listing valid configs
				try {
					$configs[] = new Server($a);
				} catch (ConfigException $e) {
					$this->logger->logException($e);
				}
			}
		}
		return $configs;
	}

	/**
	 * @param $id
	 * @throws DoesNotExistException
	 */
	public function delete($id) {
		$key = self::PREFIX.$id;
		$json = $this->config->getAppValue('user_ldap', $key, null);
		if ($json === null) {
			throw new DoesNotExistException("$key does not exist");
		}
		$this->config->deleteAppValue('user_ldap', $key);
	}
}
