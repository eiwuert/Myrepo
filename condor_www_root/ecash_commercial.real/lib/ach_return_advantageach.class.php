<?php

class ACH_Return_AdvantageACH extends ACH_Return 
{
		// ACHCommerce Return File Format
	protected $return_file_format = array(
						'merchant_id',
						'company_name', 
						'effective_entry_date', // SubmitDate
						'trans_code',
						'ABA',
						'AccountNumber',
						'AmountInCents',
						'recipient_id',
						'recipient_name',
						'reason_code', // ReturnCode
						'corrected_info', // AddendaInfo
						'trace_number'
	);
	private static $RS		  = "\r";
	
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
						//$this->log->Write("Received field does not contain ER= code");

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
										// Update status to returned in ach table
										try 
										{
											$this->db->beginTransaction();
											$this->ach_utils->Update_ACH_Row('customer', $ach_id, 'returned', NULL, $reason_code, $response['ach_report_id']);
	
											// Update failure status into transaction_register row(s) for this ach_id
											$needs_reschedule = $this->Update_Transaction_Register_ACH_Failure($ach_id);
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
										if ($needs_reschedule)
										{
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
}
?>