<?php
/*
Template: Electronic Signature Confirmation Step 1
Description: Page that gives the user the credential questions for the electronic signature
*/
?>
<?php
	include 'cms-includes/recaptchalib.php';
	include(TEMPLATES_PATH.'confirmation.func.php'); 

	if(!isset($_SESSION)) session_start();
	
	if (isset($_SESSION['language']) && (getCurrentLanguage() != $_SESSION['language'])){
		$url = $_SERVER['REQUEST_URI'];
		if (substr($url,0,4) == '/'.getCurrentLanguage().'/') $url = substr($url,4);
		lum_redirect('/'.$_SESSION['language'].'/'.$url);
	}

	// Check and make sure that the application id and hash key match or redirect to unavailable
	$confirm_string = lum_getString("[ESIG_HASH_KEY]");
	$app_id =  base64_decode(urldecode($_REQUEST['application_id']));
	if (!(md5($app_id.$confirm_string)==$_REQUEST['login'])) {
		// set login lock for bad access
		setLoginLock($app_id);
		lum_redirect("/".getCurrentLanguage()."/loan_not_available");
	}
	// make sure the application isn't locked out

        checkLoginLock($app_id);
	// Request application data and status
	$application = getApplication($app_id);

	setSession($application);
	// make sure data is available
	missingDataRedirect($application);
	// Make sure the loan is in the (disagree, prospect confirmed, confimed decline, pending and declined) statuses
	invalidStatusRedirect($application);
	
	// if the redirect is fresh, or redirect time is -1, supply the credentials and skip to next step
	if (((time() - strtotime($application['date_created'])) < (60* lum_getString("[REDIRECT_TIME_LIMIT_MINUTES]"))) || (lum_getString("[REDIRECT_TIME_LIMIT_MINUTES]") == -1)){
		if(!isset($_SESSION)) session_start();
		$_SESSION['ssn'] = $application['ssn'];
		$_SESSION['date_of_birth'] = $application['dob'];
		lum_redirect("/".getCurrentLanguage()."/esig_confirm_amount");
	} else {
		$_SESSION['ssn'] = false;
		$_SESSION['date_of_birth'] = false;
	}
?>
<?php include(TEMPLATES_PATH.'confirmation_header_html.php'); ?>
<div id="normal_page">
[cmsinclude Header]
	<div class="content">
		<div class="wrapper">
			<div class="home_bottom clearfix ">
				<br/>
				<h3>[cmstext Electronic Signature Step 1]</h3>
				<h2 class="fs-title">[cmstext Identification Validation Placeholder]</h2>
				[cmstext Hello Placeholder] <?= ucwords(strtolower($application['name_first']." ".$application['name_last']));?>[cmstext complete your loan Placeholder]<br/>
				<div class="left_column left_confirm">
					<div class="confirm">
						<p>[cmstext the first step Placeholder]</p>
						<p>[cmstext supply the information for validation Placeholder]</p>
						<p>[cmstext hit no thanks Placeholder]</p>
					</div>
				</div>
				<div class="right_column right_confirm">
					<form method="post" action="/<?=getCurrentLanguage()?>/esig_confirm_amount" id="application_form" name="application_form" class="validate_form">
						<input id="dob_datepicker" name="date_of_birth" type="text" placeholder="[cmstext DOB Placeholder]" class="dob validate[required,custom[date]] fieldset datepicker text-input"/>
						<input id="ssn" name="ssn" maxlength="11" type="text" placeholder="[cmstext SSN Placeholder]" class="ssn validate[required,custom[ssn]] fieldset text-input"/>
						<div id="captcha" name="captcha" class="captcha">
							<?= recaptcha_get_html(lum_getString("[CAPTCHA_SITE_KEY]"),null,true); ?>
						</div>
						<button type="button" name="no_thanks" class="no_thanks action-button left_side" value="No Thanks" >[cmstext No Thanks Placeholder]</button>
						<button type="submit" name="submit" class="submit action-button right_side" value="Submit" >[cmstext Submit Placeholder]</button>
					</form>
					<form method="post" action="/<?=getCurrentLanguage()?>/esig_confirm_decline" id="form_decline" name="form_decline">
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
