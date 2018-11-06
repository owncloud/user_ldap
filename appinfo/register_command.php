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

$dbConnection = \OC::$server->getDatabaseConnection();
$userMapping = new UserMapping($dbConnection);
$helper = new Helper();
$ocConfig = \OC::$server->getConfig();

$mapper = new \OCA\User_LDAP\Config\ServerMapper(
	$ocConfig,
	\OC::$server->getLogger()
);

$servers = $mapper->listAll(); // TODO filter only active?

$uBackend = new User_Proxy(
	$mapper->listAll(),
	new LDAP(),
	$ocConfig
);

$application->add(new OCA\User_LDAP\Command\CheckUser(
	$uBackend, $helper, $userMapping)
);
