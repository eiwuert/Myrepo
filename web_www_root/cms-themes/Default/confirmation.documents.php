<?php
/*
Template: Electronic Signature Documents
Description: Page that the customer previews documents
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

	// check status to see if we are still waiting for loan amount, submit it
	if ($response->page_name != 'ent_online_confirm_legal') {
		if (!isset($_POST['loan_amount']) && ($_POST['loan_amount']>0)) {
			$_SESSION['error_code'] = 'Missing Loan Amount error';
			lum_redirect("/".getCurrentLanguage()."/system_error");
		}
		// Request application loan documents
		$request = array('customer_decline' => false,
				 'client_ip_address' => $_SERVER['REMOTE_ADDR'],
				 'application_id' => $application['application_id'],
				 'fund_amount' => $_POST['loan_amount']
				 );
		
		$client = getSoapClient();
		
		$result = $client->submitPage($request);
		
		$response = BuildGenericPageResponse($result);
		
		switch($response->signature->data){
			case 'success':
				// Do nothing all good
				break;
			case 'app_error':
			case 'system_error':
				$_SESSION['error_code'] = $response->errors->data;
				lum_redirect("/".getCurrentLanguage()."/system_error");
				break;
			default:
				$_SESSION['error_code'] = 'Unknown soap resonse error';
				lum_redirect("/".getCurrentLanguage()."/system_error");
				break;
		}
		// make call to see where we are in the page flow
		//  it should return the list of documents make sure we get them
		$response = setCurrentPage($application['application_id'],'esig_confirm_documents');
	}
	
	if (!isset($response) || !isset($response->documents)){
		$_SESSION['error_code'] = 'Missing documents from soap response';
		lum_redirect("/".getCurrentLanguage()."/system_error");
	}
	
	$doc_ary = array('application' 	=> array('chk_text' 	=> '[cmstext I have read terms application text Placeholder]',
						 'link_name' 	=> 'loan_document#application',
						 'link_text' 	=> '[cmstext Application link Placeholder]'),
			 'privacy' 	=> array('chk_text' 	=> '[cmstext I have read terms privacy text Placeholder]',
						 'link_name' 	=> 'privacy-policy',
						 'link_text' 	=> '[cmstext Privacy link Placeholder]'),
			 'electronic' 	=> array('chk_text' 	=> '[cmstext I have read terms electronic text Placeholder]',
						 'link_name' 	=> 'electronic_consent',
						 'link_text' 	=> '[cmstext Electronic link Placeholder]'),
			 'auth' 	=> array('chk_text' 	=> '[cmstext I have read terms authorization text Placeholder]',
						 'link_name' 	=> 'loan_document#auth_agreement',
						 'link_text' 	=> '[cmstext Authorization link Placeholder]'),
			 'tribal' 	=> array('chk_text' 	=> '[cmstext I consent to tribal jurisdiction Placeholder]',
						 'link_name' 	=> 'loan_document#tribal_jurisdiction_consent',
						 'link_text' 	=> '[cmstext Tribal Jurisdiction Consent link Placeholder]'),
			 'loannote' 	=> array('chk_text' 	=> '[cmstext I have read terms loan note text Placeholder]',
						 'link_name' 	=> 'loan_document#loan_note_and_disclosure',
						 'link_text' 	=> '[cmstext Loan Note link Placeholder]'),
			 );
	
	$doc_list = array();
	foreach ($response->documents as $doc){
		foreach ($doc->tokens as $key => $val){
			$doc_list[] = $key;
		}
	}	
?>
<?php include(TEMPLATES_PATH.'confirmation_header_html.php'); ?>
<div id="normal_page">
[cmsinclude Header]
	<div class="content">
		<div class="wrapper">
			<div class="home_bottom clearfix ">
				<br/>
				<h3>[cmstext Electronic Signature Documents Placeholder]</h3>
				<h2 class="fs-title">[cmstext Preview Documents Placeholder]</h2>
				<?= ucwords(strtolower($application['name_first'])).", ";?> <br/>
				<div class='docs_table'>
					<p>[cmstext terms of your loan Placeholder] <a href="/<?= getCurrentLanguage();?>/loan_document" target='_blank'>[cmstext Loan Document Link1 Text].</a></p>
					<p>[cmstext preview the legal docs Placeholder]</p>
					<form class="validate_form" id="application_form" method="post" action="/<?= getCurrentLanguage();?>/esig_confirm_finalize">
						<input type="hidden" id="client_ip_address" name="client_ip_address" value="<?= $_SERVER['REMOTE_ADDR'];?>" />
						<input type="hidden" id="green_arrow" value="[cmsimage green arrow graphic]" />
						<input type="hidden" id="yellow_arrow" value="[cmsimage yellow arrow graphic]" />
						<table cellpadding="0" cellspacing="0" border="0" align="center" class="wf-legal-copy">
						<tr>
							<td valign="top"></td>
							<td valign="top"><strong>&nbsp;[cmstext Yes Placeholder]&nbsp;</strong></td>
							<td valign="top"><small>[cmstext Enable check Placeholder]</small></td>
						</tr>
<?php
	$doc_i = 0;
	foreach ($doc_list as $doc){
		$doc_i++;
		if (!isset($doc_ary[$doc])) {
		?>
							<tr>
								<td valign="top">Error</td>
								<td valign="top">missing</td>
								<td valign="top">document <?= $doc;?>.</label></td>
							</tr>								
		<?php
		} else {
		?>
							<tr>
								<td valign="top"><img id="esig_arrow_side_<?= $doc_i;?>" src="[cmsimage red arrow graphic]" alt="arrow" class="esig_arrow" /></td>
								<td valign="top"><input type="checkbox" disabled name="legal_approve_docs_<?= $doc_i;?>" class="esig_checkbox validate[required] checkbox fieldset" id="legal_approve_docs_<?= $doc_i;?>" tabindex="<?= $doc_i;?>" onclick="set_good_arrow(<?= $doc_i;?>);"/>&nbsp;</td>
								<td valign="top"><label for="legal_approve_docs_<?= $doc_i;?>"><?= $doc_ary[$doc]['chk_text'];?></label> <a href="/<?= getCurrentLanguage();?>/<?= $doc_ary[$doc]['link_name'];?>" onclick="set_check_arrow(<?= $doc_i;?>)" target='_blank'><?= $doc_ary[$doc]['link_text'];?></a><label for="legal_approve_docs_<?= $doc_i;?>">.</label></td>
							</tr>
		<?php
		}
	}
?>
						</table>
						<div class='right_side half_space'>
							<button type="submit" name="submit" class="submit action-button right_side" value="Submit" >[cmstext Submit Placeholder]</button>
							<button type="button" name="no_thanks" class="no_thanks action-button left_side" value="No Thanks" >[cmstext No Thanks Placeholder]</button>
						</div>
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
