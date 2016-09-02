<?php
/*
Template: Electronic Signature Loan Amount
Description: Page that the customer decides tha loan amoun, starts with validating previous credentials
*/
?>
<?php
	include(TEMPLATES_PATH.'confirmation.func.php'); 
	include 'cms-includes/recaptchalib.php';

	if(!isset($_SESSION)) session_start();
	$application = $_SESSION['application'];

	// make sure data is available
	missingDataRedirect($application);
	// Make sure the loan is in the (disagree, prospect confirmed, confimed decline, pending and declined) statuses
	invalidStatusRedirect($application);
	
	// setup captcha first
	$resp = recaptcha_check_answer (lum_getString("[CAPTCHA_SERVER_KEY]"),
		$_SERVER["REMOTE_ADDR"],
		$_POST["recaptcha_challenge_field"],
		$_POST["recaptcha_response_field"]);

	if (((preg_replace("/[^0-9,.]/", "", $_POST['ssn']) != $application['ssn']) || ($_POST['date_of_birth'] != $application['dob']) || !$resp->is_valid)
		&&
		((preg_replace("/[^0-9,.]/", "", $_SESSION['ssn']) != $application['ssn']) || ($_SESSION['date_of_birth'] != $application['dob'])))
	{
		// set login lock for bad access
		setLoginLock($application['application_id']);
		lum_redirect("/".getCurrentLanguage()."/validation_failed");
	}
	// make sure the application isn't locked out
	checkLoginLock($application['application_id']);
	
	// Passed validation check, make call to see where we are in the page flow, and if the amount is set yet
	setCurrentPage($application['application_id'],'esig_confirm_amount');
	
	if ($application['is_react'] == 'yes') {
		$low_value = lum_getString("[LOAN_AMOUNT_LOW_REACT]");
		$hi_value = lum_getString("[LOAN_AMOUNT_HIGH_REACT]");
	} else {
		$low_value = lum_getString("[LOAN_AMOUNT_LOW]");
		$hi_value = lum_getString("[LOAN_AMOUNT_HIGH]");
	}

?>
<?php include(TEMPLATES_PATH.'confirmation_header_html.php'); ?>
<div id="normal_page">
[cmsinclude Header]
	<div class="content">
		<div class="wrapper">
			<div class="home_bottom clearfix ">
				<br/>
				<h3>[cmstext Electronic Signature PreStep Placeholder]</h3>
				<h2 class="fs-title">[cmstext Choose A Loan Placeholder]</h2>
				[cmstext Now Placeholder] <?= ucwords(strtolower($application['name_first']." ".$application['name_last']));?> [cmstext chose you loan amount Placeholder]<br/>
				<div class="left_column">
					<div class="confirm">
						<p>[cmstext Your were qualified Placeholder] $<?php echo $application['fund_qualified'];?>.</p>
						<p>[cmstext select the exact loan amount Placeholder]</p>
						<p>[cmstext hit no thanks Placeholder]</p>
					</div>
				</div>
				<div class="right_column">
					<form class="validate_form" id="application_form" method="post" action="/<?= getCurrentLanguage();?>/esig_confirm_documents">
						<div class="pretty_select full_space loan_amount">
							<select id="loan_amount" name="loan_amount" class="validate[required] fieldset select-input" type="select-one">
								<option value="">[cmstext Select Placeholder]</option>
								<?php
									for ($i = min(max(($application['fund_qualified']*1),($application['fund_actual']*1)),$hi_value); $i >= $low_value; $i=$i-50) {
										echo "<option value=".$i.">$".$i.".00</option>\n";
									} 
								?>								
							</select>
						</div>
						<button type="submit" name="submit" class="submit action-button right_side" value="Submit" >[cmstext Submit Placeholder]</button>
						<button type="button" name="no_thanks" class="no_thanks action-button left_side" value="No Thanks" >[cmstext No Thanks Placeholder]</button>
					</form>
					<form method="post" action="/<?= getCurrentLanguage();?>/esig_confirm_decline" id="form_decline" name="form_decline">
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
