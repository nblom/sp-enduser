<?php
if (!defined('SP_ENDUSER')) die('File not included');

if (isset($_POST['username']) && isset($_POST['password'])) {
	$session_name = $settings->getSessionName();
	if ($session_name)
		session_name($session_name);
	session_start();
	session_regenerate_id(true);

	$_SESSION['timezone'] = $_POST['timezone'];
	$username = $_POST['username'];
	$password = $_POST['password'];
	foreach ($settings->getAuthSources() as $method) {
		switch ($method['type']) {
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
				while ($line = fgets($fp)) {
					if (substr($line, 0, 1) != '2')
						goto smtp_fail;
					if (substr($line, 3, 1) == ' ')
						break;
				}
				$method = 'plain';
				$starttls = false;
				smtp_ehlo:
				fwrite($fp, "EHLO halon-sp-enduser\r\n");
				$found_starttls = false;
				while ($line = fgets($fp)) {
					if (substr($line, 0, 1) != '2')
						goto smtp_fail;
					if (substr($line, 4, 5) == 'AUTH ' && strpos($line, 'CRAM-MD5') !== false)
						$method = 'md5';
					if (substr($line, 4, 8) == 'STARTTLS')
						$found_starttls = true;
					if (substr($line, 3, 1) == ' ')
						break;
				}
				if (!$starttls && $found_starttls) {
					fwrite($fp, "STARTTLS\r\n");
					$line = fgets($fp);
					if (substr($line, 0, 3) != '220')
						goto smtp_fail;
					stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
					$starttls = true;
					goto smtp_ehlo;
				}
				if ($method == 'md5') {
					fwrite($fp, "AUTH CRAM-MD5\r\n");
					$line = fgets($fp);
					$chall = substr($line, 4);
					$data = $username.' '.hash_hmac('md5', base64_decode($chall), $password);
					$data = base64_encode($data);
					fwrite($fp, "$data\r\n");
				} else {
					$plain = base64_encode($username . "\0" . $username . "\0" . $password);
					fwrite($fp, "AUTH PLAIN $plain\r\n");
				}
				while ($line = fgets($fp))
					if (substr($line, 3, 1) != '-')
						break;
				if (substr($line, 0, 3) != '235')
					goto smtp_fail;
				fwrite($fp, "QUIT\r\n");
				$_SESSION['username'] = $username;
				$_SESSION['source'] = 'smtp';
				$_SESSION['access'] = array('mail' => array(strtolower($username)));
				break 2;
				smtp_fail:
					fwrite($fp, "QUIT\r\n");
			break;
			case 'ldap':
				$method = new LDAPDatabase($method['uri'], $method['base_dn'], $method['schema'], $method['options'], $method['query'], $method['access']);
				if ($method->check($username, $password))
					break 2;
			break;
			case 'database':
				$dbh = $settings->getDatabase();
				$statement = $dbh->prepare("SELECT * FROM users WHERE username = :username;");
					$statement->execute(array(':username' => $username));
				$row = $statement->fetch(PDO::FETCH_ASSOC);
				if (!$row || !password_verify($password, $row['password']))
					break;

				$_SESSION['username'] = $row['username'];
				$_SESSION['source'] = 'database';
				$_SESSION['access'] = array();
				$statement = $dbh->prepare("SELECT * FROM users_relations WHERE username = :username;");
				$statement->execute(array(':username' => $row['username']));
				while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
					$_SESSION['access'][$row['type']][] = $row['access'];
				}
				break 2;
			break;
			case 'server':
				// Loop through all the configured nodes; the primary node going
				// down shouldn't take all auth down with it, merely slow it
				for ($i = 0; $i < count($settings->getNodes()); $i++) {
					try {
						// Attempt to connect to the node
						soap_client($i, false, $username, $password)->login();
						
						// Set the client to be logged in
						$_SESSION['username'] = $username;
						$_SESSION['source'] = 'server';
						$_SESSION['access'] = array();
						
						// Use the user's credentials instead of the config's
						$_SESSION['soap_username'] = $username;
						$_SESSION['soap_password'] = $password;
						
						break 3;
					} catch (SoapFault $e) {
						// If the node is unavailable, skip to the next one
						if($e->getMessage() != "Unauthorized")
							continue;
					}
					
					break;
				}
			break;
		}
	}
	if (isset($_SESSION['username'])) {
		header("Location: .");
		die();
	}
	$error = 'Login failed';
	session_destroy();
}

require_once BASE.'/inc/smarty.php';

if ($settings->getLoginText() !== null) $smarty->assign('login_text', $settings->getLoginText());
if ($error) $smarty->assign('error', $error);
if (has_auth_database()) $smarty->assign('forgot_password', true);

$smarty->display('login.tpl');
