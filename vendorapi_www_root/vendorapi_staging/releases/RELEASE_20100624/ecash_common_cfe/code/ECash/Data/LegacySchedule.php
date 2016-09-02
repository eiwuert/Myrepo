<?php

class ECash_Data_LegacySchedule extends ECash_Data_DataRetriever
{
	public function Fetch_Balance($app_id)
	{
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
			SELECT
			    SUM( IF( eat.name_short = 'principal' AND tr.transaction_status = 'complete', ea.amount, 0)) principal_balance,
			    SUM( IF( eat.name_short = 'service_charge' AND tr.transaction_status = 'complete', ea.amount, 0)) service_charge_balance,
			    SUM( IF( eat.name_short = 'fee' AND tr.transaction_status = 'complete', ea.amount, 0)) fee_balance,
			    SUM( IF( eat.name_short = 'irrecoverable' AND tr.transaction_status = 'complete', ea.amount, 0)) irrecoverable_balance,
			    SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status = 'complete', ea.amount, 0)) total_balance,
			    -- SUM( IF( eat.name_short <> 'irrecoverable' AND (tr.transaction_status = 'complete' OR (tr.transaction_status = 'pending' AND eat.name_short = 'principal' AND ea.amount > 0)), ea.amount, 0)) posted_total,
			    SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status = 'pending' AND eat.name_short = 'principal' AND ea.amount > 0, ea.amount, 0)) credit_principal_pending,
			    SUM( IF( eat.name_short = 'principal' AND tr.transaction_status IN ('complete', 'pending'), ea.amount, 0)) principal_pending,
			    SUM( IF( eat.name_short = 'service_charge' AND tr.transaction_status IN ('complete', 'pending'), ea.amount, 0)) service_charge_pending,
			    SUM( IF( eat.name_short = 'fee' AND tr.transaction_status IN ('complete', 'pending'), ea.amount, 0)) fee_pending,
			    SUM( IF( eat.name_short <> 'irrecoverable' AND tr.transaction_status IN ('complete', 'pending'), ea.amount, 0)) total_pending,
			    SUM( IF( eat.name_short = 'fee' AND tr.transaction_status IN ('complete', 'pending') AND tt.name_short = 'assess_fee_delivery', ea.amount, 0)) delivery_fee
			  FROM
				event_amount ea
				JOIN event_amount_type eat USING (event_amount_type_id)
				JOIN transaction_register tr USING(transaction_register_id)
				JOIN transaction_type tt USING (transaction_type_id)
			  WHERE
				ea.application_id = {$app_id}
			  GROUP BY ea.application_id
			
			";

		$result = $this->db->Query($query);
		$row = $result->fetch(PDO::FETCH_OBJ);
		return $row;
	}
	
	public function fetch_due_dates($app_id, $company_id)
	{
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
    			SELECT 
                    es.application_id,
                    date_format(es.date_effective, '%m/%d/%Y') as due_date,
                    abs(sum(es.amount_principal)) + abs(sum(es.amount_non_principal)) as total_due,
                    abs(sum(es.amount_non_principal)) as service_charge,
					abs(sum(es.amount_principal)) as principal
                FROM 
                    event_schedule es,
                    event_type et  
                WHERE 
                    es.application_id = '{$app_id}'  
                    AND et.event_type_id = es.event_type_id  
                    AND et.company_id = {$company_id}
					AND es.event_status = 'scheduled'
                    AND et.name_short IN ('payment_service_chg',
					'repayment_principal',
					'payout',
					'paydown',
					'full_balance',
					'manual_ach',
					'moneygram',
					'money_order',
					'payment_arranged',
					'payment_arranged_fees',
					'payment_card_payoff',
					'payment_debt',
					'payment_fee_ach_fail',
					'payment_fee_delivery',
					'payment_fee_lien',
					'payment_manual',
					'personal_check',
					'western_union',
					'card_payment_service_chg',
					'card_repayment_principal',
					'card_payout',
					'card_paydown',
					'card_full_balance',
					'card_payment_arranged',
					'payment_fee_card_fail',
					'card_payment_manual'
		)  
                GROUP BY 
                    es.date_effective
                ORDER BY
                    es.date_effective
                LIMIT 2
		";
		$result = $this->db->Query($query);
		return $result;
	}
	
	public function fetch_last_payment($app_id, $company_id)
	{
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . "\n" . <<<ESQL

		SELECT
                    es.date_effective                                  AS payment_date,
                   ABS( SUM(es.amount_principal + es.amount_non_principal)) AS payment_total
                FROM
                    event_schedule es
                WHERE
                    es.application_id = "{$app_id}"
                    AND es.company_id = {$company_id}
                    AND (es.amount_principal < 0 OR es.amount_non_principal < 0)
                    AND es.date_effective <= CURDATE()
                    AND es.event_status = 'registered'
                GROUP BY
                    date_effective
                ORDER BY
                    date_effective DESC
                LIMIT 1
ESQL;
		$result = $this->db->Query($query);
		return $result;
	}

	
	public function fetch_card_payment($app_id, $company_id)
	{
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . "\n" . <<<ESQL

		SELECT
			es.date_effective AS payment_date,
			ABS( SUM(es.amount_principal + es.amount_non_principal)) AS payment_total,
			cp.authorization_code
                FROM
                    event_schedule es
		    join (
			select cps.*,tr.event_schedule_id from card_process cps
			join transaction_register tr on tr.card_process_id = cps.card_process_id
		    ) cp on es.event_schedule_id = cp.event_schedule_id
                WHERE
                    es.application_id = "{$app_id}"
                    AND es.company_id = {$company_id}
                    AND (es.amount_principal < 0 OR es.amount_non_principal < 0)
                    AND es.date_effective <= CURDATE()
                    AND es.event_status = 'registered'
                GROUP BY
                    date_effective
                ORDER BY
                    date_effective DESC
                LIMIT 1
ESQL;
		$result = $this->db->Query($query);
		return $result;
	}

	/**
	 * [#47989] changed past arrangement to be <= today and future
	 * arrangement to be > today (vs. < today & >= today) so that
	 * payments that were supposed be paid today will go out correctly
	 * in documents sent today (rather than incorrectly listing the
	 * previous payment)
	 */
	public function fetch_arrangements($app_id, $company_id)
	{
		$sql = <<<ESQL
				(
				SELECT 
                    es.application_id,
				    es.event_status,
				    et.name,
                    date_format(es.date_effective, '%m/%d/%Y') as due_date,
                    abs(sum(es.amount_principal)) + abs(sum(es.amount_non_principal)) as total_due,
				    datediff(es.date_effective, curdate()) as days_til_due
                FROM 
                    event_schedule es,
                    event_type et  
                WHERE 
                    es.application_id = "{$app_id}"  
                    AND et.event_type_id = es.event_type_id  
                    AND et.company_id = {$company_id}					
                    AND et.name_short IN (
											'western_union',
											'credit_card',
											'personal_check',
											'payment_debt',
											'money_order',
											'moneygram',
											'payment_manual',
											'payment_arranged',
											'paydown',
											'card_payment_manual',
											'card_payment_arranged',
											'card_paydown'
										)  
		    		AND datediff(es.date_effective, curdate()) <= 0
                GROUP BY 
                    es.date_effective
                ORDER BY
                    es.date_effective DESC
				LIMIT 1	
				)
			UNION	
				(	
				SELECT 
                    es.application_id,
				    es.event_status,
				    et.name,
                    date_format(es.date_effective, '%m/%d/%Y') as due_date,
                    abs(sum(es.amount_principal)) + abs(sum(es.amount_non_principal)) as total_due,
				    datediff(es.date_effective, curdate()) as days_til_due
                FROM 
                    event_schedule es,
                    event_type et  
                WHERE 
                    es.application_id = "{$app_id}"  
                    AND et.event_type_id = es.event_type_id  
                    AND et.company_id = {$company_id}					
                    AND et.name_short IN (
											'western_union',
											'credit_card',
											'personal_check',
											'payment_debt',
											'money_order',
											'moneygram',
											'payment_manual',
											'payment_arranged',
											'paydown',
											'payout',
											'card_payment_manual',
											'card_payment_arranged',
											'card_paydown',
											'card_payout'
										)  
				    AND datediff(es.date_effective, curdate()) > 0
                GROUP BY 
                    es.date_effective
                ORDER BY
                    es.date_effective ASC
				LIMIT 1	
				)       
ESQL;

		$result = $this->db->Query($sql);
		return $result;
		
		
	}
	
	public function has_schedule($app_id)
	{
		$sql = "
			SELECT COUNT(*) as 'count'
			FROM event_schedule
			WHERE application_id = {$app_id}
			AND event_status = 'scheduled'"; //"
	
		$db = $this->db;
		
		$result = $db->query($sql);
		$count = $result->fetch(PDO::FETCH_OBJ)->count;
		return ($count != 0);	
	}
	
	public function fetch_schedule($app_id)
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
	            NULL as 'date_registered',
	            es.configuration_trace_data as 'comment',
	    		NULL as 'ach_return_code_id',
	            NULL as 'return_date',
	            NULL as 'return_date_display',
	            NULL as 'return_code',
	            NULL as is_fatal,
	            dces.company_id as 'debt_consolidation_company_id',
	            es.is_shifted as is_shifted,
				NULL AS bank_aba,
				NULL AS bank_account,
				NULL AS current_bank_aba,
				NULL AS current_bank_account,
				aaes.agent_affiliation_id AS agent_affiliation
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
			WHERE easub.application_id = {$app_id}
		GROUP BY easub.event_schedule_id) ea USING (event_schedule_id)
		LEFT JOIN debt_company_event_schedule as dces USING (event_schedule_id)
		LEFT JOIN agent_affiliation_event_schedule AS aaes USING (event_schedule_id)
	    WHERE es.application_id = {$app_id}
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
	            DATE_FORMAT(tr.date_created, '%m/%d/%Y') AS date_registered,
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
	            arc.is_fatal as is_fatal,
	            dces.company_id as 'debt_consolidation_company_id',
	            es.is_shifted as is_shifted,
				ach.bank_aba,
				ach.bank_account,
				app.bank_aba as current_bank_aba,
				app.bank_account as current_bank_account,
				aaes.agent_affiliation_id AS agent_affiliation
	    FROM    transaction_register tr
	    JOIN event_schedule AS es USING (event_schedule_id)
		LEFT JOIN debt_company_event_schedule as dces USING (event_schedule_id)
		LEFT JOIN agent_affiliation_event_schedule AS aaes USING (event_schedule_id)
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
			WHERE easub.application_id = {$app_id}
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
	    WHERE tr.application_id = {$app_id}
		)
		ORDER BY date_event, principal_amount asc, fee_amount ASC ";
	
		$db = $this->db;
		$schedule = array();
		$result = $db->query($query);
		$app_client = ECash::getFactory()->getWebServiceFactory()->getWebService('application');
		$bank_info = $app_client->getBankInfo($app_id);
		
		while($row = $result->fetch(PDO::FETCH_OBJ))
		{
			if(!empty($bank_info))
			{
				$row->current_bank_aba = $bank_info->bank_aba;
				$row->current_bank_account = $bank_info->bank_account;
			}			
			$event = $this->Schedule_Event_Load_From_Row($row);
			$event->amounts = $this->Event_Amount_Load_Amounts_From_Fetch_schedule_row($row);
			$schedule[] = $event;
		}
	
		return $schedule;
	}
	
	protected function Schedule_Event_Load_From_Row($row)
	{
		return ($row);
	}
	/**
	 * Returns a new Event Amount Object
	 *
	 * @param string $event_amount_type
	 * @param float $amount
	 * @param int $num_reattempt
	 * @return Event_Amount
	 */
	public function MakeEventAmount($event_amount_type, $amount, $num_reattempt = 0)
	{

		$ea = new Event_Amount();
		$ea->event_amount_type = $event_amount_type;
		$ea->amount = $amount;
		$ea->num_reattempt = $num_reattempt;
		return $ea;
	}
	
	/**
	 * To load, the passed row should have:
	 * principal
	 * principal_reatt
	 * principal_reatt_count
	 * service_charge
	 * service_charge_reatt
	 * service_charge_reatt_count
	 * fee
	 * fee_reatt
	 * fee_reatt_count
	 * irrecoverable
	 * irrecoverable_reatt
	 * irrecoverable_reatt_count
	 */	
	protected function Event_Amount_Load_Amounts_From_Fetch_schedule_row($row)
	{
		$amounts = array();
		$amounts[] = $this->MakeEventAmount('principal', $row->principal, 0);
		$amounts[] = $this->MakeEventAmount('service_charge', $row->service_charge, 0);
		$amounts[] = $this->MakeEventAmount('fee', $row->fee, 0);
		$amounts[] = $this->MakeEventAmount('irrecoverable', $row->irrecoverable, 0);
		
		return $amounts;		
	}
		
	/**
	 * Fetch_Balance_Total_By_Event_Names
	 * Fetches the total for events with a certain name, of a certain type.  
	 *
	 * @param integer $application_id The app_id you want to get the amount for.
	 * @param array $event_names the names of the events you want the total for (like 'assess_fee_lien')
	 * @param array $event_types The list of event amount types you want to include in this balance ('fee','principal','service_charge').   Null includes all types.
	 * @return float the total amount.
	 */
	public function Fetch_Balance_Total_By_Event_Names($application_id, $event_names, $event_types = null)
	{
		settype($application_id, 'integer');
		$db = $this->db;
		$event_type_where = '';
		$event_names_list = "('" . implode("','",$event_names) . "')";
	
		if (is_array($event_types) && sizeof($event_types>0))
		{
			$event_types_list = "('" . implode("','", $event_types). "')";
			$event_type_where = "AND eat.name_short IN {$event_types_list}";
		}
		$query = "-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
					SUM(IF( tr.transaction_status IN ('complete', 'pending') {$event_type_where}, ea.amount, 0)) total_amount
			FROM	event_amount AS ea
			JOIN 	event_amount_type AS eat USING (event_amount_type_id)
			JOIN 	event_schedule AS es ON es.event_schedule_id =  ea.event_schedule_id
			JOIN 	event_type AS et ON et.event_type_id = es.event_type_id
			JOIN 	transaction_register AS tr ON tr.event_schedule_id = es.event_schedule_id
			WHERE
					ea.application_id = $application_id
			AND
					et.name_short IN $event_names_list ";
	
	    
	    $result = $db->query($query);
	
	    if ($row = $result->fetch(PDO::FETCH_OBJ))
			return $row->total_amount;
		else
			return 0;
	}
	// Returns true if application has events in the event schedule of the event
	// names passed as an array.
	public function Application_Has_Events_By_Event_Names($application_id, $event_names)
	{
	    settype($application_id, 'integer');
	    $db = $this->db;
	
	    $event_names_list = "('" . implode("','",$event_names) . "')";
	
	    $query = "-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT	COUNT(*) as event_count
			FROM	event_schedule AS es
			JOIN 	event_type et USING (event_type_id)
			WHERE
					es.application_id = $application_id
			AND
					et.name_short IN $event_names_list ";
	
	    $result = $db->query($query);
	
	    $row = $result->fetch(PDO::FETCH_OBJ);
	
	    if ($row->event_count > 0)
			return true;
		else
			return false;
	}
	
}


?>
