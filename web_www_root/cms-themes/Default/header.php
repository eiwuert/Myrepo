<?php
/*
Template: (Include) Header
Description: The header for the web site
*/
?>
	<div id="header">
		<div class="wrapper clearfix">
			<div class="lang">
				<?php include(TEMPLATES_PATH.'lang.inc.php');?>
				<input id="user_language" name="user_language" type="hidden" value="<?= CMS_LANGUAGE;?>"/>
			</div>
			<div class="login">
<?php
	if ((!isset($_COOKIE[lum_getString("[SESSION_NAME]")])) ||
	    (empty($_COOKIE[lum_getString("[SESSION_NAME]")])) ||
	    (!isset($_SESSION['application']))){
?>
				[cmstext Already a Member] | 
				<div class="link" href="#">[cmstext Log In]
					<form class="login_drop" method="post" action="/<?= CMS_LANGUAGE;?>/login_customer">
						<input type="text" placeholder="[cmstext Username Placeholder]:" class="user" name="username"/>
						<input type="password" placeholder="[cmstext Password Placeholder]:" class="pass" name="credential"/>
						<p>[cmstext Forgot your] <a href="/<?= CMS_LANGUAGE;?>/request_username">[cmstext Username]</a> | <a href="/<?= CMS_LANGUAGE;?>/request_credential">[cmstext Password]</a>?</p>
						<input type="submit" class="button" value="Log In" />
					</form>
				</div>
<?php	} else { ?>
				<a href="/<?= CMS_LANGUAGE;?>/logout_customer">[cmstext Logout]</a> | <a href="/<?= CMS_LANGUAGE;?>/reset_password">[cmstext Reset Password]</a>
<?php 	} ?>
			</div>
			<a href="/<?= CMS_LANGUAGE;?>" class="logo"><img src="<?php lum_getThemeImageUrl();?>/logo.png" alt="MultiLoan Source" /></a>
			<ul id="navigation">
				<?php include(TEMPLATES_PATH.'nav.inc.php'); ?>
			</ul>
		</div>
	</div>