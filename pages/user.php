<?php
if (!defined('SP_ENDUSER')) die('File not included');

function do_change_password()
{
	global $settings, $error, $changedPassword;
	
	if ($_POST['password'] != $_POST['password2']) {
		$error = "Your new passwords don't match!";
		return;
	}
	if (!password_policy($_POST['password'], $error2)) {
		$error = $error2;
		return;
	}
	
	$dbh = $settings->getDatabase();
	
	$statement = $dbh->prepare("SELECT * FROM users WHERE username = :username;");
	$statement->execute(array(':username' => Session::Get()->getUsername()));
	$row = $statement->fetch(PDO::FETCH_ASSOC);
	
	if (!$row || !password_verify($_POST['old_password'], $row['password'])) {
		$error = "Your old password is incorrect!";
		return;
	}
	
	$statement = $dbh->prepare("UPDATE users SET password = :password WHERE username = :username;");
	$statement->execute(array(':username' => Session::Get()->getUsername(), ':password' => password_hash($_POST['password'], PASSWORD_DEFAULT)));
	$changedPassword = true;
}

$changedPassword = false;
$error = NULL;
if (Session::Get()->getSource() == 'database' && isset($_POST['password']))
	do_change_password();

require_once BASE.'/inc/smarty.php';

if (is_array($access['mail'])) $smarty->assign('access_mail', $access['mail']);
if (is_array($access['domain'])) $smarty->assign('access_domain', $access['domain']);
if ($changedPassword) $smarty->assign('password_changed', true);
if (Session::Get()->getSource() == 'database') $smarty->assign('password_changeable', true);
if ($error) $smarty->assign('error', $error);

$smarty->display('user.tpl');
