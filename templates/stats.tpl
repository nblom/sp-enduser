{capture assign="title"}{t}Statistics{/t}{/capture}
{include file='header.tpl' title=$title page_active='stats'}
<div class="container" id="panel-container">
	<div class="btn-group">
		<button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
			{t}Add...{/t} <span class="caret"></span>
		</button>
		<ul class="dropdown-menu" role="menu">
			<li><a href="#" class="add-all">{t}All{/t}</a></li>
			<li class="divider"></li>
			{foreach $domains as $domain}
				<li><a href="#" data-domain="{$domain}" class="add-domain">{$domain}</a></li>
			{/foreach}
		</ul>
	</div>
	{if count($domains) > 5}
	<span class="text-muted pull-right many-domains">
		{t}Because you have more than 5 domains, you need to choose them specifically.{/t}
	</span>
	{/if}
	<br><br>
	<div class="panel panel-default template" style="display:none">
		<div class="panel-heading">
			<h3 class="panel-title">
				<span></span>
				<button type="button" class="close"><span>&times;</span></button>
			</h3>
		</div>
		<div class="panel-body draw-charts">
			<div class="row"><div class="col-md-6">
				<div class="rrd-id" style="height:200px;display:none;"></div>
				<div class="realrrd" style="height:200px"></div>
			</div><div class="col-md-6">
				<div class="pie" style="height:200px"></div>
				<div class="since text-muted pull-right">{t}Loading...{/t}</div>
			</div></div>
		</div>
	</div>
</div>
{include file='footer.tpl'}
