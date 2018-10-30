<?php
/**
 * Created by PhpStorm.
 * User: jfd
 * Date: 30.10.18
 * Time: 11:40
 */

namespace OCA\User_LDAP\Db;


class Server {
	private $active = false;
	private $host;
	private $port;

	private $bindDN;
	private $password;

	private $tls;
	private $turnOffCertCheck;

	private $supportsPaging = false; // hasPagedResultSupport
	private $pageSize;

	private $supportsMemberOf = false; // hasMemberOfFilterSupport

	private $overrideMainServer = false;

	private $backupHost;
	private $backupPort;

	private $cacheTTL;

	private $mappings;

}

/*

User Mapping

		'ldapBaseUsers' => null,
		'ldapUserDisplayName' => null,
		'ldapUserDisplayName2' => null,
		'ldapUserFilterObjectclass' => null,
		'ldapUserFilterGroups' => null,
		'ldapUserFilter' => null,
		'ldapUserFilterMode' => null,
		'ldapLoginFilter' => null,
		'ldapLoginFilterMode' => null,
		'ldapLoginFilterEmail' => null,
		'ldapLoginFilterUsername' => null,
		'ldapLoginFilterAttributes' => null,
		'ldapQuotaAttribute' => null,
		'ldapQuotaDefault' => null,
		'ldapEmailAttribute' => null,
		'ldapUuidUserAttribute' => 'auto',
		'ldapAttributesForUserSearch' => null,
		'homeFolderNamingRule' => null,
		'ldapExpertUUIDUserAttr' => null,
		'ldapExpertUsernameAttr' => null,
		'ldapIgnoreNamingRules' => null,
// TODO username

Group mapping

		'ldapBaseGroups' => null,
		'ldapGroupFilter' => null,
		'ldapGroupFilterMode' => null,
		'ldapGroupFilterObjectclass' => null,
		'ldapGroupFilterGroups' => null,
		'ldapGroupDisplayName' => null,
		'ldapGroupMemberAssocAttr' => null,
		'ldapUuidGroupAttribute' => 'auto',
		'ldapAttributesForGroupSearch' => null,
		'ldapExpertUUIDGroupAttr' => null,
		'ldapNestedGroups' => false,

		// TODO add hasPrimaryGroups as config, only detect it once



obsolete

		'ldapBase' => null, // only used in the wizard
		'ldapExperiencedAdmin' => false, // no longer read
		'lastJpegPhotoLookup' => null, // was used to remember when avatar jpeg was last updated

		// private $useMemberOfToDetectMembership = true; // used in tandem with supportsMemberOf

 */