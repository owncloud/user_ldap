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

namespace OCA\User_LDAP\Controller;

use OCA\User_LDAP\Configuration;
use OCA\User_LDAP\Connection;
use OCA\User_LDAP\Helper;
use OCA\User_LDAP\LDAP;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ISession;

/**
 * Class ConfigurationController
 *
 * @package OCA\User_LDAP\Controller
 */
class ConfigurationController extends Controller {

	/** @var IConfig */
	protected $config;

	/** @var ISession */
	protected $session;

	/** @var IL10N */
	protected $l10n;

	/** @var LDAP */
	protected $ldapWrapper;

	/** @var Helper */
	protected $helper;

	const REFERENCE_KEY = 'ldap_configuration_active';
	const REFERENCE_KEY_LENGTH = 25;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IConfig $config
	 * @param ISession $session
	 * @param IL10N $l10n
	 * @param LDAP $ldapWrapper
	 * @param Helper $helper
	 */
	public function __construct($appName,
								IRequest $request,
								IConfig $config,
								ISession $session,
								IL10N $l10n,
								LDAP $ldapWrapper,
								Helper $helper
	) {
		parent::__construct($appName, $request);
		$this->config = $config;
		$this->session = $session;
		$this->l10n = $l10n;
		$this->ldapWrapper = $ldapWrapper;
		$this->helper = $helper;
	}

	private function isReferenceKey($key) {
		$needle = substr($key, -self::REFERENCE_KEY_LENGTH);
		return $needle === self::REFERENCE_KEY;
	}

	private function fetchAll() {
		$keys = $this->config->getAppKeys('user_ldap');
		$ids = [];
		$configs = [];
		foreach ($keys as $key) {
			if ($this->isReferenceKey($key)) {
				// determine the prefix length
				$id = substr($key, 0, -self::REFERENCE_KEY_LENGTH);
				$ids[] = $id;
				$configs[$id] = ['id' => $id];
			}
		}

		foreach ($keys as $key) {
			foreach ($ids as $id) {
				if (substr($key,0, strlen($id)) === $id) {
					$k = substr($key,strlen($id));
					// ignore password if it is set to true = is set
					$value = $this->config->getAppValue('user_ldap', $key);
					if ($k === 'ldap_agent_password' && !empty($value)) {
						$configs[$id][$k] = true;
					} else {
						$configs[$id][$k] = $value;
					}
					continue 2; // next config value
				}
			}
		}
		return $configs;
	}

	public function listAll() {
		return new DataResponse(array_values($this->fetchAll()));
	}

	/**
	 * create a new ldap config
	 *
	 * @param string $sourceId copy values from this config (optional)
	 * @return DataResponse
	 */
	public function create($sourceId = null) {
		$id = $this->helper->nextPossibleConfigurationPrefix();

		$resultData = ['configPrefix' => $id];

		$newConfig = new Configuration($this->config, $id, false);
		if ($sourceId === null) {
			// create empty config
			$configuration = new Configuration($this->config, $id, false);
			$newConfig->setConfiguration($configuration->getDefaults());
			$resultData['defaults'] = $configuration->getDefaults();
		} else {
			// copy existing config
			$originalConfig = new Configuration($this->config, $sourceId);
			$newConfig->setConfiguration($originalConfig->getConfiguration());
		}
		$newConfig->saveConfiguration();

		$configs = $this->fetchAll();
		return new DataResponse($configs[$id]);
	}
	/**
	 * get the given ldap config
	 *
	 * @param string $id config id
	 * @return DataResponse
	 */
	public function read($id) {
		$configs = $this->fetchAll();
		if (empty($id) || !isset($configs[$id])) {
			return new DataResponse(null, Http::STATUS_NOT_FOUND);
		}

		$configuration = $configs[$id];

		if (isset($configuration['ldap_agent_password']) && $configuration['ldap_agent_password'] !== '') {
			// hide password
			$configuration['ldap_agent_password'] = true;
		}

		return new DataResponse($configuration);
	}
	/**
	 * write the given ldap config, config is created if it does not exist
	 *
	 * @param string $id
	 * @param array $config
	 * @return DataResponse
	 */
	public function write($id, $config) {

		if (empty($id) ) {
			return new DataResponse(null, Http::STATUS_NOT_FOUND);
		}
		if (empty($config) ) {
			return new DataResponse(null, Http::STATUS_UNPROCESSABLE_ENTITY);
		}

		// TODO check keys

		foreach ($config as $key => $value) {
			// ignore password if it is set to true = is set don't change
			if ($key !== 'ldap_agent_password' || $value !== true) {
				$this->config->setAppValue('user_ldap', "$id$key", $value);
			}
		}

		$configs = $this->fetchAll();
		return new DataResponse($configs[$id]);
	}

	/**
	 * test the given ldap config
	 *
	 * @param string $id config id
	 * @return DataResponse
	 */
	public function test($id) {

		$configs = $this->fetchAll();
		if (empty($id) || !isset($configs[$id])) {
			return new DataResponse(null, Http::STATUS_NOT_FOUND);
		}

		$configuration = new Configuration($this->config, $id);
		$connection = new Connection($this->ldapWrapper, $configuration);

		try {
			$configurationOk = true;
			$conf = $connection->getConfiguration();
			if ($conf['ldap_configuration_active'] === '0') {
				//needs to be true, otherwise it will also fail with an irritating message
				$conf['ldap_configuration_active'] = '1';
				$configurationOk = $connection->setConfiguration($conf);
			}
			if ($configurationOk) {
				//Configuration is okay
				/*
				 * Clossing the session since it won't be used from this point on. There might be a potential
				 * race condition if a second request is made: either this request or the other might not
				 * contact the LDAP backup server the first time when it should, but there shouldn't be any
				 * problem with that other than the extra connection.
				 */
				$this->session->close();
				if ($connection->bind()) {
					/*
					 * This shiny if block is an ugly hack to find out whether anonymous
					 * bind is possible on AD or not. Because AD happily and constantly
					 * replies with success to any anonymous bind request, we need to
					 * fire up a broken operation. If AD does not allow anonymous bind,
					 * it will end up with LDAP error code 1 which is turned into an
					 * exception by the LDAP wrapper. We catch this. Other cases may
					 * pass (like e.g. expected syntax error).
					 */
					try {
						$this->ldapWrapper->read($connection->getConnectionResource(), '', 'objectClass=*', ['dn']);
					} catch (\Exception $e) {
						if ($e->getCode() === 1) {
							return new DataResponse([
								'message' => $this->l10n->t('The configuration is invalid: anonymous bind is not allowed.')
							], Http::STATUS_BAD_REQUEST);
						}
					}
					return new DataResponse([
						'message' => $this->l10n->t('The configuration is valid and the connection could be established!')
					]);
				}
				return new DataResponse([
					'message' => $this->l10n->t('The configuration is valid, but the Bind failed. Please check the server settings and credentials.')
				], Http::STATUS_BAD_REQUEST);
			}
			return new DataResponse([
				'message' => $this->l10n->t('The configuration is invalid. Please have a look at the logs for further details.')
			], Http::STATUS_BAD_REQUEST);
		} catch (\Exception $e) {
			return new DataResponse([
				'message' => $e->getMessage()
			], Http::STATUS_BAD_REQUEST);
		}
	}

	/**
	 * get the given ldap config
	 *
	 * @param string $id config id
	 * @return DataResponse
	 */
	public function delete($id) {
		$configs = $this->fetchAll();
		if (empty($id) || !isset($configs[$id])) {
			return new DataResponse(null, Http::STATUS_NOT_FOUND);
		}
		if ($this->helper->deleteServerConfiguration($id)) {
			return new DataResponse(null, Http::STATUS_OK);
		}
		return new DataResponse([
			'message' => $this->l10n->t('Failed to delete the server configuration')
		], Http::STATUS_BAD_REQUEST);
	}
}
