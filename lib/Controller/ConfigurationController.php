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

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OCA\User_LDAP\Access;
use OCA\User_LDAP\Connection;
use OCA\User_LDAP\Config\Server;
use OCA\User_LDAP\Config\ServerMapper;
use OCA\User_LDAP\Exceptions\BindFailedException;
use OCA\User_LDAP\Exceptions\ConfigException;
use OCA\User_LDAP\Helper;
use OCA\User_LDAP\LDAP;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
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

	/** @var ServerMapper */
	protected $mapper;

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
	 * @param ServerMapper $mapper
	 * @param IL10N $l10n
	 * @param LDAP $ldapWrapper
	 * @param Helper $helper
	 */
	public function __construct($appName,
								IRequest $request,
								IConfig $config,
								ISession $session,
								ServerMapper $mapper,
								IL10N $l10n,
								LDAP $ldapWrapper,
								Helper $helper
	) {
		parent::__construct($appName, $request);
		$this->config = $config;
		$this->session = $session;
		$this->mapper = $mapper;
		$this->l10n = $l10n;
		$this->ldapWrapper = $ldapWrapper;
		$this->helper = $helper;
	}

	/**
	 * @return DataResponse
	 */
	public function listAll() {
		return new DataResponse($this->mapper->listAll());
	}

	/**
	 * create a new ldap config
	 *
	 * @return DataResponse
	 */
	public function create() {
		$d = $this->request->post;
		try {
			$c = new Server($d);
		} catch (ConfigException $e) {
			return new DataResponse(['error' => $e], Http::STATUS_UNPROCESSABLE_ENTITY);
		}
		try {
			$this->mapper->insert($c);
		} catch (UniqueConstraintViolationException $e) {
			return new DataResponse(null, Http::STATUS_CONFLICT);
		}
		return new DataResponse($c);
	}
	/**
	 * get the given ldap config
	 *
	 * @param string $id config id
	 * @return DataResponse
	 */
	public function read($id) {
		try {
			$c = $this->mapper->find($id);
		} catch (DoesNotExistException $e) {
			return new DataResponse(null, Http::STATUS_NOT_FOUND);
		} catch (ConfigException $e) {
			return new DataResponse(['error' => $e], Http::STATUS_UNPROCESSABLE_ENTITY);
		}

		// hide password
		if ($c->getPassword()) {
			$c->setPassword(true);
		}

		return new DataResponse($c);
	}

	/**
	 * write the given ldap config, config is created if it does not exist
	 *
	 * @return DataResponse
	 * @throws DoesNotExistException should not happen
	 */
	public function update() {
		$d = $this->request->post;
		try {
			$n = new Server($d);
		} catch (ConfigException $e) {
			return new DataResponse(['error' => $e], Http::STATUS_UNPROCESSABLE_ENTITY);
		}
		// id must exist
		try {
			$c = $this->mapper->find($n->getId());
		} catch (DoesNotExistException $e) {
			return new DataResponse(null, Http::STATUS_NOT_FOUND);
		} catch (ConfigException $e) {
			// ignore ... we are overwriting it anyway
		}

		// copy old password if no new one was configured
		if ($n->getPassword() === true) {
			$n->setPassword($c->getPassword());
		}

		$this->mapper->update($n);

		// hide password
		$n->setPassword(true);
		return new DataResponse($n);
	}

	/**
	 * test the given ldap config
	 *
	 * @return DataResponse
	 */
	public function test() {
		$d = $this->request->post;
		try {
			$c = new Server($d);
		} catch (ConfigException $e) {
			return new DataResponse(['error' => $e], Http::STATUS_UNPROCESSABLE_ENTITY);
		}

		$c->setActive(true);

		$connection = new Connection($this->ldapWrapper, $c);

		// TODO check config
		//return new DataResponse([
		//	'message' => $this->l10n->t('The configuration is invalid. Please have a look at the logs for further details.')
		//], Http::STATUS_BAD_REQUEST);

		// try connection
		try {
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
					return new DataResponse([
						'message' => $e->getMessage(),
						'code' => $e->getCode()
					], Http::STATUS_BAD_REQUEST);
				}
				return new DataResponse([
					'message' => $this->l10n->t('The configuration is valid and the connection could be established!')
				]);
			}
			return new DataResponse([
				'message' => $this->l10n->t('The configuration is valid, but the Bind failed. Please check the server settings and credentials.')
			], Http::STATUS_BAD_REQUEST);
		} catch (BindFailedException $e) {
			return new DataResponse([
				'message' => $this->l10n->t('The configuration is valid, but the Bind failed. Please check the server settings and credentials.')
			], Http::STATUS_BAD_REQUEST);
		} catch (\Exception $e) {
			return new DataResponse([
				'message' => $e->getMessage(),
				'code' => $e->getCode()
			], Http::STATUS_BAD_REQUEST);
		}
	}

	/**
	 * delete the given ldap config
	 *
	 * @param string $id config id
	 * @return DataResponse
	 */
	public function delete($id) {
		try {
			$this->mapper->delete($id);
		} catch (DoesNotExistException $e) {
			return new DataResponse(null, Http::STATUS_NOT_FOUND);
		}
		return new DataResponse(null, Http::STATUS_OK);
	}

	/**
	 * find an entity in a mapping using some well known ldap attributes:
	 * cn, uid, samaccountname, userprincipalname, mail, displayname
	 *
	 * @param string $query to use
	 * @return DataResponse
	 */
	public function discover($query) {
		$d = $this->request->post;
		try {
			$c = new Server($d);
		} catch (ConfigException $e) {
			return new DataResponse(['error' => $e], Http::STATUS_UNPROCESSABLE_ENTITY);
		}
		if (count($c->getMappings()) !== 1) {
			return new DataResponse(['error' => 'To discover an entity the config must contain the mapping that should be used'], Http::STATUS_UNPROCESSABLE_ENTITY);
		}

		$c->setActive(true);

		$connection = new Connection($this->ldapWrapper, $c);
		try {
			if ($connection->bind()) {
				try {
					$username = Access::escapeFilterPart($query);
					$filter = "(&(|(objectClass=User)(objectClass=inetOrgPerson))(|(cn=$username)(uid=$username)(samaccountname=$username)(userprincipalname=$username)(mail=$username)(displayname=$username)))";
					$sr = $this->ldapWrapper->search(
						$connection->getConnectionResource(),
						$c->getMappings()[0]->getBaseDN(),
						$filter,
						\array_merge(Access::$uuidAttributes, ['dn', 'objectclass', 'cn', 'uid', 'samaccountname', 'userprincipalname', 'mail', 'mailnickname', 'title', 'displayname', 'name', 'givenname', 'sn', 'company', 'department', 'postalcode', 'jpegphoto', 'thumbnailphoto', 'quota', 'memberof']), // more attributes
						0, // attributes and values
						10);
					if ($sr === false) {
						return new DataResponse([
							'message' => $this->ldapWrapper->error($connection->getConnectionResource()),
							'code' => $this->ldapWrapper->errno($connection->getConnectionResource())
						], Http::STATUS_BAD_REQUEST);
					}
					$entries = $this->ldapWrapper->getEntries($connection->getConnectionResource(), $sr);
					return new DataResponse($this->toAssocArray($entries));
				} catch (\Exception $e) {
					if ($e->getCode() === 1) {
						return new DataResponse([
							'message' => $this->l10n->t('The configuration is invalid: anonymous bind is not allowed.')
						], Http::STATUS_BAD_REQUEST);
					}
					return new DataResponse([
						'message' => $e->getMessage(),
						'code' => $e->getCode()
					], Http::STATUS_BAD_REQUEST);
				}
			}
			return new DataResponse([
				'message' => $this->l10n->t('The configuration is valid, but the Bind failed. Please check the server settings and credentials.')
			], Http::STATUS_BAD_REQUEST);
		} catch (\Exception $e) {
			return new DataResponse([
				'message' => $e->getMessage(),
				'code' => $e->getCode()
			], Http::STATUS_BAD_REQUEST);
		}
	}

	/**
	 * @param array $entries
	 * @return array
	 */
	private function toAssocArray(array $entries) {
		unset($entries['count']);
		$results = [];
		foreach ($entries as $entry) {
			$dn = $entry['dn'];
			$result = [];
			unset($entry['dn'], $entry['count']);
			foreach ($entry as $key => $values) {
				if (!is_numeric($key)) {
					$rv = [];
					foreach ($values as $k => $value) {
						if (is_numeric($k)) {
							$rv[] = $value;
						}
					}
					$result[$key] = $rv;
				}
			}
			$results[$dn] = $result;
		}
		return $results;
	}
}
