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

namespace OCA\User_LDAP\User;

/**
 * IUserTools
 *
 * defines methods that are required by User class for LDAP interaction
 */
interface IUserTools {
	public function getConnection();

	public function readAttribute($dn, $attr, $filter = 'objectClass=*');

	public function dn2username($dn);

	/**
	 * returns the LDAP DN for the given internal ownCloud name of the user
	 * @param string $name the ownCloud name in question
	 * @return string|false with the LDAP DN on success, otherwise false
	 */
	public function username2dn($name);
}
