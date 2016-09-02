<?php
/*
Template: Forgot Password Requests
Description: Page that a user submits credentials when they forget thier password
*/
?>
<?php
	include 'cms-includes/recaptchalib.php';
	include(TEMPLATES_PATH.'confirmation.func.php'); 
	include(TEMPLATES_PATH.'confirmation_header_html.php'); ?>
<div id="normal_page">
[cmsinclude Header]
	<div class="content">
		<div class="wrapper">
			<div class="home_bottom clearfix ">
				<br/>
				<h3>[cmstext Forgot Password Placeholder]</h3>
				<h2 class="fs-title">[cmstext Identification Validation Placeholder]</h2>
				<div class="left_column left_confirm">
					<div class="confirm">
						<p>[cmstext to retrive your user name Placeholder]</p>
						<p>[cmstext please supply the credetials to the left Placeholder]</p>
						<p>[cmstext hit submit Placeholder]</p>
					</div>
				</div>
				<div class="right_column right_confirm">
					<form class="validate_form" id="application_form" method="post" action="/<?=lum_getCurrentLanguage()?>/send_credential">
						<input id="dob_datepicker" name="date_of_birth" type="text" placeholder="[cmstext DOB Placeholder]" class="dob validate[required,custom[date]] fieldset datepicker text-input"/>
						<input id="ssn" name="ssn" maxlength="11" type="text" placeholder="[cmstext SSN Placeholder]" class="ssn validate[required,custom[ssn]] fieldset text-input"/>
						<div id="captcha" name="captcha" class="captcha">
							<?= recaptcha_get_html(lum_getString("[CAPTCHA_SITE_KEY]"),null,true); ?>
						</div>
						<button type="submit" name="submit" class="submit action-button right_side full_space" value="Submit" >[cmstext Submit Placeholder]</button>
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
