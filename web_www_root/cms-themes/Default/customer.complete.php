<?php
/*
Template: Customer Complete Main Page
Description: Page that customers in completely paid off status
*/
	include(TEMPLATES_PATH.'customer.func.php'); 
?>
<?php
	// status texts for cms translation
	$not_avail = '[cmstext Not Available status]';
	$need_esig = '[cmstext Needs Electronic Signature status]';
	$dissagree = '[cmstext Disagreed to Loan Amount status]';
	$declined = '[cmstext Customer Declined Terms status]';
	$expired = '[cmstext Loan Has Expired status]';
	$finalize = '[cmstext Finalizing Loan status]';
	$active = '[cmstext Active Loan status]';
	$on_hold = '[cmstext Loan on Hold status]';
	$fund_failed = '[cmstext Funding Failed status]';
	$refi = '[cmstext Refinancing Loan status]';
	$past_due = '[cmstext Loan Past Due status]';
	$past_due_cu = '[cmstext Loan Past Due Contact Us status]';
	$past_due_re = '[cmstext Loan Past Due Reworking Payments status]';
	$past_due_cccs = '[cmstext Loan Past Due Credit Counsuling Services status]';
	$arrange = '[cmstext Loan Past Due Arrangement status]';
	$arrange_hold = '[cmstext Loan Past Due Arrangement on Hold status]';
	$arrange_fail = '[cmstext Loan Past Due Arrangement Failed status]';
	$deceased = '[cmstext Loan Past Due Deceased status]';
	$deceased_ver = '[cmstext Loan Past Due Deceased Verified status]';
	$bankrupt = '[cmstext Loan Past Due Bankruptcy status]';
	$bankrupt_ver = '[cmstext Loan Past Due Bankruptcy Verified status]';
	$bankrupt_ammort = '[cmstext Loan Past Due Bankruptcy Ammortized status]';
	$collect = '[cmstext Going to Collections Service status]';
	$collect_sent = '[cmstext Sent to Collections Service status]';
	$collect_rec = '[cmstext Recovered by Collections Service status]';
	$collect_int = '[cmstext Recovered without Collections Service status]';
	$paid_off = '[cmstext Loan Paid Off status]';
    
	$application = getSession();
	setCustomerPage($application,'customer_complete');
	
	$status = getApplicationStatus($application);
?>
<?php include(TEMPLATES_PATH.'confirmation_header_html.php'); ?>
<div id="normal_page">
[cmsinclude Header]
	<div class="content">
		<div class="wrapper">
			<div class="home_bottom clearfix ">
				<br/>
				<h3>[cmstext Customer Page Placeholder]</h3>
				<h2 class="fs-title">[cmstext Customer Main Page Placeholder]</h2>
				[cmstext Hello Placeholder] <?php echo ucwords(strtolower($application['name_first']." ".$application['name_last']));?>.<br/>
				<div class="left_column">
					<p>[cmstext Your loan is paid Placeholder]</p>
					<?php if ($application['status'] == "paid::customer::*root" ) { ?>
					<p>[cmstext Apply for Placeholder] <a href="/<?= getCurrentLanguage();?>/react_loan">[cmstext New Loan Text].</a></p>
					<?php } ?>
					<p>[cmstext Set your Placeholder] <a href="/<?= getCurrentLanguage();?>/customer_privacy">[cmstext Privacy Settings Text].</a></p>
					<br/>
					<br/>
				</div>
				<div class="right_column">
					<p>[cmstext loan number Placeholder]:<span class='<?=$status['class'];?>'> <?=$application['application_id'];?></span>.</p>
					<p>[cmstext Your loan status is Placeholder]: </p>
					<p><b><span class='<?=$status['class'];?>'> <?=$status['status'];?></span></b></p>
				</div>
			</div>
		</div>
	</div>
[cmsinclude Footer]
</div>
<?php include(TEMPLATES_PATH.'footer_html.php');?>
