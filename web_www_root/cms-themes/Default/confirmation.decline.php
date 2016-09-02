<?php
/*
Template: Decline the Loan
Description: Page that declines or disagrees an application
*/
?>
<?php
	include(TEMPLATES_PATH.'confirmation.func.php'); 
	function BuildSoapResponse($vapi_response){
		$soap_response = new StdClass();
		$soap_response->signature = new StdClass();
		$soap_response->content = new StdClass();
		$soap_response->content->section = new StdClass();
		$soap_response->errors = new StdClass();
	
		if (!isset($vapi_response->outcome)){
			$soap_response->signature->data = 'system_error';
			$soap_response->errors->data = 'SOAP system error. ';
		} elseif ($vapi_response->outcome == 1) {
			$soap_response->signature->data = 'success';
			$soap_response->application = $vapi_response->result;
		} else {
			$soap_response->signature->data = 'app_error';
			$soap_response->errors->data = "An error occurred processing the requested application. \n".
				(is_array($vapi_response->error) ? implode("\n", $vapi_response->error) : $vapi_response->error);
		}
		return $soap_response;
	}

	if(!isset($_SESSION)) session_start();
	$application = $_SESSION['application'];

	// make sure data is available
	missingDataRedirect($application);
	// Make sure the loan is in the (disagree, prospect confirmed, confimed decline, pending and declined) statuses
	invalidStatusRedirect($application);
	
	// set the customer decline page
	$request = array('customer_decline' => true,
			 'application_id' => $application['application_id']);
	
	$client = getSoapClient();
	
	$response_xml = $client->submitPage($request);
	
	$response = BuildSoapResponse($response_xml);

	$_SESSION['application'] = $response->application;

?>
<?php include(TEMPLATES_PATH.'confirmation_header_html.php'); ?>
[cmsinclude Header]
<div id="normal_page">
	<div class="content">
		<div class="wrapper">
			<div class="home_bottom clearfix ">
				<br/>
				<h3>[cmstext Electronic Signature]</h3>
				<h2 class="fs-title">[cmstext Loan Declined]</h2>
				<p>[cmstext Hello Placeholder] <?= ucwords(strtolower($application['name_first']." ".$application['name_last']));?>[cmstext your loan has been declined]</p>
				<p>[cmstext contact us again]</p>
			</div>
		</div>)
	</div>
</div>
[cmsinclude Footer]
<?php include(TEMPLATES_PATH.'footer_html.php');?>
