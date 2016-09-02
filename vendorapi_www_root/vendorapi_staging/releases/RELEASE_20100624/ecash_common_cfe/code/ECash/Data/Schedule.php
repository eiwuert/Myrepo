<?php

class ECash_Data_Schedule extends ECash_Data_DataRetriever
{
	/*
	 * 
	 */
	public function getEventTypes($company_id)
	{
		$query = "
			    SELECT      
			    	`et1`.`event_type_id`,
			    	`et1`.`name`,
			    	`tt1`.`clearing_type`
			    FROM        `event_type`        `et1`
			    JOIN        `event_transaction` `et2` ON ( `et1`.`event_type_id` = `et2`.`event_type_id` )
			    JOIN        `transaction_type`  `tt1` ON ( `et2`.`transaction_type_id` = `tt1`.`transaction_type_id` )
			    WHERE       `et1`.`company_id` = ?
			    ORDER BY    `et1`.`event_type_id` ASC,
			    		    `tt1`.`transaction_type_id`
			    ";
		return DB_Util_1::queryPrepared($this->db, $query, array($company_id));

	}
	
	/**
	 * @TODO replace event_type name_short in the WHERE with event_type
	 * event_type_id(s) from ECash_Model_Reference_EventType
	 * @param int $application_id application_id to retrieve data for
	 */
	public function getScheduledPaymentsLeft($application_id)
	{
		$query = "
		       select count(DISTINCT(es.date_effective))
		       from event_schedule as es
		       join event_type as et on (et.event_type_id = es.event_type_id)
		       where
		       es.event_status = 'scheduled' and
		       et.name_short IN ('payment_service_chg', 'repayment_principal', 'card_payment_service_chg', 'card_repayment_principal') and
		       application_id = ?
			   ";

		$st = $this->db->prepare($query);
		$st->execute(array($application_id));

		return $st->fetchColumn();
	}	
	/**
	 * @TODO replace event_type name_short in the WHERE with event_type
	 * event_type_id(s) from ECash_Model_Reference_EventType
	 * @param int $application_id application_id to retrieve data for
	 */
	public function getScheduledPayments($application_id)
	{
		/*
		$query = "
		       select es.date_effective,
				SUM(amount_principal) AS amount_principal,
				SUM(amount_non_principal) AS amount_principal
		       from event_schedule as es
		       join event_type as et on (et.event_type_id = es.event_type_id)
		       where
		       es.event_status = 'scheduled' and
		       et.name_short IN ('payment_service_chg', 'repayment_principal', 'card_payment_service_chg', 'card_repayment_principal') and
		       application_id = ?
		       GROUP BY date_effective
		       ORDER BY date_effective
			   ";
			   */
			   
		$query = "
		       select es.date_effective,
				SUM(amount_principal) AS amount_principal,
				SUM(amount_non_principal) AS amount_non_principal
		       from event_schedule as es
		       where
		       es.event_status = 'scheduled' and
		       application_id = ?
		       GROUP BY date_effective
		       ORDER BY date_effective
			   ";

		$st = $this->db->prepare($query);
		$st->execute(array($application_id));
		
		return $st->fetchAll();
	}

	public function getRecoveryAmounts($application_id)
	{
		$query = "
			SELECT
				SUM(IF(eat.name_short = 'principal', ea.amount, 0)) principal,
				SUM(IF(eat.name_short = 'service_charge', ea.amount, 0)) service_charge,
				SUM(IF(eat.name_short = 'fee', ea.amount, 0)) fee,
				SUM(IF(eat.name_short = 'irrecoverable', ea.amount, 0)) irrecoverable
			FROM
				event_amount ea
				JOIN event_amount_type eat USING(event_amount_type_id)
				JOIN transaction_register tr USING (transaction_register_id)
				JOIN transaction_type tt USING (transaction_type_id)
			WHERE
				ea.application_id = ?
				AND tr.transaction_status = 'complete'
				AND tt.name_short LIKE 'ext_recovery%'
		";
		return DB_Util_1::querySingleRow($this->db, $query, array($application_id), PDO::FETCH_OBJ);
	}

	/**
	 * [#45973] Added some features to allow this method to replace ecash_api.2.php Fetch_Balance_Information()
	 */
	public function getBalanceInformation($application_id, $use_current_date = FALSE)
	{
		$values = array();
		$pending = "
			    SUM( IF( eat.name_short = 'principal' AND tr.transaction_status IN ('complete', 'pending'), ea.amount, 0)) principal_pending,
			    SUM( IF( eat.name_short = 'service_charge' AND tr.transaction_status IN ('complete', 'pending'), ea.amount, 0)) service_charge_pending,
			    SUM( IF( eat.name_short = 'fee' AND tr.transaction_status IN ('complete', 'pending'), ea.amount, 0)) fee_pending,
			    SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status IN ('complete', 'pending'), ea.amount, 0)) total_pending,
			";
		
		if($use_current_date)
		{
			$pending = "
				SUM( IF( eat.name_short = 'principal' AND tr.transaction_status IN ('complete', 'pending') AND tr.date_effective <= ?, ea.amount, 0)) principal_pending,
				SUM( IF( eat.name_short = 'service_charge' AND tr.transaction_status IN ('complete', 'pending') AND tr.date_effective <= ?, ea.amount, 0)) service_charge_pending,
				SUM( IF( eat.name_short = 'fee' AND tr.transaction_status IN ('complete', 'pending') AND tr.date_effective <= ?, ea.amount, 0)) fee_pending,
				SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status IN ('complete', 'pending') AND tr.date_effective <= ?, ea.amount, 0)) total_pending,
			";

			//let's try to make this query non-deterministic
			$date = date('Y-m-d');

			for($i = 0; $i < 4; $i++)
			{
				$values[] = $date;
			}
		}

		$query = "
			SELECT
			    SUM( IF( eat.name_short = 'principal' AND tr.transaction_status = 'complete', ea.amount, 0)) principal_balance,
			    SUM( IF( eat.name_short = 'service_charge' AND tr.transaction_status = 'complete', ea.amount, 0)) service_charge_balance,
			    SUM( IF( eat.name_short = 'fee' AND tr.transaction_status = 'complete', ea.amount, 0)) fee_balance,
			    SUM( IF( eat.name_short = 'irrecoverable' AND tr.transaction_status = 'complete', ea.amount, 0)) irrecoverable_balance,
			    SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status = 'complete', ea.amount, 0)) total_balance,
				{$pending}
		    	SUM(IF(ea.num_reattempt = 0 AND eat.name_short = 'principal', ea.amount, 0)) principal_not_reatt,
				SUM(IF(ea.num_reattempt = 0 AND eat.name_short = 'service_charge', ea.amount, 0)) service_charge_not_reatt,
				SUM(IF(ea.num_reattempt = 0 AND eat.name_short = 'fee', ea.amount, 0)) fee_not_reatt,
				SUM(IF(ea.num_reattempt = 0, ea.amount, 0)) total_not_reatt,
				MAX(IF(tr.transaction_status = 'failed' AND eat.name_short = 'principal', ea.num_reattempt, 0)) principal_num_reattempts,
				MAX(IF(tr.transaction_status = 'failed' AND eat.name_short = 'service_charge', ea.num_reattempt, 0)) service_charge_num_reattempts,
				MAX(IF(tr.transaction_status = 'failed' AND eat.name_short = 'fee', ea.num_reattempt, 0)) fee_num_reattempts,
				SUM(IF( tt.name_short IN ('loan_disbursement','card_loan_disbursement','converted_principal_bal') AND tr.transaction_status IN ('complete', 'pending'), 1,0)) funded_transactions,
				SUM(IF(tr.amount > 0 AND tr.transaction_status = 'pending' AND eat.name_short <> 'irrecoverable', ea.amount, 0)) pending_credits,
				COUNT(DISTINCT IF(tt.name_short IN ('converted_sc_event', 'payment_service_chg', 'card_payment_service_chg') AND tr.transaction_status IN ('complete', 'pending'), tr.event_schedule_id, NULL)) sc_count
			FROM
				event_amount ea
				JOIN event_amount_type eat USING (event_amount_type_id)
				JOIN transaction_register tr USING(transaction_register_id)
				JOIN transaction_type tt USING(transaction_type_id)
			  WHERE
				ea.application_id = ?
			  GROUP BY ea.application_id
		";

		$values[] = $application_id;
		return DB_Util_1::querySingleRow($this->db, $query, $values, PDO::FETCH_OBJ);
	}

	public function getSchedule($application_id)
	{
		$query = "
		(
		SELECT et.name_short as 'type',
		    es.event_schedule_id,
		    es.date_modified,
		    DATE_FORMAT(es.date_modified, '%m/%d/%Y %H:%i:%S') AS date_modified_display,
		    es.event_type_id as 'type_id',
		    NULL as 'transaction_register_id',
		    NULL as 'ach_id',
		    NULL as 'clearing_type',
		    es.origin_id,
		    es.origin_group_id,
		    es.context,
		    et.name as 'event_name',
		    et.name_short as 'event_name_short',
		    es.amount_principal as 'principal_amount',
		    es.amount_non_principal as 'fee_amount',
		    ea.principal as 'principal',
		    ea.service_charge as 'service_charge',
		    ea.fee as 'fee',
		    ea.irrecoverable as 'irrecoverable',
		    es.date_event as 'date_event',
		    DATE_FORMAT(es.date_event, '%m/%d/%Y') AS date_event_display,
		    es.date_effective as 'date_effective',
		    DATE_FORMAT(es.date_effective, '%m/%d/%Y') AS date_effective_display,
		    es.event_status as 'status',
		    es.configuration_trace_data as 'comment',
			NULL as 'ach_return_code_id',
		    NULL as 'return_date',
		    NULL as 'return_date_display',
		    NULL as 'return_code',
		    dces.company_id as 'debt_consolidation_company_id',
		    es.is_shifted as is_shifted,
				NULL AS bank_aba,
				NULL AS bank_account,
				NULL AS current_bank_aba,
				NULL AS current_bank_account
	    FROM event_schedule es
	    JOIN event_type et USING (event_type_id)
	    JOIN (
	      SELECT
		  easub.event_schedule_id,
		  SUM(IF(eat.name_short = 'principal', easub.amount, 0)) principal,
		  SUM(IF(eat.name_short = 'service_charge', easub.amount, 0)) service_charge,
		  SUM(IF(eat.name_short = 'fee', easub.amount, 0)) fee,
		  SUM(IF(eat.name_short = 'irrecoverable', easub.amount, 0)) irrecoverable
		FROM
		  event_amount easub
		  LEFT JOIN event_amount_type eat USING (event_amount_type_id)
			WHERE easub.application_id = {$application_id}
		GROUP BY easub.event_schedule_id) ea USING (event_schedule_id)
		LEFT JOIN debt_company_event_schedule as dces USING (event_schedule_id)
	    WHERE es.application_id = {$application_id}
	    AND es.event_status = 'scheduled'
		)
		UNION
		(
	    SELECT  tt.name_short as 'type',
		    tr.event_schedule_id,
		    tr.date_modified,
		    DATE_FORMAT(tr.date_modified, '%m/%d/%Y %H:%i:%S') AS date_modified_display,
		    tr.transaction_type_id as 'type_id',
		    tr.transaction_register_id,
		    tr.ach_id,
		    tt.clearing_type,
		    es.origin_id,
		    es.origin_group_id,
		    es.context,
		    tt.name as 'event_name',
		    tt.name_short as 'event_name_short',
		    IF(tt.affects_principal LIKE 'yes', tr.amount, 0.00) as 'principal_amount',
		    IF(tt.affects_principal LIKE 'yes', 0.00, tr.amount) as 'fee_amount',
		    ea.principal as 'principal',
		    ea.service_charge as 'service_charge',
		    ea.fee as 'fee',
		    ea.irrecoverable as 'irrecoverable',
		    DATE(es.date_event) as 'date_event',
		    DATE_FORMAT(es.date_event, '%m/%d/%Y') AS date_event_display,
		    tr.date_effective,
		    DATE_FORMAT(tr.date_effective, '%m/%d/%Y') AS date_effective_display,
		    tr.transaction_status as 'status',
		    es.configuration_trace_data as 'comment',
		    arc.ach_return_code_id as ach_return_code_id,
		    CASE
		      WHEN tt.clearing_type = 'ach' AND ar.ach_report_id IS NOT NULL
		      THEN ar.date_request
		      ELSE
			(
			    SELECT th_1.date_created
			      FROM transaction_history th_1
			      WHERE
				th_1.transaction_register_id = tr.transaction_register_id
				AND tr.transaction_status = 'failed'
				AND th_1.status_after = 'failed'
			      ORDER BY
				th_1.date_created DESC
			      LIMIT 1
			)
		    END as 'return_date',
		    CASE
		      WHEN tt.clearing_type = 'ach' AND ar.ach_report_id IS NOT NULL
		      THEN DATE_FORMAT(ar.date_request, '%m/%d/%Y %H:%i:%S')
		      ELSE
			(
				SELECT DATE_FORMAT(th_1.date_created, '%m/%d/%Y %H:%i:%S')
				  FROM transaction_history th_1
				  WHERE
					th_1.transaction_register_id = tr.transaction_register_id
					AND tr.transaction_status = 'failed'
					AND th_1.status_after = 'failed'
				  ORDER BY
					th_1.date_created DESC
				  LIMIT 1
			)
		    END as return_date_display,
		    arc.name_short as 'return_code',
		    dces.company_id as 'debt_consolidation_company_id',
		    es.is_shifted as is_shifted,
				ach.bank_aba,
				ach.bank_account,
				app.bank_aba as current_bank_aba,
				app.bank_account as current_bank_account
	    FROM    transaction_register tr
	    JOIN event_schedule AS es USING (event_schedule_id)
		LEFT JOIN debt_company_event_schedule as dces USING (event_schedule_id)
	    LEFT JOIN (
	      SELECT
		  easub.transaction_register_id,
		  SUM(IF(eat.name_short = 'principal', easub.amount, 0)) principal,
		  SUM(IF(eat.name_short = 'service_charge', easub.amount, 0)) service_charge,
		  SUM(IF(eat.name_short = 'fee', easub.amount, 0)) fee,
		  SUM(IF(eat.name_short = 'irrecoverable', easub.amount, 0)) irrecoverable
		FROM
		  event_amount easub
		  LEFT JOIN event_amount_type eat USING (event_amount_type_id)
			WHERE easub.application_id = {$application_id}
		GROUP BY easub.transaction_register_id
	    ) ea USING(transaction_register_id)
	    LEFT JOIN transaction_type AS tt USING (transaction_type_id)
	    LEFT JOIN ach USING (ach_id)
	    LEFT JOIN ach_report AS ar USING (ach_report_id)
	    LEFT JOIN ach_return_code AS arc USING (ach_return_code_id)
	    LEFT JOIN application AS app ON (ach.application_id = app.application_id)
	    WHERE tr.application_id = {$application_id}
		)
		ORDER BY date_event, principal_amount asc, fee_amount ASC ";
	}
}

?>