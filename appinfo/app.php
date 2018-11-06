<?php
/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Dominik Schmidt <dev@dominik-schmidt.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

$config = \OC::$server->getConfig();
$logger = \OC::$server->getLogger();

$mapper = new \OCA\User_LDAP\Config\ServerMapper(
	$config,
	$logger
);


if (\count($mapper->listAll()) > 0) {
	$ldapWrapper = new OCA\User_LDAP\LDAP();
	$db = \OC::$server->getDatabaseConnection();

	$backendManager = new \OCA\User_LDAP\Connection\BackendManager(
		$config,
		$logger,
		\OC::$server->getAvatarManager(),
		\OC::$server->getUserManager(),
		$db,
		$ldapWrapper,
		new \OCA\User_LDAP\Mapping\UserMapping($db),
		new \OCA\User_LDAP\Mapping\GroupMapping($db),
		new \OCA\User_LDAP\FilesystemHelper()
	);

	$userBackend  = new OCA\User_LDAP\User_Proxy($mapper, $backendManager, $config);
	$groupBackend  = new OCA\User_LDAP\Group_Proxy($mapper, $backendManager);

	// register user backend
	OC_User::useBackend($userBackend);
	\OC::$server->getGroupManager()->addBackend($groupBackend);
}

\OCP\Util::connectHook(
	'\OCA\Files_Sharing\API\Server2Server',
	'preLoginNameUsedAsUserName',
	'\OCA\User_LDAP\Helper',
	'loginName2UserName'
);

if (OCP\App::isEnabled('user_webdavauth')) {
	OCP\Util::writeLog('user_ldap',
		'user_ldap and user_webdavauth are incompatible. You may experience unexpected behaviour',
		OCP\Util::WARN);
}
