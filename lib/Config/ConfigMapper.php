<?php
/**
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Copyright (c) 2019, ownCloud GmbH
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

class ConfigMapper {
	const PREFIX = 'conn-';

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
	 * @param Config $config
	 *
	 * @throws UniqueConstraintViolationException
	 */
	public function insert(Config $config) {
		$key = self::PREFIX . $config->getId();
		$keys = $this->config->getAppKeys('user_ldap');
		if (\in_array($key, $keys)) {
			throw new UniqueConstraintViolationException("$key already exists", null);
		}
		$this->config->setAppValue('user_ldap', $key, \json_encode($config));
	}

	/**
	 * @param Config $config
	 *
	 * @throws DoesNotExistException
	 */
	public function update(Config $config) {
		$key = self::PREFIX . $config->getId();
		$keys = $this->config->getAppKeys('user_ldap');
		if (!\in_array($key, $keys)) {
			throw new DoesNotExistException("$key does not exist");
		}
		$this->config->setAppValue('user_ldap', $key, \json_encode($config));
	}

	/**
	 * @param string|null $id
	 *
	 * @return Config
	 *
	 * @throws ConfigException
	 * @throws DoesNotExistException
	 */
	public function find($id) {
		$key = self::PREFIX . $id;
		$json = $this->config->getAppValue('user_ldap', $key, null);
		if ($json === null) {
			throw new DoesNotExistException("$key does not exist");
		}
		$a = \json_decode($json, true);
		$a['id'] = $id;
		return new Config($a);
	}

	/**
	 * @return Config[]
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
					$a['id'] = \substr($key, \strlen(self::PREFIX));
					$configs[] = new Config($a);
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
		$key = self::PREFIX . $id;
		$json = $this->config->getAppValue('user_ldap', $key, null);
		if ($json === null) {
			throw new DoesNotExistException("$key does not exist");
		}
		$this->config->deleteAppValue('user_ldap', $key);
	}

	public function nextPossibleConfigurationPrefix() {
		$qb = \OC::$server->getDatabaseConnection()->getQueryBuilder();
		$qb->select('configkey')
			->from('appconfig')
			->where(
				$qb->expr()->eq('appid', $qb->expr()->literal('user_ldap'))
			)
			->andWhere(
				$qb->expr()->like('configkey', $qb->expr()->literal(self::PREFIX . '%'))
			)
			->orderBy('configkey', 'DESC')
		;
		$result = $qb->execute();
		$maxPrefix = $result->fetchColumn();
		if ($maxPrefix === false) {
			$count = 0;
		} else {
			$count = (int)\substr($maxPrefix, \strlen(self::PREFIX . 's'));
		}

		return 's'.\str_pad($count+1, 2, '0', STR_PAD_LEFT);
	}
}
