<?php
/*
Template: View Loan Documents
Description: Page that the customer can view the loan documents
*/
?>
<?php
	include(TEMPLATES_PATH.'confirmation.func.php'); 
	
	if(!isset($_SESSION)) session_start();
	$application = $_SESSION['application'];

	// make sure data is available
	missingDataRedirect($application);
	
	$request = $application['application_id'];
	$client = getSoapClient();
	
        if (in_array($application['status'],
                array('disagree::prospect::*root',
                'confirmed::prospect::*root',
                'confirm_declined::prospect::*root',
                'pending::prospect::*root',
                'declined::prospect::*root'))) {
		$result = $client->getLoanDocumentPreview($request);
	} else {
		$result = $client->getLoanDocument($request);
	}
	
	$response = BuildGenericPageResponse($result);
	
        CheckResponseForError($response);

	if (!isset($response) || !isset($response->result) || !isset($response->result['document'])){
		$_SESSION['error_code'] = 'Missing documents from soap response';
		lum_redirect("/".getCurrentLanguage()."/system_error");
	}
?>
<?php include(TEMPLATES_PATH.'document_header_html.php'); ?>
<div id="normal_page">
	<div class="content">
		[cmstext Loan Document Label Placeholder]
		<div class="wrapper">
			<?= $response->result['document'];?>
		</div>
	</div>
</div>
<?php include(TEMPLATES_PATH.'footer_html.php');?>
