<?php 

// Did I mention this is a filthy hack? -- Marc
function schedule_sort($a, $b) 
{

	// Converted Principal Balances first
	//if ($a->type == 'loan_disbursement') return -1;
	//if ($b->type == 'loan_disbursement') return 1;

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

	if ($a->date_effective != $b->date_effective) 
	{
		return ((strtotime($a->date_effective) < strtotime($b->date_effective)) ? -1 : 1);
	} 
	else 
	{

		$orderarray = Array(
			'zero index is false which will not do',
			'loan_disbursement',
			'moneygram_disbursement',
			'check_disbursement',
			'assess_fee_ach_fail',
			'lender_assess_fee_ach',
			'cso_assess_fee_late',
			'lender_pay_fee_ach',
			'cso_assess_fee_app',
			'cso_pay_fee_broker',
			'cso_pay_fee_app',
			'assess_service_chg',
			'payment_service_chg',
			'credit_card',
			'credit_card_princ',
			'chargeback',
			'chargeback_reversal',
			'assess_fee_lien',
			'assess_fee_delivery',
			'assess_fee_transfer',
			'cso_assess_fee_broker',
			'payout'
		);

		if (array_search($a->type, $orderarray) && array_search($b->type, $orderarray)) 
		{
			$retval = 0;
			$retval = array_search($a->type, $orderarray) < array_search($b->type, $orderarray) ? -1 : 0;
			if ($retval != 0) return $retval;
			$retval = array_search($a->type, $orderarray) > array_search($b->type, $orderarray) ? 1 : 0;
			if ($retval != 0) return $retval;
		}

		if(abs($a->fee_amount) + abs($a->service_charge) < abs($b->fee_amount) + abs($b->service_charge))
			return 1;
		else if ($a->principal_amount != $b->principal_amount)
			return -1;
		
		$amta = $a->principal_amount + $a->fee_amount + $a->service_charge;
		$amtb = $b->principal_amount + $b->fee_amount + $b->service_charge;
		
		return (($amta < $amtb) ? -1 : 1);
	}
	return 0;
}

?>
