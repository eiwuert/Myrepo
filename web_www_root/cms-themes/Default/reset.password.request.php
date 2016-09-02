<?php
/*
Template: Reset Password Requests
Description: Page that a user uses to reset thier password
*/
?>
<?php
	include(TEMPLATES_PATH.'customer.func.php');
	$application = getSession();
?>
<?php	include(TEMPLATES_PATH.'credential_header_html.php'); ?>
<div id="normal_page">
[cmsinclude Header]
	<div class="content">
		<div class="wrapper">
			<div class="home_bottom clearfix ">
				<br/>
				<h3>[cmstext Reset Password Placeholder]</h3>
				<h2 class="fs-title">[cmstext Supply New Strong Password Placeholder]</h2>
				<div class="left_column left_confirm">
					<div class="confirm">
						<p>[cmstext We suggest you use a strong password Placeholder]</p>
						<p>[cmstext use number with upper and lower case letters Placeholder]</p>
						<p>[cmstext hit submit Placeholder]</p>
					</div>
				</div>
				<div class="right_column right_confirm">
					<form class="validate_form password_form" id="application_form" method="post" action="/<?=lum_getCurrentLanguage()?>/credential_submit">
						<input id="user_string1" type="password" name="user_string1" value="" placeholder="[cmstext New Password Placeholder]" class="validate[required] fieldset text-input"/>
						<input id="user_string2" type="password" name="user_string2" value=""  placeholder="[cmstext New Password Copy Placeholder]" class="validate[confirm[user_string1]] fieldset text-input"/>
						<button type="submit" name="submit" class="submit action-button full_space" value="Submit" >[cmstext Submit Placeholder]</button>
					</form>
				</div>
			</div>
		</div>
	</div>
[cmsinclude Footer]
</div>
<table id="waiting_page_fbl"><tbody><tr><td id="waiting_page_td">
	<div class="waiting_page hide">
		<p class="waiting_text">[cmstext Please Wait]</p>
	</div>
</td></tr></tbody></table>
<?php include(TEMPLATES_PATH.'footer_html.php');?>
