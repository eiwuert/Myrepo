<?php

require_once( SERVER_CODE_DIR . "base_report_query.class.php" );

class Payments_Due_Report_Query extends Base_Report_Query
{
	const TIMER_NAME    = "Payments Due Report Query - New";
	const ARCHIVE_TIMER = "Payments Due Report Query - Archive";
	const CLI_TIMER     = "CLI - ";

	// # days worth of reports
	const MAX_SAVE_DAYS = "30";

	/**
	 * Gets the application_status_id's for the cashline branch to
	 * ensure the report does not include them.  Might not be needed
	 * for commercial
	 *
	 * @returns array ids
	 */
	private function Get_Cashline_Ids()
	{
		$cashline_statuses = array();

		$asf = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');

		$cashline_statuses[] = $asf->toId('queued::cashline::*root');
		$cashline_statuses[] = $asf->toId('dequeued::cashline::*root');
		$cashline_statuses[] = $asf->toId('pending_transfer::cashline::*root');

		return implode(',', $cashline_statuses);
	}

	private function Get_Arrangement_Status_Ids()
	{
		$arrangement_statuses = array();

		$asf = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');

		$arrangement_statuses[] = $asf->toId('arrangements_failed::arrangements::collections::customer::*root');
		$arrangement_statuses[] = $asf->toId('current::arrangements::collections::customer::*root');
		//this one is pointless b/c it's part of the exclude list
		//$arrangement_statuses[] = $asf->toId('hold::arrangements::collections::customer::*root');

		return implode(',', $arrangement_statuses);
	}

	/**
	 * Saves the data for 1 day of the report and deletes old entries
	 * @param array  $data array as returned from Fetch_Current_Data()
	 * @param string $date MySQL5 formatted date (YYYY-MM-DD)
	 * @access public
	 * @throws Exception
	 */
	public function Save_Report_Data($data)
	{
		// Check the date passed
		/*if( strlen($date) == 10 )
		{
			$year  = substr($date, 0, 4);
			$month = substr($date, 5, 2);
			$day   = substr($date, 8, 2);
		}
		elseif( strlen($date) == 8 )
		{
			$year  = substr($date, 0, 4);
			$month = substr($date, 4, 2);
			$day   = substr($date, 6, 2);
		}
		else
		{
			$year  = null;
			$day   = null;
			$month = null;
		}

		if( ! checkdate($month, $day, $year) )
		{
			throw new Exception( "Payments Due Report [" . __METHOD__ . ":" . __LINE__ . "] invalid date parameter:  '{$date}'" );
		}

		// Quote the date for the query
		$date = "'{$date}'";*/

		// First make room
		// This is disabled for now.
		//$this->Delete_Old_Data();
		
		$company_ids = $this->Get_Company_Ids();
		$db = ECash::getMasterDb();
		foreach( $data as $co => $line )
		{
			for( $x = 0 ; $x < count($line) ; ++$x )
			{
				$save_query = "
					-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
					INSERT INTO resolve_payments_due_report
						(date_created,  report_date, company_id,    company_name_short, application_id, name_last,
						name_first,     email, 	     phone_home,    phone_cell,         status,         pay_period,
						direct_deposit, event_type,  clearing_type, principal,   	fees,
						service_charge, total_due,   next_due,      loan_type,          first_time_due, pay_out,
						special_arrangements, event_schedule_id, application_status_id, is_ach)
					VALUES
					";

				// Reformat the next_due date into standard mysql format
				$next_due_split = explode("/", $line[$x]['next_due']);
				if (count($next_due_split) < 3) {
					$next_due_split = $line[$x]['next_due'];
				}

			

				// All this comes from the database, no escaping necessary
				$company_id    = $company_ids[strtolower($co)];
				$date          = $db->quote($line[$x]['payment_date']);
				$co_name       = $db->quote(strtolower($co));
				$name_last     = $db->quote($line[$x]['name_last']);
				$name_first    = $db->quote($line[$x]['name_first']);
				$email 	       = $db->quote($line[$x]['email']);
				$phone_home    = $db->quote($line[$x]['phone_home']);
				$phone_cell    = $db->quote($line[$x]['phone_cell']);
				$status        = $db->quote(strtolower($line[$x]['status']));
				$frequency     = $db->quote($line[$x]['frequency']);
				$dd            = $db->quote($line[$x]['dd']);
				$event_type    = $db->quote($line[$x]['event_type']);
				$clearing_type = $db->quote($line[$x]['clearing_type']);
				$next_due      = $db->quote($next_due_split[2] . "-" . $next_due_split[0] . "-" . $next_due_split[1]);
				$loan_type     = $db->quote($line[$x]['loan_type_short']);
				$app_id        = $line[$x]['application_id'];
				$first_pay     = $line[$x]['first_payment'] ? 1 : 0;
				$special       = $line[$x]['special'] ? 1 : 0;
				$principal     = number_format($line[$x]['principal'],      2, ".", "");
				$fees          = number_format($line[$x]['fees'],           2, ".", "");
				$service       = number_format($line[$x]['service_charge'], 2, ".", "");
				$amount_due    = number_format($line[$x]['amount_due'],     2, ".", "");
				$payout        = $line[$x]['payout'] ? 1 : 0;
				$evnt_sched    = $line[$x]['event_schedule_id'];
				$app_status    = $line[$x]['application_status_id'];
				$is_ach        = $line[$x]['is_ach'] ? 1 : 0;

				$save_query .= "
					(now(), {$date}, {$company_id}, {$co_name}, {$app_id}, {$name_last},
					{$name_first}, {$email}, {$phone_home}, {$phone_cell}, {$status}, {$frequency},
					{$dd}, {$event_type}, {$clearing_type}, {$principal}, {$fees},
					{$service}, {$amount_due}, {$next_due}, {$loan_type}, '{$first_pay}', '{$payout}',
					'{$special}', {$evnt_sched}, {$app_status}, '{$is_ach}')
					";
				
				
				/**
				 * Fix for Mantis issue 1570.
				 * 
				 * Problem was redundant records for a specific app_id.
				 * Note that app_id is not an index for this table.
				 * This fix prevents future redundant records, without altering
				 * the table description. Another way, which involves a
				 * table alteration, would be to make
				 * application_id a unique index and use a single query
				 * using REPLACE.
				 *
				 * Removing existing redundant records is a one-time job
				 * for a particular database, therefore this will be done
				 * using a separate script.
				 *  
				*/
				
				/**
				 * This was not a bug.  There may be multiple entries for 
				 * an application ID in this table, but as long as they 
				 * do not occur on the same day, it's fine.  This table is
				 * used for a snapshot of a report, so there may be entries
				 * for a single application id for many different days.
				*/
				
				/*			
				$delete_query = "
					-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
					DELETE FROM resolve_payments_due_report
						WHERE application_id = {$app_id}
						";
				
				// delete existing records for this application_id
				$this->mysqli->Query($delete_query);
				*/
				// new record for this application_id
				// MUST use the Master when writing this.
				//$save_result = $this->mysqli->Query($save_query);a
				
				$save_result = $db->query($save_query);
			}
		}
	}

	public function Fetch_Payments_Due_Data($start_date, $end_date, $loan_type, $company_id, $mode = null, $force_save = false, $batch_type = NULL, $ach_batch_company = NULL)
	{
		$s_year  = substr($start_date, 0, 4);
		$s_month = substr($start_date, 4, 2);
		$s_day   = substr($start_date, 6, 2);
		$s_timestamp = mktime(0, 0, 0, $s_month, $s_day, $s_year);

		$e_year  = substr($end_date, 0, 4);
		$e_month = substr($end_date, 4, 2);
		$e_day   = substr($end_date, 6, 2);
		$e_timestamp = mktime(0, 0, 0, $e_month, $e_day, (int) $e_year);

		// CLI or web?
		if( ! empty($mode) && strtolower($mode) == 'cli' )
			$timer = self::CLI_TIMER;
		else
			$timer = "";

		// Recent, should be in saved table
		$data = array();

		// If date is from yesterday or before that
        /* Currently disabled awaiting BrianR's instruction on how to deal with this (didnt work before anyways) [benb]
		if( $s_timestamp < mktime(0, 0, 0) && $e_timestamp < mktime(0, 0, 0))
		{
			$timer .= self::ARCHIVE_TIMER;
			$data = $this->Fetch_Past_Data($start_date, $end_date, $loan_type, $company_id, $timer);
		}
		
		if (count($data)) {
			return $data;
		}*/
		
		$timer .= self::TIMER_NAME;
		$data = $this->Fetch_Current_Data($start_date, $end_date, $loan_type, $company_id, $timer, $batch_type, $ach_batch_company);
		
		// $this->Save_Report_Data($data);
        /* Currently disabled awaiting BrianR's instruction on how to deal with this (didnt work before anyways) [benb]
		if ((strtotime($start_date) < mktime(0, 0, 0)) || $force_save) {
			$this->Save_Report_Data($data);
		}*/

		return $data;
	}
	
	private function Delete_Old_Data()
	{
		// DELETE old data
		$delete_query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			DELETE FROM resolve_payments_due_report
			 WHERE report_date <= DATE_SUB(CURRENT_DATE(), INTERVAL " . self::MAX_SAVE_DAYS . " DAY)
			";
	}

	private function Fetch_Past_Data($start_date, $end_date, $loan_type, $company_id, $timer)
	{
		$this->timer->startTimer( $timer );

		$data = array();

		if(isset($_SESSION) && is_array($_SESSION['auth_company']['id']) && count($_SESSION['auth_company']['id']) > 0)
		{
			$auth_company_ids = $_SESSION['auth_company']['id'];
		}
		else
		{
			$auth_company_ids = array(-1);
		}

		if( $company_id > 0 )
			$company_list = "'{$company_id}'";
		else
			$company_list = "'" . implode("','", $auth_company_ids) . "'";

		$loan_type_list = $this->Get_Loan_Type_List($loan_type);

		$past_query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT DISTINCT
				pd.event_schedule_id AS event_schedule_id,
				pd.company_name_short AS company_name,
				pd.application_id AS application_id,
				pd.name_last AS name_last,
				pd.name_first AS name_first,
				pd.email AS email,
				pd.phone_home AS phone_home,
				pd.phone_cell AS phone_cell,
				pd.event_type AS event_type,
				pd.clearing_type AS clearing_type,
				pd.status AS status,
				UCASE(REPLACE(pd.pay_period,'_',' ')) frequency,
				pd.direct_deposit AS dd,
				pd.company_id AS company_id,
				pd.application_status_id AS application_status_id,
				UCASE(REPLACE(pd.loan_type,'_',' '))         AS loan_type,
				pd.is_ach AS is_ach,
				DATE_FORMAT(pd.next_due,'%c/%e/%y')             AS next_due,
				pd.first_time_due AS first_payment,
				pd.special_arrangements AS special,
				pd.pay_out AS payout,
				pd.principal AS principal,
				pd.fees AS fees,
				pd.service_charge AS service_charge,
				pd.total_due AS amount_due,
				pd.report_date AS payment_date 
			FROM
				resolve_payments_due_report pd
			WHERE
				pd.report_date          =  '{$start_date}'
			 AND	pd.loan_type            IN ({$loan_type_list})
			 AND	pd.company_id           IN ({$company_list})
			ORDER BY company_name
			";
		
		$db = ECash::getMasterDb();

		$past_result = $db->query($past_query);

		$data = array();

		while( $row = $past_result->fetch(PDO::FETCH_ASSOC) )
		{
			$co = $row['company_name'];
		//	unset($row['company_name']);
			
			$this->Get_Module_Mode($row);
			
			$row['name_first'] = ucfirst($row['name_first']);
			$row['name_last'] = ucfirst($row['name_last']);
			
			if($row['next_due'] == '0/0/00')
			{
				$row['next_due'] = '';	
			}

			$data[$co][] = $row;
		}


		$this->timer->stopTimer( $timer );

		return $data;
	}

	public function Fetch_Current_Data($start_date, $end_date, $loan_type, $company_id, $timer, $batch_type = NULL, $ach_batch_company = NULL)
	{
		$this->timer->startTimer( $timer );

		$timestamp_start = $start_date . '000000';
		$timestamp_end   = $end_date   . '235959';

		if(isset($_SESSION) && is_array($_SESSION['auth_company']['id']) && count($_SESSION['auth_company']['id']) > 0)
		{
			$auth_company_ids = $_SESSION['auth_company']['id'];
		}
		else
		{
			$auth_company_ids = array(ECash::getCompany()->company_id);
		}

		$data = array();

		if( $loan_type == 'all' )
			$loan_type_list = $this->Get_Loan_Type_List($loan_type);
		else
			$loan_type_list = "'{$loan_type}'";

		if( $company_id > 0 )
			$company_list = "'{$company_id}'";
		else
			$company_list = "'" . implode("','", $auth_company_ids) . "'";

		// get ID lists for query
		$holding_ids = implode(',', ECash::getFactory()->getData('Application')->getHoldingStatusIds());
		$cashline_ids            = $this->Get_Cashline_Ids();
		$arrangement_status_ids  = $this->Get_Arrangement_Status_Ids();
		//Did away with functions to get event_type_ids because the event type is irrelevant.
		//What matters is the event amount type, and the clearing type. [#21774]
		
		if (empty($batch_type))
		{
			$batch_type_sql = "";
		}
		else
		{
			if ($batch_type == "ach")
			{
				$batch_type_sql = " AND tt.clearing_type = 'ach'\n";
			}
			elseif ($batch_type == "card")
			{
				$batch_type_sql = " AND tt.clearing_type = 'card'\n";
			}
			else
			{
				$batch_type_sql = "";
			}
		}

		if (empty($ach_batch_company))
			$ach_batch_company_sql = "";
		else
			$ach_batch_company_sql = " AND ab.ach_provider_id = '{$ach_batch_company}'\n";

		// For each Application Id
		$query = "-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				es.date_effective date_effective,
				es.event_schedule_id event_schedule_id,
				UPPER(c.name_short) company_name,
				a.application_id application_id,
				IF(a.is_react = 'yes', 'React', 'New') AS new_react,
				a.name_last name_last,
				a.name_first name_first,
				a.email,
				a.phone_home,
				a.phone_cell,
				ass.name status,
				UCASE(REPLACE(a.income_frequency,'_',' ')) frequency,
				a.income_direct_deposit dd,
				IF(tt.clearing_type = 'ach', a.bank_aba, NULL) AS bank_aba,
				IF(tt.clearing_type = 'ach', a.bank_account, NULL) AS bank_account,
				IF(tt.clearing_type = 'card', ci.card_number, NULL) AS card_last_4,
				a.company_id company_id,
				a.application_status_id application_status_id,
				lt.name loan_type,
				lt.name_short loan_type_short,
				et.name as event_type,
				
				-- payment_sequence,
				(CASE

				WHEN (
				(SELECT es1.date_effective
				FROM event_schedule AS es1
                                JOIN event_type AS et1 ON (et1.company_id = es1.company_id AND et1.event_type_id = es1.event_type_id)
				WHERE es1.company_id = a.company_id
				AND es1.application_id = a.application_id
				AND
					(
						(et1.name_short IN ('payment_service_chg','repayment_principal') AND es1.context = 'generated')
						OR es1.context = 'arrange_next'
						OR es1.context = 'payout'
					)
				GROUP BY es1.date_effective
				ORDER BY es1.date_effective
				LIMIT 0,1
				) = es.date_effective) THEN '1'
				
				WHEN (
				(SELECT es1.date_effective
				FROM event_schedule AS es1
                                JOIN event_type AS et1 ON (et1.company_id = es1.company_id AND et1.event_type_id = es1.event_type_id)
				WHERE es1.company_id = a.company_id
				AND es1.application_id = a.application_id
				AND
					(
						(et1.name_short IN ('payment_service_chg','repayment_principal') AND es1.context = 'generated')
						OR es1.context = 'arrange_next'
						OR es1.context = 'payout'
					)
				GROUP BY es1.date_effective
				ORDER BY es1.date_effective
				LIMIT 1,1
				) = es.date_effective) THEN '2'
				
				WHEN (
				(SELECT es1.date_effective
				FROM event_schedule AS es1
                                JOIN event_type AS et1 ON (et1.company_id = es1.company_id AND et1.event_type_id = es1.event_type_id)
				WHERE es1.company_id = a.company_id
				AND es1.application_id = a.application_id
				AND
					(
						(et1.name_short IN ('payment_service_chg','repayment_principal') AND es1.context = 'generated')
						OR es1.context = 'arrange_next'
						OR es1.context = 'payout'
					)
				GROUP BY es1.date_effective
				ORDER BY es1.date_effective
				LIMIT 2,1
				) = es.date_effective) THEN '3'
				
				WHEN (
				(SELECT es1.date_effective
				FROM event_schedule AS es1
                                JOIN event_type AS et1 ON (et1.company_id = es1.company_id AND et1.event_type_id = es1.event_type_id)
				WHERE es1.company_id = a.company_id
				AND es1.application_id = a.application_id
				AND
					(
						(et1.name_short IN ('payment_service_chg','repayment_principal') AND es1.context = 'generated')
						OR es1.context = 'arrange_next'
						OR es1.context = 'payout'
					)
				GROUP BY es1.date_effective
				ORDER BY es1.date_effective
				LIMIT 3,1
				) = es.date_effective) THEN '4'
				
				WHEN (
				(SELECT es1.date_effective
				FROM event_schedule AS es1
                                JOIN event_type AS et1 ON (et1.company_id = es1.company_id AND et1.event_type_id = es1.event_type_id)
				WHERE es1.company_id = a.company_id
				AND es1.application_id = a.application_id
				AND
					(
						(et1.name_short IN ('payment_service_chg','repayment_principal') AND es1.context = 'generated')
						OR es1.context = 'arrange_next'
						OR es1.context = 'payout'
					)
				GROUP BY es1.date_effective
				ORDER BY es1.date_effective
				LIMIT 4,1
				) = es.date_effective) THEN '5'
				
				WHEN (
				(SELECT es1.date_effective
				FROM event_schedule AS es1
                                JOIN event_type AS et1 ON (et1.company_id = es1.company_id AND et1.event_type_id = es1.event_type_id)
				WHERE es1.company_id = a.company_id
				AND es1.application_id = a.application_id
				AND
					(
						(et1.name_short IN ('payment_service_chg','repayment_principal') AND es1.context = 'generated')
						OR es1.context = 'arrange_next'
						OR es1.context = 'payout'
					)
				GROUP BY es1.date_effective
				ORDER BY es1.date_effective
				LIMIT 5,1
				) = es.date_effective) THEN '6'
				
				WHEN (
				(SELECT es1.date_effective
				FROM event_schedule AS es1
                                JOIN event_type AS et1 ON (et1.company_id = es1.company_id AND et1.event_type_id = es1.event_type_id)
				WHERE es1.company_id = a.company_id
				AND es1.application_id = a.application_id
				AND
					(
						(et1.name_short IN ('payment_service_chg','repayment_principal') AND es1.context = 'generated')
						OR es1.context = 'arrange_next'
						OR es1.context = 'payout'
					)
				GROUP BY es1.date_effective
				ORDER BY es1.date_effective
				LIMIT 6,1
				) = es.date_effective) THEN '7'
				
				WHEN (
				(SELECT es1.date_effective
				FROM event_schedule AS es1
                                JOIN event_type AS et1 ON (et1.company_id = es1.company_id AND et1.event_type_id = es1.event_type_id)
				WHERE es1.company_id = a.company_id
				AND es1.application_id = a.application_id
				AND
					(
						(et1.name_short IN ('payment_service_chg','repayment_principal') AND es1.context = 'generated')
						OR es1.context = 'arrange_next'
						OR es1.context = 'payout'
					)
				GROUP BY es1.date_effective
				ORDER BY es1.date_effective
				LIMIT 7,1
				) = es.date_effective) THEN '8'
				
				WHEN (
				(SELECT es1.date_effective
				FROM event_schedule AS es1
                                JOIN event_type AS et1 ON (et1.company_id = es1.company_id AND et1.event_type_id = es1.event_type_id)
				WHERE es1.company_id = a.company_id
				AND es1.application_id = a.application_id
				AND
					(
						(et1.name_short IN ('payment_service_chg','repayment_principal') AND es1.context = 'generated')
						OR es1.context = 'arrange_next'
						OR es1.context = 'payout'
					)
				GROUP BY es1.date_effective
				ORDER BY es1.date_effective
				LIMIT 8,1
				) = es.date_effective) THEN '9'
				
				WHEN (
				(SELECT es1.date_effective
				FROM event_schedule AS es1
                                JOIN event_type AS et1 ON (et1.company_id = es1.company_id AND et1.event_type_id = es1.event_type_id)
				WHERE es1.company_id = a.company_id
				AND es1.application_id = a.application_id
				AND
					(
						(et1.name_short IN ('payment_service_chg','repayment_principal') AND es1.context = 'generated')
						OR es1.context = 'arrange_next'
						OR es1.context = 'payout'
					)
				GROUP BY es1.date_effective
				ORDER BY es1.date_effective
				LIMIT 9,1
				) = es.date_effective) THEN '10'
				
				WHEN (
				(SELECT es1.date_effective
				FROM event_schedule AS es1
                                JOIN event_type AS et1 ON (et1.company_id = es1.company_id AND et1.event_type_id = es1.event_type_id)
				WHERE es1.company_id = a.company_id
				AND es1.application_id = a.application_id
				AND
					(
						(et1.name_short IN ('payment_service_chg','repayment_principal') AND es1.context = 'generated')
						OR es1.context = 'arrange_next'
						OR es1.context = 'payout'
					)
				GROUP BY es1.date_effective
				ORDER BY es1.date_effective
				LIMIT 10,1
				) = es.date_effective) THEN '11'
				
				WHEN (
				(SELECT es1.date_effective
				FROM event_schedule AS es1
                                JOIN event_type AS et1 ON (et1.company_id = es1.company_id AND et1.event_type_id = es1.event_type_id)
				WHERE es1.company_id = a.company_id
				AND es1.application_id = a.application_id
				AND
					(
						(et1.name_short IN ('payment_service_chg','repayment_principal') AND es1.context = 'generated')
						OR es1.context = 'arrange_next'
						OR es1.context = 'payout'
					)
				GROUP BY es1.date_effective
				ORDER BY es1.date_effective
				LIMIT 11,1
				) = es.date_effective) THEN '12'
				
				WHEN (
				(SELECT es1.date_effective
				FROM event_schedule AS es1
                                JOIN event_type AS et1 ON (et1.company_id = es1.company_id AND et1.event_type_id = es1.event_type_id)
				WHERE es1.company_id = a.company_id
				AND es1.application_id = a.application_id
				AND
					(
						(et1.name_short IN ('payment_service_chg','repayment_principal') AND es1.context = 'generated')
						OR es1.context = 'arrange_next'
						OR es1.context = 'payout'
					)
				GROUP BY es1.date_effective
				ORDER BY es1.date_effective
				LIMIT 12,1
				) = es.date_effective) THEN '13'
				
				WHEN (
				(SELECT es1.date_effective
				FROM event_schedule AS es1
                                JOIN event_type AS et1 ON (et1.company_id = es1.company_id AND et1.event_type_id = es1.event_type_id)
				WHERE es1.company_id = a.company_id
				AND es1.application_id = a.application_id
				AND
					(
						(et1.name_short IN ('payment_service_chg','repayment_principal') AND es1.context = 'generated')
						OR es1.context = 'arrange_next'
						OR es1.context = 'payout'
					)
				GROUP BY es1.date_effective
				ORDER BY es1.date_effective
				LIMIT 13,1
				) = es.date_effective) THEN '14'

				ELSE NULL
				END) AS payment_sequence,
				
				(SELECT tt.clearing_type
				FROM event_transaction te
				JOIN transaction_type tt ON (tt.transaction_type_id=te.transaction_type_id)
				WHERE te.event_type_id = et.event_type_id
				LIMIT 1
				) AS clearing_type,
				apr.name AS ach_provider,
				DATE_FORMAT(
					(
						SELECT MIN(date_effective)
						FROM  event_schedule ees
						WHERE 
							ees.application_id = a.application_id AND
							date_effective > es.date_effective AND
							(ees.amount_non_principal < 0 OR ees.amount_principal < 0)
					),
					'%c/%e/%y'
				)  AS next_due,
				IF(
					(
						SELECT MIN(date_effective)
						FROM event_schedule evs
						WHERE
							evs.application_id = a.application_id AND
							(evs.amount_principal + evs.amount_non_principal) <= 0
					) = es.date_effective AND
					a.is_react = 'no',
					1,
					0
				) first_payment,
				IF(a.application_status_id IN ({$arrangement_status_ids}), 1, 0) special,
				IF(
					(
						SELECT
							SUM(
								IF(
									tr.transaction_register_id IS NULL, 
									es.amount_principal + es.amount_non_principal, 
									tr.amount
								)
							)
						FROM
							event_schedule espn
							LEFT JOIN transaction_register tr USING (event_schedule_id)
						WHERE
							espn.date_effective > es.date_effective AND
							espn.application_id = a.application_id AND
							(
								espn.event_status = 'scheduled' OR 
								tr.transaction_status IN ('complete', 'pending')
							)
					) <= 0, 
					0, 
					1
				) payout,				
				SUM(IF(
					eat.name_short = 'principal',
					ea.amount,
					0
				)) principal,
				SUM(IF(
					eat.name_short = 'fee',
					ea.amount,
					0
				)) fees,
				SUM(IF(
				eat.name_short = 'service_charge',
					ea.amount,
					0
				)) service_charge,
				SUM(IF(
				eat.name_short <> 'irrecoverable',
					ea.amount,
					0
				)) AS amount_due,
				if(es.event_status = 'scheduled' AND es.context = 'generated', 1, 0) as is_rollover,
				if(et.name_short = 'full_balance', 1, 0) as is_full_pull
			FROM 
				event_schedule es				
				JOIN company c USING (company_id)
				JOIN event_type et on (et.event_type_id = es.event_type_id)
				JOIN event_transaction te on (et.event_type_id = te.event_type_id)
				JOIN transaction_type tt on (te.transaction_type_id = tt.transaction_type_id)
				JOIN event_amount ea USING (event_schedule_id)
				JOIN event_amount_type eat USING (event_amount_type_id)
				LEFT JOIN transaction_register tr USING(event_schedule_id)
				JOIN application a ON es.application_id = a.application_id
				LEFT JOIN card_info AS ci ON (ci.application_id = a.application_id)
				JOIN application_status ass USING (application_status_id)
				JOIN loan_type lt USING (loan_type_id)
				LEFT OUTER JOIN 
						(SELECT application_id, COUNT(*) AS cust_no_ach
							FROM application_flag af 
							JOIN flag_type ft USING (flag_type_id) 
							WHERE name_short = 'cust_no_ach'
							AND af.active_status = 'active'
							GROUP BY application_id) cnaf
						ON (es.application_id = cnaf.application_id)
				LEFT JOIN
					ach ON (ach.ach_id = tr.ach_id)
				LEFT JOIN
					ach_batch AS ab ON (ab.ach_batch_id = ach.ach_batch_id)
				LEFT JOIN
					ach_provider AS apr ON (apr.ach_provider_id = ab.ach_provider_id)
			WHERE 
				(
					tr.transaction_register_id = ea.transaction_register_id OR
					tr.transaction_register_id IS NULL
				) AND
				es.date_effective BETWEEN '{$timestamp_start}' AND '{$timestamp_end}' AND
				es.company_id IN ({$company_list}) AND
				es.event_status <> 'suspended' AND
				lt.name_short IN ({$loan_type_list}) AND
				es.amount_principal <= 0 AND
				es.amount_non_principal <= 0 AND
				a.application_status_id NOT IN ({$cashline_ids}) AND
				cnaf.cust_no_ach IS NULL AND 
				a.application_status_id NOT IN ({$holding_ids}) AND
			 	a.is_watched != 'yes'
				{$batch_type_sql}
				{$ach_batch_company_sql}
			GROUP BY
				date_effective,
				company_name,
				application_id,
				name_last,
				name_first,
				status,
				frequency,
				dd,
				company_id,
				application_status_id,
				loan_type
			ORDER BY
				date_effective
		";

		require_once(LIB_DIR.'Payment_Card.class.php');
		//Fixed some messed up grouping and fixed the payout column [#21768]
		// This report should ALWAYS hit the master.
		//echo '<pre>' .$query .'</pre>';
		$db = ECash::getMasterDb();

		$result = $db->query($query);

		$data = array();
		while( $row = $result->fetch(PDO::FETCH_ASSOC) )
		{
			$co = $row['company_name'];
			$id = $row['application_id'];

			$this->Get_Module_Mode($row);
			
			if($row['next_due'] == '0/0/00')
			{
				$row['next_due'] = '';	
			}
			
			$card_number = isset($row['card_last_4']) ? Payment_Card::decrypt($row['card_last_4']) : NULL;
			
			$data[$co][] = array(
				'payment_date' => $row['date_effective'],
				'application_id' => $row['application_id'],
				'new_react' => $row['new_react'],
				'event_schedule_id' => $row['event_schedule_id'],
				'application_status_id' => $row['application_status_id'],
				'name_last' => ucfirst($row['name_last']),
				'name_first' => ucfirst($row['name_first']),
				'email' => $row['email'],
				'phone_home' => $row['phone_home'],
				'phone_cell' => $row['phone_cell'],
				'status' => $row['status'],
				'event_type' => $row['event_type'],
				'payment_sequence' => $row['payment_sequence'],
				'clearing_type' => $row['clearing_type'],
				'ach_provider' => $row['ach_provider'],
				'frequency' => $row['frequency'],
				'dd' => $row['dd'],
				'bank_aba' => $row['bank_aba'],
				'bank_account' => $row['bank_account'],
				'card_last_4' => substr($card_number, 12, 4),
				'next_due' => $row['next_due'],
				'first_payment' => $row['first_payment'],
				'special' => $row['special'],
				'loan_type' => $row['loan_type'],
				'loan_type_short' => $row['loan_type_short'],
				'principal' => -$row['principal'],
				'fees' => -$row['fees'],
				'service_charge' => -$row['service_charge'],
				'amount_due' => -$row['amount_due'],
				'payout' => $row['payout'],
				'is_ach' => $row['is_ach'],
				'is_rollover' => $row['is_rollover'],
				'is_full_pull' => $row['is_full_pull'],
				'module' => isset($row['module']) ? $row['module'] : null,
				'mode' => isset($row['mode']) ? $row['mode'] : null
			);
		}


		$this->timer->stopTimer( $timer );
		return $data;
	}
}

?>
