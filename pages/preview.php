<?php
if (!defined('SP_ENDUSER')) die('File not included');

$id = preg_replace('/[^0-9]/', '', $_GET['id']);
$type = $_GET['type'];
$node = null;

if ($type == 'log') {
	// Log = database backend
	$dbBackend = new DatabaseBackend($settings->getDatabase());
	$mail = $dbBackend->getMail($id);
	if (!$mail) die('Invalid mail');

	// If in queue, quarantine or if text-log is enabled, get it from SOAP if possible (so that we get more info)
	if ($mail->msgaction == 'QUEUE' || $mail->msgaction == 'QUARANTINE' || $settings->getDisplayTextlog() == true)
		$node = $settings->getNodeBySerial($mail->serialno);
	if ($node !== null) {
		$client = $node->soap();
		$nodeBackend = new NodeBackend($node);
		$node_mail = $nodeBackend->getMailInQueueOrHistory('messageid='.$mail->msgid.' actionid='.$mail->msgactionid, $errors, $node_type);
		if ($node_mail && !$errors) {
			$mail = $node_mail;
			$type = $node_type;
		}
	}
} else {
	// Fetch data from SOAP
	$node = $settings->getNode($_GET['node']);
	if (!$node) die('Invalid mail');

	$client = $node->soap();
	$nodeBackend = new NodeBackend($node);
	if ($_GET['type'] == 'history')
		$mail = $nodeBackend->getMailInHistory('historyid='.$id, $errors);
	else
		$mail = $nodeBackend->getMailInQueueOrHistory('queueid='.$id, $errors, $type);
	if (!$mail || $errors)
		die('Invalid mail');

	// Action are only available for mail in queue
	if (isset($_POST['action']) && $type == 'queue') {
		try {
			if ($_POST['action'] == 'bounce')
				$client->mailQueueBounce(array('id' => $mail->id));
			else if ($_POST['action'] == 'delete')
				$client->mailQueueDelete(array('id' => $mail->id));
			else if ($_POST['action'] == 'retry')
				$client->mailQueueRetry(array('id' => $mail->id));
		} catch (SoapFault $f) {
			die($f->getMessage());
		}
		header('Location: ?page=preview&type=queue&id='.$mail->id.'&node='.$node->getId());
		die();
	}
}

$action_colors = array(
	'DELIVER' => '#8c1',
	'QUEUE' => '#1ad',
	'QUARANTINE' => '#f70',
	'REJECT' => '#ba0f4b',
	'DELETE' => '#333',
	'BOUNCE' => '#333',
	'ERROR' => '#333',
	'DEFER' => '#b5b',
);

$action_icons = array(
	'DELIVER' => 'check',
	'QUEUE' => 'exchange',
	'QUARANTINE' => 'inbox',
	'REJECT' => 'ban',
	'DELETE' => 'trash-o',
	'BOUNCE' => 'mail-reply',
	'ERROR' => 'exclamation',
	'DEFER' => 'clock-o',
);

if ($type == 'queue' && $mail->msgaction == 'DELIVER') $mail->msgaction = 'QUEUE';

// Prepare data
if ($type == 'queue') {
	$uniq = uniqid();
	$command = array('previewmessage');
	if ($_GET['type'] == 'text')
		$command[] = '-t';
	$command[] = $mail->msgpath;
	$command[] = $uniq;
	if ($mail->msgdeltapath)
		$command[] = $mail->msgdeltapath;
	$data = soap_exec($command, $client);
	$data = str_replace("\r\n", "\n", $data);
	$data = explode("$uniq|", $data);
	$result = array();
	$result['HEADERS'] = trim($data[0]);
	for ($i = 1; $i < count($data); ++$i) {
		list($format, $content) = explode("\n", $data[$i], 2);
		$result[$format] = trim($content);
	}
	if (isset($result['TEXT']) || isset($result['HTML'])) {
		$config = HTMLPurifier_Config::createDefault();
		$config->set('Cache.DefinitionImpl', null);
		$config->set('URI.Disable', true);
		$purifier = new HTMLPurifier($config);
		$header = $result['HEADERS'];
		$headerdelta = $result['HEADERS-DELTA'];
		require_once 'inc/utils/extension.inc.php';
		$attachments = $result['ATTACHMENTS'] != "" ? array_map(function ($k) {
				$l = explode('|', $k);
				return array('type' => $l[0], 'size' => $l[1], 'name' => $l[2], 'icon' => extension_icon($l[2]));
				}, explode("\n", $result['ATTACHMENTS'])) : array();

		$body = isset($result['TEXT']) ? htmlspecialchars($result['TEXT']) : $result['HTML'];
		$body = trim($purifier->purify($body));
		$encode = isset($result['TEXT']) ? 'TEXT' : 'HTML';
	} else {
		$encode = 'HTML';
		$body = '<p class="text-center text-muted">Preview unavailable</p>';
	}
}

$javascript[] = 'static/js/preview.js';
$javascript[] = 'static/js/diff_match_patch.js';
$javascript[] = 'static/js/diff.js';

require_once BASE.'/inc/smarty.php';

if ($settings->getDisplayTextlog() && $node && $mail->msgid) $smarty->assign('support_log', true);
if ($settings->getDisplayScores()) $smarty->assign('scores', history_parse_scores($mail));
if ($node) $smarty->assign('node', $node->getId());
if ($attachments) $smarty->assign('attachments', $attachments);
if (isset($body)) $smarty->assign('body', $body);
if ($encode) $smarty->assign('encode', $encode);
if ($_GET['type'] == 'text') $smarty->assign('show_text', true);

$f = $_GET;
$f['type'] = 'text';
$smarty->assign('show_text_link', '?'.http_build_query($f));

$f = $_GET;
unset($f['type']);
$smarty->assign('show_html_link', '?'.http_build_query($f));

$smarty->assign('mail', $mail);
$smarty->assign('type', $type);
if ($header) {
	$smarty->assign('header', json_encode($header));
	$smarty->assign('headerdelta', json_encode($headerdelta));
}
$smarty->assign('referer', isset($_POST['referer']) ? $_POST['referer'] : $_SERVER['HTTP_REFERER']);
$smarty->assign('action_color', $action_colors[$mail->msgaction]);
$smarty->assign('action_icon', $action_icons[$mail->msgaction]);
$transports = $settings->getDisplayTransport();
if (isset($transports[$mail->msgtransport])) $smarty->assign('transport', $transports[$mail->msgtransport]);
$listeners = $settings->getDisplayListener();
if (isset($listeners[$mail->msglistener])) $smarty->assign('listener', $listeners[$mail->msglistener]);
$smarty->assign('time', $mail->msgts0 - $_SESSION['timezone'] * 60, '%Y-%m-%d %H:%M:%S');

$smarty->display('preview.tpl');
