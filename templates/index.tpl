{include file='header.tpl' title='Messages' page_active='index'}
<nav class="navbar navbar-toolbar navbar-static-top">
	<div class="container-fluid">
		<div class="navbar-header">
			<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#toolbar-collapse">
				<span class="sr-only">Toggle navigation</span>
				<i class="fa fa-search"></i>
			</button>
			<div class="navbar-brand visible-xs">
				{if count($sources) == 1}
					{t}{$source_name}{/t}
				{else}
				<div class="dropdown">
					<a class="dropdown-toggle navbar-brand-link" id="source-select" data-toggle="dropdown" aria-expanded="true">
						{t}{$source_name}{/t}
						<span class="caret"></span>
					</a>
					<ul class="dropdown-menu" role="menu" aria-labelledby="source-select">
						{foreach $sources as $name}
						<li role="presentation">
							<a role="menuitem" tabindex="-1" href="?source={$name}&search={$search|escape}&size={$size}">
							{if $name == 'history'}{t}History{/t}
							{elseif $name == 'queue'}{t}Queue{/t}
							{elseif $name == 'quarantine'}{t}Quarantine{/t}
							{elseif $name == 'all'}{t}All{/t}
							{else}{$name}{/if}
							</a>
						</li>
						{/foreach}
					</ul>
				</div>
				{/if}
			</div>
			{if count($sources) == 1}
				<a class="navbar-brand hidden-xs">{t}{$source_name}{/t}</a>
			{/if}
		</div>
		<div class="collapse navbar-collapse" id="toolbar-collapse">
			{if count($sources) > 1}
			<ul class="nav navbar-nav navbar-left hidden-xs">
				<li class="dropdown">
					<a href="#" class="dropdown-toggle navbar-brand" data-toggle="dropdown" role="button" aria-expanded="false">{t}{$source_name}{/t} <span class="caret"></span></a>
					<ul class="dropdown-menu" role="menu">
						{foreach $sources as $name}
						<li>
							<a href="?source={$name}&search={$search|escape}&size={$size}">
								{if $name == 'history'}{t}History{/t}
								{elseif $name == 'queue'}{t}Queue{/t}
								{elseif $name == 'quarantine'}{t}Quarantine{/t}
								{elseif $name == 'all'}{t}All{/t}
								{else}{$name}{/if}
							</a>
						</li>
						{/foreach}
					</ul>
				</li>
			</ul>
			{/if}
			<form class="navbar-form navbar-left" role="search">
				<input type="hidden" name="source" value="{$source}">
				<div class="input-group">
					<span class="input-group-addon"><span class="fa fa-search"></span></span>
					<input type="search" class="form-control" size="40" placeholder="{t}Search for...{/t}" id="search" name="search" value="{$search|escape}">
					<div class="input-group-btn">
						<button class="btn btn-default" id="dosearch">{t}Search{/t}</button>
						{if $search_domains}
							<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span class="caret"></span></button>
							<ul id="search_domain" class="dropdown-menu" role="menu">
							{foreach $search_domains as $domain}
								<li><a href="#">{$domain|escape}</a></li>
							{/foreach}
							</ul>
						{/if}
					</div>
				</div>
			</form>
			<ul class="nav navbar-nav">
				<li><a href="#" data-toggle="modal" data-target="#querybuilder"><span class="fa fa-filter" aria-hidden="true"></span> {t}Search filter{/t}</a></li>
			</ul>
			{if $mailwithaction}
			<ul class="nav navbar-nav navbar-left hidden-xs hidden-sm">
				<li class="divider"></li>
				<li class="dropdown">
					<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">{t}Actions{/t} <span class="caret"></span></a>
					<ul class="dropdown-menu" role="menu">
						<li><a data-bulk-action="delete"><i class="fa fa-trash-o"></i>&nbsp;{t}Delete selected{/t}</a></li>
						<li><a data-bulk-action="bounce"><i class="fa fa-mail-reply"></i>&nbsp;{t}Bounce selected{/t}</a></li>
						<li><a data-bulk-action="retry"><i class="fa fa-mail-forward"></i>&nbsp;{t}Release/retry selected{/t}</a></li>
					</ul>
				</li>
			</ul>
			{/if}
		</div>
	</div>
</nav>
<div class="container-fluid">
	<div class="row">
		{if $errors}
		<div class="col-sm-12">
			<p class="text-muted">
				{t}Some messages might not be available at the moment due to maintenance.{/t}
			</p>
		</div>
		{/if}
		<style>
			table {
				table-layout: fixed;
			}
			td, td > a, .list-group p, .list-group h4 {
				text-overflow: ellipsis;
				white-space: nowrap;
				overflow: hidden;
			}
			.nopad > a {
				padding-left: 0px;
				padding-right: 0px;
				padding-top: 6px;
				padding-bottom: 0px;
			}
		</style>
		<form method="post" id="multiform">
		<table class="table table-hover hidden-xs">
			<thead>
				<tr>
					<th style="width:25px"><input type="checkbox" id="select-all" class="hidden-sm"></th>
					<th style="width:30px"></th>
					<th>{t}From{/t}</th>
					{if $mailhasmultipleaddresses}<th>{t}To{/t}</th>{/if}
					<th>{t}Subject{/t}</th>
					<th class="hidden-sm">{t}Status{/t}</th>
					{if $feature_scores}<th class="visible-lg" style="width: 120px;">{t}Scores{/t}</th>{/if}
					<th>{t}Date{/t}</th>
					<th style="width: 25px;" class="hidden-sm"></th>
					<!-- Padding column to avoid having the OSX scrollbar cover the rightmost button -->
					<th style="width: 20px;">&nbsp;</th>
				</tr>
			</thead>
			<tbody>
				{foreach $mails as $mail}
				<tr {$mail.tr}>
					<td>
						{if $mail.type == 'queue'}
							<input class="hidden-sm" type="checkbox" name="multiselect-{$mail.mail->id}" value="{$mail.node}">
						{/if}
					</td>
					<td {$mail.td} class="nopad">
						<span class="fa-stack" style="font-size:12px;" title="{$mail.mail->msgaction}">
							<i class="fa fa-square fa-stack-2x" style="color: {$mail.action_color};"></i>
							<i class="fa fa-lg fa-{$mail.action_icon} fa-stack-1x" style="color:#fff;"></i>
						</span>
					</td>
					<td {$mail.td}>{$mail.mail->msgfrom|escape|emptyspace}</td>
					{if $mailhasmultipleaddresses}<td {$mail.td}>{$mail.mail->msgto|escape|emptyspace}</td>{/if}
					<td {$mail.td}>{$mail.mail->msgsubject|escape|emptyspace}</td>
					<td class="hidden-sm" {$mail.td}><span title="{$mail.description|escape}">
						{if $mail.mail->msgaction == 'QUARANTINE'}
							{t}Quarantine{/t}
						{elseif $mail.mail->msgaction == 'QUEUE'}
							{t retry=$mail.mail->msgretries}In queue (retry %1){/t} <span class="text-muted">{$mail.description|escape}</span>
						{else}
							{$mail.description|escape}
						{/if}
					</span></td>
					{if $feature_scores}<td class="visible-lg" {$mail.td}>{$mail.scores|escape|emptyspace}</td>{/if}
					<td {$mail.td}>
					{if $mail.today}
						{$mail.time|strftime2:'<span class="hidden-sm">%b %e %Y, </span>%H:%M<span class="hidden-sm">:%S</span>'}
					{else}
						{$mail.time|strftime2:'%b %e %Y<span class="hidden-sm">, %H:%M:%S</span>'}
					{/if}
					</td>
					<td class="hidden-sm">{if $mail.type == 'queue'}<a title="{t}Release/retry{/t}" data-action="retry"><i class="fa fa-mail-forward"></i></a>{/if}</td>
					<td>&nbsp;</td>
				</tr>
				{foreachelse}
				<tr>
					<td colspan="10" class="text-muted text-center">{t}No matches{/t}</td>
				</tr>
				{/foreach}
			</tbody>
		</table>
		</form>

		<div class="list-group not-rounded visible-xs">
			{foreach $mails as $mail}
				<a href="{$mail.preview|escape}" class="list-group-item" style="padding: 0px; border-left: 0;">
				<table cellspacing="0" cellpadding="0" style="width: 100%;">
				<tr>
				<td style="background-color: {$mail.action_color}; text-align: center; width: 20px;">
					<span style="color: #fff;" class="fa fa-{$mail.action_icon}"></span>
				</td>
				<td style="padding: 5px;">
					<h4 class="list-group-item-heading">
						<small class="pull-right">{if $mail.today}{$mail.time|strftime2:'%H:%M'}{else}{$mail.time|strftime2:'%b %e %Y'}{/if}</small>
						{if $mail.mail->msgfrom}{$mail.mail->msgfrom|escape}{else}<span class="text-muted">{t}Empty sender{/t}</span>{/if}
						{if $mailhasmultipleaddresses}<br><small>&rarr;&nbsp;{$mail.mail->msgto}</small>{/if}
					</h4>
					<p class="list-group-item-text clearfix">
						{$mail.mail->msgsubject|escape}
					</p>
				</td></tr></table>
				</a>
			{foreachelse}
				<a class="list-group-item disabled text-center">{t}No matches{/t}</a>
			{/foreach}
		</div>

	</div>
	<form id="nav-form">
		<nav>
			<ul class="pager">
				<li class="previous {$prev_button}"><a href="#" onclick="history.go(-1); return false;"><span aria-hidden="true">&larr;</span> {t}Newer{/t}</a></li>
				<li class="next {$next_button}"><a href="#" onclick="$('#nav-form').submit(); return false;">{t}Older{/t} <span aria-hidden="true">&rarr;</span></a></li>
			</ul>
		</nav>
		<input type="hidden" name="size" value="{$size}">
		<input type="hidden" name="search" value="{$search}">
		<input type="hidden" name="source" value="{$source}">
		{foreach from=$paging key=name item=value}
			<input type="hidden" name="{$name|escape}" value="{$value|escape}">
		{/foreach}
	</form>
	<hr>
	<p class="text-muted small">
		{t}Results per page:{/t}
	</p>
	<div class="btn-group" role="group" aria-label="Results per page">
		{foreach $pagesizes as $pagesize}
			<a class="btn btn-sm btn-default{if $size==$pagesize} active{/if}" href="?size={$pagesize}&source={$source}&search={$search|escape}">{$pagesize}</a>
		{/foreach}
	</div>
	{if $errors}
	<div style="padding-left: 17px;">
		<span class="text-muted">
		{t}Diagnostic information:{/t}
		<ul>
		{foreach $errors as $error}
				<li>{$error|escape}</li>
		{/foreach}
		</ul>
		</span>
	</div>
	{/if}
	</div>
	<div class="modal fade" id="querybuilder"><div class="modal-dialog"><div class="modal-content">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		<h4 class="modal-title">{t}Search filter{/t}</h4>
	</div>
	<div class="modal-body" id="query">
		<p>{t escape=no url='http://wiki.halon.se/Search_filter'}Even more fields and operator types are documented on the <a href="%1">search filter</a> page.{/t}</p>
		<form class="form-horizontal">
			{if $source != 'queue' and $source != 'quarantine'}
			<div class="form-group">
				<label class="col-sm-2 control-label">{t}Action{/t}</label>
				<label class="col-sm-2 control-label">{t}is{/t}</label>
				<div class="col-sm-8"><select class="form-control" id="query_action">
					<option></option>
					{if $source != 'history'}<option>QUARANTINE</option>{/if}
					<option>DELIVER</option>
					<option>DELETE</option>
					<option>REJECT</option>
					<option>DEFER</option>
					<option>ERROR</option>
					{if $source == 'log'}<option>QUEUE</option>{/if}
				</select></div>
			</div>
			{/if}
			<div class="form-group">
				<label class="col-sm-2 control-label">{t}Date{/t}</label>
				<label class="col-sm-2 control-label">{t}between{/t}</label>
				<div class="col-sm-8"><input type="datetime-local" class="form-control" id="query_date_1" placeholder="yyyy/mm/dd hh:mm:ss"></div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label"></label>
				<label class="col-sm-2 control-label">{t}and{/t}</label>
				<div class="col-sm-8"><input type="datetime-local" class="form-control" id="query_date_2" placeholder="yyyy/mm/dd hh:mm:ss"></div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label">{t}ID{/t}</label>
				<label class="col-sm-2 control-label">{t}is{/t}</label>
				<div class="col-sm-8"><input type="text" class="form-control" id="query_mid" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"></div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label">{t}From{/t}</label>
				<div class="col-sm-2"><select class="form-control" id="query_from_op"><option value="=">{t}is{/t}</option><option value="~">{t}contains{/t}</option></select></div>
				<div class="col-sm-8"><input type="email" class="form-control" id="query_from" placeholder="user@example.com"></div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label">{t}To{/t}</label>
				<div class="col-sm-2"><select class="form-control" id="query_to_op"><option value="=">{t}is{/t}</option><option value="~">{t}contains{/t}</option></select></div>
				<div class="col-sm-8"><input type="email" class="form-control" id="query_to" placeholder="user@example.com"></div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label">{t}IP{/t}</label>
				<div class="col-sm-2"><select class="form-control" id="query_ip_op"><option value="=">{t}is{/t}</option><option value="~">{t}contains{/t}</option></select></div>
				<div class="col-sm-8"><input type="text" class="form-control" id="query_ip" placeholder="0.0.0.0"></div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label">{t}Username{/t}</label>
				<div class="col-sm-2"><select class="form-control" id="query_sasl_op"><option value="=">{t}is{/t}</option><option value="~">{t}contains{/t}</option></select></div>
				<div class="col-sm-8"><input type="text" class="form-control" id="query_sasl" placeholder=""></div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label">{t}Subject{/t}</label>
				<div class="col-sm-2"><select class="form-control" id="query_subject_op"><option value="=">{t}is{/t}</option><option value="~" selected>{t}contains{/t}</option></select></div>
				<div class="col-sm-8"><input type="text" class="form-control" id="query_subject" placeholder=""></div>
			</div>
		</form>
	</div>
	<div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal">{t}Close{/t}</button>
		<button type="button" class="btn btn-primary" onclick="$('#dosearch').click()">{t}Search{/t}</button>
	</div>
	</div></div></div>
{include file='footer.tpl'}
