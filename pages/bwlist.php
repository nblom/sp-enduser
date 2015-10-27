<?php
if (!defined('SP_ENDUSER')) die('File not included');
if (!$settings->getDisplayBWlist()) die("The setting display-bwlist isn't enabled");

function checkAccess($perm)
{
	$access = Session::Get()->getAccess();
	if (count($access) == 0)
		return true;
	foreach ($access as $type)
		foreach ($type as $item)
			if ($item == $perm)
				return true;
	if (strpos($perm, '@') !== false)
		if (Session::Get()->checkAccessMail($perm))
			return true;
	return false;
}

$dbh = $settings->getDatabase();

if ($_GET['list'] == 'delete') {
	foreach (explode(',', $_GET['access']) as $a) {
		if (!checkAccess($a))
			die('invalid access');
		$statement = $dbh->prepare("DELETE FROM bwlist WHERE access = :access AND bwlist.type = :type AND bwlist.value = :value;");
		$statement->execute(array(':access' => $a, ':type' => $_GET['type'], ':value' => $_GET['value']));
	}
	header("Location: ?page=bwlist&limit=".intval($_GET['limit']).'&offset='.intval($_GET['offset']));
	die();
}

if ($_GET['list'] == 'add') {
	header('Content-Type: application/json; charset=UTF-8');

	$value = strtolower(trim($_POST['value']));
	if ($value[0] == '@') $value = substr($value, 1);

	$type = $_POST['type'];

	$added = false;
	foreach ($_POST['access'] as $access)
	{
		if (strpos($value, ' ') !== false)
			die(json_encode(array('error' => 'syntax', 'field' => 'value', 'reason' => 'Field contained whitespace')));
		if (strpos($access, ' ') !== false)
			die(json_encode(array('error' => 'syntax', 'field' => 'access', 'reason' => 'Field contained whitespace')));

		if ($access[0] == '@')
			$access = substr($access, 1);

		if (!checkAccess($access))
			die(json_encode(array('error' => 'permission', 'value' => $access)));

		if ($type == 'whitelist' || $type == 'blacklist') {
			$statement = $dbh->prepare("INSERT INTO bwlist (access, type, value) VALUES(:access, :type, :value);");
			$statement->execute(array(':access' => strtolower($access), ':type' => $type, ':value' => $value));
			$added = true;
		} else
			die(json_encode(array('error' => 'syntax', 'field' => 'type', 'reason' => 'Type not black or whitelist')));
	}
	if (!$added)
		die(json_encode(array('error' => 'syntax', 'field' => 'access', 'reason' => 'No recipients')));
	die(json_encode(array('status' => 'ok')));
}

$result = array();
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 500;

$total = null;
$access = Session::Get()->getAccess();
$search = $_GET['search'];
$in_access = $domain_access = array();
foreach (array_merge((array)$access['mail'], (array)$access['domain']) as $k => $v)
	$in_access[':access'.$k] = $v;
foreach ((array)$access['domain'] as $k => $v)
	$domain_access[':domain'.$k] = '%@'.$v;
$foundrows = $where = '';
$wheres = array();
if ($dbh->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql')
	$foundrows = 'SQL_CALC_FOUND_ROWS';
if ($search != '')
	$wheres[] = 'value LIKE :search';
if (count($access) != 0) {
	$restrict = '(access IN ('.implode(',', array_keys($in_access)).')';
	foreach (array_keys($domain_access) as $v)
		$restrict .= ' OR access LIKE '.$v;
	$restrict .= ')';
	$wheres[] = $restrict;
}

if (count($wheres))
	$where = 'WHERE '.implode(' AND ', $wheres);
$sql = "SELECT $foundrows * FROM bwlist $where ORDER BY type DESC, value ASC LIMIT :offset, :limit;";
$statement = $dbh->prepare($sql);
$statement->bindValue(':limit', (int)$limit + 1, PDO::PARAM_INT);
$statement->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
if ($search != '')
	$statement->bindValue(':search', '%'.$search.'%');
foreach ($in_access as $k => $v)
	$statement->bindValue($k, $v);
foreach ($domain_access as $k => $v)
	$statement->bindValue($k, $v);
$statement->execute();
while ($row = $statement->fetch(PDO::FETCH_ASSOC))
	$result[] = $row;
if ($dbh->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql') {
	$total = $dbh->query('SELECT FOUND_ROWS();');
	$total = (int)$total->fetchColumn();
}
if ($dbh->getAttribute(PDO::ATTR_DRIVER_NAME) == 'sqlite') {
	if ($offset == 0 && count($result) < $limit + 1) {
		$total = count($result);
	} else {
		$total = $dbh->prepare("SELECT COUNT(*) FROM bwlist $where;");
		if ($search != '')
			$total->bindValue(':search', '%'.$search.'%');
		foreach ($in_access as $k => $v)
			$total->bindValue($k, $v);
		$total->execute();
		$total = (int)$total->fetchColumn();
	}
}
$pagemore = count($result) > $limit;
if ($pagemore)
	array_pop($result);
if ($total) {
	$currpage = intval($offset/$limit);
	$lastpage = intval(($total-1)/$limit);
	$pages = range(0, $lastpage);
	if (count($pages) == 1) $pages = array();
	if ($lastpage > 10) {
		// start or end (first4 ... last4)
		$pages = array_merge(range(0, 2), array('...', intval($lastpage/2), '...'), range($lastpage - 2, $lastpage));
		// middle (first .. middle5 .. last)
		if ($currpage > 2 && $currpage < ($lastpage - 2))
			$pages = array_merge(array(0, '...'), range($currpage - 2, $currpage + 2), array('...', $lastpage));
		// beginning (first5 ... last3)
		if ($currpage > 1 && $currpage < 4)
			$pages = array_merge(range(0, 4), array('...'), range($lastpage - 2, $lastpage));
		// ending (first3 .. last5)
		if ($currpage > ($lastpage - 4) && $currpage < ($lastpage - 1))
			$pages = array_merge(range(0, 2), array('...'), range($lastpage - 4, $lastpage));
	}
}

// For users with many access levels; print them more condensed
$result2 = array();
foreach ($result as $row)
	$result2[$row['type']][$row['value']][] = $row['access'];

$javascript[] = 'static/js/bwlist.js';

require_once BASE.'/inc/smarty.php';

if ($search) $smarty->assign('search', $search);
$access = array();
foreach (Session::Get()->getAccess() as $a)
	$access = array_merge($access, $a);
$smarty->assign('useraccess', $access);
$smarty->assign('items', $result2);
if ($total) $smarty->assign('total', $total);
if ($pages) $smarty->assign('pages', $pages);
$smarty->assign('currpage', $currpage);
$smarty->assign('limit', $limit);
$smarty->assign('offset', $offset);
if ($pagemore) $smarty->assign('pagemore', true);

$smarty->display('bwlist.tpl');
