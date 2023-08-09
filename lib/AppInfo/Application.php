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

use OCA\User_LDAP\Group_Proxy;
use OCA\User_LDAP\Helper;
use OCA\User_LDAP\LDAP;
use OCA\User_LDAP\User_Proxy;
use OCP\User\UserExtendedAttributesEvent;
use OCA\User_LDAP\User\Manager;

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
					$c->getServer()->getConfig()
				);
			}
		);
		$container->registerService(
			Group_Proxy::class,
			function ($c) {
				$helper = $c->query(Helper::class);
				return new Group_Proxy(
					$helper->getServerConfigurationPrefixes(true),
					new LDAP()
				);
			}
		);
	}

	public function checkCompatibility() {
		$server = $this->getContainer()->getServer();
		if ($server->getAppManager()->isEnabledForUser('user_webdavauth')) {
			$server->getLogger()->warning(
				'user_ldap and user_webdavauth are incompatible. You may experience unexpected behaviour',
				['app' => 'user_ldap']
			);
		}
	}

	public function registerBackends() {
		$helper = new Helper();
		$configPrefixes = $helper->getServerConfigurationPrefixes(true);
		if (\count($configPrefixes) > 0) {
			$server = $this->getContainer()->getServer();
			$ldapWrapper = new LDAP();
			$ocConfig = $server->getConfig();
			$userBackend  = new User_Proxy($configPrefixes, $ldapWrapper, $ocConfig);
			$groupBackend  = new Group_Proxy($configPrefixes, $ldapWrapper);
			// register user backend
			\OC_User::useBackend($userBackend);
			$server->getGroupManager()->addBackend($groupBackend);
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

	public function registerEventListener() {
		$container = $this->getContainer();
		$server = $container->getServer();

		$uProxy = $container->query(User_Proxy::class);
		$eventDispatcher = $server->getEventDispatcher();
		$eventDispatcher->addListener(UserExtendedAttributesEvent::USER_EXTENDED_ATTRIBUTES, function(UserExtendedAttributesEvent $event) use ($uProxy) {
			$targetUser = $event->getUser();
			if ($targetUser->getBackendClassName() !== 'LDAP') {
				// If the user doesn't come from LDAP, there is nothing to do here.
				return;
			}

			try {
				$attrs = $uProxy->getExposedAttributes($targetUser->getUID());

				$event->setAttributes('user_ldap_state', 'OK');
				foreach ($attrs as $key => $value) {
					$event->setAttributes("user_ldap_attr_{$key}", $value);
				}
			} catch (\Exception $e) {
				$event->setAttributes('user_ldap_state', 'Error');
			}
		});
	}
}
