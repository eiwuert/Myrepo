<?php
/*
Template: Customer Login
Description: Page that submits the customer login request
*/
?>
<?php
	include(TEMPLATES_PATH.'customer.func.php'); 

	if (!isset($_POST['username']) || !isset($_POST['credential'])) {
		lum_redirect("/".lum_getCurrentLanguage()."/validation_failed");
	}

	// set the request variable
	$request = array('username' => $_POST['username'],
			 'password' => $_POST['credential']
			 );
	
	$client = getSoapClient();
	
	$result = $client->login($_POST['username'],$_POST['credential']);
	
	$response = CheckSoapForError($result,'validation_failed');

	$application_ids = $response->result['application_ids'];

	// make sure the application isn't locked out
	checkLoginLock(max($application_ids));
	
	$ap_id = max($application_ids);
	
	$application = getApplication($ap_id);
	
	setSession($application);

	setCustomerPage($application,null);
	
?>
<?php include(TEMPLATES_PATH.'customer_header_html.php'); ?>
<div id="normal_page">
[cmsinclude Header]
	<div class="content">
		<div class="wrapper">
			<div class="home_bottom clearfix ">
				<br/>
				<h3>[cmstext Customer Page Placeholder]</h3>
				<h2 class="fs-title">[cmstext Login Successful Placeholder]</h2>
				[cmstext you lave logged in Placeholder]<br/>
			</div>
		</div>
	</div>
[cmsinclude Footer]
</div>
<?php include(TEMPLATES_PATH.'footer_html.php');?>
