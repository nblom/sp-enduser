<?php
if (!defined('SP_ENDUSER')) die('File not included');

require_once('inc/core.php');

session_start();
session_regenerate_id(true);

class LDAPDatabase {
	private $uri = '';
	private $basedn = '';

	public function __construct($uri, $basedn) {
		$this->uri = $uri;
		$this->basedn = $basedn;
	}
	public function check($username, $password) {
		// If username and password are not specified,
		// an anonymous bind is attempted. 
		if ($username == "" || $password == "")
			return false;

		if (!function_exists('ldap_connect'))
			die('PHP module LDAP missing (install php5-ldap)');

		$ds = ldap_connect($this->uri);
		if (!$ds)
			return false;

		$bind = @ldap_bind($ds, $username, $password);
		if (!$bind)
			return false;

		$_SESSION['username'] = $username;
		$_SESSION['source'] = 'ldap';
		$_SESSION['access'] = array();

		$ldapuser = ldap_escape($username);
		//$rs = ldap_search($ds, $this->basedn, "(&(userPrincipalName=$ldapuser)(proxyAddresses=smtp:*))", array('proxyAddresses'));
		$rs = ldap_search($ds, $this->basedn, "(&(userPrincipalName=$ldapuser)(mail=*))", array('mail'));
		$entry = ldap_first_entry($ds, $rs);
		if ($entry) {
			//foreach(ldap_get_values($ds, $entry, 'proxyAddresses') as $mail) {
			foreach(ldap_get_values($ds, $entry, 'mail') as $mail) {
				if (!is_string($mail))
					continue;
				$_SESSION['access']['mail'][] = $mail;
			}
		}
		return true;
	}
}


if (isset($_POST['username']) && isset($_POST['password'])) {
	$username = $_POST['username'];
	$password = $_POST['password'];
	foreach ($settings['authentication'] as $method) {
		switch($method['type']) {
			case 'account':
				if ($username === $method['username'] && $password === $method['password'])
				{
					$_SESSION['username'] = $method['username'];
					$_SESSION['source'] = 'local';
					$_SESSION['access'] = $method['access'];
					break 2;
				}
			break;
			case 'smtp':
				$fp = fsockopen($method['host'], $method['port'] ?: '25');
				while($line = fgets($fp))
					if (substr($line, 3, 1) != '-')
						break;
				if (substr($line, 0, 1) != '2')
					goto smtp_fail;
				$plain = base64_encode($username . "\0" . $username . "\0" . $password);
				fwrite($fp, "AUTH PLAIN $plain\n");
				while($line = fgets($fp))
					if (substr($line, 3, 1) != '-')
						break;
				if (substr($line, 0, 3) != '235')
					goto smtp_fail;
				fwrite($fp, "QUIT\n");
				$_SESSION['username'] = $username;
				$_SESSION['source'] = 'smtp';
				$_SESSION['access'] = array('mail' => array($username));
				break 2;
				smtp_fail:
					fwrite($fp, "QUIT\n");
			break;
			case 'ldap':
				$method = new LDAPDatabase($method['uri'], $method['base_dn']);
				if ($method->check($username, $password))
					break 2;
			break;
			case 'database':
				if (!isset($settings['database']['dsn']))
					die('No database configured');

				$dbh = new PDO($settings['database']['dsn'], $settings['database']['user'], $settings['database']['password']);
				$statement = $dbh->prepare("SELECT * FROM users WHERE username = :username;");
					$statement->execute(array(':username' => $username));
				$row = $statement->fetch();
				if (!$row || $row['password'] !== crypt($password, $row['password']))
					break;

				$_SESSION['username'] = $row['username'];
				$_SESSION['source'] = 'database';
				$_SESSION['access'] = array();
				$statement = $dbh->prepare("SELECT * FROM users_relations WHERE username = :username;");
				$statement->execute(array(':username' => $row['username']));
				while($row = $statement->fetch()) {
					$_SESSION['access'][$row['type']][] = $row['access'];
				}
				break 2;
			break;
		}
	}
	if (isset($_SESSION['username'])) {
		header("Location: index.php");
		die();
	}
	$error = 'Login failed';
}

$title = 'Sign in';
require_once('inc/header.php');
?>
		</div>
		<?php if (isset($error)) { ?>
		<div class="message pad error"><?php p($error) ?></div>
		<?php } ?>
		<div class="halfpages">
			<div class="halfpage">
				<fieldset>
					<legend><?php p($pagename) ?></legend>
					<?php
					if (isset($settings['logintext']))
						echo $settings['logintext'];
					else { ?>
					<p>
						This site allows end-user access of e-mail security
						systems from Halon Security in the SP (spam prevention)
						series. It provides features such as quarantine and queue
						management, access to the message history, black/whitelist,
						etc.
					</p>
					<p>
						The login credentials can be verified against the
						settings file, LDAP servers, SMTP servers using SASL, 
						or database sources created dynamically as messages
						are quarantined.
					</p>
					<?php } ?>
				</fieldset>
			</div>
			<div class="halfpage">
				<fieldset>
					<legend>Sign in</legend>
					<form method="post" action="?page=login">
						<div>
							<label for="username">Username</label>
							<input name="username" type="text">
						</div>
						<div>
							<label for="password">Password</label>
							<input name="password" type="password">
						</div>
						<div>
							<label></label>
							<button type="submit">Sign in</button>
						</div>
					</form>
					<?php if (isset($settings['database']['dsn'])) { ?>
						<p><a href="?page=forget">Forgot password?</a></p>
					<?php } ?>
				</fieldset>
			</div>
		</div>
<?php require_once('inc/footer.php') ?>
