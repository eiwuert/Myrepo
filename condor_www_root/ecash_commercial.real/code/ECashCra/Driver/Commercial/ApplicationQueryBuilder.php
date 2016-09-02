<?php

class ECashCra_Driver_Commercial_ApplicationQueryBuilder
{
	public function getFailedRedisbursementsQuery($date, $company, array &$args)
	{
		$query = "
			SELECT
				{$this->buildFields($this->getCommonApplicationFields())}
			FROM
				transaction_register sh
				JOIN application a ON (sh.application_id = a.application_id)
				JOIN transaction_register tr ON (
						tr.application_id = a.application_id
					AND tr.transaction_status = 'failed'
					AND tr.date_effective < sh.date_effective
				)
			WHERE
				sh.transaction_status = 'pending'
				AND sh.date_effective BETWEEN (?) AND (?)
				AND sh.date_created >= 20060606000000 -- #12345 No updates before 2006/06/06
				AND sh.company_id = (
					SELECT company_id
					FROM company
					WHERE
						name_short = ?
				)
			GROUP BY
				sh.application_id
			ORDER BY
				sh.date_created ASC
		";

		$args[] = $date.' 00:00:00';
		$args[] = $date.' 23:59:59';
		$args[] = $company;

		return $query;
	}
	
	public function getStatusChangesFromInactiveQuery($date, $company, array &$args)
	{
		$query = "
			SELECT
				{$this->buildFields($this->getCommonApplicationFields())}
			FROM
				status_history new_sh
				JOIN application_status new_aps USING (application_status_id)
				JOIN application a USING (application_id)
				LEFT JOIN status_history new_sh_chk ON (
					new_sh_chk.application_id = new_sh.application_id
					AND new_sh_chk.date_created >= new_sh.date_created
					AND new_sh_chk.status_history_id > new_sh.status_history_id
					AND new_sh_chk.date_created BETWEEN ? AND ?
				)
				LEFT JOIN status_history prev_sh ON (
					prev_sh.application_id = new_sh.application_id
					AND prev_sh.date_created <= ?
				)
				LEFT JOIN status_history prev_sh_chk ON (
					prev_sh_chk.application_id = new_sh.application_id
					AND prev_sh_chk.date_created BETWEEN prev_sh.date_created AND ?
					AND prev_sh_chk.status_history_id > prev_sh.status_history_id
				)
				LEFT JOIN application_status prev_aps ON (
					prev_aps.application_status_id = prev_sh.application_status_id
				)
			WHERE
				new_sh.date_created BETWEEN ? AND ?
				AND new_sh_chk.status_history_id IS NULL
				AND prev_sh_chk.status_history_id IS NULL
				AND new_sh.company_id = (
					SELECT company_id
					FROM company
					WHERE
						name_short = ?
				)
				AND prev_aps.name_short = 'paid';
		";
				
		$prev_date = date('Y-m-d', strtotime('-1 day', strtotime($date)));

		$args = array();
		$args[] = $date.' 00:00:00';
		$args[] = $date.' 23:59:59';
		$args[] = $prev_date.' 23:59:59';
		$args[] = $prev_date.' 23:59:59';
		$args[] = $date.' 00:00:00';
		$args[] = $date.' 23:59:59';
		$args[] = $company;
		
		return $query;
	}

	public function getStatusHistoryQuery($date, $status_ids, $company, array &$args)
	{
		$status_placeholders = implode(',', array_fill(0, count($status_ids), '?'));
		
		$fields = $this->getCommonApplicationFields();
		$fields['balance'] = 'IFNULL(SUM(tr.amount), 0)';
		$fields['application_status_id'] = 'sh.application_status_id';
		
		$query = "
			SELECT
				{$this->buildFields($fields)}
			FROM
				status_history sh
				JOIN application a USING (application_id)
				LEFT JOIN transaction_register tr ON (
						tr.application_id = a.application_id
					AND tr.transaction_status = 'complete'
				)
				LEFT JOIN transaction_type tt ON (
					tt.transaction_type_id = tr.transaction_type_id
				)
			WHERE
					sh.application_status_id IN ({$status_placeholders})
				AND sh.date_created BETWEEN (?) AND (?)
				AND sh.company_id = (
					SELECT company_id
					FROM company
					WHERE
						name_short = ?
				)
			GROUP BY
				sh.application_id
			HAVING
				-- We do not want to pick up customers with cancellations. This will be handled by the 'export_cancel' script
				COUNT(IF(tt.name_short LIKE 'cancel_%', tr.transaction_register_id, NULL)) = 0
			ORDER BY
				sh.date_created ASC
		";
				
		$args = $status_ids;
		$args[] = $date.' 00:00:00';
		$args[] = $date.' 23:59:59';
		$args[] = $company;
		
		return $query;
	}
	
	public function getApplicationStatusHistoryQuery($application_id, $status_ids, $company, array &$args)
	{
		$status_placeholders = implode(',', array_fill(0, count($status_ids), '?'));
		
		$fields = $this->getCommonApplicationFields();
		$fields['balance'] = 'IFNULL(SUM(tr.amount), 0)';
		$fields['application_status_id'] = 'sh.application_status_id';
		$fields['date'] = 'DATE(sh.date_created)';
		
		$query = "
			SELECT
				{$this->buildFields($fields)}
			FROM
				status_history sh
				JOIN application a USING (application_id)
				LEFT JOIN transaction_register tr ON (
						tr.application_id = a.application_id
					AND tr.transaction_status = 'complete'
				)
			WHERE
					sh.application_status_id IN ({$status_placeholders})
				AND a.application_id = ?
				AND a.company_id = (
					SELECT company_id
					FROM company
					WHERE
						name_short = ?
				)
			ORDER BY
				sh.date_created ASC
		";
				
		$args = $status_ids;
		$args[] = $application_id;
		$args[] = $company;
		
		return $query;
	}
	public function getCancellationTransactionsQuery($date, $company, array &$args)
	{
		$query = "
			SELECT DISTINCT
				{$this->buildFields($this->getCommonApplicationFields())}
			FROM
				transaction_ledger tl
				JOIN application a USING (application_id)
			WHERE
					tl.date_created BETWEEN (?) AND (?)
					AND tl.date_created >= 20060606000000 -- #12345 No updates before 2006/06/06
				AND tl.company_id = (
					SELECT company_id
					FROM company
					WHERE
						name_short = ?
				)
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

	
	public function getApplicationCancellationsQuery($application_id, $company, array &$args)
	{
		$fields = $this->getCommonApplicationFields();
		$fields['date'] = 'DATE(tl.date_created)';
		$query = "
			SELECT DISTINCT
				{$this->buildFields($fields)}
			FROM
				transaction_ledger tl
				JOIN application a USING (application_id)
			WHERE
				    a.application_id = ?
				AND tl.company_id = (
					SELECT company_id
					FROM company
					WHERE
						name_short = ?
				)
				AND tl.transaction_type_id IN (
					SELECT transaction_type_id
					FROM transaction_type
					WHERE name_short IN ('cancel_fees', 'cancel_principal')
				)
			ORDER BY
				tl.date_created ASC
		";
				
		$args = array(
			$application_id,
			$company
		);
		
		return $query;
	}
	public function getCancellationStatusesQuery($date, $status_ids, $company, array &$args)
	{
		$status_placeholders = implode(',', array_fill(0, count($status_ids), '?'));
		
		$query = "
			SELECT
				{$this->buildFields($this->getCommonApplicationFields())}
			FROM
				status_history sh
				JOIN application a USING (application_id)
			WHERE
					sh.application_status_id IN ({$status_placeholders})
				AND sh.date_created BETWEEN (?) AND (?)
				AND sh.company_id = (
					SELECT company_id
					FROM company
					WHERE
						name_short = ?
				)
				AND sh.date_created >= 20060606000000 -- #12345 No updates before 2006/06/06
				AND a.date_fund_actual IS NOT NULL
			GROUP BY
				sh.application_id
			ORDER BY
				sh.date_created ASC
				";

		$args = $status_ids;
		$args[] = $date . ' 00:00:00';
		$args[] = $date . ' 23:59:59';
		$args[] = $company;

		return $query;
	}
	public function getRecoveriesQuery($date, $company, array &$args)
	{
		$fields = $this->getCommonApplicationFields();
		$fields['balance'] = 'IFNULL(SUM(tr.amount), 0)';
		$fields['recovery_amount'] = 'IFNULL(SUM(-tl.amount), 0)';
		
		$query = "
			SELECT DISTINCT
				{$this->buildFields($fields)}
			FROM
				transaction_ledger tl
				JOIN application a USING (application_id)
				LEFT JOIN transaction_register tr ON (
						tr.application_id = a.application_id
					AND tr.transaction_status = 'complete'
				)
			WHERE
				    tl.date_created BETWEEN (?) AND (?)
				AND tl.company_id = (
					SELECT company_id
					FROM company
					WHERE
						name_short = ?
				)
				AND tl.transaction_type_id IN (
					SELECT transaction_type_id
					FROM transaction_type
					WHERE name_short LIKE 'ext_recovery%'
				)
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
	
	public function getApplicationRecoveriesQuery($application_id, $company, array &$args)
	{
		$fields = $this->getCommonApplicationFields();
		$fields['balance'] = 'IFNULL(SUM(tr.amount), 0)';
		$fields['recovery_amount'] = 'IFNULL(SUM(-tl.amount), 0)';
		$fields['date'] = 'DATE(tl.date_created)';
		
		$query = "
			SELECT DISTINCT
				{$this->buildFields($fields)}
			FROM
				transaction_ledger tl
				JOIN application a USING (application_id)
				LEFT JOIN transaction_register tr ON (
						tr.application_id = a.application_id
					AND tr.transaction_status = 'complete'
				)
			WHERE
				    a.application_id = ?
				AND a.company_id = (
					SELECT company_id
					FROM company
					WHERE
						name_short = ?
				)
				AND tl.transaction_type_id IN (
					SELECT transaction_type_id
					FROM transaction_type
					WHERE name_short LIKE 'ext_recovery%'
				)
			GROUP BY
				tl.application_id
			ORDER BY
				tl.date_created ASC
		";
				
		$args = array(
			$application_id,
			$company
		);
		
		return $query;
	}
	
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
	
	public function getApplicationFunded($application_id, $company, $active_status_id, array &$args)
	{
		$query = "
			SELECT DISTINCT
				{$this->buildFields($this->getCommonApplicationFields())}
			FROM
				status_history sh
				JOIN application a USING (application_id)
			WHERE
				    a.application_id = ?
				AND sh.application_status_id = ?
				AND a.company_id = (
					SELECT company_id
					FROM company
					WHERE
						name_short = ?
				)
			ORDER BY
				sh.date_created ASC
		";
				
		$args = array(
			$application_id,
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
