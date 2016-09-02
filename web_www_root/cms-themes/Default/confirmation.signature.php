<?php
/*
Template: Electronic Signature Final Step
Description: Page that the customer actually signs
*/
?>
<?php
	include(TEMPLATES_PATH.'confirmation.func.php'); 
	
	if(!isset($_SESSION)) session_start();
	$application = $_SESSION['application'];

	// make sure data is available
	missingDataRedirect($application);
	// Make sure the loan is in the (disagree, prospect confirmed, confimed decline, pending and declined) statuses
	invalidStatusRedirect($application);
	// make call to see where we are in the page flow, and make sure the amount is set yet
	$response = setCurrentPage($application['application_id'],'esig_confirm_documents');
?>
<?php include(TEMPLATES_PATH.'confirmation_header_html.php'); ?>
<div id="normal_page">
[cmsinclude Header]
	<div class="content">
		<div class="wrapper">
			<div class="home_bottom clearfix ">
				<br/>
				<h3>[cmstext Electronic Signature Final Step Placeholder]</h3>
				<h2 class="fs-title">[cmstext Electronic Signature Placeholder]</h2>
				[cmstext And finally Placeholder] <?= ucwords(strtolower($application['name_first'])).", ";?>[cmstext supply your signature Placeholder]<br/>
				<div class="left_column">
					<div class="confirm">
						<p>[cmstext Your electronic signature means Placeholder] <a href="/<?=getCurrentLanguage()?>/loan_document" target='_blank'>[cmstext Loan Document Link Text]</a>, <a href="/<?=getCurrentLanguage()?>/electronic_consent" target='_blank'>[cmstext Electronic Consent Link Text]</a> [cmstext and] <a href="/<?=getCurrentLanguage()?>/privacy_policy" target='_blank'>[cmstext Privacy Policy Link Text]</a> [cmstext on the previous page].</p>
						<p>[cmstext We are storing your IP Address Placeholder] <?= $_SERVER['REMOTE_ADDR'];?></p>
						<p>[cmstext Place your name Placeholder]</p>
						<p>[cmstext hit no thanks Placeholder]</p>
					</div>
				</div>
				<div class="right_column">
					<form class="validate_form" id="application_form" method="post" action="/<?=getCurrentLanguage()?>/esig_confirm_complete">
<?php
foreach ($_POST as $key => $val){
	if ($val == 'on'){
		echo'
						<input type="hidden" id="'.$key.'" name="'.$key.'" value="TRUE" />';
	}
}
?>
						<input type="hidden" name="ip_address" id="ip_address" value="<?= $_SERVER['REMOTE_ADDR'];?>" />
						<input type="hidden" name="test_string" id="test_string" value="<?=md5(strtoupper($application['name_first']." ".$application['name_last']));?>" />
						<input id="name_esig" name="name_esig" type="text" placeholder="[cmstext Full Name Placeholder]" class="esig validate[required] fieldset text-input"/>
						<button type="submit" name="submit" class="submit action-button right_side most_space dbl_tall" value="Submit" >[cmstext Submit Placeholder]</button>
						<button type="button" name="no_thanks" class="no_thanks action-button left_side some_space dbl_tall" value="No Thanks" >[cmstext No Thanks Placeholder]</button>
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
