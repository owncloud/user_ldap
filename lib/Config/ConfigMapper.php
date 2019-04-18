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
}
