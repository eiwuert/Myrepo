<?php
/*
Template: Customer Privacy Settings Submission Page
Description: Page that customers in good status land on after the login submission
*/
?>
<?php
	include(TEMPLATES_PATH.'customer.func.php'); 
	// status texts for cms translation
	$application = getSession();
	$post = $_POST;
	
	$attrib_list = array('bad_info', 'do_not_contact', 'best_contact', 'do_not_market');
	$fields_list = array('phone_home', 'phone_cell', 'phone_work', 'customer_email', 'ref_phone_1', 'ref_phone_2', 'ref_phone_3', 'ref_phone_4', 'ref_phone_5', 'ref_phone_6', 'street');
	
	$fields = array();
	foreach ($attrib_list as $attrib){
		foreach ($fields_list as $field){
			$posted = false;
			foreach ($_POST as $post) {
				if ((strpos($post,$field) === false) || (strpos($post,$attrib) === false)) {
					// not this post, do nothing
				} else {
					$posted = true;
				}
				$fields[$field][$attrib] = $posted;
			}
		}
	}

	$client = getSoapClient();
	
	$result = $client->setContactFields($application['application_id'],$fields);
	
	$response = CheckSoapForError($result);
	
	$application = getApplication($application['application_id']);

	setSession($application);
	
?>
<?php include(TEMPLATES_PATH.'confirmation_header_html.php'); ?>
<div id="normal_page">
[cmsinclude Header]
	<div class="content">
		<div class="wrapper">
			<div class="home_bottom clearfix ">
				<br/>
				<h3>[cmstext Customer Page Placeholder]</h3>
				<h2 class="fs-title">[cmstext Customer Privacy Settings Page Placeholder]</h2>
				[cmstext Thank you Placeholder] <?= ucwords(strtolower($application['name_first']." ".$application['name_last']));?>.<br/>
				[cmstext your privacy settings have been submitted to our system.]<br/><br/><br/>
			</div>
		</div>
	</div>
[cmsinclude Footer]
</div>
<?php include(TEMPLATES_PATH.'footer_html.php');?>
