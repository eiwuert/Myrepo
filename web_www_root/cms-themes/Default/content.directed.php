<?php
/*
Template: Directed Content
Description: Content page for the site
*/
	include(TEMPLATES_PATH.'customer.func.php'); 
?>
<?php
/*
	$application = getSession();
	setCustomerPage($application,array('more_info','loan_not_available','loan_cancelled',
		'application_withdrawn','reviewing_application','manager_review','account_closed'));
*/
?>
<?php include(TEMPLATES_PATH.'header_html.php'); ?>
[cmsinclude Header]
	<div class="wrapper">
		<div class="interior">
			<h1>[PAGE_TITLE]</h1>
			[cmsrichtext Content]
		</div>
	</div>
[cmsinclude Footer]
<?php include(TEMPLATES_PATH.'footer_html.php');?>
