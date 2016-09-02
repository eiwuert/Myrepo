<?php
/*
Template: Customer Service Main Page
Description: Page that customers in good status land on after the login submission
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
	setCustomerPage($application,'customer_portal');
	
	$status = getApplicationStatus($application);
	
	$total_balance = $application['transactions'][0]->total_balance;
	
	$idx = 0;
	if (isset($application['transactions'][1][$idx])) {
		$pay = $application['transactions'][1];
		while (($pay[$idx]['amount_principal']+$pay[$idx]['amount_non_principal']) >= 0) $idx++;
		$amount = 0;
		$date = $pay[$idx]['date_effective'];
		$idx = 0;
		while ($idx <= count($pay)) {
			if ($pay[$idx]['date_effective'] == $date) {
				if ($pay[$idx]['amount_principal'] < 0) $amount -= $pay[$idx]['amount_principal'];
				if ($pay[$idx]['amount_non_principal'] < 0) $amount -= $pay[$idx]['amount_non_principal'];
			}
			$idx++;
		}

		$next_payment = "[cmstext Your next payment for Placeholder] $".money_format('%i', ($amount))." [cmstext should be made by Placeholder] ".$date.".";
	} else {
		$next_payment = '';
	}
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
					<p>[cmstext Your remaining balance is Placeholder] $<?=money_format('%i', $total_balance);?>.</p>
					<p><?=$next_payment?></p>
					<p>[cmstext Review your loan document Placeholder] <a href="/<?= getCurrentLanguage();?>/loan_document" target='_blank'>[cmstext Loan Document Link Text].</a></p>
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
