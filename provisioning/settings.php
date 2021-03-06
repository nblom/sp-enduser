<?php

/*
 * This is the configuration file, in PHP format. In most cases, it's
 * ok just to edit our settings, and remove the // comments.
 */

/*
 * System "nodes" are the most important directives; they specify where
 * your Halon mail gateway(s) are, and how to access it/them.
 * 
 * If you are planning on using authentication methods other than the default
 * 'server' mode (which authenticates users directly against the server), you
 * need to specify a username and password that will be used to access the
 * server for non-server users.
 * 
 * It might be a good idea to read about authentication scripts on our wiki, to
 * create specific access rights so that if this end-user web is compromised,
 * your gateways are not. In some cases, even read-only access is good enough
 * for this application.
 */

$settings['node'][] = array(
		'address' => 'http://10.2.0.30',
		'username' => 'admin',
		'password' => 'admin',
		);
$settings['node'][] = array(
		'address' => 'http://10.2.0.31/',
		'username' => 'admin',
		'password' => 'admin',
		);

/*
 * The API key is used by the Halon mail gateways to communicate with
 * this application, such as creating database users when messages are
 * quarantined, or doing black/whitelist lookups.
 */

$settings['api-key'] = 'secret';

/*
 * The "mail from" and "public-url" settings are used by this application
 * as self identification, for example in mail such as forgot password
 * reminders or digest lists. The default source determine what's shown
 * on the first page, or when pressing "messages" in the menu.
 */

//$settings['mail']['from'] = 'Mail quarantine <postmaster@example.org>';
//$settings['public-url'] = 'http://10.2.0.166/enduser/';
$settings['default-source'] = 'log';
$settings['display-scores'] = true;
$settings['display-textlog'] = true;
//$settings['display-history'] = true;
//$settings['display-queue'] = true;
//$settings['display-quarantine'] = true;
//$settings['display-all'] = true;
//$settings['display-bwlist'] = true;
//$settings['display-listener']['mailserver:1'] = 'Inbound';
//$settings['display-transport']['mailtransport:2'] = 'Internet';

/*
 * It's possible to use this application completely without a database.
 * However, features such as local users (if SMTP or LDAP authentication
 * is not suitable) and black/whitelisting requires a database. You can use
 * most databases, such as SQLite, MySQL and PostgreSQL. Below are a few
 * examples. You should use PHP PDO format.
 */

$settings['database']['dsn'] = 'mysql:host=localhost;port=5432;dbname=spenduser';
$settings['database']['user'] = 'spenduser';
$settings['database']['password'] = 'spenduser';

/*
 * Logs are normally read from the nodes directly, but for performance, you can
 * instead opt to configure your nodes to log to a central database server, as
 * described at: http://wiki.halon.se/End-user#History_log
 */
$settings['database-log'] = true;

/*
 * Authentication is probably the second most important configuration
 * directive, as it specifies how end-users should identify themselves.
 * 
 * You can use the following types:
 *  - LDAP, against for example an Exchange server
 *  - SMTP (SASL), against a mail server, if the username is an e-mail
 *  - Database, populated by the Halon mail gateways when mail are quarantined
 *  - Local accounts, statically configured in this file (with access rights).
 *    Use lower case letters when manually adding an access level.
 *  - Server account, authorized against an account on the nodes themselves.
 * 
 * If no authorization methods are specified, 'server' is assumed.
 */

//$settings['authentication'][] = array(
//		'type' => 'database',
//		);
$settings['authentication'][] = array(
		'type' => 'account',
		'username' => 'foo',
		'password' => 'foo',
		'access' => array(
			'mail' => array('admin@example.local'),
			),
		);
//$settings['authentication'][] = array(
//		'type' => 'ldap',
//		'uri' => 'ldap://10.2.7.2',
//		'base_dn' => 'CN=Users,DC=dev,DC=halon,DC=local',
//		'schema' => 'msexchange',
//		'options' => array(LDAP_OPT_PROTOCOL_VERSION => 3),
//		);
//$settings['authentication'][] = array(
//		'type' => 'smtp',
//		'host' => '10.2.0.30',
//		'port' => 25,
//		);
$settings['authentication'][] = array(
		'type' => 'server',
		);
$settings['authentication'][] = array(
		'type' => 'database',
		);

/*
 * The quarantine filter is used to restrict the end-user access to
 * only certain quarantines, in case you have multiple quarantines with
 * different purposes.
 */

//$settings['quarantine-filter'][] = 'mailquarantine:1';
//$settings['quarantine-filter'][] = 'mailquarantine:2';

/*
 * The default filter-pattern to use when creating additional
 * inbound/outbound restrictions are "{from} or {to}", however
 * in some cases it's necessary to know if the message is
 * inbound or outbound.
 */

//$settings['filter-pattern'] = '{from} server=mailserver:2 or {to} server=mailserver:1';

/*
 * It's possible to send "digest" messages with a list of what's in
 * the quarantine. It is added as a cron job, to be run every 24 hours:
 * # php cron.php.txt digestday
 * and it will use the authentication sources to find users. To use LDAP,
 * add a 'bind_dn' and 'bind_password' to your LDAP source. To use static
 * users (type account), add a 'email' to them. To send digest messages to
 * EVERY RECIPIENT (user or not) that has quarantine messages, enable the
 * to-all option below. To have a "direct release link" in the messages,
 * enable the digest secret below.
 */

//$settings['digest']['to-all'] = true;
//$settings['digest']['secret'] = 'badsecret';

/*
 * If hosting multiple websites on the same server, it's important to use
 * different session names for each site.
 */

//$settings['session-name'] = 'spenduser';

/*
 * Customizable text in the interface.
 */

//$settings['pagename'] = "Halon SP for end-users";
//$settings['logintext'] = "Some text you'd like to display on the login form";
