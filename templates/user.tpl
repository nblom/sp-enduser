{capture assign="title"}{t}Account{/t}{/capture}
{include file='header.tpl' title=$title page_active='user'}
<div class="container">
	<div class="col-md-6">
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">{t}Permissions{/t}</h3>
			</div>
			<div class="panel-body">
				{if not $access_mail and not $access_domain}
					<p>
						{t}You have no restrictions, you may view messages to/from any domain.{/t}
					</p>
				{else}
					<p>
					{t}You are authorized to view messages sent from/to the following{/t}
					{if $access_domain}
						{t}domains:{/t}</p>
						<ul>
							{foreach $access_domain as $access} 
								<li>{$access|escape}</li>
							{/foreach}
						</ul>
						{if $access_mail}
							<p>{t}And the following users:{/t}</p>
						{/if}
					{else}
						{t}users:{/t}</p>
					{/if}
					<ul>
						{foreach $access_mail as $access} 
							<li>{$access|escape}</li>
						{/foreach}
					</ul>
				{/if}
			</div>
		</div>
	</div>
	<div class="col-md-6">
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">{t}Change password{/t}</h3>
			</div>
			<div class="panel-body">
				{if $password_changed}
					<div class="alert alert-success">{t}Password changed{/t}</div>
				{/if}
				{if $error}
					<div class="alert alert-danger">{$error|escape}</div>
				{/if}
				{if $password_changeable}
					<form class="form-horizontal" method="post" action="?page=user">
						<div class="form-group">
							<label class="col-sm-3 control-label" for="old_password">{t}Old Password{/t}</label>
							<div class="col-sm-9">
								<input type="password" class="form-control" name="old_password" id="old_password" placeholder="Your old password">
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label" for="password">{t}New Password{/t}</label>
							<div class="col-sm-9">
								<input type="password" class="form-control" name="password" id="password" placeholder="Your new password">
								<input type="password" class="form-control" name="password2" id="password2" placeholder="And again, just to be sure">
							</div>
						</div>
						<div class="form-group">
							<div class="col-sm-offset-2 col-sm-10">
								<button type="submit" class="btn btn-primary">{t}Change{/t}</button>
							</div>
						</div>
					</form>
				{else}
					<p>
						{t}You are authenticated externally, so password changes are not done here.{/t}
					</p>
				{/if}
			</div>
		</div>
	</div>
</div>
{include file='footer.tpl'}
