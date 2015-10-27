{include file='header.tpl' title='Message' show_back=true body_class='has-bottom-bar'}
<nav class="navbar navbar-toolbar navbar-static-top hidden-xs">
	<div class="container-fluid">
		<div class="navbar-header">
			<a id="history_back" class="navbar-brand" href="javascript:history.go(-1);"><i class="fa fa-long-arrow-left"></i>&nbsp;{t}Back{/t}</a>
		</div>
		<ul class="nav navbar-nav navbar-right">
			{if isset($node)}
				{if $support_log}
					<li><a href="?page=log&id={$mail->id}&node={$node}&type={$type}"><i class="fa fa-file-text-o"></i>&nbsp;{t}Text log{/t}</a></li>
				{/if}
				{if $type == 'queue'}
					<li><a href="?page=download&id={$mail->id}&node={$node}"><i class="fa fa-download"></i>&nbsp;{t}Download{/t}</a></li>
					<li class="divider"></li>
					<li><a data-action="delete"><i class="fa fa-trash-o"></i>&nbsp;{t}Delete{/t}</a></li>
					<li><a data-action="bounce"><i class="fa fa-mail-reply"></i>&nbsp;{t}Bounce{/t}</a></li>
					<li><a data-action="retry"><i class="fa fa-mail-forward"></i>&nbsp;{if $mail->msgaction=='QUARANTINE'}{t}Release{/t}{else}{t}Retry{/t}{/if}</a></li>
				{/if}
			{/if}
		</ul>
	</div>
</nav>
{if isset($node) and ($support_log or $type == 'queue')}
<nav class="navbar navbar-default navbar-fixed-bottom visible-xs" id="bottom-bar">
	<div class="container-fluid">
		<ul class="nav navbar-nav">
			<li class="dropdown">
				<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">{t}Actions{/t} <span class="caret"></span></a>
				<ul class="dropdown-menu" role="menu">
					{if $support_log}
						<li><a href="?page=log&id={$mail->id}&node={$node}&type={$type}"><i class="fa fa-file-text-o"></i>&nbsp;{t}Text log{/t}</a></li>
					{/if}
					{if $type == 'queue'}
						<li><a href="?page=download&id={$mail->id}&node={$node}"><i class="fa fa-download"></i>&nbsp;{t}Download{/t}</a></li>
						<li class="divider"></li>
						<li><a data-action="delete"><i class="fa fa-trash-o"></i>&nbsp;{t}Delete{/t}</a></li>
						<li><a data-action="bounce"><i class="fa fa-mail-reply"></i>&nbsp;{t}Bounce{/t}</a></li>
						<li><a data-action="retry"><i class="fa fa-mail-forward"></i>&nbsp;{if $mail->msgaction=='QUARANTINE'}{t}Release{/t}{else}{t}Retry{/t}{/if}</a></li>
					{/if}
				</ul>
			</li>
		</ul>
	</div>
</nav>
{/if}
<div class="container-fluid">
	<div class="row">
		<div class="col-md-5 col-md-push-7">
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title">{t}Details{/t}</h3>
				</div>
				<div class="panel-body">
					<dl class="dl-horizontal">
						<dt>{t}Action{/t}</dt><dd>
							<span class="fa-stack" style="font-size: 9px;">
								<i class="fa fa-square fa-stack-2x" style="color: {$action_color};"></i>
								<i class="fa fa-lg fa-{$action_icon} fa-stack-1x" style="color:#fff;"></i>
							</span>
							{$mail->msgaction}
						</dd>
						<dt>{t}From{/t}</dt><dd class="wrap">{$mail->msgfrom|escape|emptyspace}</dd>
						<dt>{t}To{/t}</dt><dd class="wrap">{$mail->msgto|escape|emptyspace}</dd>
						<dt>{t}Date{/t}</dt><dd>{$time|date_format:"%Y-%m-%d %H:%M:%S"}</dd>
						<dt>{t}Size{/t}</dt><dd class="wrap" title="{$mail->msgsize} bytes">{$mail->msgsize|format_size}</dd>
						<dt>{t}Details{/t}</dt>
						<dd>
						{if $mail->msgaction == 'QUEUE'}
							{t retry=$mail->msgretries}In queue (retry %1){/t}<br><span class="text-muted">{$mail->msgerror|escape}</span>
						{else}
							{$mail->msgdescription|escape|emptyspace}
						{/if}
						</dd>
						{if $listener}<dt>{t}Received by{/t}</dt><dd>{$listener}</dd>{/if}
						<dt>{t}Server{/t}</dt><dd>{$mail->msgfromserver|escape|emptyspace}</dd>
						{if $mail->msgsasl}<dt>{t}User{/t}</dt><dd>{$mail->msgsasl|escape}</dd>{/if}
						{if $transport}<dt>{t}Destination{/t}</dt><dd>{$transport}</dd>{/if}
						<dt>ID</dt><dd>{$mail->msgid}</dd>
					</dl>
				</div>
			</div>
			{if $scores}
			<div class="panel panel-default hidden-xs">
				<div class="panel-heading">
					<h3 class="panel-title">{t}Scores{/t}</h3>
				</div>
				<table class="table">
					<thead>
						<tr>
							<th>{t}Engine{/t}</th>
							<th>{t}Result{/t}</th>
							<th class="hidden-xs">{t}Signature{/t}</th>
						</tr>
					</thead>
					<tbody>
					{foreach $scores as $score}
						<tr>
							<td>{$score.name}</td>
							<td>{$score.score}</td>
							<td class="text-muted hidden-xs wrap">{$score.text}</td>
						</tr>
					{foreachelse}
						<tr>
							<td colspan="3" class="text-muted text-center">{t}No Scores{/t}</td>
						</tr>
					{/foreach}
					</tbody>
				</table>
			</div>
			{/if}
		</div>
		<div class="col-md-7 col-md-pull-5">
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title" style="white-space: nowrap;">{if $mail->msgsubject}{$mail->msgsubject|escape}{else}<span class="text-muted">{t}No Subject{/t}</span>{/if}</h3>
				</div>
				{if ! isset($body)}
					<div class="panel-body msg-body"><p class="text-muted text-center">{t}Content unavailable{/t}<br><small>{t}Message is not in queue or quarantine{/t}</small></p></div>
				{elseif $encode == 'TEXT'}
					<pre class="panel-body msg-body">{$body}</pre>
				{elseif $encode == 'HTML'}
					<div class="panel-body msg-body">{$body}</div>
				{/if}
				{if $attachments}
				<div class="panel-footer">
					<ul class="list-inline">
						{foreach $attachments as $attachment}
							<li class="nowrap">
								<i class="fa fa-{$attachment.icon}"></i>
								{$attachment.name|escape}&nbsp;<small class="text-muted">({$attachment.size|format_size})</small>
							</li>
						{/foreach}
					</ul>
				</div>
				{/if}
			</div>
			{if (isset($body) && $encode == 'HTML') or $show_text}
				<p style="float:right;">
				{if $encode == 'HTML'}
					{t escape=no url=$show_text_link}Switch to <a href="%1">plain text</a> version{/t}
				{/if}
				{if $show_text}
					{t escape=no url=$show_html_link}Switch to <a href="%1">HTML</a> version{/t}
				{/if}
				</p>
				<br style="clear:both">
			{/if}
			{if $header}
			<div class="panel panel-default">
				<div class="panel-heading">
					<div class="pull-right preview-headers-legend">
						<div style="background-color: #ddffdd; border: 1px solid #ccc;"></div>
						<p style="color: green;">{t}Added{/t}</p>
						<div style="background-color: #ffdddd; border: 1px solid #ccc;"></div>
						<p style="color: red;">{t}Removed{/t}</p>
					</div>
					<h3 class="panel-title">{t}Headers{/t}</h3>
				</div>
				<div class="panel-body preview-headers-container">
					<div class="preview-headers wrap" id="preview-headers-go-here"></div>
				</div>
			</div>
			<script>
				var headers_original = {$header};
				var headers_modified = {$headerdelta};
				$("#preview-headers-go-here").html(diff_lineMode(headers_original,
					headers_modified ? headers_modified : headers_original, true));
			</script>
			{/if}
		</div>
	</div>
	{if isset($node)}
	<form id="actionform" method="post" action="?page=preview&node={$node}&id={$mail->id}">
		<input type="hidden" name="action" id="action" value="">
		<input type="hidden" name="referer" id="referer" value="{$referer|escape}">
	</form>
	{/if}
</div>
{include file='footer.tpl'}
