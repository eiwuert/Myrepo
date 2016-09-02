<?php

/**
 * Class for handling ACH returns from Regal
 * 
 * This class is based on the ACH_Return_Teledraft class because there are some 
 * customizations that AALM wanted that were integrated into the Teledraft returns
 * class.
 *
 * History:
 * 20080915 - Refactored this particular class quite a bit to make it easier to 
 *            process on a file by file basis.  Process_ACH_Report() is now 
 *            a wrapper for Process_ACH_Report_Data() which does the actual
 *            processing of the report.  This makes it easier to pass any report
 *            we want and process it regardless of where we got the data or what
 *            date it was requested in.  The plan is to eventually allow uploaded
 *            return files.  [BR]
 * 
 */
class ACH_Return_Regal extends ACH_Return 
{
	/**
	 * Regal's Return File Format
	 * 
	 * Items with an asterisk (*) next to them are required
	 * for the returns handling to work.
	 */
	protected $return_file_format = array(
		'proc_tran_id', 			// Processor Tran ID 		(Field  1)
		'proc_batch_id', 			// Processor Batch ID 		(Field  2)
		'trans_code', 				// Action (Debit / Credit) 	(Field  3)
		'merchant_trans_id',		// In House ID (our ach_id) (Field  4)
		'proc_submit_date', 		// Submit Date 				(Field  5)
		'indiv_name', 				// Name (Customer Name) 	(Field  6)
		'return_date', 				// Return Date 				(Field  7)
		'proc_check_no', 			// Check No (Unknown) 		(Field  8)
		'proc_check_trans_date', 	// Check / Trans Date 		(Field  9)
		'cust_bank_aba',			// Bank ABA 				(Field 10)
		'cust_acct_num', 			// Account Number (Last 4) 	(Field 11)
		'proc_reference', 			// Reference 				(Field 12)
		'proc_import_id',			// Import ID (Unknown) 		(Field 13)
		'proc_submit_count', 		// Submit Count 			(Field 14)
		'proc_status',				// Status (Returned) 		(Field 15)
		'fed_return_code', 			// Return Code * 			(Field 16)
		'proc_return_reason', 		// Status Desc 				(Field 17)
		'amount_debit',				// Amount Debit * 			(Field 18)
		'amount_credit',			// Amount Credit * 			(Field 19)
	);

	/**
	 * Regal's Corrections File Format
	 *
	 */
	protected $correction_file_format = array(
		'proc_tran_id', 			// Processor Tran ID 		(Field  1)
		'proc_submit_date', 		// Submit Date 				(Field  2)
		'return_file_source',		// Orig. Batch Filename		(Field  3)
		'proc_company', 			// Company (Unknown) 		(Field  4)
		'indiv_name', 				// Name (Customer Name) 	(Field  5)
		'merchant_trans_id',		// In House ID (our ach_id) (Field  6)
		'correct_routing_number',	// Bank ABA 				(Field  7)
		'correct_acct_number', 		// Account Number (Last 4) 	(Field  8)
		'proc_reference', 			// Reference 				(Field  9)
		'fed_return_code', 			// Return Code * 			(Field 10)
		'proc_return_reason', 		// Status Desc 				(Field 11)
		'addenda_info',				// Addenda					(Field 12)
	);

	private static $RS		  = "\r\n";
	private $report_type;
	
	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	/**
	 * Processes the ACH Returns for a particular date range
	 *
	 * @param string $end_date
	 * @param string $override_start_date
	 * @return boolean
	 */
	public function Process_ACH_Returns($end_date, $override_start_date = NULL)
	{
		return $this->Process_ACH_Report($end_date, 'returns', $override_start_date);
	}

	/**
	 * Processes the ACH Corrections for a particular date range
	 *
	 * @param string $end_date
	 * @param string $override_start_date
	 * @return boolean
	 */
	public function Process_ACH_Corrections($end_date, $override_start_date = NULL)
	{
		return $this->Process_ACH_Report($end_date, 'corrections', $override_start_date);
	}
	

	/**
	 * A wrapper that pulls the reports for a particular date range and for a
	 * particular report type and passes it to Process_ACH_Report_Data().
	 *
	 * @param string $end_date
	 * @param string $report_type
	 * @param string $override_start_date
	 * @return boolean
	 */
	public function Process_ACH_Report ($end_date, $report_type, $override_start_date = NULL)
	{
		$this->log->Write("Processing ACH ".ucfirst(($report_type))." Report ...\n");

		if(empty($override_start_date))
		{
			$this->getReportRunDates($start_date, $end_date, $report_type);
		}
		else
		{
			$start_date = $override_start_date;
		}
		
		$this->log->Write("Process_ACH_Report(): start date: {$start_date}, end date: {$end_date}");

		$result = $this->fetchReportByDate($report_type, $start_date, $end_date);

		if($result->rowCount() > 0)
		{
			$count = 0;
			while($report = $result->fetch(PDO::FETCH_ASSOC))
			{
				$this->log->Write("Processing ACH report for {$report['request_date']}");
				$this->Process_ACH_Report_Data($report, $report_type);
				$count++;
			}
			$this->log->Write("ACH: $count " . ucfirst($report_type) . " Reports were successfully processed.");
		}
		else
		{
			$this->log->Write("Unable to retrieve report type $report_type for $start_date");
			return FALSE;
		}

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
		
	}
	
	/**
	 * Process an ACH Report
	 *
	 * @param array $response
	 * @return boolean
	 */
	public function Process_ACH_Report_Data ($response, $report_type)
	{
		$this->business_day = $end_date;
		$commented_corrections = array();
		$reschedule_list = array();
		$this->ach_exceptions = array();

		switch($report_type)
		{
			case 'returns':
				$report_format = $this->return_file_format;
				break;
			
			case 'corrections';
				$report_format = $this->correction_file_format;
				break;
				
			default:
				throw new Exception("Unknown report format $report_type!");
		}
		try 
		{
			// if no error code received, proceed
			if (strpos($response['received'], 'ER=') === FALSE || strpos($response['received'], ",ERROR,") === FALSE)
			{
				$ach_report_data = $this->Parse_Report_Batch($response['received'], $report_format);

				$this->log->Write("Found " . count($ach_report_data) . " items in ACH report.");
				foreach ($ach_report_data as $report_data)
				{
					$this->ach_exceptions_flag = FALSE;
					
					if(!is_array($report_data))
						continue;

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

					$ach_id			= ltrim($report_data['merchant_trans_id'], '0');
								
					if (is_numeric($ach_id))
					{
						$debit_amount	    = trim($report_data['amount_debit']);
						$credit_amount	    = trim($report_data['amount_credit']);
						$reason_code	    = trim($report_data['fed_return_code']);		
						$recipient_name     = trim($report_data['indiv_name']);
						$reason_description = trim($report_data['return_description']);

						// There are two different fields for amount, which is stupid since
						// a transaction can only be for one amount and there's already a
						// transaction type field telling us whether or not it's a credit
						// or debit transaction that's being returned. [BR]
						if (strtolower($report_data['trans_code']) === 'debit')
						{
							$report_data['amount'] = $report_data['amount_debit'];
						}
						else
						{
							$report_data['amount'] = $report_data['amount_credit'];
						}							
	
						$process_type = 'normal';
						$this->log->Write("Process_ACH_Report: ach_id: $ach_id, process type: $process_type");

						if ($report_type == 'returns')
						{
								// Update status to returned in ach table
								try 
								{
									$this->db->beginTransaction();
									if($this->ach_utils->Update_ACH_Row('customer', $ach_id, 'returned', NULL, $reason_code, $response['ach_report_id']))
									{
										// Update failure status into transaction_register row(s) for this ach_id
										$this->Update_Transaction_Register_ACH_Failure($ach_id);
										$this->db->commit();
									}
									else 
									{
										$this->log->Write("Unable to locate ach_id $ach_id");
										$this->db->rollback();
										continue;
									}

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
								$application_id = $this->Get_Return_App_ID($ach_id, $process_type);
								if(! empty($application_id))
								{
									$reschedule_list[] = $application_id;

									// GF #10079:
									// AALM wants to hit a stat, but not for credits, only debits,
									// trans_code == Credit for credit
									// trans_code == Debit for debits
									if (strtolower($report_data['trans_code']) === 'debit')
									{
										if (!isset($debit_list))
											$debit_list = array();
	
										// We only want to send this stat once per application_id per return file
										// We can do that by making an array and only inserting unique keys into it 
										if (!in_array($application_id, $debit_list))
										{
											$debit_list[] = $application_id;
								
											// Hit ach_return stat
											$stat = new Stat();
											$stat->Setup_Stat($application_id);
											$stat->Hit_Stat('ach_return');
										}
									} 

								}
								else 
								{
									$this->log->Write("Unable to locate application id for ach id: $ach_id");
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
								if ( $this->Validate_COR_Account($report_data['addenda_info'], $normalized_account) )
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
								if ( $this->Validate_COR_ABA($report_data['addenda_info'], $normalized_ABA) )
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
								if ( $this->Validate_Name($report_data['addenda_info'], $normalized_name_last, $normalized_name_first) )
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
								if ( $this->Validate_Tran_Code($report_data['addenda_info'], $bank_account_type) )
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
				$this->log->Write("ACH: " . ucfirst($report_type) . " Report " . $response['ach_report_id'] .  " has been received without errors.", LOG_INFO);
				$count++;
			}
			else
			{
				// Mark report as failed
				$this->Update_ACH_Report_Status($response['ach_report_id'], 'failed');
				$this->log->Write("ACH: " . ucfirst($report_type) . " Report " . $response['ach_report_id'] .  " was received with errors.", LOG_ERR);
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
		

	
		return TRUE;
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
		
		/**
		 * Get the directory prefix
		 */
		if($report_type == 'returns')
		{
			$directory_url = eCash_Config::getInstance()->ACH_REPORT_RETURNS_URL;	
		}
		else
		{
			$directory_url = eCash_Config::getInstance()->ACH_REPORT_CORRECTIONS_URL;	
		}
		
		// make multiple request attempts
		for ($i = 0; $i < 5; $i++) 
		{ 
			try 
			{
				$transport = ACHTransport::CreateTransport($transport_type, $batch_server,  $batch_login, $batch_pass, $transport_port);
			
				if ($transport->hasMethod('setDate')) 
				{
					$transport->setDate($start_date);
				}
			
				if ($transport->hasMethod('setCompanyId')) 
				{
					$transport->setCompanyId($this->ach_report_company_id);
				}
				
				// Fetch the filename
				if($url = $this->Get_Report_Filename($transport, $directory_url, $report_type, $start_date))
				{
					$report_response = '';
					$report_success = $transport->retrieveReport($url, $report_type, $report_response);
				}
				else
				{
					// File doesn't exist.  If we're checking for a corrections file, we'll go ahead
					// and consider it good.
					if($report_type === 'corrections' && $i === 5)
					{
						$report_success = TRUE;
					}

					$report_success = FALSE;
				}

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
	 * @param String $hostname
	 * @param Int $port
	 * @param String $username
	 * @param String $password
	 * @param String $directory
	 * @return String
	 */
	public function Get_Report_Filename($transport, $directory_url, $report_type, $start_date)
	{
		$list = $transport->getDirectoryList($directory_url);
		
		// Prefix for Returns: PreviouslyPaidReturnsReport_20080811184645.csv
		// Prefix for Corrections: CorrectionsBySubmitDateReport_20080911170217.csv
		
		switch($report_type)
		{
			case 'returns':
				$prefix = 'PreviouslyPaidReturnsReport';
				break;

			case 'corrections':
				$prefix = 'CorrectionsBySubmitDateReport';
				break;
		}

		if(is_array($list))
		{		
			foreach($list as $filename => $attrib)
			{
				if(stristr($filename, $prefix . "_".date("Ymd",strtotime($start_date))))
				{
					return $directory_url . "/" . $filename;
				}
			}
		}
		else
		{
			if(stristr($list, $prefix . "_".date("Ymd",strtotime($start_date))))
			{
				return $directory_url . "/" . $list;
			}
		}
		
		return FALSE;
	}
	
	/**
	 * Retrieves a PDO result set of ACH reports based on
	 * the report type and a date range
	 *
	 * @param string $report_type - 'returns', 'corrections'
	 * @param string $start_date  - 'Y-m-d'
	 * @param string $end_date    - 'Y-m-d'
	 * @param return PDOStatement
	 */
	public function fetchReportByDate($report_type, $start_date, $end_date)
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
		}

		// We want to grab only the most recent file in the case that there is more than one
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					SELECT 	ach_report_id, 
							ach_report_request, 
							remote_response as received,
							date_request
					FROM	ach_report
					WHERE	company_id = {$this->server->company_id}
					AND		ach_report_request LIKE 'report={$type}%'
					AND		date_request BETWEEN '{$start_date}' AND '{$end_date}'
					AND		report_status != 'obsoleted'
					ORDER BY date_created DESC
			";
		return $this->db->Query($query);
	}
	
	/**
	 * Retrieves an ACH report using it's unique id
	 *
	 * @param integer $ach_report_id
	 * @return array $report
	 */
	public function fetchReportById($ach_report_id)
	{
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					SELECT 	ach_report_id, 
							ach_report_request, 
							remote_response as received
					FROM	ach_report
					WHERE	company_id = {$this->server->company_id}
					AND		ach_report_id = " . $this->db->quote($ach_report_id) . "
			";
		$result = $this->db->Query($query);
		
		if($result->rowCount() > 0)
		{
			$report = $result->fetch(PDO::FETCH_ASSOC);
			return $report;
		}
		else
		{
			$this->log->Write("Unable to retrieve report id $ach_report_id");
			return false;
		}
	}

	/**
	 * Used to determind the appropriate start date for a particular report type
	 *
	 * @param string $start_date  - 'Y-m-d'
	 * @param string $end_date    - 'Y-m-d'
	 * @param string $report_type - 'returns', 'corrections'
	 */
	public function getReportRunDates(&$start_date, &$end_date, $report_type)
	{
		$this->business_day = $end_date;

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

		if( !empty($row['last_run_date']) )
		{
			$last_run_date = $row['last_run_date'];
			$start_date = date("Y-m-d", strtotime("+1 day", strtotime($last_run_date)));
		}
		else
		{
			$start_date = date("Y-m-d", strtotime("now"));
		}

	}
	
	/**
	 * Parses a CSV file using the specified format and returns
	 * and associative array of data
	 *
	 * @param array $return_file
	 * @param array $report_format
	 * @return array
	 */
	public function Parse_Report_Batch ($return_file, $report_format)
	{
		// Split file into rows
		$return_data_ary = explode(self::$RS, $return_file);
		
		$parsed_data_ary = array();
		$i = 0;

		// Shift off the first row which is the header
		$definition = array_shift($return_data_ary);
		
		$this->log->Write("Field Defintiion: \n" . var_export($definition, true));

		foreach ($return_data_ary as $line)
		{
			if ( strlen(trim($line)) > 0 )
			{
				$this->log->Write("Parse_Report_Batch():$line\n");
				//  Split each row into individual columns
				
				$matches = array();
				$col_data_ary = preg_split("#,(?=(?:[^\"]*\”[^\"]*\”)*(?![^\"]*\”))#", $line);
				
				$parsed_data_ary[$i] = array();
				foreach ($col_data_ary as $key => $col_data)
				{
					// Apply column name map so we can return a friendly structure
					$parsed_data_ary[$i][$report_format[$key]] = str_replace('"', '', $col_data);
				}

				$i++;
			}
		}
		
		return $parsed_data_ary;
	}
}
?>
