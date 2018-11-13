<?php
/**
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
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

namespace OCA\User_LDAP\Config;


use function Sabre\Event\Loop\instance;

class LegacyWrapper
{
    /** @var Server */
    private $server;

    public function __construct(Server $server) {
        $this->server = $server;
    }

    public function __set($name, $value)
    {
        switch ($name) {
            // server config
            case 'ldap_host'                         : $this->server->setHost($value); break;
            case 'ldap_port'                         : $this->server->setPort($value); break;
            case 'ldap_backup_host'                  : $this->server->setBackupHost($value); break;
            case 'ldap_backup_port'                  : $this->server->setBackupPort($value); break;
            case 'ldap_override_main_server'         : $this->server->setOverrideMainServer($value); break;
            case 'ldap_dn'                           : $this->server->setBindDN($value); break;
            case 'ldap_agent_password'               : $this->server->setPassword($value); break;
            case 'ldap_cache_ttl'                    : $this->server->setCacheTTL($value); break;
            case 'ldap_tls'                          : $this->server->setTls($value); break;
            case 'ldap_turn_off_cert_check'          : $this->server->setTurnOffCertCheck($value); break;
            case 'ldap_configuration_active'         : $this->server->setActive($value); break;
            // memberof is handled by a single flag
            case 'has_memberof_filter_support'       : $this->server->setSupportsMemberOf($value); break;
            case 'use_memberof_to_detect_membership' : $this->server->setSupportsMemberOf($value); break;
            case 'ldap_paging_size'                  : $this->server->setPageSize($value); break;
            // supports paging?

            // user tree
            case 'ldap_base_users'                   :
                $tree = $this->getUserTrees()[0];
                $this->server->setUserTrees([]);
                // copy first tree config to all base dns
                foreach ($this->getMultiValues($value) as $base) {
                    $newTree = clone $tree;
                    $newTree->setBaseDN($base);
                    $this->server->setUserTree($base, $newTree);
                }
                break;
            case 'ldap_userlist_filter'              : foreach ($this->getUserTrees() as $ut) { $ut->setFilter($value); } break;
            case 'ldap_user_filter_mode'             : foreach ($this->getUserTrees() as $ut) { $ut->setFilterMode($value); } break;
            case 'ldap_userfilter_objectclass'       : foreach ($this->getUserTrees() as $ut) { $ut->setFilterObjectclass($this->getMultiValues($value)); } break;
            case 'ldap_userfilter_groups'            : foreach ($this->getUserTrees() as $ut) { $ut->setFilterGroups($this->getMultiValues($value)); } break;
            case 'ldap_display_name'                 : foreach ($this->getUserTrees() as $ut) { $ut->setDisplayNameAttribute($value); } break;
            case 'ldap_attributes_for_user_search'   : foreach ($this->getUserTrees() as $ut) { $ut->setAdditionalSearchAttributes($this->getMultiValues($value)); } break;
            case 'ldap_expert_uuid_user_attr'        : foreach ($this->getUserTrees() as $ut) { $ut->setUuidAttribute($value); } break;

            case 'ldap_login_filter'                 : foreach ($this->getUserTrees() as $ut) { $ut->setLoginFilter($value); } break;
            case 'ldap_login_filter_mode'            : foreach ($this->getUserTrees() as $ut) { $ut->setLoginFilterMode($value); } break;
            case 'ldap_loginfilter_email'            : foreach ($this->getUserTrees() as $ut) { $ut->setLoginFilterEmail($value); } break;
            case 'ldap_loginfilter_username'         : foreach ($this->getUserTrees() as $ut) { $ut->setLoginFilterUsername($value); } break;
            case 'ldap_loginfilter_attributes'       : foreach ($this->getUserTrees() as $ut) { $ut->setLoginFilterAttributes($this->getMultiValues($value)); }break;
            case 'ldap_user_display_name_2'          : foreach ($this->getUserTrees() as $ut) { $ut->setDisplayName2Attribute($value); } break;
            case 'ldap_quota_def'                    : foreach ($this->getUserTrees() as $ut) { $ut->setQuotaDefault($value); } break;
            case 'ldap_quota_attr'                   : foreach ($this->getUserTrees() as $ut) { $ut->setQuotaAttribute($value); } break;
            case 'ldap_email_attr'                   : foreach ($this->getUserTrees() as $ut) { $ut->setEmailAttribute($value); } break;
            case 'home_folder_naming_rule'           : foreach ($this->getUserTrees() as $ut) { $ut->setHomeFolderNamingRule($value); } break;
            case 'ldap_expert_username_attr'         : foreach ($this->getUserTrees() as $ut) { $ut->setExpertUsernameAttr($value); } break;

            //group tree
            case 'ldap_base_groups'                  :
                $tree = $this->getGroupTrees()[0];
                $this->server->setGroupTrees([]);
                // copy first tree config to all base dns
                foreach ($this->getMultiValues($value) as $base) {
                    $newTree = clone $tree;
                    $newTree->setBaseDN($base);
                    $this->server->setGroupTree($base, $newTree);
                }
                break;
            case 'ldap_group_filter'                 : foreach ($this->getGroupTrees() as $ut) { $ut->setFilter($value); } break;
            case 'ldap_group_filter_mode'            : foreach ($this->getGroupTrees() as $ut) { $ut->setFilterMode($value); } break;
            case 'ldap_groupfilter_objectclass'      : foreach ($this->getGroupTrees() as $ut) { $ut->setFilterObjectclass($this->getMultiValues($value)); } break;
            case 'ldap_groupfilter_groups'           : foreach ($this->getGroupTrees() as $ut) { $ut->setFilterGroups($this->getMultiValues($value)); } break;
            case 'ldap_group_display_name'           : foreach ($this->getGroupTrees() as $ut) { $ut->setDisplayNameAttribute($value); } break;
            case 'ldap_attributes_for_group_search'  : foreach ($this->getGroupTrees() as $ut) { $ut->setAdditionalSearchAttributes($this->getMultiValues($value)); } break;
            case 'ldap_expert_uuid_group_attr'       : foreach ($this->getGroupTrees() as $ut) { $ut->setUuidAttribute($value); } break;

            case 'ldap_group_member_assoc_attribute' : foreach ($this->getGroupTrees() as $ut) { $ut->setMemberAttribute($value); } break;
            case 'ldap_nested_groups'                : foreach ($this->getGroupTrees() as $ut) { $ut->setNestedGroups($value); } break;
            case 'ldap_dynamic_group_member_url'     : foreach ($this->getGroupTrees() as $ut) { $ut->setDynamicGroupMemberURL($value); } break;

            //case 'ldap_base'                         : break;
            //case 'last_jpegPhoto_lookup'             : break;
            //case 'ldap_experienced_admin'            : break;
        }
    }

    private function getUserTrees() {
        $trees = $this->server->getUserTrees();
        if (empty($trees)) {
            // create tree on the fly
            $this->server->setUserTree('', new UserTree([]));
        }
        return $this->server->getUserTrees();
    }

    private function getGroupTrees() {
        $trees = $this->server->getGroupTrees();
        if (empty($trees)) {
            // create tree on the fly
            $this->server->setGroupTree('', new GroupTree([]));
        }
        return $this->server->getGroupTrees();
    }

    private function getMultiValues($value) {
        return \preg_split('/\r\n|\r|\n|;/', $value);
    }

}