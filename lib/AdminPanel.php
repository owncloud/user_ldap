<?php

namespace OCA\User_LDAP;

use OCP\IL10N;
use OCP\Settings\ISettings;
use OCP\Template;

class AdminPanel implements ISettings {

	/** @var Helper  */
	protected $helper;
	/** @var IL10N  */
	protected $l;

	public function __construct(Helper $helper, IL10N $l) {
		$this->helper = $helper;
		$this->l = $l;
	}

	public function getPriority() {
		return 20;
	}

	public function getPanel() {
		// fill template
		$tmpl = new Template('user_ldap', 'settings');

		$prefixes = $this->helper->getServerConfigurationPrefixes();
		$hosts = $this->helper->getServerConfigurationHosts();

		$wizardHtml = '';
		$toc = array();

		$wControls = new Template('user_ldap', 'part.wizardcontrols');
		$wControls = $wControls->fetchPage();
		$sControls = new Template('user_ldap', 'part.settingcontrols');
		$sControls = $sControls->fetchPage();


		$wizTabs = array();
		$wizTabs[] = array('tpl' => 'part.wizard-server',      'cap' => $this->l->t('Server'));
		$wizTabs[] = array('tpl' => 'part.wizard-userfilter',  'cap' => $this->l->t('Users'));
		$wizTabs[] = array('tpl' => 'part.wizard-loginfilter', 'cap' => $this->l->t('Login Attributes'));
		$wizTabs[] = array('tpl' => 'part.wizard-groupfilter', 'cap' => $this->l->t('Groups'));
		$wizTabsCount = count($wizTabs);
		for($i = 0; $i < $wizTabsCount; $i++) {
			$tab = new Template('user_ldap', $wizTabs[$i]['tpl']);
			if($i === 0) {
				$tab->assign('serverConfigurationPrefixes', $prefixes);
				$tab->assign('serverConfigurationHosts', $hosts);
			}
			$tab->assign('wizardControls', $wControls);
			$wizardHtml .= $tab->fetchPage();
			$toc['#ldapWizard'.($i+1)] = $wizTabs[$i]['cap'];
		}

		$tmpl->assign('tabs', $wizardHtml);
		$tmpl->assign('toc', $toc);
		$tmpl->assign('settingControls', $sControls);

		// assign default values
		$config = new \OCA\User_LDAP\Configuration('', false);
		$defaults = $config->getDefaults();
		foreach($defaults as $key => $default) {
			$tmpl->assign($key.'_default', $default);
		}

		return $tmpl;
	}

	public function getSectionID() {
		return 'authentication';
	}

}