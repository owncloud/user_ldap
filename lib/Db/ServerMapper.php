<?php
/**
 * Created by PhpStorm.
 * User: jfd
 * Date: 01.11.2018
 * Time: 17:10
 */

namespace OCA\User_LDAP\Db;


use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IConfig;

class ServerMapper {

	const PREFIX = 'config-';

	private $config;

	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	/**
	 * @param Server $server
	 * @throws UniqueConstraintViolationException
	 */
	public function insert(Server $server) {
		$key = self::PREFIX.$server->getId();
		$keys = $this->config->getAppKeys('user_ldap');
		if (isset($keys[$key])) {
			throw new UniqueConstraintViolationException("config with id $key already exists", null);
		}
		$this->config->setAppValue('user_ldap', $key, json_encode($server));
	}

	/**
	 * @param Server $server
	 * @throws \InvalidArgumentException if entity has no id
	 * @throws DoesNotExistException
	 */
	public function update(Server $server) {
		$key = self::PREFIX.$server->getId();
		if (empty($key)) {
			throw new \InvalidArgumentException('empty id');
		}
		$keys = $this->config->getAppKeys('user_ldap');
		if (!isset($keys[$key])) {
			throw new DoesNotExistException("$key does not exist");
		}
		$this->config->setAppValue('user_ldap', $key, json_encode($server));
	}

	public function find($id) {
		$key = self::PREFIX.$id;
		$json = $this->config->getAppValue('user_ldap', $key, null);
		if ($json === null) {
			return null;
		}
		$a = json_decode($json, true);
		return new Server($a);
	}

	public function listAll() {
		$keys = $this->config->getAppKeys('user_ldap');
		$configs = [];
		foreach ($keys as $key) {
			if (strpos($key, self::PREFIX) === 0) {
				$json = $this->config->getAppValue('user_ldap', $key, null);
				$a = json_decode($json);
				$configs[] =  new Server($a);
			}
		}
		return $configs;
	}
}