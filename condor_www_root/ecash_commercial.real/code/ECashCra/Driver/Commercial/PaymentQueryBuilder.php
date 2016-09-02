<?php

class ECashCra_Driver_Commercial_PaymentQueryBuilder
{
	public function getNonACHPaymentsQuery($date, $company, array &$args)
	{
		$fields = $this->getCommonApplicationFields();
		$fields['payment_id'] = 'tr.transaction_register_id';
		$fields['payment_type'] = "IF (tr.amount > 0, 'CREDIT', 'DEBIT')";
		$fields['payment_date'] = 'DATE(th.date_created)';
		$fields['payment_amount'] = 'ABS(tr.amount)';
		$fields['payment_return_code'] = "
			IF (
				(th.status_after = 'failed')
				OR (
					tt.name_short IN (
						'chargeback',
						'ext_recovery_reversal_fee',
						'ext_recovery_reversal_pri'
					)
				), 
			'R', NULL)";
		$fields['payment_method'] = "
				CASE
					WHEN tt.name_short IN (
					'moneygram_fees',
					'moneygram_princ',
					'western_union_princ',
					'western_union_fees',
					'ext_recovery_fees',
					'ext_recovery_princ',
					'ext_recovery_reversal_fee',
					'ext_recovery_reversal_pri'
					)
					THEN 'EFT'
					
					WHEN tt.name_short IN (
					'money_order_fees',
					'money_order_princ'
					)
					THEN 'MONEY ORDER'
					
					WHEN tt.name_short IN (
					'credit_card_princ',
					'credit_card_fees',
					'chargeback',
					'chargeback_reversal'
					)
					THEN 'CARD1'
					
					WHEN tt.name_short IN (
					'quickcheck'
					)
					THEN 'DEMAND DRAFT'
					
					ELSE NULL
				END
		";
		
		unset($fields['bank_acct_number']);
		unset($fields['bank_aba']);
		$query = "
			SELECT
				{$this->buildFields($fields)}
			FROM
				transaction_register tr
				JOIN application a USING (application_id)
				JOIN transaction_history th USING (transaction_register_id)
				JOIN transaction_type tt USING (transaction_type_id)
			WHERE
					th.date_created BETWEEN ? AND ?
				AND ((
							th.status_before = 'pending'
						AND th.status_after = 'failed')
					OR (
							th.status_before = 'new'
						AND th.status_after = 'pending')
				)
				AND th.company_id = (
					SELECT company_id
					FROM company
					WHERE
						name_short = ?
				)
			HAVING
				payment_method IS NOT NULL
			ORDER BY
				tr.date_created ASC
		";
		
		$args = array(
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$company
		);
		return $query;
	}
	
	public function getAppNonACHPaymentsQuery($application_id, $company, array &$args)
	{
		$fields = $this->getCommonApplicationFields();
		$fields['payment_id'] = 'tr.transaction_register_id';
		$fields['payment_type'] = "IF (tr.amount > 0, 'CREDIT', 'DEBIT')";
		$fields['payment_date'] = 'DATE(th.date_created)';
		$fields['payment_amount'] = 'ABS(tr.amount)';
		$fields['payment_return_code'] = "IF (th.status_after = 'failed', 'R', NULL)";
		$fields['payment_method'] = "
				CASE
					WHEN tt.name_short IN (
					'moneygram_fees',
					'moneygram_princ',
					'western_union_princ',
					'western_union_fees'
					)
					THEN 'EFT'
					
					WHEN tt.name_short IN (
					'money_order_fees',
					'money_order_princ'
					)
					THEN 'MONEY ORDER'
					
					WHEN tt.name_short IN (
					'credit_card_princ',
					'credit_card_fees'
					)
					THEN 'CARD1'
				END
		";
		unset($fields['bank_acct_number']);
		unset($fields['bank_aba']);		
		$query = "
			SELECT
				{$this->buildFields($fields)}
			FROM
				transaction_register tr
				JOIN application a USING (application_id)
				JOIN transaction_history th USING (transaction_register_id)
				JOIN transaction_type tt USING (transaction_type_id)
			WHERE
					a.application_id = ?
				AND ((
							th.status_before = 'pending'
						AND th.status_after = 'failed')
					OR (
							th.status_before = 'new'
						AND th.status_after = 'pending')
				)
				AND tt.name_short IN (
					'moneygram_fees',
					'moneygram_princ',
					'western_union_princ',
					'western_union_fees',
					'money_order_fees',
					'money_order_princ',
					'credit_card_princ',
					'credit_card_fees'
				)
				AND a.company_id = (
					SELECT company_id
					FROM company
					WHERE
						name_short = ?
				)
			ORDER BY
				tr.date_created ASC
		";
		
		$args = array(
			$application_id,
			$company
		);
		return $query;
	}
	
	public function getACHPaymentsQuery($date, $company, array &$args)
	{
		$fields = $this->getCommonApplicationFields();
		$fields['payment_id'] = 'ach.ach_id';
		$fields['payment_type'] = "IF (ach.ach_type = 'credit', 'CREDIT', 'DEBIT')";
		$fields['payment_date'] = 'ach.ach_date';
		$fields['payment_amount'] = 'ach.amount';
		$fields['payment_return_code'] = "NULL";
		$fields['payment_method'] = "'ACH'";
		
		$query = "
			SELECT
				{$this->buildFields($fields)}
			FROM
				ach
				JOIN application a USING (application_id)
				JOIN transaction_register tr USING (ach_id)
			WHERE
					ach.ach_date = ?
				AND a.company_id =  (
					SELECT company_id
					FROM company
					WHERE
						name_short = ?
				)
			ORDER BY
				tr.date_created ASC
		";
		
		$args = array(
			$date,
			$company
		);
		return $query;
	}
	
	public function getAppACHPaymentsQuery($application_id, $company, array &$args)
	{
		$fields = $this->getCommonApplicationFields();
		$fields['payment_id'] = 'ach.ach_id';
		$fields['payment_type'] = "IF (ach.ach_type = 'credit', 'CREDIT', 'DEBIT')";
		$fields['payment_date'] = 'ach.ach_date';
		$fields['payment_amount'] = 'ach.amount';
		$fields['payment_return_code'] = "NULL";
		$fields['payment_method'] = "'ACH'";
		
		$query = "
			SELECT
				{$this->buildFields($fields)}
			FROM
				ach
				JOIN application a USING (application_id)
				JOIN transaction_register tr USING (ach_id)
			WHERE
					a.application_id = ?
				AND a.company_id =  (
					SELECT company_id
					FROM company
					WHERE
						name_short = ?
				)
			ORDER BY
				tr.date_created ASC
		";
		
		$args = array(
			$application_id,
			$company
		);
		return $query;
	}
	
	public function getACHReturnsQuery($date, $company, array &$args)
	{
		$fields = $this->getCommonApplicationFields();
		$fields['payment_id'] = 'ach.ach_id';
		$fields['payment_type'] = "IF (ach.ach_type = 'credit', 'CREDIT', 'DEBIT')";
		$fields['payment_date'] = 'ar.date_request';
		$fields['payment_amount'] = 'ach.amount';
		$fields['payment_return_code'] = "IFNULL(arc.name_short, 'R')";
		$fields['payment_method'] = "'ACH'";
		
		$query = "
			SELECT
				{$this->buildFields($fields)}
			FROM
				ach_report ar
				JOIN ach USING (ach_report_id)
				LEFT JOIN ach_return_code arc USING (ach_return_code_id)
				JOIN application a USING (application_id)
				JOIN transaction_register tr USING (ach_id)
			WHERE
					ar.date_request = ?
				AND a.company_id =  (
					SELECT company_id
					FROM company
					WHERE
						name_short = ?
				)
				AND
					ach.ach_status = 'returned'
			ORDER BY
				tr.date_created ASC
		";
		$args = array(
			$date,
			$company
		);
		
		return $query;
	}
	
	public function getAppACHReturnsQuery($application_id, $company, array &$args)
	{
		$fields = $this->getCommonApplicationFields();
		$fields['payment_id'] = 'ach.ach_id';
		$fields['payment_type'] = "IF (ach.ach_type = 'credit', 'CREDIT', 'DEBIT')";
		$fields['payment_date'] = 'ar.date_request';
		$fields['payment_amount'] = 'ach.amount';
		$fields['payment_return_code'] = "IFNULL(arc.name_short, 'R')";
		$fields['payment_method'] = "'ACH'";
		
		$query = "
			SELECT
				{$this->buildFields($fields)}
			FROM
				ach_report ar
				JOIN ach USING (ach_report_id)
				LEFT JOIN ach_return_code arc USING (ach_return_code_id)
				JOIN application a USING (application_id)
				JOIN transaction_register tr USING (ach_id)
			WHERE
					a.application_id = ?
				AND a.company_id =  (
					SELECT company_id
					FROM company
					WHERE
						name_short = ?
				)
				AND
					ach.ach_status = 'returned'
			ORDER BY
				tr.date_created ASC
		";
		$args = array(
			$application_id,
			$company
		);
		
		return $query;
	}
	
	protected function getCommonApplicationFields()
	{
		return array(
			'application_id' => 'a.application_id',
			'fund_date' => 'a.date_fund_actual',
			'fund_amount' => 'a.fund_actual',
			'date_first_payment' => 'a.date_first_payment',
			'fee_amount' => 'ROUND(IFNULL(a.finance_charge, a.fund_actual * 0.30), 2)',
			'employer_name' => 'a.employer_name',
			'employer_street1' => 'a.work_address_1',
			'employer_street2' => 'a.work_address_2',
			'employer_city' => 'a.work_city',
			'employer_state' => 'a.work_state',
			'employer_zip' => 'a.work_zip_code',
			'pay_period' => "
				CASE a.income_frequency
					WHEN 'twice_monthly'
					THEN 'semi_monthly'
					
					ELSE a.income_frequency
				END
			",
			'phone_work' => 'a.phone_work',
			'phone_ext' => 'a.phone_work_ext',
			'name_first' => 'a.name_first',
			'name_middle' => 'a.name_middle',
			'name_last' => 'a.name_last',
			'street1' => 'a.street',
			'street2' => 'a.unit',
			'city' => 'a.city',
			'state' => 'a.state',
			'zip' => 'a.zip_code',
			'phone_home' => 'a.phone_home',
			'phone_cell' => 'a.phone_cell',
			'email' => 'a.email',
			'ip_address' => 'a.ip_address',
			'dob' => 'a.dob',
			'ssn' => 'a.ssn',
			'driver_license_number' => 'a.legal_id_number',
			'driver_license_state' => 'a.legal_id_state',
			'bank_name' => 'a.bank_name',
			'bank_aba' => 'a.bank_aba',
			'bank_acct_number' => 'a.bank_account'
		);
	}
	
	protected function buildFields($fields)
	{
		$field_string = '';
		foreach ($fields as $label => $value)
		{
			$field_string .= "{$value} {$label},\n";
		}
		
		return substr($field_string, 0, -2);
	}
}

?>
