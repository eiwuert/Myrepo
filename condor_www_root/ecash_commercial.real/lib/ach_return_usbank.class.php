<?php

/**
 * U.S. Bank Specific Returns Processing Implementation
 * 
 * U.S. Bank uses a fixed length file format similar to the NACHA ACH
 * batch file format.  I used the AdvantageACH returns class as a base,
 * but had to make a lot of changes to the parser.
 * 
 * @author: Brian Ronald <brian.ronald@sellingsource.com>
 * 
 * FEATURE: Added the feature to hit the 'ach_return' stat when there is a debit
 * failure from GForge #10079.
 * 
 */
class ACH_Return_USBank extends ACH_Return 
{
	/**
	 * Used to determine whether or not the returns file will contain
	 * both the returns and corretions in one file or to retrieve and process
	 * two separate files.
	 */
	protected $COMBINED_RETURNS = TRUE;
	
	private static $RS		  = "\n";
	
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

							$ach_id		    = ltrim($report_data['recipient_id'], '0');
							$reason_code    =  trim($report_data['reason_code']);
							$recipient_name = trim($report_data['recipient_name']);
							
							if (is_numeric($ach_id))
							{
								$process_type = 'normal';
								$this->log->Write("Process_ACH_Report: ach_id: $ach_id, Recipient Name: '{$report_data['recipient_name']}', Reason Code: {$report_data['reason_code']}");

								/**
								 * Since we're using a combined report we have to switch our processing 
								 * based on the return reason code rather than the report type, which
								 * will always be called 'returns'. [BR]
								 */
								switch (strtolower(substr($reason_code, 0, 1)))
								{
									case 'r':
										$return_type = 'return';
										continue;
										break;
									case 'c':
										$return_type = 'correction';
										var_dump($report_data);
										break;

									/**
									 * The return code is unrecognized, add to our ACH exceptions
									 */
									default:
										$this->log->Write("Unrecognized Return Code: '{$reason_code}'");
										$exception = array(
											'ach_id'  => $ach_id, 
											'exception' => "Unrecognized Return Code: '{$reason_code}'",
										);
										$this->ach_exceptions[] = $exception;
										$this->Insert_ACH_Exception($report_data);
										continue;
										break;
								}

								if ($return_type == 'return')
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
										$application_id = $this->Get_Return_App_ID($ach_id, $process_type);
										if ($application_id)
										{
											$reschedule_list[] = $application_id;
										}

										// GF #10079:
										// AALM wants to hit a stat, but not for credits, only debits,
										// transaction_code == 21 for credit
										// transaction_code == 26 for debits
										if ($report_data['transaction_code'] == '26')
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
								}
								elseif ($return_type == 'correction')
								{
									// Process corrections -- update related application data, if possible

									/**
									 * U.S. Bank specs suck.  They don't tell us how to handle change of addenda
									 * records where the corrected data field contains more than one piece of data.
									 * Typically we look for this data split using slashes. After looking at
									 * a return file provided by Geneva Roth I've determined that they just use
									 * a ton of whitespace. They may have a standard, but it's not documented
									 * and it's easy enough to trim out the excess white space on either side
									 * of the entry and delimit using whitespace. [BR]
									 */
									//$corrected_data_ary = explode("/", $report_data['corrected_info']);
									$corrected_data_ary = preg_split('/\s+/', rtrim(ltrim($report_data['addenda_info'])));

									$do_update = false;
									
									switch($reason_code)
									{
									case 'C01':
										// Incorrect account number
										if ( $this->Validate_COR_Account($corrected_data_ary[0], $normalized_account) )
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
										if ( $this->Validate_Tran_Code($corrected_data_ary[0], $bank_account_type) )
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
										if ( $this->Validate_COR_Account($corrected_data_ary[0], $normalized_account)	&&
										     $this->Validate_Tran_Code($corrected_data_ary[1], $bank_account_type)			)
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
										$application_id = $this->Get_Return_App_ID($ach_id, $process_type);
										if ($application_id)
										{
											$updated = $this->Update_Application_Info($application_id, $app_update_ary);
											if ($updated === FALSE)
											{
												$this->log->Write("Unable to update App ID: {$application_id}");
											}
											else
											{
												// A Dirty hack by RayLo to keep for entering duplicate corrections comments
												// We will keep an array of commented corrections so that we dont comment
												// this application again while going through the corrections
												if(!in_array($application_id,$commented_corrections))
												{
													$commented_corrections[] = $application_id;
													$this->ach_utils->Add_Comment($application_id, $reason_code.' - '.$comment_text);
													$commented_corrections[] = $application_id;
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
		foreach($reschedule_list as $application_id)
		{
			Set_Standby($application_id, 'reschedule');
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
	
	/**
	 * Parses a fixed field length file per U.S. Bank Spec
	 * 
	 * The file is similar to the NACHA Batch file format in it's structure.
	 * The file uses the following record format:
	 * 
	 * File Header
	 * Batch Header
	 * Entry Detail Record
	 * Corporate Entry Detail Record
	 * Addenda Record
	 * Notification of Change Addenda Record
	 * Batch Trailer
	 * File Trailer
	 * 
	 * For every Entry Detail Record there is an Addenda or Notification of Change
	 * Addenda Record.  In this method I'm using the Trace Number to combine the 
	 * Entry Detail and the Addenda Record into one return record.
	 * 
	 * The file format supports multiple batches and a lot of checksums to ensure
	 * the file is valid.  At some point I may do file validation but at this point
	 * I'm just looking for the Entry Detail and Addenda record types since it's
	 * all we need to return a transaction within eCash.  [BR]
	 *
	 * @param string $return_file
	 * @return array
	 */
	public function Parse_Report_Batch ($return_file)
	{
		// Split file into rows using defined record separator
		$return_data = explode(self::$RS, $return_file);

		$header  = array();
		$trailer = array();
		$records = array();
		
		foreach ($return_data as $line)
		{
			$row = array();
			if ( strlen(trim($line)) > 0 )
			{
				// Record Type code is always the first byte
				$code = substr($line, 0, 1);
				
				switch ($code)
				{
					case '1': // File Header
						$header['dest_routing']     = substr($line,  3, 10); // 04-13
						$header['origin_routing']   = substr($line, 13, 10); // 14-23
						$header['creation_date']    = substr($line, 23,  6); // 24-29
						$header['creation_time']    = substr($line, 29,  4); // 30-33
						$header['file_id_modifier'] = substr($line, 33,  1); // 34-34
						$header['destination_name'] = trim(substr($line, 40, 23)); // 41-63
						$header['origin_name']      = trim(substr($line, 63, 23)); // 64-86
						break;

					case '5': // Batch Header
						break;

					case '6': // Entry Detail Record or Corporate Entry Detail Record
						$transaction_code = substr($line,  1,  2); // 01-01
						$row['transaction_code'] = $transaction_code;
						$row['routing_number']   = substr($line,  3,  8); // 04-11
						$row['check_digit']      = substr($line, 11,  1); // 12-12
						$row['account_number']   = substr($line, 12, 17); // 13-29

						$amount                  = ltrim(substr($line, 29, 10), 0); // 30-39
						$amount = number_format(($amount / 100), 2);

						// If the transaction code is 21 or 31 it's a credit, 26 or 36 a debit
						if($transaction_code == 21 || $transaction_code == 31)
						{
							$row['credit_amount'] = $amount;
						}
						else if ($transaction_code == 26 || $transaction_code == 36)
						{
							$row['debit_amount'] = $amount;
						}

						$row['recipient_id']     = trim(substr($line, 39, 15)); // 40-54
						$row['recipient_name']   = trim(substr($line, 54, 22)); // 55-76
						$row['trace_number']     = substr($line, 79, 15); // 80-94
						break;

					case '7': // Addenda Record / Notification of Change Addenda Record
						$trace_number = substr($line, 79, 15);
						$type_code = substr($line,  1,  2);
						if($type_code === 98) // Notification of Change
						{
							$records[$trace_number]['corrected_info'] = trim(substr($line, 34,  64));
						}
						$records[$trace_number]['reason_code']        = substr($line,  3,  3);
						$records[$trace_number]['origin_dfi']         = substr($line, 27,  8);
						$records[$trace_number]['addenda_info']       = trim(substr($line, 35, 44));
						break;

					case '8': // Batch Trailer
						break;

					case '9': // File Trailer
						break;
				}

				$records[$row['trace_number']] = $row;
			}
		}
		
		return $records;
	}
}
?>
