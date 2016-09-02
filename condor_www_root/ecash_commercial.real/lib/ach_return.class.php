<?php

/**
 * Abstract ACH Return Class
 *
 */
require_once(LIB_DIR . "ach_return_interface.iface.php");
require_once(LIB_DIR . "ach_utils.class.php");

abstract class ACH_Return implements ACH_Return_Interface 
{	
	
	protected $log;
	protected $server;
	
	protected $ach_utils;
	protected $holiday_ary;
	protected $paydate_obj;
	protected $paydate_handler;
	protected $biz_rules;
	protected $business_day;
	
	private static $RS		  = "\n";
	
	/**
	 * Used to determine whether or not the returns file will contain
	 * both the returns and corretions in one file or to retrieve and process
	 * two separate files.
	 */
	protected $COMBINED_RETURNS = FALSE;
	
	public function __construct(Server $server)
	{
		$this->server			= $server;
		$this->db = ECash_Config::getMasterDbConnection();
		$this->company_id		= $server->company_id;
		$this->company_abbrev	= strtolower($server->company);
		// Set up separate log object for ACH purposes
		$this->log = new Applog(APPLOG_SUBDIRECTORY.'/ach', APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, strtoupper($this->company_abbrev));
		$this->ach_utils = new ACH_Utils($server);
	}
	
	public function Process_ACH_Returns($end_date, $override_start_date = NULL)
	{
		return $this->Process_ACH_Report($end_date, 'returns', $override_start_date);
	}

	public function Process_ACH_Corrections($end_date, $override_start_date = NULL)
	{
		return $this->Process_ACH_Report($end_date, 'corrections', $override_start_date);
	}
	
	// Grabs application list from standby table and runs adjust_schedule.
	public function Reschedule_Apps($app_limit = 100)
	{
		require_once(CUSTOMER_LIB."failure_dfa.php");
		
		//mantis:7357 - filter company
		$query = "SELECT DISTINCT st.application_id 
			  FROM standby st
			  JOIN application ap ON (st.application_id = ap.application_id)
			  WHERE st.process_type='reschedule'
			     AND
				ap.company_id = {$this->company_id}
			 ORDER BY st.date_created
			 LIMIT {$app_limit} ";

		$result = $this->db->Query($query);
		
		if($result->rowCount() == 0) {
			return false;
		}

		$reschedule_list = array();
		
		while ($row = $result->fetch(PDO::FETCH_OBJ)) {
			$reschedule_list[] = $row->application_id;
		}
		
		$reschedule_list = array_unique($reschedule_list);
		$this->log->Write("Apps to reschedule: ". count($reschedule_list));
		
		foreach($reschedule_list as $application_id)
		{
			try
			{
				$fdfap = new stdClass();
				$fdfap->application_id = $application_id;
				$fdfap->server = $this->server;

				$fdfa = new FailureDFA($application_id);
				$fdfa->run($fdfap);

				Remove_Standby($application_id, 'reschedule');
			}
			catch (Exception $e)
			{
				$this->log->Write("Unable to reschedule app {$application_id}: {$e->getMessage()}");
				Remove_Standby($application_id, 'reschedule');
				Set_Standby($application_id, 'reschedule_failed');
			}
		}
		
		return true;
	}

	protected function Validate_COR_ABA ($value, &$normalized_value)
	{
		if ( is_numeric($value)			&&
		     strlen($value) == 9		)
		{
			$normalized_value = $value;
			return true;
		}
		
		return false;
	}

	protected function Validate_COR_Account ($value, &$normalized_value)
	{
		if ( is_numeric($value)			&&
		     strlen($value) > 3		&&
		     strlen($value) < 18		)
		{
			$normalized_value = $value;
			return true;
		}
		return false;
	}

	protected function Validate_Tran_Code ($value, &$normalized_value)
	{
		if ( is_numeric($value)			&&
		     $value >= 22	&&
		     $value <= 39 		)
		{
			if ($value <= 29)
			{
				$bank_account_type = 'checking';
			}
			else
			{
				$bank_account_type = 'savings' ;
			}
			$normalized_value = $bank_account_type;
			return true;
		}
		
		return false;
	}

	protected function Validate_Name ($value, &$normalized_name_last, &$normalized_name_first)
	{
		$name_ary = explode(" ", $value);
		$name_first	= strtolower(trim($name_ary[0]));
		$name_last	= strtolower(trim($name_ary[1]));
		if ( strlen($name_last ) >  1	&&
		     strlen($name_last ) < 50	&&
		     strlen($name_first) >  0	&&
		     strlen($name_first) < 50		)
		{
			$normalized_name_last	= $name_last;
			$normalized_name_first	= $name_first;
			return true;
		}
		
		return false;
	}

	protected function Update_Application_Info ($application_id, $app_update_ary)
	{
		$agent_id = Fetch_Current_Agent();
		
		if ( empty($application_id) || count($app_update_ary) < 1 )
		{
			return false;
		}

		$set_phrases	= "";
		$where_phrases	= "";

		foreach ($app_update_ary as $key => $value) 
		{
			if (strlen($set_phrases) > 0)
			{
				$set_phrases .= ",
				";
			}
			$set_phrases .= " $key = " . $this->db->quote($value);

			if (strlen($where_phrases) > 0)
			{
				$where_phrases .= "
				OR ";
			}
			$where_phrases .= " $key <> " . $this->db->quote($value);
		}
		
		$query = "
    	    -- eCash3.5 ".__FILE__.":".__LINE__.":".__METHOD__."()
			UPDATE application
				SET 
					modifying_agent_id = '{$agent_id}',
					$set_phrases
				WHERE
						application_id	= $application_id
					AND	company_id		= {$this->company_id}
					AND (
							$where_phrases
						)
		";
		$result = $this->db->Query($query);
		return $result->rowCount();
	}

	public function Fetch_ACH_File($type, $start_date)
	{
		return $this->Send_Report_Request($start_date, $type);
	}

	public function Send_Report_Request($start_date, $report_type)
	{
		$return_val = array();
		/**
		 * Holds a query string emulating the request.
		 */
		$transport_type = eCash_Config::getInstance()->ACH_TRANSPORT_TYPE;
		$batch_server   = eCash_Config::getInstance()->ACH_BATCH_SERVER;
		$batch_login    = eCash_Config::getInstance()->ACH_REPORT_LOGIN;
		$batch_pass     = eCash_Config::getInstance()->ACH_REPORT_PASS;
		$transport_port   = eCash_Config::getInstance()->ACH_BATCH_SERVER_PORT;
		
		for ($i = 0; $i < 5; $i++) { // make multiple request attempts
			try {
				$transport = ACHTransport::CreateTransport($transport_type, $batch_server, $batch_login, $batch_pass, $transport_port);
			
				if (EXECUTION_MODE != 'LIVE' && $transport->hasMethod('setBatchKey'))
				{
					$transport->setBatchKey(eCash_Config::getInstance()->ACH_BATCH_KEY);
				}
				
				if ($transport->hasMethod('setDate')) 
				{
					$transport->setDate($start_date);
				}
			
				if ($transport->hasMethod('setCompanyId')) 
				{
					$transport->setCompanyId($this->ach_report_company_id);
				}
				
				switch($report_type)
				{
					case "returns":
						$prefix = eCash_Config::getInstance()->ACH_REPORT_RETURNS_URL_PREFIX;
						$suffix = eCash_Config::getInstance()->ACH_REPORT_RETURNS_URL_SUFFIX;
						$returns_url = eCash_Config::getInstance()->ACH_REPORT_RETURNS_URL;
	
						if($prefix != NULL && $suffix != NULL)
						{
							$url = $prefix.date("Ymd",strtotime($start_date)).$suffix;
						}
						else if($returns_url != NULL)
						{
							$url = $returns_url;
						}
						else
						{
							$url = eCash_Config::getInstance()->ACH_REPORT_URL;
						}
						
						break;
				
					case "corrections":	
						$prefix = eCash_Config::getInstance()->ACH_REPORT_CORRECTIONS_URL_PREFIX;
						$suffix = eCash_Config::getInstance()->ACH_REPORT_CORRECTIONS_URL_SUFFIX;
						$corrections_url = eCash_Config::getInstance()->ACH_REPORT_CORRECTIONS_URL;
						
						if($prefix != NULL && $suffix != NULL)
						{
							$url = $prefix.date("Ymd",strtotime($start_date)).$suffix;
						}
						else if($corrections_url != NULL)
						{
							$url = $corrections_url;
						}
						else
						{
							$url = eCash_Config::getInstance()->ACH_REPORT_URL;
						}
						
						break;	
				}
			
				$report_response = '';
				$report_success = $transport->retrieveReport($url, $report_type, $report_response);
				
				if (!$report_success) {
					$this->log->write('(Try '.($i + 1).') Received an error code. Not trying again.');
					$this->log->write('Error: '.$report_response);
				}
				break;
			} catch (Exception $e) {
				$this->log->write('(Try '.($i + 1).') '.$e->getMessage());
				$report_response = '';
				$report_success = false;
				sleep(5);
			}
		}
		
		if ($report_success) 
		{
			$request = 'report='.$report_type.
					'&sdate='.date("Ymd", strtotime($start_date)).
					'&edate='.date("Ymd", strtotime($start_date)).
					'&compid='.$this->ach_report_company_id;

			$this->log->Write("Successfully retrieved '".strlen($report_response)."' byte(s) $report_type report for $start_date.");
			$this->Insert_ACH_Report_Response($request, $report_response, $start_date);

			return true;

		} 
		else 
		{
			$this->log->Write("ACH '$report_type' report: was unable to retrieve report from $url", LOG_ERR);
			return false;
		}
	}

	public function Fetch_Report($start_date, $report_type)
	{
		if($report_type == 'returns')
		{
			$type = "RET";
		}
		else
		{
			$type = "COR";
		}
		// We want to grab only the most recent file in the case that there is more than one
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					SELECT 	ach_report_id, 
							ach_report_request, 
							remote_response as received
					FROM	ach_report
					WHERE	company_id = {$this->server->company_id}
					AND		ach_report_request LIKE 'report={$type}%'
					AND		date_request = '{$start_date}'
					AND		report_status != 'obsoleted'
					ORDER BY date_created DESC
					LIMIT 1
			";
		$result = $this->db->Query($query);
		
		if($result->rowCount() > 0)
		{
			$report = $result->fetch(PDO::FETCH_ASSOC);
			return $report;		
		}
		else
		{
			$this->log->Write("Unable to retrieve report type $report_type for $start_date");
			return false;
		}
	}

	public function Parse_Report_Batch ($return_file)
	{
		// Split file into rows
		$return_data_ary = explode(self::$RS, $return_file);

		$parsed_data_ary = array();
		$i = 0;

		foreach ($return_data_ary as $line)
		{
			if ( strlen(trim($line)) > 0 )
			{
				$this->log->Write("Parse_Report_Batch():$line\n");
				//  Split each row into individual columns
				
				$matches = array();
				preg_match_all('#(?<=^"|,")(?:[^"]|"")*(?=",|"$)|(?<=^|,)[^",]*(?=,|$)#', $line, $matches);
				$col_data_ary = $matches[0];
				
				$parsed_data_ary[$i] = array();
				foreach ($col_data_ary as $key => $col_data)
				{
					// Apply column name map so we can return a friendly structure
					$parsed_data_ary[$i][$this->return_file_format[$key]] = str_replace('"', '', $col_data);
				}

				$i++;
			}
		}
		return $parsed_data_ary;
	}
	
	public function Insert_ACH_Report_Response($request_data, &$response, $start_date)
	{
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
			UPDATE ach_report
			SET report_status = 'obsoleted'
			WHERE ach_report_request = ".$this->db->quote($request_data)."
			AND date_request = ".$this->db->quote($start_date)."
			AND company_id = {$this->company_id}
			";
		$result = $this->db->Query($query);
		
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					INSERT INTO ach_report
							(
								date_created,
								date_request,
								company_id,
								ach_report_request,
								remote_response,
								report_status
							)
						VALUES
							(
								current_timestamp,
								?,
								{$this->company_id},
								?,
								?,
								'received'
							)
		";
		
		// MySQL max_allowed_packet setting must be big enough to accommodate the largest conceivable return report;
		//	otherwise, we'll have to bind ach_file_outbound as 'b' (blob) and use 
		//	$stmt->send_long_data(1, $response) repeatedly (until it returns FALSE ??!? -- poorly documented).

		$stmt = $this->db->prepare($query);
		$stmt->execute(array($start_date, $request_data, $response));
		
		$ach_report_id = $this->db->lastInsertId();

		return $ach_report_id;
	}

	protected function Update_ACH_Report_Status ($ach_report_id, $status)
	{
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					UPDATE ach_report
					SET
						report_status= '$status'
					WHERE
							ach_report_id  = $ach_report_id
						AND	report_status <> '$status'
		";

		$result = $this->db->Query($query);

		return true;
	}
	
	protected function Get_Return_App_ID ($id)
	{
		$application_id = NULL;

		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
						SELECT
							application_id
						FROM
							ach
						WHERE
								ach_id		= $id
							AND	company_id	= {$this->company_id}
			";

		$result = $this->db->Query($query);
		if ($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			$application_id = $row['application_id'];
		}

		return $application_id;
	}

	protected function Insert_ACH_Exception($report_data=NULL)
	{
		if($report_data)
		{
			$effective_entry_date = date('Y-m-d');
			
			$ach_id		= ltrim($report_data['recipient_id'], '0');
			$recipient_name = isset($report_data['recipient_name']) ? trim($report_data['recipient_name']) : "";
			$debit_amount	=  isset($report_data['debit_amount']) ? trim($report_data['debit_amount']) : '0.00';
			$credit_amount	=  isset($report_data['credit_amount']) ? trim($report_data['credit_amount']) : '0.00';
			$reason_code	=  isset($report_data['reason_code']) ? trim($report_data['reason_code']) : "";
	
			// Check for pre-existing ach_exception entry for this ach_id
			//mantis:7358 - added company to the queries
			$check_query = "
				SELECT
					ach_id
				FROM
					ach_exception
				WHERE
					ach_id = '{$ach_id}'
				AND
					date_created > curdate()
				AND
					company_id = {$this->company_id}
				";
			
			$result = $this->db->Query($check_query);
			$count = $result->rowCount();
			
			if ($count == 0)
			{
				// Insert ACH Exception into ach_exception	
				$ins_query = "-- /* SQL LOCATED IN file=" . __FILE__ . " line=" . __LINE__ . " method=" . __METHOD__ . " */
					INSERT INTO 
						ach_exception
					(
						date_created,
						date_modified,
						return_date,
						recipient_id,
						recipient_name,
						ach_id,
						debit_amount,
						credit_amount,
						reason_code,
						company_id
					)
					VALUES
					(
						current_timestamp,
						current_timestamp,
						'{$effective_entry_date}',
						'{$ach_id}',
						'{$recipient_name}',
						'{$ach_id}',
						'{$debit_amount}',
						'{$credit_amount}',
						'{$reason_code}',
						 {$this->company_id}
					)";
				
				try
				{
					$result = $this->db->Query($ins_query);
					return TRUE;
				}
				catch(Exception $e)
				{
					return FALSE;
				}
			}
		}
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $ach_id
	 * @return unknown
	 */
	protected function Update_Transaction_Register_ACH_Failure ($ach_id)
	{
		$agent_id = Fetch_Current_Agent($this->server);
		
		// First, look for the transaction register row
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
			SELECT transaction_register_id, transaction_status 
			FROM transaction_register
			WHERE ach_id = {$ach_id}";

		$result = $this->db->Query($query);
		$row = $result->fetch(PDO::FETCH_OBJ);
		
		if ($row == null) {
			$this->log->Write("Could not locate transaction w/ ACH ID of {$ach_id}.");
			$exception = array(
				'ach_id'  => $ach_id, 
				'exception' => "Could not locate transaction w/ ACH ID of {$ach_id}.",
				'recipient_name' => $recipient_name
			);
			$this->ach_exceptions[] = $exception;
			$this->ach_exceptions_flag = TRUE;
			return false;
		}
		
		$trid = $row->transaction_register_id; 
		$trstat = $row->transaction_status;

		if($trstat == 'failed') {
			$this->log->Write("Transaction {$trid} already marked as failed! ACH ID:{$ach_id}.");
			$exception = array(
				'ach_id'  => $ach_id, 
				'exception' => "Transaction {$trid} already marked as failed! ACH ID:{$ach_id}.",
				'recipient_name' => $recipient_name
			);
			$this->ach_exceptions[] = $exception;
			$this->ach_exceptions_flag = TRUE;
			return false;
		}

		Set_Loan_Snapshot($trid,"failed");
		
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					UPDATE transaction_register
					SET
						transaction_status	= 'failed',
						modifying_agent_id	= '$agent_id'
					WHERE
							ach_id		= $ach_id
						AND	company_id	= {$this->company_id}
						AND	transaction_status in ('pending','complete') ";

		$this->log->Write("Setting transaction {$trid} w/ ACH ID of {$ach_id} to failed.");
		$result = $this->db->Query($query);

		// If this is complete, we need to strip it out of the transaction ledger
		if ($trstat == 'complete') {
			$query = "DELETE FROM transaction_ledger
                                  WHERE transaction_register_id = {$trid}";
			$this->log->Write("Deleting completed ledger item for transaction {$trid}");
			$result = $this->db->Query($query);
		} 

		return true;
	}
	
	/**
	 * Helper method to get the COMBINED_RETURNS flag from the class.
	 *
	 * @return bool
	 */
	public function useCombined()
	{
		return $this->COMBINED_RETURNS;
	}
}

?>
