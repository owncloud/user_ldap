<?php
/**
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Copyright (c) 2019, ownCloud GmbH.
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

// FIXME: The command registration should be moved into info.xml once the app compatibility is bumped to OC 10.3
// see https://github.com/owncloud/user_ldap/issues/447 for details

use OCA\User_LDAP\Helper;
use OCA\User_LDAP\LDAP;
use OCA\User_LDAP\User_Proxy;
use OCA\User_LDAP\Mapping\UserMapping;

$dbConnection = \OC::$server->getDatabaseConnection();
$userMapping = new UserMapping($dbConnection);
$helper = new Helper();
$ocConfig = \OC::$server->getConfig();
$uBackend = new User_Proxy(
	$helper->getServerConfigurationPrefixes(true),
	new LDAP(),
	$ocConfig
);

$application->add(new OCA\User_LDAP\Command\CheckUser(
	$uBackend, $helper, $userMapping)
);
