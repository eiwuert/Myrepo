<?php
/*
Template: React Page
Description: Outside frame for react application
*/
?>
<?php
	include(TEMPLATES_PATH.'customer.func.php');
	
	// if its an auto-log, log in first
	if (isset($_REQUEST['link'])){
		$confirm_string = lum_getString("[REACT_HASH_KEY]");
		$app_id =  base64_decode(urldecode($_REQUEST['link']));
		if (!(md5($app_id.$confirm_string)==$_REQUEST['key'])) lum_redirect("/".getCurrentLanguage()."/validation_failed");
		if ((base64_decode(urldecode($_REQUEST['exp'])) < time()-(60*24*lum_getString("[REACT_TIME_LIMIT_DAYS]"))) && (lum_getString("[REACT_TIME_LIMIT_DAYS]") >= 0)) lum_redirect("/".getCurrentLanguage()."/login_expired");
	}

	//  Get application details
	$application = getSession();

	// make sure that the application is in the appropriate status.
	if (in_array(!$application['status'],array("paid::customer::*root", "settled::customer::*root"))){
		lum_redirect("/".getCurrentLanguage()."/customer_portal");
	}
	
	include(TEMPLATES_PATH.'application_header_html.php');
?>
<div id="normal_page">
[cmsinclude Header]
	<div class="content">
		<div class="wrapper">
			<div class="home_bottom clearfix ">
				<br/>
				<h3>[cmstext Customer Page Placeholder]</h3>
				<h2 class="fs-title">[cmstext Customer New Loan Application Placeholder]</h2>
				[cmstext Hello Placeholder] <?php echo ucwords(strtolower($application['name_first']." ".$application['name_last']));?>.<br/>
				[cmstext This is your opportunity Placeholder] 
				<form class="validate_form" id="application_form" method="post" action="/<?=getCurrentLanguage()?>/application_submit">
					<div class="white_box apply_now clearfix">
						[cmsinclude Application]
					</div>
				</form>
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
<script type="text/javascript">
	$(function () {
	    $("select#pay_frequency").change();
	});
	window.onload = function() {
	  change_paydate_model('<?= $application['income_frequency']?>');
	  funcShowDayWeek('<?= $application['day_of_week']?>');
	};
</script>
<style>
	select {
		color: #2c3e50 !important;
	}
</style>
