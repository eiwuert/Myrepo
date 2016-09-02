<?php


class ACH_Return_Intercept extends ACH_Return 
{
		// Intercept Return File Format
	protected $return_file_format = array(
						'location_key_not_used',
						'company_key_not_used',
						'entry_key_not_used',
						'originating_bank_name',
						'processor_name',
						'achid',
						'pin',
						'phone_number',
						'fax_number',
						'company_name',
						'entry_description',
						'app_discretionary_data',
						'T_F_correction_flag',
						'aba',
						'account_number',
						'corrected_info',
						'sec',
						'effective_entry_date',
						'recipient_id',
						'recipient_name',
						'debit_amount',
						'credit_amount',
						'reason_description',
						'reason_code',
						'account_type',
						'trans_code',
						'T_F_return_flag',
						'recipient_discretionary_data',
						'trace_number',
						'original_trace_number',
						'T_F_xcelerated_return_flag' );	
						
	public function __construct(Server $server)
	{
		parent::__construct($server);
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
					if (strpos($response['received'], 'ER=') === false)
					{
						$this->log->Write("Received field does not contain ER= code");

						$ach_report_data = $this->Parse_Report_Batch($response['received']);

						$this->log->Write("Found " . count($ach_report_data) . " items in ACH report.");
						
						foreach ($ach_report_data as $report_data)
						{
							$this->ach_exceptions_flag = FALSE;
							
							if(!is_array($report_data))
								continue;

							if(! isset($report_data['recipient_id']) || empty($report_data['recipient_id'])) {
								$this->log->Write("Unrecognized Report Entry: " . var_export($report_data,true));
								$exception = array(
									'ach_id'  => $ach_id, 
									'exception' => "Unrecognized Report Entry: " . var_export($report_data,true),
								);
								$this->ach_exceptions[] = $exception;
								$this->Insert_ACH_Exception($report_data);
								continue;
							}

							$ach_id		= ltrim($report_data['recipient_id'], '0');
							$reason_code	=  trim($report_data['reason_code']);
							$debit_amount	=  trim($report_data['debit_amount']);
							$credit_amount	=  trim($report_data['credit_amount']);
							
							// need these as well for reporting exceptions to CLK
							$recipient_name = trim($report_data['recipient_name']);
							$reason_description = trim($report_data['reason_description']);
							$effective_entry_date = trim($report_data['effective_entry_date']);
		
							$trace_number = trim($report_data['trace_number']);
							$original_trace_number = trim($report_data['original_trace_number']);
														
							if (is_numeric($ach_id))
							{
								// If ach_id is less than our starting sequence number in ach table, then this is a legacy return
								//	from Cashline, and the recipient ID is actually a Cashline ID
								//$process_type = ( ($ach_id >= 2000000) ? 'normal' : 'cashline');
								$process_type = 'normal';
								$this->log->Write("Process_ACH_Report: ach_id: $ach_id, process type: $process_type");

								if ($report_type == 'returns')
								{
									if ($process_type == 'cashline')
									{
										if ($debit_amount > 0)
										{
											$return_amount = -1 * $debit_amount;
										}
										else
										{
											$return_amount = $credit_amount;
										}
										
										try	{
											$result = $this->crp->reportACHReturn(
												$ach_id, $this->company_id, 
												$return_amount, $reason_code, 
												$response['ach_report_id'],
												$start_date
											);
											if ($result)
											{
												$reschedule_list[] = $result;
											}
										}																
										catch(Exception $e)	{
											$this->log->Write("Process_ACH_Report(): ACH: Cashline processing failed: {$e->getMessage()}", LOG_ERR);
											$exception = array(
												'ach_id' => $ach_id,
												'exception' => "Cashline Processing Failed: {$e->getMessage()}.",
												'recipient_name' => $recipient_name
											);
											$this->ach_exceptions[] = $exception;
											$this->ach_exceptions_flag = TRUE;
										}
									}
									else
									{
										// Update status to returned in ach table
										try 
										{
											$this->db->beginTransaction();
											$this->ach_utils->Update_ACH_Row('customer', $ach_id, 'returned', NULL, $reason_code, $response['ach_report_id']);
	
											// Update failure status into transaction_register row(s) for this ach_id
											$this->Update_Transaction_Register_ACH_Failure($ach_id);
											$this->db->commit();
										}
										catch (Exception $e)
										{
											$this->log->Write("There was an error failing an eCash transaction: {$e->getMessage()}");
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
									}
								}
								elseif ($report_type == 'corrections')

								{
									// Process corrections -- update related application data, if possible
									$corrected_data_ary = explode("/", $report_data['corrected_info']);
									foreach ($corrected_data_ary as $key => $correction_item)
									{
										$corrected_data_ary[$key] = trim($correction_item);
									}
									
									$do_update = false;
									
									switch($reason_code)
									{
									case 'C01':
										// Incorrect account number
										if ( $this->Validate_COR_Account($corrected_data_ary[1], $normalized_account) )
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
										if ( $this->Validate_COR_ABA($corrected_data_ary[0], $normalized_ABA) )
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
										if ( $this->Validate_COR_ABA($corrected_data_ary[0], $normalized_ABA)			&&
										     $this->Validate_COR_Account($corrected_data_ary[1], $normalized_account) 		)
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
										if ( $this->Validate_Name($corrected_data_ary[0], $normalized_name_last, $normalized_name_first) )
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
										if ( $this->Validate_Tran_Code($corrected_data_ary[2], $bank_account_type) )
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
										if ( $this->Validate_COR_Account($corrected_data_ary[1], $normalized_account)	&&
										     $this->Validate_Tran_Code($corrected_data_ary[2], $bank_account_type)			)
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
										if ( $this->Validate_COR_ABA($corrected_data_ary[0], $normalized_ABA)			&&
										     $this->Validate_COR_Account($corrected_data_ary[1], $normalized_account)	&&
										     $this->Validate_Tran_Code($corrected_data_ary[2], $bank_account_type)			)
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

				$auth_info = $this->ach_utils->getInterceptAuthCodes();
				$url = eCash_Config::getInstance()->ACH_REPORT_URL . $auth_info['session_id'];
				
				$report_response = '';
				$report_success = $transport->retrieveReport($url, $report_type, $report_response, $auth_info['value_1'], $auth_info['value_2'], $auth_info['value_3']);
				var_dump($report_success);
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
	
}
?>