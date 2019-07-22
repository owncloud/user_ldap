<?php
/**
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

namespace OCA\User_LDAP\Ldap;

use OC\ServerNotAvailableException;
use OCA\User_LDAP\ILDAPWrapper;
use OCP\ILogger;

class Server {
	/** @var ILDAPWrapper */
	private $ldapWrapper;

	/** @var ILogger */
	private $logger;

	/** @var string */
	private $host;

	/** @var int */
	private $port;

	private $configuration;

	/** @var resource */
	private $resource;

	/**
	 * Server constructor.
	 *
	 * @param ILDAPWrapper $ldapWrapper
	 * @param ILogger $logger
	 * @param string $host
	 * @param int $port
	 * @param $configuration
	 */
	public function __construct(ILDAPWrapper $ldapWrapper,
								ILogger $logger,
								$host,
								$port,
								$configuration
	) {
		$this->ldapWrapper = $ldapWrapper;
		$this->logger = $logger;
		$this->host = $host;
		$this->port = $port;
		$this->configuration = $configuration;
	}

	/**
	 * @param string $serverData
	 *
	 * @return bool
	 * @throws ServerNotAvailableException
	 */
	public function openConnection($serverData = '') {
		$this->logger->debug(
			"Trying to connect to {$serverData} server {$this->host}:{$this->port}",
			['app' => 'user_ldap']
		);

		$this->prepareConnection();
		if ($this->connect()) {
			if (@$this->ldapWrapper->bind(
				$this->resource,
				$this->configuration->ldapAgentName,
				$this->configuration->ldapAgentPassword
			)) {
				$this->logger->debug(
					"Bind to {$serverData} server success: " . \var_export($this->resource, true),
					['app' => 'user_ldap']
				);
				return true;
			}
		}

		return false;
	}

	/**
	 * @return resource
	 */
	public function getResource() {
		return $this->resource;
	}

	/**
	 * Preconfigure environment before trying to connect
	 *
	 * @return void
	 */
	private function prepareConnection() {
		if ($this->configuration->turnOffCertCheck) {
			if (\putenv('LDAPTLS_REQCERT=never')) {
				$this->logger->debug(
					'Turned off SSL certificate validation successfully.',
					['app' => 'user_ldap']
				);
			} else {
				$this->logger->warning(
					'Could not turn off SSL certificate validation.',
					['app' => 'user_ldap']
				);
			}
		}
	}

	/**
	 * Create a resource and set connection options from configuration
	 *
	 * @return bool
	 * @throws ServerNotAvailableException
	 */
	private function connect() {
		if ($this->host === '') {
			return false;
		}

		$this->resource = $this->ldapWrapper->connect($this->host, $this->port);
		if ($this->ldapWrapper->setOption($this->resource, LDAP_OPT_PROTOCOL_VERSION, 3)) {
			if ($this->ldapWrapper->setOption($this->resource, LDAP_OPT_REFERRALS, 0)) {
				if ($this->configuration->ldapTLS) {
					$this->ldapWrapper->startTls($this->resource);
				}
			}
		} else {
			throw new ServerNotAvailableException('Could not set required LDAP Protocol version.');
		}

		// Set network timeout threshold to avoid long delays when ldap server cannot be resolved
		$this->ldapWrapper->setOption($this->resource, LDAP_OPT_NETWORK_TIMEOUT, \intval($this->configuration->ldapNetworkTimeout));
		if (!$this->ldapWrapper->isResource($this->resource)) {
			$this->resource = null; // to indicate it really is not set, connect() might have set it to false
			throw new ServerNotAvailableException("Connect to {$this->host}:{$this->port} failed");
		}
		return true;
	}
}
