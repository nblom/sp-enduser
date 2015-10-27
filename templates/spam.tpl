{include file='header.tpl' title='Spam settings' page_active='spam'}

{$levels = array()}
{capture assign="level"}{t}Disabled{/t}{/capture}
{$levels['disabled'] = $level}
{capture assign="level"}{t}Low{/t}{/capture}
{$levels['low'] = $level}
{capture assign="level"}{t}Medium{/t}{/capture}
{$levels['medium'] = $level}
{capture assign="level"}{t}High{/t}{/capture}
{$levels['high'] = $level}

<div class="container-fluid">
	<div class="col-md-6 col-lg-8">
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">
					{t}Spam settings{/t}
					<a class="pull-right" data-toggle="collapse" href="#search">
						<span class="fa fa-search"></span>
					</a>
				</h3>
			</div>
			<div id="search" class="{if not $search}collapse{/if}"><div class="panel-body">
				<form class="form-horizontal" method="get">
					<input type="hidden" name="page" value="spam">
					<div class="input-group">
						<span class="input-group-addon"><span class="fa fa-search"></span></span>
						<input type="text" class="form-control" placeholder="{t}Search for...{/t}" name="search" value="{$search|escape}">
						<span class="input-group-btn">
							<button class="btn btn-default" type="search">{t}Search{/t}</button>
						</span>
					</div>
				</form>
			</div></div>
			<table class="table">
				<thead class="hidden-xs">
					<tr>
						<th class="hidden-xs" style="width: 30px"></th>
						<th class="hidden-xs">{t}For recipient{/t}</th>
						<th class="hidden-xs">{t}Level{/t}</th>
						<th class="visible-xs"></th>
						<th style="width: 30px"></th>
						<th style="width: 30px"></th>
					</tr>
				</thead>
				<tbody>
				{foreach $items as $item}
					{$edit_url="?page=spam&list=edit&access={$item.access|escape}"}
					<tr>
						<td class="hidden-xs" data-href="{$edit_url}"></td>
						<td class="hidden-xs" data-href="{$edit_url}">{if $item.access}{$item.access|escape}{else}<span class="text-muted">{t}everyone{/t}</span>{/if}</td>
						<td class="hidden-xs" data-href="{$edit_url}">{$levels[$item.settings->level]}</td>
						<td class="visible-xs" data-href="{$edit_url}">
							<p>
								<span class="fa fa-pencil"></span>&nbsp; {$levels[$item.settings->level]}
							</p>
							<p>
								<span class="fa fa-inbox"></span>&nbsp;
								{if $item.access}{$item.access|escape}{else}<span class="text-muted">{t}everyone{/t}</span>{/if}
							</p>
						</td>
						<td style="width: 30px; vertical-align: middle">
							<a href="{$edit_url}"><i class="fa fa-pencil-square-o"></i></a>
						</td>
						<td style="width: 30px; vertical-align: middle">
							<a onclick="return confirm('Really delete spam settings for {$item.access|escape}?')" title="{t}Remove{/t}" href="?page=spam&list=delete&limit={$limit}&offset={$offset}&access={$item.access|urlencode}"><i class="fa fa-remove"></i></a>
						</td>
					</tr>
				{foreachelse}
					<tr><td colspan="6" class="text-muted">{t}No spam settings{/t}</td></tr>
				{/foreach}
				</tbody>
			</table>
		</div>
		<nav>
			{if $total}
			<ul class="pagination">
				{foreach $pages as $p}
					{if $p === '...'}
					<li class="disabled"><a href="#">...</a></li>
					{elseif $p === $currpage}
					<li class="active"><a href="#">{$p+1}</a></li>
					{else}
					<li><a href="?page=spam&offset={$limit*$p}&limit={$limit}&search={$search|urlencode}">{$p+1}</a></li>
					{/if}
				{/foreach}
			</ul>
			{else}
			<ul class="pager">
				<li class="previous{if $offset == 0} disabled{/if}"><a href="javascript:history.go(-1);"><span aria-hidden="true">&larr;</span> {t}Previous{/t}</a></li>
				<li class="next{if !$pagemore} disabled{/if}"><a href="?page=spam&offset={$offset+$limit}&limit={$limit}&search={$search|urlencode}">{t}Next{/t} <span aria-hidden="true">&rarr;</span></a></li>
			</ul>
			{/if}
		</nav>
	</div>
	<div class="col-md-6 col-lg-4">
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">{if $edit}{t}Edit{/t}{else}{t}Add...{/t}{/if}</h3>
			</div>
			<div class="panel-body">
				<form class="form-horizontal" id="spam_add">
					<input type="hidden" id="action" value="{if $edit}edit{else}add{/if}">
					<div class="form-group">
						<label for="type" class="control-label col-md-3">{t}Level{/t}</label>
						<div class="col-md-9">
							<select id="level" class="form-control">
								<option value="">{t}Select level{/t}</option>
								{html_options options=$levels selected=$edit.settings->level}
							</select>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-md-3" style="white-space: nowrap;">{t}For recipient{/t}</label>
						<div class="col-md-9">
							{if $edit}
								<input type="hidden" class="form-control recipient" value="{$edit.access|escape}">
								<p class="form-control-static">{if $edit.access}{$edit.access|escape}{else}<span class="text-muted">{t}everyone{/t}</span>{/if}</p>
							{else}
								{if count($useraccess) == 1}
									<input type="hidden" class="form-control recipient" value="{$useraccess.0|escape}">
									<p class="form-control-static">{$useraccess.0|escape}</p>
								{elseif count($useraccess) > 0}
									<button id="check-all" class="btn btn-info">{t}Select all{/t}</button>
									<button id="add-access" class="btn btn-default">{t}Add custom{/t}</button>
									{if count($useraccess) > 5}<div class="panel panel-default" style="height: 115px; padding-left: 10px; margin-top: 5px; overflow-y: scroll;">{/if}
									<div id="extra-accesses"></div>
									{foreach $useraccess as $a}
									<div class="checkbox">
										<label>
											<input type="checkbox" class="recipient" value="{$a|escape}"> {$a|escape}
										</label>
									</div>
									{/foreach}
									{if count($useraccess) > 5}</div>{/if}
								{else}
									<input type="text" class="form-control recipient" placeholder="{t}everyone{/t}">
								{/if}
							{/if}
						</div>
					</div>
					<div class="col-md-offset-3 col-md-9">
						{if $edit}
						<button type="submit" class="btn btn-primary">{t}Save{/t}</button>
						<a href="?page=spam"><button class="btn btn-info">{t}Cancel{/t}</button></a>
						{else}
						<button type="submit" class="btn btn-primary">{t}Add{/t}</button>
						{/if}
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<style>
	table {
		table-layout: fixed;
	}
	td {
		text-overflow: ellipsis;
		white-space: nowrap;
		overflow: hidden;
	}
</style>
<script>
$(document).ready(function() {
	$('td[data-href], tr[data-href] td').wrapInner(function() {
		return '<a class="data-link" href="' + ($(this).data('href') || $(this).parent().data('href')) + '"></a>';
	});
});
</script>
{include file='footer.tpl'}
