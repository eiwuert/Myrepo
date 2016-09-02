<?php

class ACH_Return_Teledraft extends ACH_Return 
{
	protected $return_file_format = array(
						'client_id',
						'selection_date', 
						'client_account_id',
						'td_return_id',
						'td_return_date',
						'bank_return_id',
						'bank_return_file_id',
						'td_return_type',
						'fed_return_code',
						'return_description',
						'merchant_id',
						'td_trans_id',
						'merchant_trans_id',
						'correct_acct_number',
						'correct_routing_number',
						'correct_name',
						'correct_trans_code',
						'correct_indiv_id',
						'record_type_code',
						'trans_code',
						'receiving_dfi_id',
						'check_digit',
						'dfi_acct_number',
						'amount',
						'indiv_id_number',
						'indiv_name',
						'discretionary_data',
						'addenda_record_indicator',
						'trace_number',
						'addenda_record_type_code',
						'addenda_type_code',
						'addenda_return_reason_code',
						'addenda_orig_entry_trace_num',
						'addenda_date_of_death',
						'addenda_orig_rec_dfi_id',
						'addenda_info',
						'addenda_trace_number'
	);
	
	protected $results_file_format = array(
						'transaction_type',
						'account_type', 
						'debit_credit',
						'check_number',
						'routing_number',
						'account_number',
						'amount',
						'transaction_date',
						'customer_name',
						'customer_address_1',
						'customer_address_2',
						'customer_city',
						'customer_state',
						'customer_zipcode',
						'customer_phone',
						'driver_license_state',
						'driver_license_number',
						'social_security_number',
						'merchant_id',
						'merchant_trans_id',
						'teledraft_trans_id',
						'batch_id',
						'batch_datetime',
						'tran_status_code',
						'reason_codes',
						'approved_amount',
						'discount_fee',
						'transaction_fee',
						'charge_back_fee',
						'net_due_merchant',
						'settlement_amount',
						'total_settlement_amount'
	);
	private static $RS		  = "\n";
	private $report_type;
	
	public function __construct(Server $server)
	{
		parent::__construct($server);
	}
	
	public function Process_ACH_Results($end_date, $override_start_date = NULL)
	{
		return $this->Process_ACH_Report($end_date, 'results', $override_start_date);
	}
	
	public function Process_ACH_Report ($end_date, $report_type, $override_start_date = NULL)
	{
		$this->business_day = $end_date;
		$commented_corrections = array();
		$reschedule_list = array();
		$this->ach_exceptions = array();
		
		$this->log->Write("Process_ACH_Report ...\n");

		// Get the most recent business date of the last report execution of the same type
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					SELECT 
						max(business_day) as last_run_date
					FROM 
						process_log
					WHERE
							step	 = 'ach_{$report_type}'
						AND	state	 = 'completed'
						AND company_id	 = {$this->company_id}
						AND business_day <> '1970-01-01'
		";
//		$result = $this->mysqli->Query($query);
//		$row = $result->Fetch_Array_Row();
		$result = $this->db->Query($query);
		$row = $result->fetch(PDO::FETCH_ASSOC);

		if(NULL !== $override_start_date)
		{
			$start_date = date("Y-m-d", strtotime($override_start_date));
		}
		elseif( !empty($row['last_run_date']) )
		{
			$last_run_date = $row['last_run_date'];
			$start_date = date("Y-m-d", strtotime("+1 day", strtotime($last_run_date)));
		}
		else
		{
			$start_date = date("Y-m-d", strtotime("now"));
		}

		$count = 0;

		$this->log->Write("Process_ACH_Report(): start date: {$start_date}, end date: {$end_date}");

		try
		{
			while($start_date <= $end_date)
			{
				$this->log->Write("Process_ACH_Report(): Running report {$report_type} for date {$start_date}");

				if($response = $this->Fetch_Report($start_date, $report_type))
				{
					// if no error code received, proceed
					if (strpos($response['received'], 'ER=') === false || strpos($response['received'], ",ERROR,") === false)
					{
						//$this->log->Write("Received field does not contain ER= code");

						$ach_report_data = $this->Parse_Report_Batch($response['received']);

						$this->log->Write("Found " . count($ach_report_data) . " items in ACH report.");
						
						foreach ($ach_report_data as $report_data)
						{
							$this->ach_exceptions_flag = FALSE;
							
							if(!is_array($report_data))
								continue;
							
							// Ignore transactions that passed	
							if(isset($report_data['tran_status_code']) && in_array($report_data['tran_status_code'], array('APRVD','TRNFR','RVRSL')))
							{
								continue;
							}

							if(! isset($report_data['merchant_trans_id']) || empty($report_data['merchant_trans_id'])) {
								$this->log->Write("Unrecognized Report Entry: " . var_export($report_data,true));
								$exception = array(
									'ach_id'  => $ach_id, 
									'exception' => "Unrecognized Report Entry: " . var_export($report_data,true),
								);
								$this->ach_exceptions[] = $exception;
								$this->Insert_ACH_Exception($report_data);
								continue;
							}
							
							$ach_id		= ltrim($report_data['merchant_trans_id'], '0');
							$debit_amount	=  trim($report_data['amount']);
							$credit_amount	=  trim($report_data['amount']);
									
							switch($report_type)
							{
								case 'returns':
								case 'corrections':

									$reason_code	=  trim($report_data['fed_return_code']);		
									
									$recipient_name = trim($report_data['indiv_name']);
									$reason_description = trim($report_data['return_description']);
									$effective_entry_date = trim($report_data['td_return_date']);
				
									$trace_number = trim($report_data['trace_number']);
									$original_trace_number = trim($report_data['trace_number']);
									break;
								
								case 'results':
									$recipient_name = trim($report_data['customer_name']);
							
									switch($report_data['tran_status_code'])
									{
										case "FAILA":
											$reason_code	=  trim($report_data['tran_status_code']);
											$reason_description = trim($report_data['reason_codes']);
											break;
										case "FAILT":
											$reason_code	=  trim($report_data['reason_codes']);
											$reason_description = "Fed Return"; // Reason code description
											break;
										case "FAILV":
											$reason_code	=  trim($report_data['tran_status_code']);
											$reason_description = trim($report_data['reason_codes']);;
											break;
									}
									
									$effective_entry_date = trim($report_data['transaction_date']);
									break;
							}				
										
							if (is_numeric($ach_id))
							{
								// If ach_id is less than our starting sequence number in ach table, then this is a legacy return
								//	from Cashline, and the recipient ID is actually a Cashline ID
								//$process_type = ( ($ach_id >= 2000000) ? 'normal' : 'cashline');
								$process_type = 'normal';
								$this->log->Write("Process_ACH_Report: ach_id: $ach_id, process type: $process_type");

								if ($report_type == 'returns' || $report_type == 'results')
								{
										// Update status to returned in ach table
										try 
										{
//											MySQLi_1e::Get_Instance()->Start_Transaction();
											$this->db->beginTransaction();
											$this->ach_utils->Update_ACH_Row('customer', $ach_id, 'returned', NULL, $reason_code, $response['ach_report_id']);
	
											// Update failure status into transaction_register row(s) for this ach_id
											$this->Update_Transaction_Register_ACH_Failure($ach_id);
//											MySQLi_1e::Get_Instance()->Commit();
											$this->db->commit();
										}
										catch (Exception $e)
										{
											$this->log->Write("There was an error failing an eCash transaction: {$e->getMessage()}");
//											MySQLi_1e::Get_Instance()->Rollback();
											if ($this->db->getInTransaction())
											{
												$this->db->rollback();
											}
											throw new $e;
										}	

										// Add this app to the rescheduling list
										$app_id = $this->Get_Return_App_ID($ach_id, $process_type);
										if ($app_id)
										{
											$reschedule_list[] = $app_id;
										}
								
										// GF #10079:
										// AALM wants to hit a stat, but not for credits, only debits,
										// trans_code == 21 for credit
										// trans_code == 26 for debits
										if ($report_data['trans_code'] == '26')
										{
											if (!isset($debit_list))
												$debit_list = array();

											// We only want to send this stat once per application_id per return file
											// We can do that by making an array and only inserting unique keys into it 
											if (!in_array($app_id, $debit_list))
											{
												$debit_list[] = $app_id;
									
												// Hit ach_return stat
												$stat = new Stat();
												$stat->Setup_Stat($app_id);
												$stat->Hit_Stat('ach_return');
											}
										} 
								}
								elseif ($report_type == 'corrections')
								{
									// Process corrections -- update related application data, if possible
									//$corrected_data_ary = explode("/", $report_data['corrected_info']);
//									foreach ($corrected_data_ary as $key => $correction_item)
//									{
//										$corrected_data_ary[$key] = trim($correction_item);
//									}
									
									$do_update = false;
					
									switch($reason_code)
									{
									case 'C01':
										// Incorrect account number
										if ( $this->Validate_COR_Account($report_data['correct_acct_number'], $normalized_account) )
										{
											$app_update_ary = array (
														 'bank_account'	=> $normalized_account
														 );
											$comment_text = "Acct# auto correction: Set to $normalized_account";
											$do_update = true;
										}
										break;
 											
									case 'C02':
										// Incorrect routing number
										if ( $this->Validate_COR_ABA($report_data['correct_routing_number'], $normalized_ABA) )
										{
											$app_update_ary = array (
														 'bank_aba'		=> $normalized_ABA
														 );
											$comment_text = "ABA# auto correction: Set to $normalized_ABA";
											$do_update = true;
										}
										break;
 											
									case 'C03':
										// Incorrect routing number AND account number
										if ( $this->Validate_COR_ABA($report_data['correct_routing_number'], $normalized_ABA)			&&
										     $this->Validate_COR_Account($report_data['correct_acct_number'], $normalized_account) 		)
										{
											$app_update_ary = array (
														 'bank_aba'		=> $normalized_ABA,
														 'bank_account'	=> $normalized_account
														 );
											$comment_text = "ABA/Acct# auto correction: Set to $normalized_ABA / $normalized_account";
											$do_update = true;
										}
										break;
 											
									case 'C04':
										// Incorrect individual name
										if ( $this->Validate_Name($report_data['correct_name'], $normalized_name_last, $normalized_name_first) )
										{
											$app_update_ary = array (
														 'name_last'		=> $normalized_name_last,
														 'name_first'	=> $normalized_name_first
														 );
											$comment_text = "Applicant Name auto correction: Set to $normalized_name_last, $normalized_name_first";
											$do_update = true;
										}
										break;
 											
									case 'C05':
										// Incorrect transaction code
										if ( $this->Validate_Tran_Code($report_data['correct_trans_code'], $bank_account_type) )
										{
											$app_update_ary = array (
														 'bank_account_type'	=> $bank_account_type
														 );
											$comment_text = "Acct Type auto correction: Set to $bank_account_type";
											$do_update = true;
										}
										break;
 											
									case 'C06':
										// Incorrect account number AND transaction code
										if ( $this->Validate_COR_Account($report_data['correct_acct_number'], $normalized_account)	&&
										     $this->Validate_Tran_Code($report_data['correct_trans_code'], $bank_account_type)			)
										{
											$app_update_ary = array (
														 'bank_account'		=> $normalized_account,
														 'bank_account_type'	=> $bank_account_type
														 );
											$comment_text = "Acct#/Type auto correction: Set to $normalized_account / $bank_account_type";
											$do_update = true;
										}
										break;
 											
									case 'C07':
										// Incorrect routing number, account number, AND transaction code
										if ( $this->Validate_COR_ABA($report_data['correct_routing_number'], $normalized_ABA)			&&
										     $this->Validate_COR_Account($report_data['correct_acct_number'], $normalized_account)	&&
										     $this->Validate_Tran_Code($report_data['correct_trans_code'], $bank_account_type)			)
										{
											$app_update_ary = array (
														 'bank_aba'			=> $normalized_ABA,
														 'bank_account'		=> $normalized_account,
														 'bank_account_type'	=> $bank_account_type
														 );
											$comment_text = "ABA/Acct#/Type auto correction: Set to $normalized_ABA / $normalized_account / $bank_account_type";
											$do_update = true;
										}
										break;
									}
									
									if ($do_update)
									{
										$app_id = $this->Get_Return_App_ID($ach_id, $process_type);
										if ($app_id)
										{
											$updated = $this->Update_Application_Info($app_id, $app_update_ary);
											if ($updated === FALSE)
											{
												$this->log->Write("Unable to update App ID: {$app_id}");
											}
											else
											{
												// A Dirty hack by RayLo to keep for entering duplicate corrections comments
												// We will keep an array of commented corrections so that we dont comment
												// this application again while going through the corrections
												if(!in_array($app_id,$commented_corrections))
												{
													$commented_corrections[] = $app_id;
													$this->ach_utils->Add_Comment($app_id, $reason_code.' - '.$comment_text);
													$commented_corrections[] = $app_id;
												}
											}
										}
										else
										{
											$this->log->Write("Unable to locate Application ID for :'{$ach_id}', using process type '{$process_type}'");
										}
									}
								}
							}
							else
							{
								$this->log->Write("Unrecognized Report Entry: " . var_export($report_data,true));
								$exception = array(
									'ach_id'  => $ach_id, 
									'exception' => "Unrecognized Report Entry: " . var_export($report_data,true),
								);
								$this->ach_exceptions[] = $exception;
								$this->ach_exceptions_flag = TRUE;
							}
							
							// Insert ach exception if any exceptions thrown for ach record.
							if($this->ach_exceptions_flag)
							{
								$this->Insert_ACH_Exception($report_data);
							}
						}
						
						// Mark report as processed
						$this->Update_ACH_Report_Status($response['ach_report_id'], 'processed');
						$this->log->Write("ACH: " . ucfirst($report_type) . " Report " . $response['ach_report_id'] .  " has been received without errors ($start_date).", LOG_INFO);
						$count++;
					}
					else
					{
						// Mark report as failed
						$this->Update_ACH_Report_Status($response['ach_report_id'], 'failed');
						$this->log->Write("ACH: " . ucfirst($report_type) . " Report " . $response['ach_report_id'] .  " was received with errors ($start_date).", LOG_ERR);
					}
				}	
					
				// Advance start date by one day
				$this->log->Write("Process_ACH_Report(): advance start date\n");
				$start_date = date("Y-m-d", strtotime("+1 day", strtotime($start_date)));
			}
		}
		catch(Exception $e)
		{
			$this->log->Write("ACH: Processing of $report_type failed and transaction will be rolled back.", LOG_ERR);
			$this->log->Write("ACH: No data recovery should be necessary after the cause of this problem has been determined.", LOG_INFO);
			throw $e;
		}
		
		// This was not running inside a Transaction ...	
		//$this->mysqli->Commit();				
		
		// Now put everyone in the reschedule_list into the standby table for later processing 
		$this->log->Write("Removing duplicate reschedule candidates");
		$reschedule_list = array_unique($reschedule_list);
		foreach($reschedule_list as $app_id)
		{
			Set_Standby($app_id, 'reschedule');
		}

		$this->log->Write("ACH: $count " . ucfirst($report_type) . " Reports were successfully processed.", LOG_ERR);
		
		if(count($this->ach_exceptions) > 0) 
		{
			$this->log->Write("ACH: " . count($this->ach_exceptions) . " Exceptions found.", LOG_ERR);
			$report_body = "";

			require_once(LIB_DIR . '/CsvFormat.class.php');

			$csv = CsvFormat::getFromArray(array(
				'ACH ID',
				'Name',
				'Exception Message'));

			foreach ($this->ach_exceptions as $e)
			{
				$csv .= CsvFormat::getFromArray(array(
					$e['ach_id'],
					$e['recipient_name'],
					$e['exception']));
			}

			$attachments = array(
				array(
					'method' => 'ATTACH',
					'filename' => 'ach-exceptions.csv',
					'mime_type' => 'text/plain',
					'file_data' => gzcompress($csv),
					'file_data_length' => strlen($csv)));

			if(eCash_Config::getInstance()->NOTIFICATION_ERROR_RECIPIENTS != NULL) {
				$recipients = eCash_Config::getInstance()->NOTIFICATION_ERROR_RECIPIENTS;
			}

			if (!empty($recipients))
			{
				$subject = 'Ecash Alert '. strtoupper($this->company_abbrev); //mantis:7727
				$body = $this->company_abbrev . ' - ACH ' . ucwords($report_type) . ' Exception Report';
				require_once(LIB_DIR . '/Mail.class.php');
				try
				{
					eCash_Mail::sendExceptionMessage($recipients, $body, $subject, array(), $attachments);
				}
				catch (Exception $e)
				{
					$this->log->Write("The ACH Exception Report Failed to send but returns have been logged.");
				}
			}
		}
				
	
		return $count;
	}
	
	
	protected function Insert_ACH_Exception($report_data=NULL)
	{
		if($report_data)
		{
			$effective_entry_date = date('Y-m-d');
			$ach_id		= ltrim($report_data['merchant_trans_id'], '0');
			
			// Need proper values here for Results files
			$recipient_name = isset($report_data['indiv_name']) ? trim($report_data['indiv_name']) : "";
			$debit_amount	=  isset($report_data['amount']) ? trim($report_data['amount']) : '0.00';
			$credit_amount	=  isset($report_data['amount']) ? trim($report_data['amount']) : '0.00';
			$reason_code	=  isset($report_data['fed_return_code']) ? trim($report_data['fed_return_code']) : "";
	
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
//			$result = $this->mysqli->Query($check_query);
//			$count = $this->mysqli->Affected_Row_Count();
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
//					$this->mysqli->Query($ins_query);
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
				$transport = ACHTransport::CreateTransport($transport_type, $batch_server,  $batch_login, $batch_pass, $transport_port);
			
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
						
					case "results":
						$results_dir = dirname(eCash_Config::getInstance()->ACH_REPORT_RETURNS_URL);
						$filename = $this->Get_Result_Filename($batch_server, 21, $batch_login, $batch_pass, $results_dir, $start_date);
						$url = $results_dir . "/" . $filename;
						
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
		
		//if ($report_success && strlen($report_response) > 0) 
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
	
	/**
	 * This method returns the results filename because a portion of the filename
	 * consists of a batch_id that is generated on Teledraft's side and we can't predict it. 
	 *
	 * @param String $server
	 * @param Int $port
	 * @param String $username
	 * @param String $password
	 * @param String $directory
	 * @return String
	 */
	public function Get_Result_Filename($server, $port=null, $username, $password, $directory, $start_date)
	{
		$client_id = eCash_Config::getInstance()->CLIENT_ID;
		
		$ftp = ftp_ssl_connect($server, $port);
		@ftp_login($ftp, $username, $password);
		ftp_pasv($ftp, true);
		$list = ftp_nlist($ftp, $directory);
		
		foreach($list as $filename)
		{
			if(stristr($filename, $client_id . "_".date("Ymd",strtotime($start_date))))
			{
				return $filename;
			}
		}
		
		return FALSE;
	}
	
	public function Fetch_Report($start_date, $report_type)
	{
		$this->report_type = $report_type;
		switch($report_type)
		{
			case 'returns':
				$type = "RET";
				break;
			case 'corrections':
				$type = "COR";
				break;
			case 'results':
				$type = "RES";
				break;
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
//		$result = $this->mysqli->Query($query);
		$result = $this->db->Query($query);
		
		if($result->rowCount() > 0)
		{
//			$report = $result->Fetch_Array_Row(MYSQLI_ASSOC);
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
					if($this->report_type == 'results')
					{
						$parsed_data_ary[$i][$this->results_file_format[$key]] = str_replace('"', '', $col_data);
					}else{
						$parsed_data_ary[$i][$this->return_file_format[$key]] = str_replace('"', '', $col_data);
					}
				}

				$i++;
			}
		}
		
		return $parsed_data_ary;
	}
}
?>
