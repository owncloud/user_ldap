<?php
/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Lyonel Vincent <lyonel@ezix.org>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
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

namespace OCA\User_LDAP;

use OC\ServerNotAvailableException;
use OCA\User_LDAP\Config\Server;
use OCA\User_LDAP\Exceptions\BindFailedException;
use OCP\ICacheFactory;
use OCP\Util;

/**
 * responsible for LDAP connections in context with the provided configuration
 * also handles caching related to this connection
 */
class Connection extends LDAPUtility {
	private $ldapConnectionRes;

	// for now, these are the autodetected unique attributes
	public $uuidAttributes = [
		'entryuuid', 'nsuniqueid', 'objectguid', 'guid', 'ipauniqueid'
	];

	/**
	 * @var \OCP\ICache
	 */
	protected $cache;

	/**
	 * @var Server
	 */
	protected $server;

	/**
	 * Constructor
	 * @param ICacheFactory $cf
	 * @param ILDAPWrapper $ldap
	 * @param Server $server
	 */
	public function __construct(ICacheFactory $cf, ILDAPWrapper $ldap, Server $server) {
		parent::__construct($ldap);
		$this->server = $server;

		if ($cf->isAvailable()) {
			$this->cache = $cf->create();
		}
	}

	public function __destruct() {
		if ($this->getLDAP()->isResource($this->ldapConnectionRes)) {
			@$this->getLDAP()->unbind($this->ldapConnectionRes);
		}
	}

	/**
	 * defines behaviour when the instance is cloned
	 */
	public function __clone() {
		$this->ldapConnectionRes = null; // use new connection resource
	}

	/**
	 * Returns the LDAP handler
	 *
	 * @throws \OC\ServerNotAvailableException
	 * @throws BindFailedException
	 */
	public function getConnectionResource() {
		if ($this->getLDAP()->isResource($this->ldapConnectionRes)) {
			return $this->ldapConnectionRes;
		}

		$this->ldapConnectionRes = null;
		$this->establishConnection();

		if ($this->ldapConnectionRes === null) {
			Util::writeLog('user_ldap', "No LDAP Connection to server {$this->server->getHost()}:{$this->server->getPort()}", Util::ERROR);
			throw new ServerNotAvailableException('Connection to LDAP server could not be established');
		}
		return $this->ldapConnectionRes;
	}

	/**
	 * resets the connection resource
	 */
	public function resetConnectionResource() {
		if ($this->ldapConnectionRes !== null) {
			@$this->getLDAP()->unbind($this->ldapConnectionRes);
			$this->ldapConnectionRes = null;
		}
	}

	/**
	 * @param string|null $key
	 * @return string
	 */
	private function getCacheKey($key) {
		$prefix = "LDAP-user_ldap-{$this->server->getId()}-";
		if ($key === null) {
			return $prefix;
		}
		return $prefix.\md5($key);
	}

	/**
	 * @param string $key
	 * @return mixed|null
	 */
	public function getFromCache($key) {
		if ($this->cache === null || !$this->server->getCacheTTL()) {
			return null;
		}
		$key = $this->getCacheKey($key);

		return \json_decode(\base64_decode($this->cache->get($key)), true);
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 */
	public function writeToCache($key, $value) {
		if ($this->cache === null
			|| !$this->server->getCacheTTL()
			|| !$this->server->isActive()) {
			return;
		}
		$key   = $this->getCacheKey($key);
		$value = \base64_encode(\json_encode($value));
		$this->cache->set($key, $value, $this->server->getCacheTTL());
	}

	public function clearCache() {
		if ($this->cache !== null) {
			$this->cache->clear($this->getCacheKey(null));
		}
	}

	/**
	 * @return Server
	 */
	public function getServer() {
		return $this->server;
	}

	/**
	 * Connects and Binds to LDAP
	 *
	 * @throws \OC\ServerNotAvailableException
	 * @throws BindFailedException
	 */
	private function establishConnection() {
		if (!$this->server->isActive()) {
			return null;
		}
		if (!$this->ldapConnectionRes) {
			if ($this->server->isTurnOffCertCheck()) {
				if (\putenv('LDAPTLS_REQCERT=never')) {
					Util::writeLog('user_ldap',
						'Turned off SSL certificate validation successfully.',
						Util::DEBUG);
				} else {
					Util::writeLog('user_ldap',
						'Could not turn off SSL certificate validation.',
						Util::WARN);
				}
			}

			try {
				// skip contacting main server after failed connection attempt
				// until cache TTL is reached
				if (empty($this->server->getBackupHost())
					|| (!$this->server->isOverrideMainServer()
						&& !$this->getFromCache('overrideMainServer'))
				) {
					$this->doConnect(
						$this->server->getHost(),
						$this->server->getPort(),
						$this->server->getTimeout());
					if (@$this->ldap->bind(
						$this->ldapConnectionRes,
						$this->server->getBindDN(),
						$this->server->getPassword())
					) {
						return true;
					}
					Util::writeLog('user_ldap',
						'Bind failed: ' . $this->getLDAP()->errno($this->ldapConnectionRes) . ': ' . $this->getLDAP()->error($this->ldapConnectionRes),
						Util::DEBUG); // log only in debug mod because this is triggered by wrong passwords
					throw new BindFailedException();
				}
			} catch (ServerNotAvailableException $e) {
				if (empty($this->server->getBackupHost())) {
					throw $e;
				}
			} catch (BindFailedException $e) {
				if (empty($this->server->getBackupHost())) {
					throw $e;
				}
			}

			if (empty($this->server->getBackupHost())) {
				$this->ldapConnectionRes = null;
				return false;
			}

			// try the Backup (Replica!) Server
			Util::writeLog('user_ldap',
				"Trying to connect to backup server {$this->server->getBackupHost()}:{$this->server->getBackupPort()}",
				Util::DEBUG);
			$this->doConnect(
				$this->server->getBackupHost(),
				$this->server->getBackupPort(),
				$this->server->getTimeout());
			if (@$this->ldap->bind(
				$this->ldapConnectionRes,
				$this->server->getBindDN(),
				$this->server->getPassword())
			) {
				if (!$this->getFromCache('overrideMainServer')) {
					//when bind to backup server succeeded and failed to main server,
					//skip contacting him until next cache refresh
					$this->writeToCache('overrideMainServer', true);
				}
				return true;
			}
			Util::writeLog('user_ldap',
				"Bind to backup server failed: {$this->getLDAP()->errno($this->ldapConnectionRes)}: {$this->getLDAP()->error($this->ldapConnectionRes)}",
				Util::DEBUG);
			throw new BindFailedException();
		}
		return null;
	}

	/**
	 * @param string $host
	 * @param string $port
	 * @param int $timeout
	 * @return bool
	 * @throws \OC\ServerNotAvailableException
	 */
	private function doConnect($host, $port, $timeout) {
		if ($host === '') {
			return false;
		}
		$this->ldapConnectionRes = $this->getLDAP()->connect($host, $port);
		if ($this->getLDAP()->setOption($this->ldapConnectionRes, LDAP_OPT_PROTOCOL_VERSION, 3)) {
			if ($this->getLDAP()->setOption($this->ldapConnectionRes, LDAP_OPT_REFERRALS, 0)) {
				if ($this->server->isTls()) {
					$this->getLDAP()->startTls($this->ldapConnectionRes);
				}
			}
		} else {
			throw new ServerNotAvailableException('Could not set required LDAP Protocol version.');
		}
		// Set network timeout threshold to avoid long delays when ldap server cannot be resolved
		$this->getLDAP()->setOption($this->ldapConnectionRes, LDAP_OPT_NETWORK_TIMEOUT, $timeout);
		if (!$this->getLDAP()->isResource($this->ldapConnectionRes)) {
			$this->ldapConnectionRes = null; // to indicate it really is not set, connect() might have set it to false
			throw new ServerNotAvailableException("Connect to $host:$port failed");
		}
		return true;
	}

	/**
	 * Binds to LDAP
	 *
	 * @throws \OC\ServerNotAvailableException
	 * @throws BindFailedException
	 *
	 */
	public function bind() {
		if (!$this->server->isActive()) {
			return false;
		}

		// binding is done via getConnectionResource()
		$cr = $this->getConnectionResource();

		if (!$this->getLDAP()->isResource($cr)) {
			return false;
		}
		return true;
	}
}
