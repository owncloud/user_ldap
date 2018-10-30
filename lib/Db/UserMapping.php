<?php
/**
 * Created by PhpStorm.
 * User: jfd
 * Date: 30.10.18
 * Time: 12:59
 */

namespace OCA\User_LDAP\Db;


class UserMapping {

	private $baseDN;
    private $displayNameAttribute;
    private $displayName2Attribute;
    private $filterObjectclass;
    private $filterGroups;
    private $filter;
    private $filterMode;
    private $loginFilter;
    private $loginFilterMode;
    private $loginFilterEmail;
    private $loginFilterUsername;
    private $loginFilterAttributes;
    private $quotaAttribute;
    private $quotaDefault;
    private $emailAttribute;
    private $uuidAttribute = 'auto';
    private $attributesForUserSearch;
    private $homeFolderNamingRule;
    private $expertUUIDUserAttr;
    private $expertUsernameAttr;
    private $ignoreNamingRules = false;
// TODO username
}