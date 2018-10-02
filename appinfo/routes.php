<?php
/**
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH.
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

return [
	'routes' => [
		[
			'name' => 'configuration#listAll',
			'url' => 'configurations',
			'verb' => 'GET'
		],
		[
			'name' => 'configuration#create',
			'url' => 'configurations',
			'verb' => 'POST'
		],
		[
			'name' => 'configuration#read',
			'url' => 'configurations/{id}',
			'verb' => 'GET'
		],
		[
			'name' => 'configuration#test',
			'url' => 'configurations/{id}/test',
			'verb' => 'GET'
		],
		[
			'name' => 'configuration#write',
			'url' => 'configurations/{id}',
			'verb' => 'POST'
		],
		[
			'name' => 'configuration#delete',
			'url' => 'configurations/{id}',
			'verb' => 'DELETE'
		],
		[
			'name' => 'mapping#clear',
			'url' => 'ajax/clearMappings.php',
			'verb' => 'POST'
		],
		[
			'name' => 'wizard#cast',
			'url' => 'ajax/wizard.php',
			'verb' => 'POST'
		],
	]
];
