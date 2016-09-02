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
		       et.name_short IN ('payment_service_chg', 'repayment_principal') and
		       application_id = ?
			   ";

		$st = $this->db->prepare($query);
		$st->execute(array($application_id));

		return $st->fetchColumn();
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

	public function getBalanceInformation($application_id)
	{
		$query = "
			SELECT
			    SUM( IF( eat.name_short = 'principal' AND tr.transaction_status = 'complete', ea.amount, 0)) principal_balance,
			    SUM( IF( eat.name_short = 'service_charge' AND tr.transaction_status = 'complete', ea.amount, 0)) service_charge_balance,
			    SUM( IF( eat.name_short = 'fee' AND tr.transaction_status = 'complete', ea.amount, 0)) fee_balance,
			    SUM( IF( eat.name_short = 'irrecoverable' AND tr.transaction_status = 'complete', ea.amount, 0)) irrecoverable_balance,
			    SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status = 'complete', ea.amount, 0)) total_balance,
			    SUM( IF( eat.name_short = 'principal' AND tr.transaction_status IN ('complete', 'pending'), ea.amount, 0)) principal_pending,
			    SUM( IF( eat.name_short = 'service_charge' AND tr.transaction_status IN ('complete', 'pending'), ea.amount, 0)) service_charge_pending,
			    SUM( IF( eat.name_short = 'fee' AND tr.transaction_status IN ('complete', 'pending'), ea.amount, 0)) fee_pending,
			    SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status IN ('complete', 'pending'), ea.amount, 0)) total_pending
			  FROM
				event_amount ea
				JOIN event_amount_type eat USING (event_amount_type_id)
				JOIN transaction_register tr USING(transaction_register_id)
			  WHERE
				ea.application_id = ?
			  GROUP BY ea.application_id
		";

		return DB_Util_1::querySingleRow($this->db, $query, array($application_id), PDO::FETCH_OBJ);
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
		    IF(tt.clearing_type = 'quickcheck', qrc.ach_return_code_id, arc.ach_return_code_id) as ach_return_code_id,
		    CASE
		      WHEN tt.clearing_type = 'quickcheck' AND er.ecld_return_id IS NOT NULL
		      THEN er.date_created
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
		      WHEN tt.clearing_type = 'quickcheck' AND er.ecld_return_id IS NOT NULL
		      THEN DATE_FORMAT(er.date_created, '%m/%d/%Y')
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
		    IF(tt.clearing_type = 'quickcheck', ecld.return_reason_code, arc.name_short) as 'return_code',
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
	    LEFT JOIN ecld USING (ecld_id)
	    LEFT JOIN ecld_return AS er USING (ecld_return_id)
	    LEFT JOIN ach_report AS ar USING (ach_report_id)
	    LEFT JOIN ach_return_code AS arc USING (ach_return_code_id)
	    LEFT JOIN ach_return_code AS qrc ON (qrc.name_short = ecld.return_reason_code)
	    LEFT JOIN application AS app ON (ach.application_id = app.application_id)
	    WHERE tr.application_id = {$application_id}
		)
		ORDER BY date_event, principal_amount asc, fee_amount ASC ";
	}
}

?>