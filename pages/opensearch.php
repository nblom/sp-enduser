<?php
if (!defined('SP_ENDUSER')) die('File not included');

require_once BASE.'/inc/core.php';
header('Content-type: text/xml');

$url = 'http'.(empty($_SERVER['HTTPS'])?'':'s').'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['REQUEST_URI']).'/';

?>
<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/"
                       xmlns:moz="http://www.mozilla.org/2006/browser/search/">
	<ShortName><?php echo htmlspecialchars($settings->getPageName()); ?></ShortName>
	<Description>Search <?php echo htmlspecialchars($settings->getPageName()); ?></Description>
	<InputEncoding>UTF-8</InputEncoding>
	<Image width="16" height="16" type="image/x-icon"><?php echo htmlspecialchars($url); ?>favicon.ico</Image>
	<Url type="text/html" method="get" template="<?php echo htmlspecialchars($url); ?>?page=index&amp;search={searchTerms}&amp;ref=opensearch"/>
	<moz:SearchForm><?php echo htmlspecialchars($url); ?>?page=index</moz:SearchForm>
</OpenSearchDescription>
