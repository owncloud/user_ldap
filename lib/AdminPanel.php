<?php

namespace OCA\User_LDAP;

use OCA\User_LDAP\Config\Config;
use OCP\IConfig;
use OCP\IL10N;
use OCP\Settings\ISettings;
use OCP\Template;

class AdminPanel implements ISettings {

	/** @var IConfig */
	protected $config;

	/** @var Helper */
	protected $helper;

	/** @var IL10N */
	protected $l;

	public function __construct(IConfig $config, Helper $helper, IL10N $l) {
		$this->config = $config;
		$this->helper = $helper;
		$this->l = $l;
	}

	public function getPriority() {
		return 20;
	}

	public function getPanel() {
		// fill template
		$tmpl = new Template('user_ldap', 'settings');
		return $tmpl;
	}

	public function getSectionID() {
		return 'authentication';
	}
}
