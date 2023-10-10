<?php
/**
 * @author Alexander Bergolth <leo@strike.wu.ac.at>
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
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

class LDAP implements ILDAPWrapper {
	protected $curFunc = '';
	protected $curArgs = [];
	private array $pagedSearchControl;

	/**
	 * @param \LDAP\Connection $link
	 * @param string $dn
	 * @param string $password
	 * @return bool|mixed
	 */
	public function bind($link, $dn, $password) {
		return $this->invokeLDAPMethod('bind', $link, $dn, $password);
	}

	/**
	 * @param string $host
	 * @param string $port
	 * @return mixed
	 */
	public function connect($host, $port) {
		if (\strpos($host, '://') === false) {
			$host = 'ldap://' . $host;
		}
		if (\strpos($host, ':', \strpos($host, '://') + 1) === false) {
			//ldap_connect ignores port parameter when URLs are passed
			if ($port !== '') {
				$host .= ':' . $port;
			}
		}
		return $this->invokeLDAPMethod('connect', $host);
	}

	/**
	 * @param \LDAP\Connection $link
	 * @param \LDAP\Result $result
	 * @param string $cookie
	 * @param int $estimated $cookie
	 * @return bool
	 */
	public function controlPagedResultResponse($link, $result, &$cookie = null, &$estimated = null) {
		$ret = ldap_parse_result($link, $result, $errcode, $matcheddn, $errmsg, $referrals, $controls);
		if (!$ret) {
			throw new \Exception('ldap_parse_result failed');
		}
		$cookie = $controls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'] ?? '';
		$estimated = $controls[LDAP_CONTROL_PAGEDRESULTS]['value']['size'] ?? '';
		return true;
	}

	/**
	 * @param \LDAP\Connection $link
	 * @param int $pageSize
	 * @param bool $isCritical
	 * @param string $cookie
	 * @return mixed|true
	 */
	public function controlPagedResult($link, $pageSize, $isCritical, $cookie) {
		$this->pagedSearchControl = compact('pageSize', 'isCritical', 'cookie');
		return true;
	}

	/**
	 * @param \LDAP\Connection $link
	 * @param LDAP|resource $result
	 * @return mixed
	 */
	public function countEntries($link, $result) {
		return $this->invokeLDAPMethod('count_entries', $link, $result);
	}

	/**
	 * @param \LDAP\Connection $link
	 * @return mixed|string
	 */
	public function errno($link) {
		return $this->invokeLDAPMethod('errno', $link);
	}

	/**
	 * @param \LDAP\Connection $link
	 * @return int|mixed
	 */
	public function error($link) {
		return $this->invokeLDAPMethod('error', $link);
	}

	/**
	 * Escape a string for use in an LDAP filter or DN
	 * @param string $value The value to escape.
	 * @param string $ignore Characters to ignore when escaping.
	 * @param int $flags The context the escaped string will be used in:
	 *   LDAP_ESCAPE_FILTER for filters to be used with ldap_search(), or
	 *   LDAP_ESCAPE_DN for DNs.
	 * @return string Returns the escaped string.
	 * @link http://www.php.net/manual/en/function.ldap-escape.php
	 */
	public function escape($value, $ignore = null, $flags = null) {
		return $this->invokeLDAPMethod('escape', $value, $ignore, $flags);
	}

	/**
	 * Splits DN into its component parts
	 * @param string $dn
	 * @param int $withAttrib
	 * @return array|false
	 * @link http://www.php.net/manual/en/function.ldap-explode-dn.php
	 */
	public function explodeDN($dn, $withAttrib) {
		return $this->invokeLDAPMethod('explode_dn', $dn, $withAttrib);
	}

	/**
	 * @param \LDAP\Connection $link
	 * @param LDAP|resource $result
	 * @return mixed
	 */
	public function firstEntry($link, $result) {
		return $this->invokeLDAPMethod('first_entry', $link, $result);
	}

	/**
	 * @param \LDAP\Connection $link
	 * @param LDAP|resource $result
	 * @return array|mixed
	 */
	public function getAttributes($link, $result) {
		return $this->invokeLDAPMethod('get_attributes', $link, $result);
	}

	/**
	 * @param \LDAP\Connection $link
	 * @param LDAP|resource $result
	 * @return mixed|string
	 */
	public function getDN($link, $result) {
		return $this->invokeLDAPMethod('get_dn', $link, $result);
	}

	/**
	 * @param \LDAP\Connection $link
	 * @param LDAP|resource $result
	 * @return array|mixed
	 */
	public function getEntries($link, $result) {
		return $this->invokeLDAPMethod('get_entries', $link, $result);
	}

	/**
	 * @param \LDAP\Connection $link
	 * @param resource $result
	 * @return mixed
	 */
	public function nextEntry($link, $result) {
		return $this->invokeLDAPMethod('next_entry', $link, $result);
	}

	/**
	 * @param \LDAP\Connection $link
	 * @param string $baseDN
	 * @param string $filter
	 * @param array $attr
	 * @return mixed
	 */
	public function read($link, $baseDN, $filter, $attr) {
		return $this->invokeLDAPMethod('read', $link, $baseDN, $filter, $attr);
	}

	/**
	 * @param \LDAP\Connection $link
	 * @param string $baseDN
	 * @param string $filter
	 * @param array $attr
	 * @param int $attrsOnly
	 * @param int $limit
	 * @return mixed
	 */
	public function search($link, $baseDN, $filter, $attr, $attrsOnly = 0, $limit = 0) {
		if ($this->pagedSearchControl['pageSize'] > 0) {
			$control = [['oid' => LDAP_CONTROL_PAGEDRESULTS, 'value' => [
				'size' => $this->pagedSearchControl['pageSize'],
				'cookie' => $this->pagedSearchControl['cookie']]]];
		}

		return ldap_search($link, $baseDN, $filter, $attr, $attrsOnly, $limit, -1, 0, $control ?? []);
	}

	/**
	 * @param \LDAP\Connection $link
	 * @param string $option
	 * @param int $value
	 * @return bool|mixed
	 */
	public function setOption($link, $option, $value) {
		return $this->invokeLDAPMethod('set_option', $link, $option, $value);
	}

	/**
	 * @param \LDAP\Connection $link
	 * @return mixed|true
	 */
	public function startTls($link) {
		return $this->invokeLDAPMethod('start_tls', $link);
	}

	/**
	 * @param \LDAP\Connection $link
	 * @return bool|mixed
	 */
	public function unbind($link) {
		return $this->invokeLDAPMethod('unbind', $link);
	}

	/**
	 * Checks whether the server supports LDAP
	 * @return boolean
	 * */
	public function areLDAPFunctionsAvailable() {
		return \function_exists('ldap_connect');
	}

	/**
	 * Checks whether PHP supports LDAP Paged Results
	 * */
	public function hasPagedResultSupport(): bool {
		return true;
	}

	public function isResource($resource) {
		if ($resource instanceof \LDAP\Connection) {
			return true;
		}
		if ($resource instanceof \LDAP\Result) {
			return true;
		}
		if ($resource instanceof \LDAP\ResultEntry) {
			return true;
		}
		return \is_resource($resource);
	}

	private function formatLdapCallArguments($func, $arguments): string {
		$argumentsLog = \implode(
			",",
			\array_map(
				static function ($argument) {
					if (\is_string($argument) || \is_bool($argument) || \is_numeric($argument)) {
						return (string)$argument;
					}
					return \gettype($argument);
				},
				$arguments
			)
		);

		return $func."(".$argumentsLog.")";
	}

	/**
	 * @return mixed
	 * @throws ServerNotAvailableException
	 */
	private function invokeLDAPMethod() {
		$arguments = \func_get_args();
		$func = 'ldap_' . \array_shift($arguments);
		if (\function_exists($func)) {
			// Start logging event
			$eventId = \uniqid($func, true);
			\OC::$server->getEventLogger()->start($eventId, $this->formatLdapCallArguments($func, $arguments));

			// Execute call
			$this->preFunctionCall($func, $arguments);
			$result = \call_user_func_array($func, $arguments);
			if ($result === false) {
				$this->postFunctionCall();
			}

			// Finish logging event
			\OC::$server->getEventLogger()->end($eventId);
			return $result;
		}
		return null;
	}

	/**
	 * @param string $functionName
	 * @param array $args
	 */
	private function preFunctionCall($functionName, $args) {
		$this->curFunc = $functionName;
		$this->curArgs = $args;
	}

	private function postFunctionCall() {
		if ($this->isResource($this->curArgs[0])) {
			$errorCode = \ldap_errno($this->curArgs[0]);
			$errorMsg  = \ldap_error($this->curArgs[0]);
			if ($errorCode !== self::LDAP_SUCCESS) {
				if ($this->curFunc === 'ldap_bind') {
					$errDiag = "";
					\ldap_get_option($this->curArgs[0], LDAP_OPT_DIAGNOSTIC_MESSAGE, $errDiag);
					if ($errDiag === "") {
						$errDiag = 'no extended diagnostics';
					}
					$logMessage = "Bind failed: (), $errDiag, " . \var_export($this->curArgs[0], true);
					\OC::$server->getLogger()->debug($logMessage, ['app' => 'user_ldap']);
				} elseif ($this->curFunc === 'ldap_get_entries'
						  && $errorCode === -4) {
				} elseif ($errorCode === self::LDAP_NO_SUCH_OBJECT) {
					//for now
				} elseif ($errorCode === self::LDAP_REFERRAL) {
					//referrals, we switch them off, but then there is AD :)
				} elseif ($errorCode === -1) {
					throw new ServerNotAvailableException('Lost connection to LDAP server.');
				} elseif ($errorCode === self::LDAP_INAPPROPRIATE_AUTH) {
					throw new \Exception('LDAP authentication method rejected', $errorCode);
				} elseif ($errorCode === self::LDAP_OPERATIONS_ERROR) {
					throw new \Exception('LDAP Operations error', $errorCode);
				} else {
					\OC::$server->getLogger()->debug(
						"LDAP error $errorMsg ($errorCode) after calling $this->curFunc",
						[ 'app' => 'user_ldap']
					);
				}
			}
		}

		$this->curFunc = '';
		$this->curArgs = [];
	}
}
