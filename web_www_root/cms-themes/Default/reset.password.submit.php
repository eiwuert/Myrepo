<?php
/*
Template: Reset Password Retriever
Description: Page that the customer submits a rest password
*/
?>
<?php
	include(TEMPLATES_PATH.'customer.func.php'); 
	$application = getSession();

	if (!isset($_POST['user_string1']) || !isset($_POST['user_string1']) || ($_POST['user_string1'] != $_POST['user_string2'])) {
		lum_redirect("/".lum_getCurrentLanguage()."/validation_failed");
	}

	$client = getSoapClient();
	
	$result = $client->resetPassword($application['application_id'],$_POST['user_string1']);
	
	$response = BuildGenericPageResponse($result);
	
	switch($response->signature->data){
		case 'success':
			// Do nothing all good
			break;
		case 'app_error':
		case 'system_error':
			$_SESSION['error_code'] = $response->errors->data;
			lum_redirect("/".lum_getCurrentLanguage()."/system_error");
			break;
		default:
			$_SESSION['error_code'] = 'Unknown soap resonse error';
			lum_redirect("/".lum_getCurrentLanguage()."/system_error");
			break;
	}
?>
<?php include(TEMPLATES_PATH.'header_html.php'); ?>
<div id="normal_page">
[cmsinclude Header]
	<div class="content">
		<div class="wrapper">
			<div class="home_bottom clearfix ">
				<br/>
				<h3>[cmstext Password Reset Placeholder]</h3>
				<h2 class="fs-title">[cmstext Password Set Placeholder]</h2>
				[cmstext your password has been changed Placeholder]<br/><br/><br/>
			</div>
		</div>
	</div>
[cmsinclude Footer]
</div>
<?php include(TEMPLATES_PATH.'footer_html.php');?>
