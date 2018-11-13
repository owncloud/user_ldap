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


use OCA\User_LDAP\Exceptions\ConfigException;

class Server implements \JsonSerializable {

	private $id;
	private $active;
	private $host;
	private $port;
	private $bindDN;
	private $password;
	private $tls;
	private $turnOffCertCheck;
	private $supportsPaging;
	private $pageSize;
	private $supportsMemberOf;
	private $overrideMainServer;
	private $backupHost;
	private $backupPort;
	private $backupTTL;
	private $cacheTTL;
    /**
     * @var UserTree[]
     */
    private $userTrees = [];
    /**
     * @var GroupTree[]
     */
    private $groupTrees = [];

	/**
	 * Server constructor.
	 *
	 * @param array|null $data
	 * @throws ConfigException
	 */
	public function __construct(array $data = null) {
		if ($data === null) {
			$data = [];
		}
		if (empty($data['id'])) {
			throw new ConfigException('Server config object must have an "id" property. Pro-tip: generate a uuid.');
		}
		$this->id = (string)$data['id'];
		$this->active =             isset($data['active'])             ?   (bool)$data['active']             : false;
		$this->host =               isset($data['host'])               ? (string)$data['host']               : '127.0.0.1';
		$this->port =               isset($data['port'])               ?    (int)$data['port']               : 369;
		$this->bindDN =             isset($data['bindDN'])             ? (string)$data['bindDN']             : '';
		$this->password =           isset($data['password'])           ?         $data['password']           : ''; // can also be true
		$this->tls =                isset($data['tls'])                ?   (bool)$data['tls']                : false;
		$this->turnOffCertCheck =   isset($data['turnOffCertCheck'])   ?   (bool)$data['turnOffCertCheck']   : false;
		$this->supportsPaging =     isset($data['supportsPaging'])     ?   (bool)$data['supportsPaging']     : false;
		$this->pageSize =           isset($data['pageSize'])           ?    (int)$data['pageSize']           : 500;
		$this->supportsMemberOf =   isset($data['supportsMemberOf'])   ?   (bool)$data['supportsMemberOf']   : false;
		$this->overrideMainServer = isset($data['overrideMainServer']) ?   (bool)$data['overrideMainServer'] : false;
		$this->backupHost =         isset($data['backupHost'])         ? (string)$data['backupHost']         : null;
		$this->backupPort =         isset($data['backupPort'])         ?    (int)$data['backupPort']         : null;
		$this->backupTTL =          isset($data['backupTTL'])          ?    (int)$data['backupTTL']          : 30;
		$this->cacheTTL =           isset($data['cacheTTL'])           ?    (int)$data['cacheTTL']           : 600;
		if (empty($data['users'])) {
            $data['users'] = [];
        }
        foreach ($data['users'] as $userTree) {
            $this->userTrees[] = new UserTree($userTree);
        }
        if (empty($data['users'])) {
            $data['groups'] = [];
        }
        foreach ($data['groups'] as $groupTree) {
            $this->groupTrees[] = new GroupTree($groupTree);
        }
	}

	/**
	 * @return string
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @param string $id
	 */
	public function setId($id) {
		$this->id = $id;
	}

	/**
	 * @return bool
	 */
	public function isActive() {
		return $this->active;
	}

	/**
	 * @param bool $active
	 */
	public function setActive($active) {
		$this->active = $active;
	}

	/**
	 * @return string
	 */
	public function getHost() {
		return $this->host;
	}

	/**
	 * @param string $host
	 */
	public function setHost($host) {
		$this->host = (string)$host;
	}

	/**
	 * @return int
	 */
	public function getPort() {
		return $this->port;
	}

	/**
	 * @param int $port
	 */
	public function setPort($port) {
		$this->port = (int)$port;
	}

	/**
	 * @return string
	 */
	public function getBindDN() {
		return $this->bindDN;
	}

	/**
	 * @param string $bindDN
	 */
	public function setBindDN($bindDN) {
		$this->bindDN = (string)$bindDN;
	}

	/**
	 * @return string|true
	 */
	public function getPassword() {
		return $this->password;
	}

	/**
	 * @param string|true $password
	 */
	public function setPassword($password) {
		$this->password = $password;
	}

	/**
	 * @return bool
	 */
	public function isTls() {
		return $this->tls;
	}

	/**
	 * @param bool $tls
	 */
	public function setTls($tls) {
		$this->tls = (bool)$tls;
	}

	/**
	 * @return bool
	 */
	public function isTurnOffCertCheck() {
		return $this->turnOffCertCheck;
	}

	/**
	 * @param bool $turnOffCertCheck
	 */
	public function setTurnOffCertCheck($turnOffCertCheck) {
		$this->turnOffCertCheck = (bool)$turnOffCertCheck;
	}

	/**
	 * @return bool
	 */
	public function isSupportsPaging() {
		return $this->supportsPaging;
	}

	/**
	 * @param bool $supportsPaging
	 */
	public function setSupportsPaging($supportsPaging) {
		$this->supportsPaging = (bool)$supportsPaging;
	}

	/**
	 * @return int
	 */
	public function getPageSize() {
		return $this->pageSize;
	}

	/**
	 * @param int $pageSize
	 */
	public function setPageSize($pageSize) {
		$this->pageSize = (int)$pageSize;
	}

	/**
	 * @return bool
	 */
	public function isSupportsMemberOf() {
		return $this->supportsMemberOf;
	}

	/**
	 * @param bool $supportsMemberOf
	 */
	public function setSupportsMemberOf($supportsMemberOf) {
		$this->supportsMemberOf = (bool)$supportsMemberOf;
	}

	/**
	 * @return bool
	 */
	public function isOverrideMainServer() {
		return $this->overrideMainServer;
	}

	/**
	 * @param bool $overrideMainServer
	 */
	public function setOverrideMainServer($overrideMainServer) {
		$this->overrideMainServer = (bool)$overrideMainServer;
	}

	/**
	 * @return string
	 */
	public function getBackupHost() {
		return $this->backupHost;
	}

	/**
	 * @param string $backupHost
	 */
	public function setBackupHost($backupHost) {
		$this->backupHost = (string)$backupHost;
	}

	/**
	 * @return int
	 */
	public function getBackupPort() {
		return $this->backupPort;
	}

	/**
	 * @param int $backupPort
	 */
	public function setBackupPort($backupPort) {
		$this->backupPort = (int)$backupPort;
	}

	/**
	 * @return int
	 */
	public function getBackupTTL() {
		return $this->backupTTL;
	}

	/**
	 * @param int $backupTTL
	 */
	public function setBackupTTL($backupTTL) {
		$this->backupTTL = (int)$backupTTL;
	}

	/**
	 * @return int
	 */
	public function getCacheTTL() {
		return $this->cacheTTL;
	}

	/**
	 * @param int $cacheTTL
	 */
	public function setCacheTTL($cacheTTL) {
		$this->cacheTTL = (int)$cacheTTL;
	}

    /**
     * @param string $baseDN
     * @return UserTree|null
     */
    public function getUserTree($baseDN) {
        return $this->userTrees[$baseDN]??null;
    }

	/**
	 * @return UserTree[]
	 */
	public function getUserTrees() {
		return $this->userTrees;
	}

    /**
     * @param string $baseDN
     * @param UserTree $tree
     */
    public function setUserTree($baseDN, $tree) { // TODO normalize dn?
        if ($tree === null) {
            unset($this->userTrees[$baseDN]);
        } else {
            $this->userTrees[$baseDN] = $tree;
        }
    }

    /**
     * @param UserTree[] $trees
     */
    public function setUserTrees($trees) {
        $this->userTrees = $trees;
    }

    /**
     * @param string $baseDN
     * @return GroupTree|null
     */
    public function getGroupTree($baseDN) {
        return $this->groupTrees[$baseDN]??null;
    }

    /**
     * @return GroupTree[]
     */
    public function getGroupTrees() {
        return $this->groupTrees;
    }

    /**
     * @param string $baseDN
     * @param GroupTree $tree
     */
    public function setGroupTree($baseDN, $tree) { // TODO normalize dn?
        if ($tree === null) {
            unset($this->groupTrees[$baseDN]);
        } else {
            $this->groupTrees[$baseDN] = $tree;
        }
    }

    /**
     * @param GroupTree[] $trees
     */
    public function setGroupTrees($trees) {
        $this->groupTrees = $trees;
    }

	/**
	 * Specify data which should be serialized to JSON
	 *
	 * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return mixed data which can be serialized by <b>json_encode</b>,
	 * which is a value of any type other than a resource.
	 * @since 5.4.0
	 */
	public function jsonSerialize() {
		$data = [];
		// maybe using an array to store the properties makes more sense ... but please with explicit getters and setters
		foreach ($this as $key => $value) {
			if ($key === 'groupTrees' || $key === 'userTrees') {
				$data[$key] = [];
				/** @var Tree $mapping */
				foreach ($value as $mapping) {
					$data[$key][] = $mapping->jsonSerialize();
				}
			} else {
				$data[$key] = $value;
			}
		}
		return $data;
	}
}

/*

obsolete

		'ldapBase' => null, // only used in the wizard
		'ldapExperiencedAdmin' => false, // no longer read
		'lastJpegPhotoLookup' => null, // was used to remember when avatar jpeg was last updated

		private $expertUUIDUserAttr; // if the admin wants to override the uuid attribute it is stored here ... so same as the uuid attribute

		// private $useMemberOfToDetectMembership = true; // used in tandem with supportsMemberOf

 */