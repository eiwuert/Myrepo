<?php 

// Sorts a schedule according to the CLK rules.

// Did I mention this is a filthy hack? -- Marc
function schedule_sort($a, $b) {

	// Converted Principal Balances first
	if ($a->type == 'converted_principal_bal') return -1;
	if ($b->type == 'converted_principal_bal') return 1;

	// Then converted service charges
	if ($a->type == 'converted_service_chg_bal') return -1;
	if ($b->type == 'converted_service_chg_bal') return 1;

	// All OTHER events, work as such:
	// 1) Sort by action date
	// 2) All debits for a matching action date are first, 
	//    unless they are of type loan_disbursement

	if ($a->date_event != $b->date_event) {
		return ((strtotime($a->date_event) < strtotime($b->date_event)) ? -1 : 1);
	} else {

		// Nice little piccadillo - disbursements are before any other event on a given day.
		if ($a->type == 'loan_disbursement') return -1;
		if ($b->type == 'loan_disbursement') return 1;

		// ANOTHER little piccadillo - if you find an ACH Fee assessment - that's next.
		if ($a->type == 'assess_fee_ach_fail') return -1;
		if ($b->type == 'assess_fee_ach_fail') return 1;

		// [Changelog:3.5.2.40][i364][Mantis:1416][20061024175127UTC][Jason_Schmidt]
		if ("loan_disbursement"  == $a->type && "loan_disbursement"   != $b->type) return -1;
		if ("loan_disbursement"  == $b->type && "loan_disbursement"   != $a->type) return  1;
		if ("assess_service_chg" == $a->type && "payment_service_chg" == $b->type) return  1;
		if ("assess_service_chg" == $b->type && "payment_service_chg" == $a->type) return -1;
		if ("assess_service_chg" == $a->type && strpos($b->type , "payout") !== FALSE) return  -1;
		if ("assess_service_chg" == $b->type && strpos($a->type , "payout") !== FALSE) return 1;
		
		// Sort Entries for manual Payments and Chargebacks
		if ("chargeback" == $a->type && "credit_card_princ" == $b->type) return 1;
		if ("credit_card_princ" == $a->type && "chargeback_reversal" == $b->type) return -1;
		if ("chargeback" == $a->type && "chargeback_reversal" == $b->type) return -1;
				
		$amta = $a->principal_amount + $a->fee_amount;
		$amtb = $b->principal_amount + $b->fee_amount;
		return (($amta < $amtb) ? -1 : 1);
	}
	return 0;
}

?>
