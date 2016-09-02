<?php
/*
Template: Electronic Signature Complete
Description: Page that submits the customer signature
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

	// Make sure signature and ip match
	if ((strtoupper($_POST['name_esig']) != strtoupper($application['name_first']." ".$application['name_last']))
		|| (md5(strtoupper($_POST['name_esig'])) != $_POST['test_string'])
		|| ($_SERVER['REMOTE_ADDR'] != $_POST['ip_address']))
	{
		$_SESSION['error_code'] = 'Electronic signature did not match';
		lum_redirect("/".getCurrentLanguage()."/system_error");
	}
	$app_id = $application['application_id'];
	
	// submit esignature
	$request['customer_decline'] = FALSE;
	$request['client_ip_address'] = $_SERVER['REMOTE_ADDR'];
	$request['application_id'] = $app_id;
	$request['esignature'] = strtoupper($_POST['name_esig']);
	foreach( $_POST as $key => $val) {
		if (!(strpos($key,'legal_approve_docs') === false)){
			$request[$key] = 'TRUE';
		}
	}
		
	$client = getSoapClient();
	
	$result = $client->submitPage($request);
	
	$response = BuildGenericPageResponse($result);
	
	CheckResponseForError($response);
	
	$response = setCurrentPage($app_id,'esig_confirm_complete'); 
	
	// Get final application data and status
	$application = getApplication($app_id);

	setSession($application);
	
	$_SESSION['application'] = $application;
?>
<?php include(TEMPLATES_PATH.'confirmation_header_html.php'); ?>
<div id="normal_page">
[cmsinclude Header]
	<div class="content">
		<div class="wrapper">
			<div class="home_bottom clearfix ">
				<br/>
				<h3>[cmstext Loan Approval Placeholder]</h3>
				<h2 class="fs-title">[cmstext Your Loan Approved Placeholder]</h2>
				[cmstext Welcom Placeholder] <?= ucwords(strtolower($application['name_first']." ".$application['name_last']));?>, [cmstext your loan for Placeholder] $<?= $response->qualify_info->loan_amount?> [cmstext should be in your bank account Placeholder] <?= date($application['date_fund_estimated']);?><br/>
				<p>[cmstext We will be sending Placeholder].</p>
			</div>
		</div>
	</div>
[cmsinclude Footer]
</div>
<?php include(TEMPLATES_PATH.'footer_html.php');?>
