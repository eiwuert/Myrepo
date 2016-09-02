<?php

class ECashCra_Driver_Commercial_ApplicationQueryBuilder
{
	const FAILED_REDISBURSEMENTS_APPLICATION_TEMP_TABLE = 'temp_failed_redisbursements_application';
	const STATUS_HISTORY_APPLICATION_TEMP_TABLE = 'temp_status_history_application';
	const RECOVERIES_TRANSACTION_TEMP_TABLE = 'temp_recoveries_transaction';
	const RECOVERIES_APPLICATION_TEMP_TABLE = 'temp_recoveries_application';
	
	/**
	 * Returns a query that will provide the application ID's with failed redisbursement transactions.
	 * 
	 * @param string $date
	 * @param string $company
	 * @param array $args
	 * @return string
	 */
	public function getFailedRedisbursementsApplicationsQuery($date, $company, array &$args)
	{
		$query = "
			SELECT DISTINCT
				sh.application_id
			FROM
				transaction_register AS sh
				INNER JOIN transaction_register AS tr
					ON sh.application_id = tr.application_id
					AND tr.transaction_status = 'failed'
					AND tr.date_effective < sh.date_effective
			WHERE
				sh.transaction_status = 'pending'
				AND sh.date_effective BETWEEN ? AND ?
				AND sh.date_created >= 20060606000000 -- #12345 No updates before 2006/06/06
				AND sh.company_id = ( SELECT company_id FROM company WHERE name_short = ? )
		";
		
		$args[] = $date.' 00:00:00';
		$args[] = $date.' 23:59:59';
		$args[] = $company;
		
		return $query;
	}
	
	public function getFailedRedisbursementsQuery()
	{
		return "
			SELECT
				{$this->buildFields($this->getCommonApplicationFields())}
			FROM
				" . self::FAILED_REDISBURSEMENTS_APPLICATION_TEMP_TABLE . " a
		";
	}

	public function getStatusHistoryQuery()
	{
		$fields = $this->getCommonApplicationFields();
		$fields['balance'] = 'IFNULL(SUM(tr.amount), 0)';
		$fields['application_status_name'] = 'a.application_status_name';

		$query = "
			SELECT
				{$this->buildFields($fields)}
			FROM
				" . self::STATUS_HISTORY_APPLICATION_TEMP_TABLE . " AS a
				LEFT JOIN transaction_register tr
					ON tr.application_id = a.application_id
					AND tr.transaction_status = 'complete'
				LEFT JOIN transaction_type tt
					ON tt.transaction_type_id = tr.transaction_type_id
			GROUP BY
				a.application_id
			HAVING
				-- We do not want to pick up customers with cancellations. This will be handled by the 'export_cancel' script
				COUNT(IF(tt.name_short LIKE 'cancel_%', tr.transaction_register_id, NULL)) = 0
		";

		return $query;
	}

	public function getCancellationTransactionsQuery($date, $company, array &$args)
	{
		$query = "
			SELECT
				tl.application_id
			FROM
				transaction_ledger tl
			WHERE
				tl.date_created BETWEEN ? AND ?
				AND tl.date_created >= '2006-06-06 00:00:00' -- #12345 No updates before 2006/06/06
				AND tl.company_id = ( SELECT company_id FROM company WHERE name_short = ? )
				AND tl.transaction_type_id IN (
					SELECT transaction_type_id
					FROM transaction_type
					WHERE name_short IN ('cancel_fees', 'cancel_principal')
				)
			ORDER BY
				tl.date_created ASC
		";

		$args = array(
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$company
		);

		return $query;
	}
	
	public function getRecoveriesTempTableQuery($date, $company, array &$args)
	{
		$query = "
			CREATE TEMPORARY TABLE " . self::RECOVERIES_TRANSACTION_TEMP_TABLE . " (INDEX (application_id))
			SELECT DISTINCT
				tr.application_id,
				IFNULL(SUM(tr.amount), 0) AS balance,
				IFNULL(SUM(-tl.amount), 0) AS recovery_amount
			FROM
				transaction_ledger tl
				LEFT JOIN transaction_register tr
					ON tr.application_id = tl.application_id AND tr.transaction_status = 'complete'
			WHERE
				tl.date_created BETWEEN ? AND ?
				AND tl.company_id = ( SELECT company_id FROM company WHERE name_short = ? )
				AND tl.transaction_type_id IN ( SELECT transaction_type_id FROM transaction_type WHERE name_short LIKE 'ext_recovery%' )
			GROUP BY
				tl.application_id
			ORDER BY
				tl.date_created ASC
		";
		
		$args = array(
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$company
		);
		
		return $query;
	}
	
	public function getRecoveriesTempTableApplicationsQuery()
	{
		return "SELECT application_id FROM " . self::RECOVERIES_TRANSACTION_TEMP_TABLE;
	}

	public function getRecoveriesQuery()
	{
		$fields = $this->getCommonApplicationFields();
		$fields['balance'] = 't.balance';
		$fields['recovery_amount'] = 't.recovery_amount';

		$query = "
			SELECT DISTINCT
				{$this->buildFields($fields)}
			FROM
				" . self::RECOVERIES_TRANSACTION_TEMP_TABLE . " AS t
				JOIN " . self::RECOVERIES_APPLICATION_TEMP_TABLE . " AS a
					ON t.application_id = a.application_id
		";

		return $query;
	}

	/**
	 * This method is no longer used. It would be run as part of the react_fund_updates script.
	 * 
	 * @deprecated
	 */
	public function getFundedReacts($date, $company, $active_status_id, array &$args)
	{
		$query = "
			SELECT DISTINCT
				{$this->buildFields($this->getCommonApplicationFields())}
			FROM
				status_history sh
				JOIN application a USING (application_id)
			WHERE
				    sh.date_created BETWEEN (?) AND (?)
				AND sh.application_status_id = ?
				AND a.company_id = (
					SELECT company_id
					FROM company
					WHERE
						name_short = ?
				)
				AND	a.is_react = 'yes'
			ORDER BY
				sh.date_created ASC
		";

		$args = array(
			$date . ' 00:00:00',
			$date . ' 23:59:59',
			$active_status_id,
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
			//'fee_amount' => 'a.fee_amount', //not present in AALM schema
			'employer_name' => 'a.employer_name',
			'employer_street1' => 'a.work_address_1',
			'employer_street2' => 'a.work_address_2',
			'employer_city' => 'a.work_city',
			'employer_state' => 'a.work_state',
			'employer_zip' => 'a.work_zip_code',
			'pay_period' => "IF(a.income_frequency = 'twice_monthly','semi_monthly',a.income_frequency)",
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
