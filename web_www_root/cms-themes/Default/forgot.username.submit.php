<?php
/*
Template: Forgot Username Retriever
Description: Page that the customer submits a forgotten username
*/
?>
<?php
	include(TEMPLATES_PATH.'confirmation.func.php'); 
	include 'cms-includes/recaptchalib.php';

	// setup captcha first
	$resp = recaptcha_check_answer (lum_getString("[CAPTCHA_SERVER_KEY]"),
		$_SERVER["REMOTE_ADDR"],
		$_POST["recaptcha_challenge_field"],
		$_POST["recaptcha_response_field"]);

	if ((!$resp->is_valid) || !isset($_POST['ssn']) || !isset($_POST['date_of_birth'])) {
		lum_redirect("/".lum_getCurrentLanguage()."/validation_failed");
	}
	
	$client = getSoapClient();
	
	$result = $client->emailUsername(str_replace('-','',$_POST['ssn']),$_POST['date_of_birth']);
	
	$response = BuildGenericPageResponse($result);
	
	$response = CheckSoapForError($result,'validation_failed');
?>
<?php include(TEMPLATES_PATH.'confirmation_header_html.php'); ?>
<div id="normal_page">
[cmsinclude Header]
	<div class="content">
		<div class="wrapper">
			<div class="home_bottom clearfix ">
				<br/>
				<h3>[cmstext Forgot Username Placeholder]</h3>
				<h2 class="fs-title">[cmstext Email Sent Placeholder]</h2>
				[cmstext your username has been emailed Placeholder]<br/><br/><br/>
			</div>
		</div>
	</div>
[cmsinclude Footer]
</div>
<?php include(TEMPLATES_PATH.'footer_html.php');?>
