<?php
/**
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
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

namespace OCA\User_LDAP\Tests\User;

use OCA\User_LDAP\Connection;
use OCA\User_LDAP\User\UserEntry;
use OCA\User_LDAP\Attributes\ConverterException;
use OCP\IConfig;
use OCP\ILogger;

class UserEntryTest extends \Test\TestCase {
	/**
	 * @var IConfig|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected $config;
	/**
	 * @var ILogger|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected $logger;
	/**
	 * @var Connection|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected $connection;

	protected function setUp(): void {
		parent::setUp();
		$this->config     = $this->createMock(IConfig::class);
		$this->logger     = $this->createMock(ILogger::class);
		$this->connection = $this->createMock(Connection::class);
	}

	/**
	 */
	public function testInvalidNew() {
		$this->expectException(\InvalidArgumentException::class);

		new UserEntry($this->config, $this->logger, $this->connection, []);
	}

	public function testGetDN() {
		$userEntry = new UserEntry(
			$this->config,
			$this->logger,
			$this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar']
			]
		);
		self::assertEquals('cn=foo,dc=foobar,dc=bar', $userEntry->getDN());
	}

	public function testGetUserName() {
		$this->connection->expects($this->once())
			->method('__get')
			->with($this->equalTo('ldapUserName'))
			->will($this->returnValue('uid'));
		$userEntry = new UserEntry(
			$this->config,
			$this->logger,
			$this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'uid' => [0 => 'foo']
			]
		);
		self::assertEquals('foo', $userEntry->getUserName());
	}

	public function testGetUserIdIsConfigured() {
		$this->connection->expects($this->once())
			->method('__get')
			->with($this->equalTo('ldapExpertUsernameAttr'))
			->will($this->returnValue('mail'));
		$userEntry = new UserEntry(
			$this->config,
			$this->logger,
			$this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'mail' => [0 => 'a@b.c']
			]
		);
		self::assertEquals('a@b.c', $userEntry->getUserId());
	}

	/**
	 * @dataProvider uuidDataProvider
	 * @param $uuidAttr string
	 * @param $uuidValue string
	 * @param $expected string
	 */
	public function testGetUserIdFallbackOnUUID($uuidAttr, $uuidValue, $expected) {
		$this->connection->expects($this->exactly(3))
			->method('__get')
			->withConsecutive(
				[$this->equalTo('ldapExpertUsernameAttr')],
				[$this->equalTo('ldapExpertUUIDUserAttr')],
				[$this->equalTo('ldapExpertUUIDUserAttr')]
			)

			->willReturnOnConsecutiveCalls(
				null,
				$uuidAttr,
				$uuidAttr
			);
		$userEntry = new UserEntry(
			$this->config,
			$this->logger,
			$this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				$uuidAttr => [0 => $uuidValue]
			]
		);
		self::assertEquals($expected, $userEntry->getUserId());
	}

	/**
	 */
	public function testGetUserIdUndetermined() {
		$this->expectException(\OutOfBoundsException::class);

		$this->connection->expects($this->exactly(1))
			->method('__get')
			->with($this->equalTo('ldapExpertUsernameAttr'))
			->will($this->returnValue('mail'));
		$userEntry = new UserEntry(
			$this->config,
			$this->logger,
			$this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar']
			]
		);
		$userEntry->getUserId();
	}

	public function uuidDataProvider() {
		return [
			// openldap
			['entryuuid', '563418fc-423b-1033-8d1c-ad5f418ee02e', '563418fc-423b-1033-8d1c-ad5f418ee02e'],
			// redhad FreeIPA
			['ipauniqueid', '9ca8bb70-bc3a-11df-9a4d-000c29a5c12c', '9ca8bb70-bc3a-11df-9a4d-000c29a5c12c'],
			// Microsoft AD
			['objectguid', "\x53\xdf\x4e\x49\x3e\xb3\xd1\x4e\x80\x0b\x53\xdf\x4e\x49\x3e\xb3", '494EDF53-B33E-4ED1-800B-53DF4E493EB3'],
			// binary UUID may end with a \r readline char, don't trim it away
			['objectguid', "\x13\x5C\x27\xB5\x66\x64\xFD\x43\xA1\x29\xA1\x2A\x6D\x3D\x9A\x0D", 'B5275C13-6466-43FD-A129-A12A6D3D9A0D'],
			// Novell eDirectory
			['guid', "\x81\xC9\x53\x4C\xBA\x5D\xD9\x11\x89\xA2\x89\x0B\x9B\xD4\x8A\x51", '4C53C981-5DBA-11D9-89A2-890B9BD48A51'],
			// 389 Directory Server / Oracle Directory Server
			['nsuniqueid', '66446001-1dd211b2-66225011-2ee211db', '66446001-1dd211b2-66225011-2ee211db'],
		];
	}

	/**
	 * @dataProvider uuidDataProvider
	 * @param $uuidAttr string
	 * @param $uuidValue string
	 * @param $expected string
	 */
	public function testGetUUIDIsConfigured($uuidAttr, $uuidValue, $expected) {
		$this->connection->expects($this->exactly(2))
			->method('__get')
			->with($this->equalTo('ldapExpertUUIDUserAttr'))
			->will($this->returnValue($uuidAttr));
		$userEntry = new UserEntry(
			$this->config,
			$this->logger,
			$this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				$uuidAttr => [0 => $uuidValue]
			]
		);
		self::assertEquals($expected, $userEntry->getUUID());
	}

	/**
	 * @dataProvider uuidDataProvider
	 * @param $uuidAttr string
	 * @param $uuidValue string
	 * @param $expected string
	 */
	public function testGetUUIDIsAuto($uuidAttr, $uuidValue, $expected) {
		$this->connection->expects($this->exactly(2))
			->method('__get')
			->withConsecutive(
				[$this->equalTo('ldapExpertUUIDUserAttr')],
				[$this->equalTo('ldapExpertUUIDUserAttr')]
			)
			->willReturnOnConsecutiveCalls(
				'auto',
				'auto'
			);
		$userEntry = new UserEntry(
			$this->config,
			$this->logger,
			$this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				$uuidAttr => [0 => $uuidValue]
			]
		);
		self::assertEquals($expected, $userEntry->getUUID());
	}

	/**
	 */
	public function testGetUUIDUndetermined() {
		$this->expectException(\OutOfBoundsException::class);

		$this->connection->expects($this->once())
			->method('__get')
			->with($this->equalTo('ldapExpertUUIDUserAttr'))
			->will($this->returnValue('auto'));
		$userEntry = new UserEntry(
			$this->config,
			$this->logger,
			$this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
			]
		);
		$userEntry->getUUID();
	}

	/**
	 */
	public function testGetUUIDInvalidBinaryUUID() {
		$this->expectException(ConverterException::class);

		$this->connection->expects($this->exactly(1))
			->method('__get')
			->with($this->equalTo('ldapExpertUUIDUserAttr'))
			->will($this->returnValue('objectguid'));
		$userEntry = new UserEntry(
			$this->config,
			$this->logger,
			$this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'objectguid'  => [0 => "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"],
			]
		);
		$userEntry->getUUID();
	}

	public function testGetDisplayName() {
		$this->connection->expects($this->exactly(2))
			->method('__get')
			->withConsecutive(
				[$this->equalTo('ldapUserDisplayName')],
				[$this->equalTo('ldapUserDisplayName2')]
			)
			->willReturnOnConsecutiveCalls(
				'displayname',
				''
			);
		$userEntry = new UserEntry(
			$this->config,
			$this->logger,
			$this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'displayname' => [0 => 'Foo'],
			]
		);
		self::assertEquals('Foo', $userEntry->getDisplayName());
	}

	public function testGetDisplayNameWithSecondDisplayName() {
		$this->connection->expects($this->exactly(2))
			->method('__get')
			->withConsecutive(
				[$this->equalTo('ldapUserDisplayName')],
				[$this->equalTo('ldapUserDisplayName2')]
			)
			->willReturnOnConsecutiveCalls(
				'displayname',
				'mail'
			);
		$userEntry = new UserEntry(
			$this->config,
			$this->logger,
			$this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'displayname' => [0 => 'Foo'],
				'mail' => [0 => 'foo@foobar.bar']
			]
		);
		self::assertEquals('Foo (foo@foobar.bar)', $userEntry->getDisplayName());
	}

	public function testGetDisplayNameFallback() {
		$this->connection->expects($this->exactly(3))
			->method('__get')
			->withConsecutive(
				[$this->equalTo('ldapUserDisplayName')],
				[$this->equalTo('ldapUserDisplayName2')],
				[$this->equalTo('ldapExpertUsernameAttr')]
			)
			->willReturnOnConsecutiveCalls(
				'displayname',
				'mail',
				'uid'
			);
		$userEntry = new UserEntry(
			$this->config,
			$this->logger,
			$this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'uid' => [0 => 'foo'],
			]
		);
		self::assertEquals('foo', $userEntry->getDisplayName());
	}

	public function testGetQuota() {
		$this->connection->expects($this->once())
			->method('__get')
			->with($this->equalTo('ldapQuotaAttribute'))
			->will($this->returnValue('quota'));
		$userEntry = new UserEntry(
			$this->config,
			$this->logger,
			$this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'quota' => [0 => '5 GB']
			]
		);
		self::assertEquals('5 GB', $userEntry->getQuota());
	}

	public function testGetQuotaInvalid() {
		$this->connection->expects($this->exactly(3))
			->method('__get')
			->withConsecutive(
				[$this->equalTo('ldapQuotaAttribute')],
				[$this->equalTo('ldapQuotaDefault')],
				[$this->equalTo('ldapQuotaDefault')]
			)
			->willReturnOnConsecutiveCalls(
				'invalid',
				'1 GB',
				'1 GB'
			);
		$userEntry = new UserEntry(
			$this->config,
			$this->logger,
			$this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'quota' => [0 => 'invalid']
			]
		);
		self::assertEquals('1 GB', $userEntry->getQuota());
	}

	public function testGetQuotaDefault() {
		$this->connection->expects($this->exactly(3))
			->method('__get')
			->withConsecutive(
				[$this->equalTo('ldapQuotaAttribute')],
				[$this->equalTo('ldapQuotaDefault')],
				[$this->equalTo('ldapQuotaDefault')]
			)
			->willReturnOnConsecutiveCalls(
				null,
				'2 GB',
				'2 GB'
			);
		$userEntry = new UserEntry(
			$this->config,
			$this->logger,
			$this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
			]
		);
		self::assertEquals('2 GB', $userEntry->getQuota());
	}

	public function testGetQuotaDefaultInvalid() {
		$this->connection->expects($this->exactly(3))
			->method('__get')
			->withConsecutive(
				[$this->equalTo('ldapQuotaAttribute')],
				[$this->equalTo('ldapQuotaDefault')],
				[$this->equalTo('ldapQuotaDefault')]
			)
			->willReturnOnConsecutiveCalls(
				'invalid',
				'invalid',
				'invalid'
			);
		$userEntry = new UserEntry(
			$this->config,
			$this->logger,
			$this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'quota' => [0 => 'invalid']
			]
		);
		self::assertEquals(null, $userEntry->getQuota());
	}

	public function testGetQuotaDefaultFallback() {
		$this->connection->expects($this->exactly(2))
			->method('__get')
			->withConsecutive(
				[$this->equalTo('ldapQuotaAttribute')],
				[$this->equalTo('ldapQuotaDefault')]
			)
			->willReturnOnConsecutiveCalls(
				null,
				null
			);
		$userEntry = new UserEntry(
			$this->config,
			$this->logger,
			$this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
			]
		);
		self::assertEquals(null, $userEntry->getQuota());
	}

	public function testGetEmailAddress() {
		$this->connection->expects($this->once())
			->method('__get')
			->with($this->equalTo('ldapEmailAttribute'))
			->will($this->returnValue('mail'));
		$userEntry = new UserEntry(
			$this->config,
			$this->logger,
			$this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'mail' => [0 => 'a@b.c']
			]
		);
		self::assertEquals('a@b.c', $userEntry->getEMailAddress());
	}

	public function testGetEmailAddressUnset() {
		$this->connection->expects($this->once())
			->method('__get')
			->with($this->equalTo('ldapEmailAttribute'))
			->will($this->returnValue('mail'));
		$userEntry = new UserEntry(
			$this->config,
			$this->logger,
			$this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar']
			]
		);
		self::assertNull($userEntry->getEMailAddress());
	}

	public function testGetHomeAttributeWithAbsolutePath() {
		$this->config->expects($this->any())
			->method('getSystemValue')
			->willReturnCallback(function ($key, $default = '') {
				if ($key === 'datadirectory') {
					return '/absolute/path';
				}
				return $default;
			});
		$this->connection->expects($this->once())
			->method('__get')
			->with($this->equalTo('homeFolderNamingRule'))
			->will($this->returnValue('attr:home'));
		$userEntry = new UserEntry(
			$this->config,
			$this->logger,
			$this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'home' => [0 => '/absolute/path/to/home']
			]
		);
		self::assertEquals('/absolute/path/to/home', $userEntry->getHome());
	}

	/**
	 * The home attribute is controlled by the LDAP directory, not by the
	 * ownCloud admin. An absolute path outside of the data directory must be
	 * refused: pointing it at the ownCloud code root would expose - and allow
	 * overwriting - the application's own PHP files, which is remote code
	 * execution.
	 *
	 * @dataProvider providesHomeOutsideDataDir
	 */
	public function testGetHomeOutsideDataDirIsRefused($home) {
		$this->expectException(\OutOfBoundsException::class);

		$this->config->expects($this->any())
			->method('getSystemValue')
			->willReturnCallback(function ($key, $default = '') {
				if ($key === 'datadirectory') {
					return '/var/www/owncloud/data';
				}
				if ($key === 'user_ldap.home_base_dirs') {
					return [];
				}
				return $default;
			});
		// the error path resolves the uid for the log message, which reads
		// further config options
		$this->connection->expects($this->any())
			->method('__get')
			->willReturnCallback(function ($key) {
				return $key === 'homeFolderNamingRule' ? 'attr:home' : 'mail';
			});
		$userEntry = new UserEntry(
			$this->config,
			$this->logger,
			$this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'mail' => [0 => 'a@b.c'],
				'home' => [0 => $home]
			]
		);
		$userEntry->getHome();
	}

	public function providesHomeOutsideDataDir() {
		return [
			// the reported vector: the ownCloud code root itself
			'application root' => ['/var/www/owncloud'],
			'apps directory' => ['/var/www/owncloud/apps'],
			'system directory' => ['/etc'],
			// traversal through the relative branch, which is concatenated
			// onto the data directory without any normalization
			'relative traversal' => ['../../../../etc'],
			'relative traversal to root' => ['..'],
			'traversal mid-path' => ['foo/../../../etc'],
			// an absolute path can carry dot segments too - these resolve to a
			// location outside of the data directory
			'absolute traversal mid-path' => ['/var/www/../../opt'],
			'absolute traversal out of data dir' => ['/var/www/owncloud/data/../../apps'],
			'absolute traversal with dot segments' => ['/var/www/./owncloud/./apps'],
			'absolute traversal to root' => ['/..'],
			'absolute dot segments to code root' => ['/var/www/owncloud/data/../.'],
			// a sibling directory that merely shares the data dir's prefix
			// must not pass a naive string comparison
			'prefix sibling' => ['/var/www/owncloud/data-evil'],
		];
	}

	/**
	 * A symlink inside the data directory must not be usable to point a home that
	 * looks contained at a location outside of it.
	 */
	public function testGetHomeEscapingViaSymlinkIsRefused() {
		$this->expectException(\OutOfBoundsException::class);

		$tmp = \realpath(\sys_get_temp_dir()) . '/oc-ldaphome-' . \uniqid();
		\mkdir($tmp . '/data', 0777, true);
		\mkdir($tmp . '/apps', 0777, true);
		\symlink($tmp . '/apps', $tmp . '/data/escape');

		try {
			$this->config->expects($this->any())
				->method('getSystemValue')
				->willReturnCallback(function ($key, $default = '') use ($tmp) {
					if ($key === 'datadirectory') {
						return $tmp . '/data';
					}
					if ($key === 'user_ldap.home_base_dirs') {
						return [];
					}
					return $default;
				});
			$this->connection->expects($this->any())
				->method('__get')
				->willReturnCallback(function ($key) {
					return $key === 'homeFolderNamingRule' ? 'attr:home' : 'mail';
				});
			$userEntry = new UserEntry(
				$this->config,
				$this->logger,
				$this->connection,
				[
					'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
					'mail' => [0 => 'a@b.c'],
					'home' => [0 => $tmp . '/data/escape']
				]
			);
			$userEntry->getHome();
		} finally {
			\unlink($tmp . '/data/escape');
			\rmdir($tmp . '/apps');
			\rmdir($tmp . '/data');
			\rmdir($tmp);
		}
	}

	/**
	 * A symlinked data directory is a legitimate setup and must keep working: the
	 * home is compared after resolving both sides.
	 */
	public function testGetHomeUnderSymlinkedDataDirIsAccepted() {
		$tmp = \realpath(\sys_get_temp_dir()) . '/oc-ldaphome-' . \uniqid();
		\mkdir($tmp . '/real-data', 0777, true);
		\symlink($tmp . '/real-data', $tmp . '/data');

		try {
			$this->config->expects($this->any())
				->method('getSystemValue')
				->willReturnCallback(function ($key, $default = '') use ($tmp) {
					if ($key === 'datadirectory') {
						return $tmp . '/data';
					}
					if ($key === 'user_ldap.home_base_dirs') {
						return [];
					}
					return $default;
				});
			$this->connection->expects($this->once())
				->method('__get')
				->with($this->equalTo('homeFolderNamingRule'))
				->will($this->returnValue('attr:home'));
			$userEntry = new UserEntry(
				$this->config,
				$this->logger,
				$this->connection,
				[
					'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
					'home' => [0 => $tmp . '/data/alice']
				]
			);
			self::assertEquals($tmp . '/real-data/alice', $userEntry->getHome());
		} finally {
			\unlink($tmp . '/data');
			\rmdir($tmp . '/real-data');
			\rmdir($tmp);
		}
	}

	/**
	 * Admins with home directories on a separate mount can opt back in by
	 * listing the permitted base directories explicitly.
	 */
	public function testGetHomeOutsideDataDirAllowedByConfig() {
		$this->config->expects($this->any())
			->method('getSystemValue')
			->willReturnCallback(function ($key, $default = '') {
				if ($key === 'datadirectory') {
					return '/var/www/owncloud/data';
				}
				if ($key === 'user_ldap.home_base_dirs') {
					return ['/mnt/nfs/homes'];
				}
				return $default;
			});
		$this->connection->expects($this->once())
			->method('__get')
			->with($this->equalTo('homeFolderNamingRule'))
			->will($this->returnValue('attr:home'));
		$userEntry = new UserEntry(
			$this->config,
			$this->logger,
			$this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'home' => [0 => '/mnt/nfs/homes/alice']
			]
		);
		self::assertEquals('/mnt/nfs/homes/alice', $userEntry->getHome());
	}

	/**
	 * Dot segments that resolve back inside the data directory are fine - it is
	 * where the path lands that matters, not how it is spelled.
	 *
	 * @dataProvider providesHomeWithDotSegmentsInsideDataDir
	 */
	public function testGetHomeWithDotSegmentsInsideDataDirIsAccepted($home, $expected) {
		$this->config->expects($this->any())
			->method('getSystemValue')
			->willReturnCallback(function ($key, $default = '') {
				if ($key === 'datadirectory') {
					return '/var/www/owncloud/data';
				}
				if ($key === 'user_ldap.home_base_dirs') {
					return [];
				}
				return $default;
			});
		$this->connection->expects($this->once())
			->method('__get')
			->with($this->equalTo('homeFolderNamingRule'))
			->will($this->returnValue('attr:home'));
		$userEntry = new UserEntry(
			$this->config,
			$this->logger,
			$this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'home' => [0 => $home]
			]
		);
		self::assertEquals($expected, $userEntry->getHome());
	}

	public function providesHomeWithDotSegmentsInsideDataDir() {
		return [
			'dot segment' => ['/var/www/owncloud/data/./alice', '/var/www/owncloud/data/alice'],
			'traversal that returns' => ['/var/www/owncloud/data/foo/../alice', '/var/www/owncloud/data/alice'],
			'duplicate slashes' => ['/var/www/owncloud//data//alice', '/var/www/owncloud/data/alice'],
			'trailing slash' => ['/var/www/owncloud/data/alice/', '/var/www/owncloud/data/alice'],
			'the data directory itself' => ['/var/www/owncloud/data', '/var/www/owncloud/data'],
			'relative with dot segments' => ['./alice', '/var/www/owncloud/data/alice'],
		];
	}

	/**
	 * A base directory that is not usable as one must not widen the check. Values
	 * such as '.' or a relative path normalize to something unrelated to the
	 * intended directory - notably '.' normalizes to the empty string, which used
	 * to make the containment check accept every absolute path.
	 *
	 * @dataProvider providesUnusableBaseDirs
	 */
	public function testGetHomeIsRefusedForUnusableBaseDir($baseDir) {
		$this->expectException(\OutOfBoundsException::class);

		$this->config->expects($this->any())
			->method('getSystemValue')
			->willReturnCallback(function ($key, $default = '') use ($baseDir) {
				if ($key === 'datadirectory') {
					return '/var/www/owncloud/data';
				}
				if ($key === 'user_ldap.home_base_dirs') {
					return [$baseDir];
				}
				return $default;
			});
		// the error path resolves the uid for the log message, which reads
		// further config options
		$this->connection->expects($this->any())
			->method('__get')
			->willReturnCallback(function ($key) {
				return $key === 'homeFolderNamingRule' ? 'attr:home' : 'mail';
			});
		$userEntry = new UserEntry(
			$this->config,
			$this->logger,
			$this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'mail' => [0 => 'a@b.c'],
				'home' => [0 => '/var/www/owncloud/apps']
			]
		);
		$userEntry->getHome();
	}

	public function providesUnusableBaseDirs() {
		return [
			'empty' => [''],
			// these normalize to the empty string
			'current directory' => ['.'],
			'current directory with slash' => ['./'],
			'dot segments only' => ['./.'],
			// a relative base dir would resolve against the working directory of
			// whichever process happens to run the check
			'relative' => ['data'],
			'relative with dot' => ['./data'],
			'parent' => ['..'],
			'not a string' => [null],
		];
	}

	public function testGetHomeAttributeWithRelativePath() {
		$this->config->expects($this->any())
			->method('getSystemValue')
			->willReturnCallback(function ($key, $default = '') {
				if ($key === 'datadirectory') {
					return '/path/to/data';
				}
				return $default;
			});
		$this->connection->expects($this->once())
			->method('__get')
			->with($this->equalTo('homeFolderNamingRule'))
			->will($this->returnValue('attr:home'));
		$userEntry = new UserEntry(
			$this->config,
			$this->logger,
			$this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'home' => [0 => 'f/o/o']
			]
		);
		self::assertEquals('/path/to/data/f/o/o', $userEntry->getHome());
	}

	public function testGetHomeUnset() {
		$this->connection->expects($this->once())
			->method('__get')
			->with($this->equalTo('homeFolderNamingRule'))
			->will($this->returnValue('attr:home'));
		$userEntry = new UserEntry(
			$this->config,
			$this->logger,
			$this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
			]
		);
		self::assertNull($userEntry->getHome());
	}

	/**
	 */
	public function testGetHomeUnsetButRequired() {
		$this->expectException(\Exception::class);

		$this->config->expects($this->once())
			->method('getAppValue')
			->will($this->returnValue(true));
		$this->connection->expects($this->exactly(2))
			->method('__get')
			->withConsecutive(
				[$this->equalTo('homeFolderNamingRule')],
				[$this->equalTo('ldapExpertUsernameAttr')]
			)
			->willReturnOnConsecutiveCalls(
				'attr:home',
				'mail'
			);
		$userEntry = new UserEntry(
			$this->config,
			$this->logger,
			$this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'mail' => [0 => 'a@b.c']
			]
		);
		$userEntry->getHome();
	}

	public function testGetAvatarImageInJpegPhoto() {
		$userEntry = new UserEntry(
			$this->config,
			$this->logger,
			$this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'jpegphoto' => [0 => 'binarydata']
			]
		);
		self::assertEquals('binarydata', $userEntry->getAvatarImage());
	}

	public function testGetAvatarImageInThumbnailPhoto() {
		$userEntry = new UserEntry(
			$this->config,
			$this->logger,
			$this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'thumbnailphoto' => [0 => 'binarydata']
			]
		);
		self::assertEquals('binarydata', $userEntry->getAvatarImage());
	}

	public function testGetSearchTerms() {
		$this->connection->expects($this->once())
			->method('__get')
			->with($this->equalTo('ldapAttributesForUserSearch'))
			->will($this->returnValue(['mail', 'uid', 'firstname']));
		$userEntry = new UserEntry(
			$this->config,
			$this->logger,
			$this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'mail' => [0 => 'a@b.c', 1 => 'alt@b.c'], // all mails should be found
				'uid' => [0 => 'foo'],
				'firstname' => [0 => 'Foo'] // same ass foo, should be omitted
			]
		);
		self::assertEquals(['a@b.c', 'alt@b.c', 'foo'], $userEntry->getSearchTerms());
	}

	public function testGetSearchTermsWithConversion() {
		$this->connection->expects($this->once())
			->method('__get')
			->with($this->equalTo('ldapAttributesForUserSearch'))
			->will($this->returnValue(['objectguid']));  // objectguid is converted by default
		$userEntry = new UserEntry(
			$this->config,
			$this->logger,
			$this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'objectguid' => [0 => "\xf3\x71\xe2\x36\xa9\x48\x63\x4e\xb6\xbd\x41\xb6\x9d\x9b\x59\xb3"], // all mails should be found
			]
		);
		self::assertEquals(['36e271f3-48a9-4e63-b6bd-41b69d9b59b3'], $userEntry->getSearchTerms());
	}

	public function testGetSearchTermsUnconfigured() {
		$this->connection->expects($this->once())
			->method('__get')
			->with($this->equalTo('ldapAttributesForUserSearch'))
			->will($this->returnValue([]));
		$userEntry = new UserEntry(
			$this->config,
			$this->logger,
			$this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
			]
		);
		self::assertEquals([], $userEntry->getSearchTerms());
	}

	public function testGetAttribute() {
		$userEntry = new UserEntry(
			$this->config,
			$this->logger,
			$this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'samaccountname' => [0 => 'user007'],
				'whencreated' => [0 => '20220930084030.0Z']
			]
		);
		self::assertSame('user007', $userEntry->getAttribute('samaccountname'));
		self::assertSame('20220930084030.0Z', $userEntry->getAttribute('whencreated'));
	}

	public function testGetAttributeMissing() {
		$userEntry = new UserEntry(
			$this->config,
			$this->logger,
			$this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'samaccountname' => [0 => 'user007'],
				'whencreated' => [0 => '20220930084030.0Z']
			]
		);
		self::assertNull($userEntry->getAttribute('missingone'));
	}

	public function testGetAttributeNotTrimmed() {
		$userEntry = new UserEntry(
			$this->config,
			$this->logger,
			$this->connection,
			[
				'dn' => [0 => 'cn=foo,dc=foobar,dc=bar'],
				'samaccountname' => [0 => '	user007 '],
				'whencreated' => [0 => '20220930084030.0Z']
			]
		);
		self::assertSame('	user007 ', $userEntry->getAttribute('samaccountname'));
	}

	public function testLdapEntryLowercasedKeys() {
		$val = 'cn=foo,dc=foobar,dc=bar';
		$input = ['Dn' => ['count' => 1, $val]];
		$userEntry = new UserEntry($this->config, $this->logger, $this->connection, $input);
		// This requests the dn using lowercase 'dn' so it should return the value properly
		$this->assertEquals($val, $userEntry->getDN());
	}
}
