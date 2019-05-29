<?php
/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Brice Maron <brice@bmaron.net>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
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

use OCA\User_LDAP\AppInfo\Application;
use OCA\User_LDAP\Config\ConfigMapper;

class Helper {
	/**
	 * FIXME use public AppConfig API
	 * returns prefixes for each saved LDAP/AD server configuration.
	 * @param bool $activeConfigurations optional, whether only active configuration shall be
	 * retrieved, defaults to false
	 * @return array with a list of the available prefixes
	 *
	 * Configuration prefixes are used to set up configurations for n LDAP or
	 * AD servers. Since configuration is stored in the database, table
	 * appconfig under appid user_ldap, the common identifiers in column
	 * 'configkey' have a prefix. The prefix for the very first server
	 * configuration is empty.
	 * Configkey Examples:
	 * Server 1: ldap_login_filter
	 * Server 2: s1_ldap_login_filter
	 * Server 3: s2_ldap_login_filter
	 *
	 * The prefix needs to be passed to the constructor of Connection class,
	 * except the default (first) server shall be connected to.
	 *
	 */
	public function getServerConfigurationPrefixes($activeConfigurations = false) {
		$serverConfigs = $this->getAllConfigurations();
		$prefixes = [];
		foreach ($serverConfigs as $serverConfig) {
			if ($activeConfigurations === true) {
				$c = \json_decode($serverConfig['configvalue'], true);
				if (\intval($c['ldapConfigurationActive']) !== 1) {
					continue;
				}
			}
			$prefixes[] = $this->stripPrefix($serverConfig['configkey']);
		}

		return $prefixes;
	}

	/**
	 *
	 * determines the host for every configured connection
	 * @return array an array with configprefix as keys
	 *
	 */
	public function getServerConfigurationHosts() {
		$serverConfigs = $this->getAllConfigurations();
		$result = [];
		foreach ($serverConfigs as $serverConfig) {
			$prefix = $this->stripPrefix($serverConfig['configkey']);
			$c = \json_decode($serverConfig['configvalue'], true);
			$result[$prefix] = $c['ldapHost'];
		}

		return $result;
	}

	/**
	 * checks whether there is one or more disabled LDAP configurations
	 * @return bool
	 */
	public function haveDisabledConfigurations() {
		$all = $this->getAllConfigurations();
		foreach ($all as $serverConfig) {
			$c = \json_decode($serverConfig['configvalue'], true);
			if (\intval($c['ldapConfigurationActive']) !== 1) {
				return true;
			}
		}
		return \count($all) === 0;
	}

	/**
	 * extracts the domain from a given URL
	 * @param string $url the URL
	 * @return string|false domain as string on success, false otherwise
	 */
	public function getDomainFromURL($url) {
		$uinfo = \parse_url($url);
		if (!\is_array($uinfo)) {
			return false;
		}

		$domain = false;
		if (isset($uinfo['host'])) {
			$domain = $uinfo['host'];
		} elseif (isset($uinfo['path'])) {
			$domain = $uinfo['path'];
		}

		return $domain;
	}

	/**
	 * listens to a hook thrown by server2server sharing and replaces the given
	 * login name by a username, if it matches an LDAP user.
	 *
	 * @param array $param
	 * @throws \Exception
	 */
	public function loginName2UserName($param) {
		if (!isset($param['uid'])) {
			throw new \Exception('key uid is expected to be set in $param');
		}

		$configPrefixes = $this->getServerConfigurationPrefixes(true);
		$ldapWrapper = new LDAP();
		$app = new Application();

		$userBackend  = new User_Proxy(
			$configPrefixes,
			$ldapWrapper,
			$app->getContainer()->query(ConfigMapper::class),
			\OC::$server->getConfig()
		);
		$uid = $userBackend->loginName2UserName($param['uid']);
		if ($uid !== false) {
			$param['uid'] = $uid;
		}
	}

	/**
	 * from https://tools.ietf.org/html/rfc4514#section-2.4
	 *
	 * If that UTF-8-encoded Unicode
	 * string does not have any of the following characters that need
	 * escaping, then that string can be used as the string representation
	 * of the value.
	 *
	 * - a space (' ' U+0020) or number sign ('#' U+0023) occurring at
	 * the beginning of the string;
	 *
	 * - a space (' ' U+0020) character occurring at the end of the
	 * string;
	 *
	 * - one of the characters '"', '+', ',', ';', '<', '>',  or '\'
	 * (U+0022, U+002B, U+002C, U+003B, U+003C, U+003E, or U+005C,
	 * respectively);
	 *
	 * - the null (U+0000) character.
	 *
	 * Other characters may be escaped.
	 *
	 * Each octet of the character to be escaped is replaced by a backslash
	 * and two hex digits, which form a single octet in the code of the
	 * character.  Alternatively, if and only if the character to be escaped
	 * is one of
	 *
	 * ' ', '"', '#', '+', ',', ';', '<', '=', '>', or '\'
	 * (U+0020, U+0022, U+0023, U+002B, U+002C, U+003B,
	 * U+003C, U+003D, U+003E, U+005C, respectively)
	 *
	 * it can be prefixed by a backslash ('\' U+005C).
	 * normalizes a DN received from the LDAP server
	 *
	 * @param string $dn the DN in question
	 * @return string the normalized DN
	 */
	public static function normalizeDN($dn) {
		// 1. lowercase to make comparisons and everything work
		// TODO RDNs may be defined as caseExactMatch
		$dn = \mb_strtolower($dn, 'UTF-8');

		// 2. escape special dn chars of RFC4514
		$replacements = [
			'\22' => '\"',
			'\23' => '\#',
			'\2b' => '\+',
			'\2c' => '\,',
			'\3b' => '\;',
			'\3c' => '\<',
			'\3d' => '\=',
			'\3e' => '\>',
			'\5c' => '\\\\',
		];
		$dn = \str_replace(\array_keys($replacements), \array_values($replacements), $dn);

		// 3. translate hex code into ascii again
		// TODO ldap_dn2str / str2dn http://php.net/manual/de/function.ldap-explode-dn.php#34724
		// /e not supported http://php.net/manual/de/function.ldap-explode-dn.php#121219
		// seems to be what we are looking for to unescape ESC HEX HEX notation of unicode
		// but it is not available in php
		$dn = \preg_replace_callback(
			'/\\\([0-9A-Fa-f]{2})/',
			function ($matches) {
				return \chr(\hexdec($matches[1]));
			},
			$dn
		);

		// 4. throw out whitespace after commas
		// "uid=foo, cn=bar, dn=..." -> "uid=foo,cn=bar,dn=..."
		$dn = \preg_replace('/([^\\\]),(\s+)/u', '\1,', $dn);

		return $dn;
	}

	/**
	 * @deprecated use normalizeDN, this method is only here to generate the
	 * same dn used before normalizeDN was used.
	 *
	 * @param string $dn
	 * @return string
	 *
	 * @since 0.12.0
	 */
	public static function legacySanitizeDN($dn) {
		//OID sometimes gives back DNs with whitespace after the comma
		// a la "uid=foo, cn=bar, dn=..." We need to tackle this!
		$dn = \preg_replace('/([^\\\]),(\s+)/u', '\1,', $dn);

		//make comparisons and everything work
		$dn = \mb_strtolower($dn, 'UTF-8');

		//escape DN values according to RFC 2253 – this is already done by ldap_explode_dn
		//to use the DN in search filters, \ needs to be escaped to \5c additionally
		//to use them in bases, we convert them back to simple backslashes in readAttribute()
		$replacements = [
			'\,' => '\5c2C',
			'\=' => '\5c3D',
			'\+' => '\5c2B',
			'\<' => '\5c3C',
			'\>' => '\5c3E',
			'\;' => '\5c3B',
			'\"' => '\5c22',
			'\#' => '\5c23',
			'('  => '\28',
			')'  => '\29',
			'*'  => '\2A',
		];
		$dn = \str_replace(\array_keys($replacements), \array_values($replacements), $dn);

		return $dn;
	}

	protected function getAllConfigurations() {
		$qb = \OC::$server->getDatabaseConnection()->getQueryBuilder();
		$qb->select('configkey', 'configvalue')
			->from('appconfig')
			->where(
				$qb->expr()->eq('appid', $qb->expr()->literal('user_ldap'))
			)
			->andWhere(
				$qb->expr()->like('configkey', $qb->expr()->literal(ConfigMapper::PREFIX . '%'))
			);

		$result = $qb->execute();
		return $result->fetchAll();
	}

	protected function stripPrefix($configurationId) {
		return \implode(
			'',
			\explode(ConfigMapper::PREFIX, $configurationId, 2)
		);
	}
}
