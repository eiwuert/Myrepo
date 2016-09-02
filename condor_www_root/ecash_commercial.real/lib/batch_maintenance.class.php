<?php

require_once(LIB_DIR . "ach.class.php");
require_once(SQL_LIB_DIR . "scheduling.func.php");
require_once(SQL_LIB_DIR . "tagging.lib.php");
require_once (LIB_DIR . "/Document/Document.class.php");
require_once (LIB_DIR . "/Document/AutoEmail.class.php");

class Batch_Maintenance
{

	private $ach;
	private $server;
	private $db;
	private $pdc;
	private $tagging_enabled;
	private $cash_report_enabled;
	private $cso_enabled;
	public function __construct(Server $server)
	{
		$this->server = $server;
		$this->db = ECash_Config::getMasterDbConnection();

		$this->ach	   = ACH::Get_ACH_Handler($server, 'batch');
		
		$holidays = Fetch_Holiday_List();
		$this->pdc	= new Pay_Date_Calc_3($holidays);
		$this->tagging_enabled = (eCash_Config::getInstance()->INVESTOR_GROUP_TAGGING_ENABLED != NULL) ? eCash_Config::getInstance()->INVESTOR_GROUP_TAGGING_ENABLED : FALSE;
		$this->cash_report_enabled = (eCash_Config::getInstance()->DAILY_CASH_REPORT_ENABLED != NULL) ? eCash_Config::getInstance()->DAILY_CASH_REPORT_ENABLED : FALSE;
		$this->cso_enabled = (eCash_Config::getInstance()->CSO_ENABLED != NULL) ? eCash_Config::getInstance()->CSO_ENABLED : FALSE;
	}

	// The $obj in this isn't really necessary, it's mostly to return a message when 
	// the method is called from the user interface, but for the nightly cronjob
	// at most it'll get logged.
	public function Close_Out()
	{
		$obj = new stdClass();

		$date = date("Y-m-d", strtotime("now"));
		$this->ach->Set_Closing_Timestamp($date);
		$stamp = $this->ach->Get_Closing_Timestamp($date);		
		$obj->message = "The closing time has been set to {$stamp}\n";
		$obj->closing_time = $stamp;
		return($obj);
	}
	
	public function Create_Daily_Cash_Report() 
	{
		require_once(SERVER_MODULE_DIR."reporting/daily_cash_report.class.php");
		$dcr = new Daily_Cash_Report_Query($this->server);
		$dcr->Create_Daily_Cash_Report();
	}

	public function Create_IT_Settlement_Report()
	{
		require_once(ECASH_DIR.'ITSettlement/it_settlement.class.php');
		$settlement = new IT_Settlement($this->server);
		//Update_Progress('ach','Getting previous settlement date','97.5');
		//get the start date by checking to see the last time the settlement report was generated
		$previous_settlement = date("Y-m-d H:i:s", strtotime('+1 second', strtotime($settlement->getLastSettlementTime('completed'))));
		
		//If there isn't one, go back a month
		if (!$previous_settlement) 
		{
			$previous_settlement = 	date("Y-m-d H:i:s",strtotime('-1 month'));
		}
		//create the process
		$process_id = Set_Process_Status($this->db,$this->server->company_id,'it_settlement','started');
		//generate the reports
		//Update_Progress('ach','Generating IT Settlement report','98');
		$settlement_time = $settlement->getLastSettlementTime('started');
		$report_id = $settlement->generateReport($previous_settlement,$settlement_time,date('Y-m-d'));
		//send the reports
		//Update_Progress('ach','Sending IT Settlement report','98.5');
		$settlement->sendReport($report_id);
		//end the process
		Set_Process_Status($this->db,$this->server->company_id,'it_settlement','completed',null,$process_id);
	}

	// The $obj in this isn't really necessary, it's mostly to return a message when 
	// the method is called from the user interface, but for the nightly cronjob
	// at most it'll get logged.
	public function Send_Batch()
	{
		$obj = new stdClass();

		$today = date('Y-m-d');
		$tomorrow = $this->pdc->Get_Next_Business_Day($today);
		$close_time = $this->ach->Get_Closing_Timestamp($today);
		
		if (!$close_time)
		{
			$obj->message = "You must set a closing time before sending.\n";
			Update_Progress('ach', $obj->message, 999);
		}	       
		else
		{	    

			if ($this->ach->Has_Sent_ACH($tomorrow))
			{
				$str  = "You have already sent\n";
				$str .= "an ACH batch for {$tomorrow}.\n";
				$str .= "You cannot resend for this business day.";
				$obj->message = $str;
				Update_Progress('ach', $obj->message, 999);
			}
			else
			{
				try 
				{
					Update_Progress('ach', 'Recording current scheduled events to the Transaction Register', 10);
					Record_Current_Scheduled_Events_To_Register($today, NULL, NULL, 'ach');

					if ($this->tagging_enabled)
					{
						Update_Progress('ach', 'Tagging accounts with Investor Group Information', 40);
						$this->Tag_Approved_Applications();
					}
					$batch_ids = array();
					if($this->ach->useCombined() === TRUE)
					{
						Update_Progress('ach', 'Sending Combined batch', 60);
						// Already initialized from its initial __construct
						$ach_receipt	= $this->ach->Do_Batch('combined', $tomorrow);
	
						$str = "\nACH batch information:\n";
						$str .= "\tBatch ID: \t{$ach_receipt['batch_id']}\n";
						$str .= "\tStatus: \t" . ucfirst($ach_receipt['status']) . "\n";

						if(isset($ach_receipt['ref_no']) && ! empty($ach_receipt['ref_no']))
						{
							$str .= "\tReference No: \t{$ach_receipt['ref_no']}\n";
						}
						
						$str .= "\tNumber of Debit Transactions: \t{$ach_receipt['db_count']}\n";
						$str .= "\tTotal Debit Amount: $" . number_format($ach_receipt['db_amount'],2) . "\n";
						$str .= "\tNumber of Credits Transactions: \t{$ach_receipt['cr_count']}\n";
						$str .= "\tTotal Credit Amount: $" . number_format($ach_receipt['cr_amount'],2) . "\n";
						//adding batch id to list for tagging file
						if(!empty($ach_receipt['batch_id']))
							$batch_ids[] = $ach_receipt['batch_id'];
					}
					else 
					{
						Update_Progress('ach', 'Sending Credit batch', 50);
						// Already initialized from its initial __construct
						$credit_receipt	= $this->ach->Do_Batch('credit', $tomorrow);

						// Re-Initialize the new batch (do this for each batch)
						$this->ach->Initialize_Batch();
						Update_Progress('ach', 'Sending Debit batch', 60);
						$debit_receipt	= $this->ach->Do_Batch('debit' , $tomorrow);
					
						$str  = "Credit batch information:\n";
						$str .= "\tBatch ID: {$credit_receipt['batch_id']}\n";
						$str .= "\tStatus: " . ucfirst($credit_receipt['status']) . "\n";
						$str .= "\tReference No: {$credit_receipt['ref_no']}\n";
						$str .= "\tNumber of credits: {$credit_receipt['cr_count']}\n";
						$str .= "\tTotal Credit Amount: {$credit_receipt['cr_amount']}\n";
						$str .= "\nDebit batch information:\n";
						$str .= "\tBatch ID: {$debit_receipt['batch_id']}\n";
						$str .= "\tStatus: " . ucfirst($debit_receipt['status']) . "\n";
						$str .= "\tReference No: {$debit_receipt['ref_no']}\n";
						$str .= "\tNumber of debits: {$debit_receipt['db_count']}\n";
						$str .= "\tTotal Debit Amount: {$debit_receipt['db_amount']}\n";
						//adding batch ids to list for tagging file
						if(!empty($debit_receipt['batch_id']))
							$batch_ids[] = $debit_receipt['batch_id'];
						if(!empty($credit_receipt['batch_id']))	
							$batch_ids[] = $credit_receipt['batch_id'];
					}
					
					Update_Progress('ach', $str, 75);
					$obj->message = $str;
				
					if ($this->tagging_enabled)
					{
						Update_Progress('ach', 'Sending New Account Tag Info', 80);
						$this->Send_Tags($batch_ids);
					}		

					Update_Progress('ach', 'Moving Approved statuses to Active', 85);
					$this->Status_Approved_Move_To_Active();
					Update_Progress('ach', 'Account Statuses have been updated', 90);
					
					if($this->cso_enabled)
					{
						Update_Progress('ach', 'Registering accrued charges','92');
						$this->Register_Accruals();
						Update_Progress('ach', 'Updating accounts with grace period arrangements','94');
						$this->gracePeriodToActive();
						Update_Progress('ach', 'Past Due accounts have been updated to Active', '95');
						Update_Progress('ach', 'Creating IT Settlement Reports', 97);
						$this->Create_IT_Settlement_Report();
						Update_Progress('ach', 'The IT Settlement report has been created and sent', 99);
					}
					
					if($this->cash_report_enabled)
					{
						Update_Progress('ach', 'Creating Daily Cash Report', 97);
						$this->Create_Daily_Cash_Report();
						Update_Progress('ach', 'The Daily Cash Report Has Been Created', 99);
					}
	
					Update_Progress('ach', 'Finished Batch Processing', 100);
				}
				catch( Exception $e ) 
				{
					$error_message = $e->getMessage();
					ECash::getLog()->Write("ACH Batch Error: $error_message");
					ECash::getLog()->Write($e->getTraceAsString());

					/* If there is an error, try sending it to the NOTIFICATION_ERROR_RECIPIENTS.
					 * If we forgot to define it, and the EXECUTION_MODE is Live, then
					 * email the right TSS people.  This is done so that for RC environments
					 * the NOTIFICATION_ERROR_RECIPIENTS can be defined for whomever is testing
					 * but if it's not defined and we're not in the LIVE environment 
					 * nothing happens.
					 */
					if(eCash_Config::getInstance()->NOTIFICATION_ERROR_RECIPIENTS != NULL) 
					{
						$recipients = eCash_Config::getInstance()->NOTIFICATION_ERROR_RECIPIENTS;
					} 
					else if (EXECUTION_MODE == 'LIVE') 
					{
						$recipients = 'brian.ronald@sellingsource.com';
					}

					if(! empty($recipients))
					{
						$subject = 'Ecash Alert '. strtoupper($this->server->company); //mantis:7727
						$body = 'An ERROR has occured with the ACH Batch - EXECUTION MODE: ' . EXECUTION_MODE . "\n\n";
						$body .= "Error Message: \n" . $error_message . "\n\n";
						$body .= "Trace: \n" . $e->getTraceAsString() . "\n\n";

						require_once(LIB_DIR . '/Mail.class.php');
						eCash_Mail::sendExceptionMessage($recipients, $body, $subject);
					}
					
					Update_Progress('ach', 'An ERROR has occurred with the ACH Batch!');
					Update_Progress('ach', $error_message);
					return($obj);
				}
				
				//Register scheduled accrued charges, since we just sent off payments for them. [AGEAN LIVE #8325]
				ECash::getLog()->Write('Registering accrued charges for '.$today);
				$this->Register_Accruals();
			}
		}

		return($obj);
	}

	protected function Register_Accruals() 
	{
			$today = date('Y-m-d');
			
			//Record accrued charges to the register
			Record_Current_Scheduled_Events_To_Register($today, NULL, NULL, 'accrued charge');
			
			//get accrued charge transaction types
			$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
			SELECT
			    transaction_type_id
			FROM 
				transaction_type
			WHERE
				company_id = '{$this->server->company_id}'
			AND 
				clearing_type = 'accrued charge'";
			
			$typelist = array();
			$st = $this->db->query($query);
			while ($row = $st->fetch(PDO::FETCH_OBJ))
			{
				$typelist[] = $row->transaction_type_id;
			}
			
			$agent_id = Fetch_Current_Agent();
		
			//Set newly registered transactions to pending status
			$upd_query = "
			UPDATE transaction_register
			SET transaction_status = 'pending',
			modifying_agent_id = '{$agent_id}'
			WHERE transaction_status = 'new'
			AND transaction_type_id IN (".implode(",",$typelist).")";
		
			$rows = $this->db->exec($upd_query);
			ECash::getLog()->Write("Updated {$rows} non-ACH rows from 'new' to 'pending'.");
		
			//Get statuses to complete
			$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					SELECT  
						tr.transaction_register_id,
						tr.application_id,
						tr.transaction_type_id,
						tt.pending_period,
						tt.period_type,
						tr.date_effective
					FROM    transaction_register tr
					JOIN transaction_type tt ON tr.transaction_type_id = tt.transaction_type_id
					WHERE   tr.company_id = {$this->server->company_id}
					AND	tr.transaction_type_id	IN (" . implode(",", $typelist) . ")
					AND tr.transaction_status	IN ('pending')
					AND tr.date_effective <= '{$today}'
		";

		$result = $this->db->query($query);
		while ($row = $result->fetch(PDO::FETCH_OBJ))
		{
			//Determine the pending window of the statuses.
			$window = intval($row->pending_period);
			
			switch ($row->period_type) 
			{
				case "business":
					$limit = $this->pdc->Get_Business_Days_Backward(date("Y-m-d"), $window);
					break;
				case "calendar":
				default:
					$limit = date("Y-m-d", strtotime("-{$window} days", strtotime(date("Y-m-d"))));
					break;
			}
			
			//Complete them!
			if (strtotime($row->date_effective) <= strtotime($limit))
			{
				$post_result = Post_Transaction($row->application_id, $row->transaction_register_id);
			}
		}
	}
	
	protected function Send_Tags($batch_ids) 
	{
		Set_Process_Status($this->db, $this->server->company_id, 'send_tags', 'started');

		list($usec, $sec) = explode(" ", microtime(false));
		$microseconds = (integer)((float)$usec * 1000000);
		$tmp_file_sfx = date("YmdHis") . str_pad($microseconds, 6, '0', STR_PAD_LEFT);
		$filename =  "/tmp/ecash_{$this->server->company}_" . $tmp_file_sfx . ".tags.csv";

		$this->Save_Tagging_CSV($batch_ids, $filename);

		if ($this->Send_Tagging_CSV($filename)) 
		{
			Set_Process_Status($this->db, $this->server->company_id, 'send_tags', 'completed');
		} 
		else 
		{
			Set_Process_Status($this->db, $this->server->company_id, 'send_tags', 'failed');
		}
	}

	protected function Save_Tagging_CSV($batch_ids, $filename)
	{
		$ids = implode(',' , $batch_ids);
		$date = date('Y-m-d');
		$company_id = $this->server->company_id;
		/*If today is a business day then pull in the non-ach transactions
		that have an effective date of the next business day.
		If today is a weekend or holiday then pull in the non-ach transactions
		that have an effective date of today
		This is being done because they have always been pulled in for the next business day
		and anything on a weekend or holiday was not being reported, and if we just began using
		today it would cause duplicates for the transition date, and the reporting of non-ach transactions
		would be a day later. [RB] [#21743] */
		if($this->pdc->isBusinessDay(strtotime($date)))
		{
			$date = $this->pdc->Get_Next_Business_Day($date);
		}	

		/**
		 * Broke this up into a Union so the ACH portion can pull data based on the 
		 * ach_date from that batch and not rely on the date_effective which may be 
		 * set in the past.  It's a little slower, but not by too much. [GForge #18054][BR]
		 */
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
		-- DONT_KILL_01123581321345589144233377610
		(-- ACH ONLY
			SELECT DISTINCT
				DATE_FORMAT(ach.ach_date, '%Y-%m-%d') AS transaction_date,
				a.application_id AS application_id,
				a.is_react,
				a.customer_id,
				SUBSTR(atd.tag_name, 4) AS lender_tag,
				a.name_first AS first_name,
				a.name_last AS last_name,
				ach.ach_id AS transaction_id,
				a.bank_aba AS bank_aba,
				a.bank_account AS bank_account,
				IF(a.bank_account_type = 'checking', 'C', 'S') AS acct_type,
				'ACH' AS ach_flag,
				tt.name_short AS transaction_type,
				-tr.amount * 100 AS total_payment,
				IF (tt.affects_principal = 'yes', -tr.amount, 0) * 100 principal_amount,
				IF (tt.affects_principal = 'no', -tr.amount, 0) * 100 service_charge_amount,
				a.fund_actual * 100 AS amount_funded,
				(IF (SUM(tr_1.amount) IS NOT NULL, abs(SUM(tr_1.amount)), 0) + IF (tt.affects_principal = 'no' AND tr.amount < 0, abs(tr.amount), 0)) * 100 AS total_service_charge,
				ass.name AS status
			FROM    ach	
	        JOIN    transaction_register AS tr ON ach.ach_id = tr.ach_id
			JOIN 	transaction_type AS tt ON tr.transaction_type_id = tt.transaction_type_id
			JOIN 	application AS a ON tr.application_id = a.application_id
			JOIN 	application_status AS ass USING (application_status_id)
			LEFT JOIN application_tags AS at ON (at.application_id = a.application_id AND at.tag_id IS NOT NULL)
			LEFT JOIN application_tag_details AS atd ON (atd.tag_id = at.tag_id AND atd.company_id = a.company_id)
			LEFT JOIN transaction_register AS tr_1 ON (
				tr_1.application_id = tr.application_id AND
				tr_1.transaction_status = 'complete' AND
				tr_1.amount < 0 AND
				tr_1.transaction_type_id IN (SELECT transaction_type_id FROM transaction_type WHERE affects_principal = 'no' AND company_id = {$company_id})
			)
			WHERE  ach.company_id = {$company_id}
			AND    ach.ach_batch_id in ({$ids})
			GROUP BY 	tr.transaction_register_id
		)
		UNION
		( -- Non ACH
			SELECT DISTINCT
	            DATE_FORMAT(tr.date_effective, '%Y-%m-%d')  AS transaction_date,
				a.application_id AS application_id,
				a.is_react,
				a.customer_id,
				SUBSTR(atd.tag_name, 4) AS lender_tag,
				a.name_first AS first_name,
				a.name_last AS last_name,
				tr.transaction_register_id AS transaction_id,
				a.bank_aba AS bank_aba,
				a.bank_account AS bank_account,
				IF(a.bank_account_type = 'checking', 'C', 'S') AS acct_type,
				'INT' AS ach_flag,
				tt.name_short AS transaction_type,
				-tr.amount * 100 AS total_payment,
				IF (tt.affects_principal = 'yes', -tr.amount, 0) * 100 principal_amount,
				IF (tt.affects_principal = 'no', -tr.amount, 0) * 100 service_charge_amount,
				a.fund_actual * 100 AS amount_funded,
				(IF (SUM(tr_1.amount) IS NOT NULL, abs(SUM(tr_1.amount)), 0) + IF (tt.affects_principal = 'no' AND tr.amount < 0, abs(tr.amount), 0)) * 100 AS total_service_charge,
				ass.name AS status
			FROM	transaction_register AS tr
			JOIN 	event_schedule AS es USING (event_schedule_id)
			JOIN 	transaction_type AS tt ON tr.transaction_type_id = tt.transaction_type_id
			JOIN 	application AS a ON tr.application_id = a.application_id
			JOIN 	application_status AS ass USING (application_status_id)
			LEFT JOIN application_tags AS at ON (at.application_id = a.application_id AND at.tag_id IS NOT NULL)
			LEFT JOIN application_tag_details AS atd ON (atd.tag_id = at.tag_id AND atd.company_id = a.company_id)
			LEFT JOIN transaction_register AS tr_1 ON (
				tr_1.application_id = tr.application_id AND
				tr_1.transaction_status = 'complete' AND
				tr_1.amount < 0 AND
				tr_1.transaction_type_id IN (SELECT transaction_type_id FROM transaction_type WHERE affects_principal = 'no' AND company_id = {$company_id})
			)
			WHERE  tr.company_id = {$company_id}
			AND    tr.date_effective = '$date'
	        AND    tt.clearing_type <> 'ach'
			GROUP BY	tr.transaction_register_id
		)
		ORDER BY application_id, transaction_id, transaction_type ";

		$result = $this->db->query($query);
		$tt_map = $this->Impact_Transaction_Type_Map();
		$fp = fopen($filename, 'w');
		while ($row = $result->fetch(PDO::FETCH_OBJ)) 
		{
			fputcsv($fp, array(
		 		date('Y-m-d', strtotime($row->transaction_date)),
		 		$row->application_id,
		 		$row->lender_tag,
		 		$row->first_name,
		 		$row->last_name,
		 		$row->transaction_id,
		 		$row->bank_aba,
		 		$row->bank_account,
		 		$row->acct_type,
		 		$row->ach_flag,
		 		$tt_map[$row->transaction_type],
		 		round($row->total_payment, 0),
		 		round($row->principal_amount, 0),
		 		round($row->service_charge_amount, 0),
		 		round($row->amount_funded, 0),
		 		round($row->total_service_charge, 0),
		 		$row->status,
		 		$row->customer_id,
		 		strtolower($row->is_react)
		 	));
		}
		fclose($fp);
		return $filename;
	}
	
	protected function Send_Tagging_CSV($filename) 
	{
		$batch_login = eCash_Config::getInstance()->ACH_BATCH_LOGIN;
		$batch_pass  = eCash_Config::getInstance()->ACH_BATCH_PASS;

		require_once(LIB_DIR . "achtransport_sftp.class.php");
		require_once(LIB_DIR . "achtransport_https.class.php");
		try 
		{
			$transport_type   = eCash_Config::getInstance()->ACH_TRANSPORT_TYPE;
			$transport_url    = eCash_Config::getInstance()->ACH_BATCH_URL;
			$transport_server = eCash_Config::getInstance()->ACH_BATCH_SERVER;
			$transport_port   = eCash_Config::getInstance()->ACH_BATCH_SERVER_PORT;
			$transport = ACHTransport::CreateTransport($transport_type, $transport_server, $batch_login, $batch_pass, $transport_port);
		
			if (EXECUTION_MODE != 'LIVE' && $transport->hasMethod('setBatchKey')) 
			{
				$transport->setBatchKey(eCash_Config::getInstance()->ACH_BATCH_KEY);
			}
		
			$batch_response = '';

			// If we're using SFTP, we need to specify the whole path including a filename
			if($transport_type === 'SFTP') 
			{
				/**
				 * If the Tag URL Prefix exists, we need to use it instead
				 * of the generated URL.
				 */
				if(! empty(ECash_Config::getInstance()->TAG_URL_PREFIX))
				{
					$remote_filename = ECash_Config::getInstance()->TAG_URL_PREFIX . "_tag_" . date('Ymd') . ".csv";
				}
				else
				{
					$remote_filename = eCash_Config::getInstance()->TAG_URL ."/{$this->server->company}_tag_" . date('Ymd') . ".csv";
				}
			} 
			else 
			{
				$remote_filename = $transport_url;
			}
			
			$batch_success = $transport->sendBatch($filename, $remote_filename, $batch_response);
		} 
		catch (Exception $e) 
		{
			ECash::getLog()->write($e->getMessage());
			return false;
		}
		return $batch_success;
	}
	
	public function Impact_Transaction_Type_Map() 
	{
		return array(
			'adjustment_internal_fees' 	=> 'T11',
			'adjustment_internal_princ' => 'T11',
			'assess_fee_ach_fail'		=> 'T06',
			'assess_service_chg' 		=> 'T05',
			'bad_data_payment_debt_fee' => 'T16',
			'bad_data_payment_debt_pri' => 'T16',
			'cancel_fees' 				=> 'T26',
			'cancel_principal' 			=> 'T26',
			'chargeback' 				=> 'T31',
			'chargeback_reversal' 		=> 'T32',
			'converted_principal_bal'	=> 'T09',
			'converted_sc_event' 		=> 'T23',
			'converted_service_chg_bal' => 'T10',
			'credit_card_fees' 			=> 'T21',
			'credit_card_princ' 		=> 'T21',
			'debt_writeoff_fees' 		=> 'T16',
			'debt_writeoff_princ' 		=> 'T16',
			'ext_recovery_fees' 		=> 'T17',
			'ext_recovery_princ' 		=> 'T17',
			'ext_recovery_reversal_fee' => 'T29',
			'ext_recovery_reversal_pri' => 'T29',
			'full_balance' 				=> 'T14',
			'loan_disbursement' 		=> 'T01',
			'moneygram_fees' 			=> 'T19',
			'moneygram_princ' 			=> 'T19',
			'money_order_fees' 			=> 'T18',
			'money_order_princ' 		=> 'T18',
			'paydown' 					=> 'T25',
			'payment_arranged_fees' 	=> 'T12',
			'payment_arranged_princ' 	=> 'T12',
			'payment_debt_fees' 		=> 'T28',
			'payment_debt_principal' 	=> 'T28',
			'payment_fee_ach_fail' 		=> 'T07',
			'payment_manual_fees' 		=> 'T13',
			'payment_manual_princ' 		=> 'T13',
			'payment_service_chg' 		=> 'T04',
			'payout_fees' 				=> 'T27',
			'payout_principal' 			=> 'T27',
			'personal_check_fees' 		=> 'T22',
			'personal_check_princ' 		=> 'T22',
			'quickcheck' 				=> 'T15',
			'refund_3rd_party_fees' 	=> 'T30',
			'refund_3rd_party_princ' 	=> 'T30',
			'refund_fees' 				=> 'T24',
			'refund_princ' 				=> 'T24',
			'repayment_principal' 		=> 'T03',
			'western_union_fees' 		=> 'T20',
			'western_union_princ' 		=> 'T20',
			'writeoff_fee_ach_fail' 	=> 'T08',
		);
	}
	
	public function Tag_Approved_Applications() 
	{
		Set_Process_Status($this->db, $this->server->company_id, 'tag_applications', 'started');
		$this->db->beginTransaction();
		ECash::getLog()->Write("Tagging Funded Applications.");
		
		try
		{
			$query = "
					SELECT
						 application_id, fund_actual
					FROM transaction_register tr 
					JOIN application a USING (application_id)
					JOIN application_status_flat asf USING (application_status_id)
					LEFT OUTER JOIN application_tags USING (application_id)
					WHERE
				    	(level0 = 'approved' AND level1 = 'servicing' AND level2 = 'customer' AND level3 = '*root')
						AND	a.company_id = {$this->server->company_id}
						AND tag_id IS NULL /* eliminates false detection of bogusly set application_tags entries */
					GROUP BY application_id ";
			
			$result = $this->db->query($query);
			 
			$tags = Load_Tags();
			$tag_weights = Load_Tag_Weight_Map();
			 
			$distribution_map = Create_Distribution_Array($tag_weights);

			while($row = $result->fetch(PDO::FETCH_OBJ))
			{
			 	$tag_id = Assign_Tag($row->application_id, $row->fund_actual, $tag_weights, $distribution_map);
				ECash::getLog()->Write("Account {$row->application_id} Tagged: ".$tags[$tag_id]->tag_name);
			}
			Set_Process_Status($this->db, $this->server->company_id, 'tag_applications', 'completed');
			$this->db->commit();
		}
		catch(Exception $e)
		{
			ECash::getLog()->Write("Tagging of apps failed. Transaction will be rolled back.", LOG_ERR);
			$this->db->rollBack();
			Set_Process_Status($this->db, $this->server->company_id, 'tag_applications', 'failed');
			throw $e;
		}


		return true;
	}
	
	public function Status_Approved_Move_To_Active()
	{
		 try
		 {
			 $query = "
					 SELECT
						 application_id, date_modified
					 FROM
						 application a,
						 application_status_flat asf
					 WHERE
							 a.application_status_id = asf.application_status_id
						 AND	(level0 = 'approved' AND level1 = 'servicing' AND level2 = 'customer' AND level3 = '*root')
						 AND	a.company_id = {$this->server->company_id}
						 AND exists (SELECT 'X' FROM transaction_register tr WHERE tr.application_id = a.application_id)
					 FOR UPDATE
			 ";

			 $result = $this->db->query($query);

			 while($row = $result->fetch(PDO::FETCH_OBJ))
			 {
				// Have to set this for Update_Status to work.
				$_SESSION['LOCK_LAYER']['App_Info'][$row->application_id]['date_modified']  = $row->date_modified;
				ECash::getLog()->Write("Account {$row->application_id}: Approved -> Active.");
				doOnlyUpdateStatus($row->application_id, array( 'active', 'servicing', 'customer', '*root' ));
				
				Set_Standby($row->application_id, 'approval_terms');
				
				eCash_Document_AutoEmail::Queue_For_Send($this->server, $row->application_id, 'APPROVAL_TERMS');
		
			 }
		}
		catch(Exception $e)
		{
			ECash::getLog()->Write("Movement of apps from approved to active status failed.", LOG_ERR);
			throw $e;
		}


		return true;
	}
	
	public function gracePeriodToActive()
	{
		
		 try
		 {
		 	$business_rules = new ECash_BusinessRulesCache(ECash_Config::getMasterDbConnection());
		 	$rule_set_ids = $business_rules->Get_Rule_Set_Ids_By_Parm_Value('loan_type_model','CSO');
		 	$rule_sets = implode(',',$rule_set_ids);
		 	
			 $query = "
 					 SELECT
						 a.application_id, a.date_modified
					 FROM
						 application a
                     JOIN
                          application_status_flat asf ON a.application_status_id = asf.application_status_id
                     JOIN 
                          event_schedule es ON es.application_id = a.application_id
                     JOIN
                          transaction_register tr ON tr.event_schedule_id = es.event_schedule_id
					 WHERE
					 	(level0 = 'past_due' AND level1 = 'servicing' AND level2 = 'customer' AND level3 = '*root')
					 AND	
                        a.company_id = {$this->server->company_id}
                     AND 
                     	a.rule_set_id IN ({$rule_sets})
                     AND
                        es.origin_id IS NOT NULL
                     AND
                        tr.transaction_status = 'pending'
					 FOR UPDATE
			 ";

			 $result = $this->db->query($query);

			 while($row = $result->fetch(PDO::FETCH_OBJ))
			 {
				// Have to set this for Update_Status to work.
				$_SESSION['LOCK_LAYER']['App_Info'][$row->application_id]['date_modified']  = $row->date_modified;
				ECash::getLog()->Write("Account {$row->application_id}: Successful arrangements, moving from Past Due -> Active.");
				
				Update_Status(null,$row->application_id, array( 'active', 'servicing', 'customer', '*root' ));
			 }
		}
		catch(Exception $e)
		{
			ECash::getLog()->Write("Movement of apps from past due to active status failed.", LOG_ERR);
			throw $e;
		}


		return true;
	}
	
}

?>
