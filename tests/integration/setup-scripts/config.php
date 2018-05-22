<?php
  $bdn = 'dc=owncloud,dc=com';
  $adn = 'cn=admin,dc=owncloud,dc=com';
  $apwd = 'admin';
  $host = \getenv("LDAP_HOST");
  if ($host === false) {
  	$host = "localhost";
  }
  $port = 389;
