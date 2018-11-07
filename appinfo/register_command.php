<?php
/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
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

use OCA\User_LDAP\Helper;
use OCA\User_LDAP\LDAP;
use OCA\User_LDAP\User_Proxy;
use OCA\User_LDAP\Mapping\UserMapping;

$db = \OC::$server->getDatabaseConnection();
$userMapping = new UserMapping($db);
$helper = new Helper();
$config = \OC::$server->getConfig();
$logger = \OC::$server->getLogger();

$mapper = new \OCA\User_LDAP\Config\ServerMapper(
	$config,
	$logger
);

$backendManager = new \OCA\User_LDAP\Connection\BackendManager(
	$config,
	$logger,
	\OC::$server->getAvatarManager(),
	\OC::$server->getUserManager(),
	$db,
	new LDAP(),
	$userMapping,
	new \OCA\User_LDAP\Mapping\GroupMapping($db),
	new \OCA\User_LDAP\FilesystemHelper()
);
$uBackend = new User_Proxy(
	$mapper,
	$backendManager,
	$config
);

$application->add(new OCA\User_LDAP\Command\CheckUser(
	$uBackend, $helper, $userMapping)
);
