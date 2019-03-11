<?php
/**
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 *
 * @copyright Copyright (c) 2019, ownCloud GmbH
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

namespace OCA\User_LDAP\AppInfo;

use OCA\User_LDAP\Config\ConfigMapper;
use OCA\User_LDAP\Config\ServerMapper;
use OCA\User_LDAP\Connection\BackendManager;
use OCA\User_LDAP\FilesystemHelper;
use OCA\User_LDAP\Group_Proxy;
use OCA\User_LDAP\Helper;
use OCA\User_LDAP\ILDAPWrapper;
use OCA\User_LDAP\LDAP;
use OCA\User_LDAP\Mapping\GroupMapping;
use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\User_Proxy;
use OCP\AppFramework\IAppContainer;

class Application extends \OCP\AppFramework\App {
	/**
	 * @param array $urlParams
	 */
	public function __construct($urlParams = []) {
		parent::__construct('user_ldap', $urlParams);
		$this->registerService();
	}

	private function registerService() {
		$container = $this->getContainer();
		$container->registerService(
			User_Proxy::class,
			function ($c) {
				$helper = $c->query(Helper::class);
				return new User_Proxy(
					$helper->getServerConfigurationPrefixes(true),
					new LDAP(),
					$c->query(ConfigMapper::class),
					$c->getServer()->getConfig()
				);
			}
		);
		$server = $container->getServer();

		$container->registerService(ILDAPWrapper::class, function (IAppContainer $c) {
			return new LDAP();
		});
		$container->registerService(BackendManager::class, function (IAppContainer $c) use ($server) {
			return new BackendManager(
				$server->getConfig(),
				$server->getLogger(),
				$server->getMemCacheFactory(),
				$server->getAvatarManager(),
				$server->getUserManager(),
				$server->getDatabaseConnection(),
				$c->query(ILDAPWrapper::class),
				$c->query(UserMapping::class),
				$c->query(GroupMapping::class),
				$c->query(FilesystemHelper::class)
			);
		});
	}

	public function checkCompatibility() {
		$server = $this->getContainer()->getServer();
		if ($server->getAppManager()->isEnabledForUser('user_webdavauth')) {
			$server->getLogger()->warning(
				'user_ldap and user_webdavauth are incompatible. You may experience unexpected behaviour',
				['app' => 'user_ldap']);
		}
	}

	public function registerBackends() {
		$container = $this->getContainer();
		$server =$container->getServer();
		$config = $server->getConfig();

		$mapper = new ServerMapper($config, $server->getLogger());
		if (\count($mapper->listAll()) > 0) {
			$backendManager = $container->query(BackendManager::class);

			// FIXME cleanup core to make registering user backends work with public apis. Currently OC_User::findFirstActiveUsedBackend is called during login ... urgh
			\OC_User::useBackend(
				new User_Proxy($mapper, $backendManager, $config)
			);

			$server->getGroupManager()->addBackend(
				new Group_Proxy($mapper, $backendManager)
			);
		}
	}

	public function registerHooks() {
		\OCP\Util::connectHook(
			'\OCA\Files_Sharing\API\Server2Server',
			'preLoginNameUsedAsUserName',
			$this->getContainer()->query(Helper::class),
			'loginName2UserName'
		);
	}
}
