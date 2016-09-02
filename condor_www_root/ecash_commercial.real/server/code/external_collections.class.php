<?php

require_once(SERVER_CODE_DIR . "external_collections_query.class.php");

class External_Collections extends External_Collections_Query
{
	private $ld;

	function __construct($server)
	{
		parent::__construct($server);
	}

	public function Get_Pending_Count($system_id)
	{
		/* Get count of pending external collection records where level0='pending' and level1='external_collections' and level2='*root'*/
		$data = new stdClass();
		$data->pending_count = $this->Fetch_Pending_Count();
		$data->adjustment_count = $this->Fetch_Adjustment_Count();
		$data->system_id = $system_id;
		ECash::getTransport()->Set_Data($data);
		return TRUE;
	}

	public function Show_Available_Batch_Downloads($current_system_id, $from_date, $to_date) //mantis:5598 - $to_date
	{
		$ext_coll_data = $this->Query_Available_Batch_Downloads($from_date, $to_date); //mantis:5598 - $to_date
		$data = new stdClass();
		$data->ext_coll_data = $ext_coll_data;
		ECash::getTransport()->Set_Data( $data );
	}

	public function Process_EC( $strtotime, $collection_company )
	{
		// Requiring the file here because the functions are only used here.
		require_once(dirname(__FILE__)."/../../sql/lib/comment.func.php");

		/*-Start a process log*/
		$process_id = Set_Process_Status($this->db, $this->server->company_id, 'process_ec_records', 'started', $strtotime);

		try
		{
			$this->db->beginTransaction();

			/*-Check if something has been sent already by looking in external_collection_batch for the date_completed = $strtotime:*/
			if( !$this->Check_Sent_EC($strtotime) )  // This ALWAYS return false at this point.
			{
				/*-Insert ext_coll_batch skeleton row to get ID*/
				$ec_batch_id = $this->Insert_Ext_Coll_Batch($collection_company);

				$filename = $this->server->company . date('Ymdhis',time()).".txt";

				/* Grab the records that are marked as level0='pending' and level1='external_collections' and level2='*root' for the batch file*/
				$ec_records = $this->Fetch_Ext_Coll_Records();
				$item_count = count($ec_records);

				$file_contents = '"LastName"	"FirstName"	"Address"	"City"	"State"	"Zip"	"CustomerPhone"	"SSN"	"CellPhone"	"Employer"	"EmployerPhone"	"EmailAddress"	"ReferenceName1"	"ReferenceRelationship1"	"ReferencePhone1"	"ReferenceName2"	"ReferenceRelationship2"	"ReferencePhone2"	"CustomerNumber"	"BankName"	"AccountType"	"ABA"	"AccountNumber"	"EmployerPhoneExt"	"DOB"	"LastAdvAmt"	"LastAdvance"	"AccountBalance"	"LastQC"	"PrincipalBalance"	"ChargesBalance"	"FeesBalance"	"LastTransDate"	"LastTransType"	"FailureReason"'."\n";

				if( ! empty($ec_records) && (count($ec_records) > 0) )
				{
					foreach($ec_records as $ext_coll_record)
					{
						/* Do something here to prepare each record for the flat file*/
						$ec_references = $this->Fetch_References_For_Ext_Coll($ext_coll_record['customernumber']);


						// DLH, 2005.12.20, I might be misinterpreting this but it seems to me that if the
						// personal reference records don't exist, they must still be represented by a placeholder.
						// There was some code that left the personal reference fields out completely if the personal
						// reference fields don't exist.  I changed this to include an empty string place holder because
						// otherwise I don't know how a process would be able to parse the file.

						$ext_coll_record['referencename1']         = $ext_coll_record['referencename2']         = '';
						$ext_coll_record['referencerelationship1'] = $ext_coll_record['referencerelationship2'] = '';
						$ext_coll_record['referencephone1']        = $ext_coll_record['referencephone2']        = '';

						$count = 1;
						foreach($ec_references as $ec_ref)
						{
							$ext_coll_record['referencename'.$count] = $ec_ref['referencename'];
							$ext_coll_record['referencerelationship'.$count] = $ec_ref['referencerelationship'];
							$ext_coll_record['referencephone'.$count] = $ec_ref['referencephone'];

							$count++;
						}

						$file_contents .= $ext_coll_record['lastname']."\t".
						$ext_coll_record['firstname'].' '.$ext_coll_record['middlename']."\t".
						$ext_coll_record['address1'].' '.$ext_coll_record['address2']."\t".
						$ext_coll_record['city']."\t".
						$ext_coll_record['state']."\t".
						$ext_coll_record['zip']."\t".
						$ext_coll_record['customerphone']."\t".
						$ext_coll_record['ssn']."\t".
						$ext_coll_record['cellphone']."\t".
						$ext_coll_record['employer']."\t".
						$ext_coll_record['employerphone']."\t".
						$ext_coll_record['emailaddress']."\t".
						$ext_coll_record['referencename1']."\t".
						$ext_coll_record['referencerelationship1']."\t".
						$ext_coll_record['referencephone1']."\t".
						$ext_coll_record['referencename2']."\t".
						$ext_coll_record['referencerelationship2']."\t".
						$ext_coll_record['referencephone2']."\t".
						$ext_coll_record['customernumber']."\t".
						$ext_coll_record['bankname']."\t".
						$ext_coll_record['accounttype']."\t".
						$ext_coll_record['aba']."\t".
						$ext_coll_record['accountnumber']."\t".
						$ext_coll_record['employerphoneext']."\t".
						$ext_coll_record['dob']."\t".
						$ext_coll_record['lastadvamount']."\t".
						$ext_coll_record['lastadvance']."\t".
						$ext_coll_record['accountbalance']."\t".
						$ext_coll_record['lastqc']."\t".
						$ext_coll_record['principalbalance']."\t".
						$ext_coll_record['chargebalance']."\t".
						$ext_coll_record['feebalance']."\t".
						$ext_coll_record['last_fail_date']."\t".
						$ext_coll_record['last_fail_type']."\t".
						$ext_coll_record['last_fail_reason']."\n";


						//-For each record that is pulled insert a record in external_collection using
						// the batch ID (Use the total bal$ec_batch_idance from loan_snapshot)*/
						$this->Insert_Ext_Coll_Record($ext_coll_record['customernumber'],
						$ec_batch_id,
						$ext_coll_record['accountbalance']);

						Add_Comment($this->server->company_id,
						$ext_coll_record['customernumber'], $this->server->agent_id,
							    'Moved to ' . strtoupper($collection_company) . ' 2nd Tier',
							    'standard', $this->server->system_id);

						/*-Update the app's statuses to ec_sent*/
						unset($_SESSION['LOCK_LAYER']); //prevent bug with locking if app was viewed previously
						ECash::getApplicationById($ext_coll_record['customernumber']);
	        			$engine = ECash::getEngine();	
	     			   $engine->executeEvent('SECOND_TIER_SENT', array());

					}

					/*-Update the batch record with filename and file contents*/
					$this->Update_Ext_Coll_Batch($ec_batch_id, $filename, $file_contents, $item_count);

					// DLH, 2005.12.21, broken up into 2 parts now - Process and Download; this is just the process part
					//ob_clean();  // I have no idea what this was doing in here.
					/*-Generate headers and echo data out to client for download*/
					// $data_length = strlen($file_contents);
					// header( "Accept-Ranges: bytes\n");
					// header( "Content-Length: $data_length\n");
					// header( "Content-Disposition: attachment; filename={$filename}\n");
					// header( "Content-Type: text/plain\n\n");
					// echo $file_contents;
				}
				else
				{
					/*-Update process_log */
					$this->db->rollBack();
					Set_Process_Status($this->db, $this->server->company_id, 'process_ec_records', 'failed', NULL, $process_id);
					return FALSE;
				}
			}
			else
			{
				/*-Update process_log */
				$this->db->rollBack();
				Set_Process_Status($this->db, $this->server->company_id, 'process_ec_records', 'failed', NULL, $process_id);
				return FALSE;
			}
		}
		catch(PDOException $e)
		{
			/*-Update process_log */
			$this->db->rollBack();
			Set_Process_Status($this->db, $this->server->company_id, 'process_ec_records', 'failed', NULL, $process_id);
			return FALSE;
		}

		/*-Update process_log */
		Set_Process_Status($this->db, $this->server->company_id, 'process_ec_records', 'completed', NULL, $process_id);
		$this->db->commit();
		return TRUE;
	}

	private function Create_Adjustment_File_Contents($adjustment_data)
	{
		$file_lines = array();
		foreach ($adjustment_data as $row)
		{
			$file_lines[] =
				'"'.str_replace('"', '""', $row->application_id).'",'.
				'"'.str_replace('"', '""', $row->customer_name).'",'.
				'"'.str_replace('"', '""', $row->ext_col_company_name).'",'.
				'"'.str_replace('"', '""', $row->adjustment_amount).'",'.
				'"'.str_replace('"', '""', $row->adjustment_date).'",'.
				'"'.str_replace('"', '""', $row->new_balance).'"';
		}

		return implode("\n", $file_lines);
	}

	public function Process_Adjustments()
	{
		// Requiring the file here because the functions are only used here.
		require_once(dirname(__FILE__)."/../../sql/lib/comment.func.php");
		$strtotime = date('Y-m-d');
		/*-Start a process log*/
		$process_id = Set_Process_Status($this->db, $this->server->company_id, 'process_adjustment_records', 'started', $strtotime);

		$filename = $this->server->company . '-adjustments-'.date('Ymdhis',time()).".txt";

		$ids_to_delete = array();
		$records = $this->Fetch_External_Collections_Adjustments($ids_to_delete);
		$file_contents = $this->Create_Adjustment_File_Contents($records);
		$item_count = count($records);

		try
		{
			$this->db->beginTransaction();

			/*-Insert ext_coll_batch skeleton row to get ID*/
			$ec_batch_id = $this->Insert_Ext_Coll_Batch('other', true);
			$this->Update_Ext_Coll_Batch($ec_batch_id, $filename, $file_contents, $item_count);

			$this->Remove_External_Collections_Adjustments($ids_to_delete);
			$this->db->commit();
		}
		catch(PDOException $e)
		{
			/*-Update process_log */
			$this->db->rollBack();
			Set_Process_Status($this->db, $this->server->company_id, 'process_adjustment_records', 'failed', NULL, $process_id);
			return FALSE;
		}

		/*-Update process_log */
		Set_Process_Status($this->db, $this->server->company_id, 'process_adjustment_records', 'completed', NULL, $process_id);
		return TRUE;
	}

	public function Create_EC_Delta_From ( $application_id , $adjustment_amount )
	{
		$balance_info = Fetch_Balance_Information($application_id);
		$old_balance = ($balance_info->total_balance - $adjustment_amount);

		//This is to temporarily get around a silly unique index on ext_collections
		$filename = uniqid($application_id);
		$query = "
			-- eCash3.0 ".__FILE__.":".__LINE__.":".__METHOD__."()
			INSERT INTO ext_corrections
			(date_created, company_id, application_id, old_balance,
				adjustment_amount, new_balance, file_name, file_contents)
			VALUES (NOW(), ?, ?, ?, ?, ?, ?, '')
		";
		$args = array($this->server->company_id, $application_id, $old_balance,
			$adjustment_amount, $balance_info->total_balance, $filename);

		$this->db->queryPrepared($query, $args);
		return TRUE;
	}

	public function Download_External_Collections_File( $ext_collections_batch_id )
	{
		$file_contents = $this->Query_External_Collections_File($ext_collections_batch_id, $filename);
		if ( $file_contents == '' )
		{
			$file_contents = "Sorry, no data found for batch_id: $ext_collections_batch_id";
			$filename = "file_not_available.txt";
		}
		$data_length = strlen($file_contents);

		// This redirection wasn't a good idea - didn't work.
		// $schema   = $_SERVER['SERVER_PORT'] == '443' ? 'https' : 'http';
		// $host     = strlen($_SERVER['HTTP_HOST']) > 0 ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
		// $port     = strlen($_SERVER["SERVER_PORT"]) > 0 ? '' : ":$_SERVER[SERVER_PORT]";
		// $location = $schema . '://' . $host . $port . '/ec_download.php';
		// header( "Location: $location");

		header( "Accept-Ranges: bytes\n");
		header( "Content-Length: $data_length\n");
		header( "Content-Disposition: attachment; filename={$filename}\n");
		header( "Content-Type: text/plain\n\n");
		echo $file_contents;
		exit;  // If we don't exit, we'll get the html stuff after the data.
	}

	public function Show_Incoming_Batches($from_date, $to_date)
	{
		get_log("collections")->Write(__FILE__.":".__LINE__.":".__METHOD__.var_export($from_date, true).var_export($to_date, true), LOG_NOTICE);

		$full_contents = $this->Fetch_Ready_Inc_Coll_Batches($from_date, $to_date);

		foreach ($full_contents as &$file_meta)
		{
			$file_meta->record_count = substr_count(trim($file_meta->file_contents),"\n") + 1;
			// removing file contents unless needed for aggregate calculations later
			unset($file_meta->file_contents);
		}

		$data = new stdClass();
		$data->inc_coll_data = $full_contents;
		ECash::getTransport()->Set_Data( $data );

	}

	public function Process_Incoming_EC_File( $batch_id )
	{
		get_log("collections")->Write(__FILE__.":".__LINE__.":".__METHOD__."({$batch_id})", LOG_NOTICE);

		// retrieve file contents from database
		list($batch_status, $file_name, $file_contents) = $this->Fetch_Inc_Coll_Batch($batch_id);

		get_log("collections")->Write(__FILE__.":".__LINE__.":".__METHOD__."({$batch_id}): Batch File: {$file_name}. Status: {$batch_status}", LOG_NOTICE);

		switch ($batch_status)
		{
			case "failed":
			case "success":
			case "partial":
				return;

			case "in-progress":
				throw new OutOfBoundsException ("Attempting to process illegal Incoming External Collections file of status: {$batch_status}");

			case "received-partial":
			case "received-full":
			default:
				$this->Update_Inc_Coll_Batch_Status($batch_id, 'in-progress');
				$batch_status = 'partial';
		}

		// dump contents into items table
		try 
		{
			$fp = fopen("php://temp", "w+");

			fputs($fp, $file_contents);

			rewind($fp);

			$trimmer = create_function('&$a,$b', '$a = trim($a);');

			while ($row = fgetcsv($fp))
			{
//				var_dump($row);
//				if ($i++ == 10) throw new Exception("artificial limit of {$i} hit"); // force a partial
				array_walk($row,$trimmer);
				$this->Insert_Inc_Coll_Record($row, $batch_id);
			}
						
		} 
		catch (Exception $e) 
		{
			get_log("collections")->Write(__FILE__.":".__LINE__.":".__METHOD__."({$batch_id}): Exception Caught: {$e->getMessage()}. Loading remaining records to database.", LOG_ERROR);

			$this->Update_Inc_Coll_Batch_Status($batch_id, 'partial');

			// write remainder of pointer to db as a new record
			// something needs to be done with the current "row", atm it dissapears into the aether
//			fputcsv($fp,$row);
			$file_contents = stream_get_contents($fp);
			fclose($fp);
			unset($fp);

			if (preg_match("/\.(\d+)$/",$file_name, $fnm))
			{
				$num = $fnm[1];
				$file_name = preg_replace("/\.\d+$/", "." . ($num + 1), $file_name);
			} 
			else 
			{
				$file_name = $file_name . ".1";
			}

			$query = "
				INSERT IGNORE INTO incoming_collections_batch
				(date_created, file_name, batch_status, file_contents)
				VALUES (NOW(), ?, 'received-partial', ?)
			";
			$this->db->queryPrepared($query, array($file_name, $file_contents));
		}
	}

	public function Process_Incoming_EC_Items( $batch_id, $skipchecks = NULL )
	{
		get_log("collections")->Write(__FILE__.":".__LINE__.":".__METHOD__."({$batch_id}, {$skipchecks})", LOG_NOTICE);

		set_time_limit(0);

		$this->Munge_CLID_Inc_Coll_Record($batch_id);

		$exceptions = array();

		if (!is_array($skipchecks) || !in_array("application",$skipchecks))
		{
			$exceptions = $this->Check_Valid_Inc_Coll_Record($batch_id);
			$this->Inc_Coll_Item_Set_Message($exceptions, "Application Not Found or invalid company_id or invalid SSN");

			// since we're handling this here.. we don't want to handle it in the item processor
			$skipchecks[] = "application";

		}
//var_dump($exceptions)		;
		// retrieve list of unworked items from table
		// loop thru list
		$successes = array();
		$fet = $this->Fetch_Inc_Coll_Records($batch_id, $exceptions);

		$this->ld = new Loan_Data($this->server);

		foreach($fet as $row)
		{ 
			try 
			{
				$ret = $this->Process_Incoming_EC_Item($row, $skipchecks);

				if ($ret !== true)
				{
					$exceptions[] = $row->incoming_collections_item_id;
				}
				else
				{
					$successes[] = $row->incoming_collections_item_id;
				}
			} 
			catch (Exception $e) 
			{
				get_log("collections")->Write(__FILE__.":".__LINE__.":".__METHOD__."({$batch_id}, {$skipchecks}): Exception Caught: {$e->getMessage()}. Adding {$row->incoming_collections_item_id} to exceptions list.", LOG_ERROR);
				// instead of this, check to see if the status was set by the process, and if not, THEN set an exception
//				var_dump($e);
				$exceptions[] = $row->incoming_collections_item_id;
			}
		}

		$queryskel = "
		-- eCash3.0 ".__FILE__.":".__LINE__.":".__METHOD__."()
			UPDATE incoming_collections_item SET status = '%tkn%' WHERE incoming_collections_item_id in (%lst%)
		";


		try 
		{
			$this->db->beginTransaction();

			get_log("collections")->Write(__FILE__.":".__LINE__.":".__METHOD__."({$batch_id}, {$skipchecks}): " . count($exceptions) . " Exceptions. " . count($successes) . " Completed.", LOG_NOTICE);

			if (count($exceptions))
			{
				$this->db->exec(str_replace(array('%tkn%', '%lst%'), array('flagged', implode(",", $exceptions)), $queryskel));
			}

			if (count($successes))
			{
				$this->db->exec(str_replace(array('%tkn%', '%lst%'), array('success', implode(",", $successes)), $queryskel));
			}

			// instead of just declaring, should check for validity
			$this->Update_Inc_Coll_Batch_Status($batch_id, 'success');

			$this->db->commit();

		} 
		catch (Exception $e) 
		{
			get_log("collections")->Write(__FILE__.":".__LINE__.":".__METHOD__."({$batch_id}, {$skipchecks}): Exception Caught: {$e->getMessage()}. Setting batch {$batch_id} to failed.", LOG_ERROR);

			$this->db->rollBack();

			$this->Update_Inc_Coll_Batch_Status($batch_id, 'failed');

		}
	}

	public function Process_Incoming_EC_Item($record, $skipchecks = NULL)
	{
		get_log("collections")->Write(__FILE__.":".__LINE__.":".__METHOD__."({$record}, {$skipchecks})", LOG_NOTICE);

//		var_dump($record);

		// check status
		if ( (!is_array($skipchecks) || !in_array("status",$skipchecks)) && $record->status != 'new')
		{
			get_log("collections")->Write(__FILE__.":".__LINE__.":".__METHOD__."({$record}, {$skipchecks}): Not a 'new' record.", LOG_NOTICE);
			$this->Inc_Coll_Item_Set_Message($record->incoming_collections_item_id, "Invalid item status {$record->status}");
			return $record;
		}

		if (!is_array($skipchecks) || !in_array("value",$skipchecks))
		{
			// retrieve maximum allowed transaction value
			$max_value = 2000; // get from business rule

			if ( abs((float) $record->correction_amount) >= $max_value )
			{
				get_log("collections")->Write(__FILE__.":".__LINE__.":".__METHOD__."({$record}, {$skipchecks}): Amount exceeds maximum value.", LOG_NOTICE);
				$this->Inc_Coll_Item_Set_Message($record->incoming_collections_item_id, "Transaction amount exceeds maximum value.");
				return $record;
			}
		}

		// check for valid app id
		if ( (!is_array($skipchecks) || !in_array("application",$skipchecks)) && count($this->Check_Valid_Inc_Coll_Record($record->application_id, TRUE)) )
		{
			get_log("collections")->Write(__FILE__.":".__LINE__.":".__METHOD__."({$record}, {$skipchecks}): {$record->application_id} Not a valid application", LOG_NOTICE);
			$this->Inc_Coll_Item_Set_Message($record->incoming_collections_item_id, "Not a valid Application.");
			return $record;
		}

		// check code
		try 
		{
			
			get_log("collections")->Write(__FILE__.":".__LINE__.":".__METHOD__."({$record}, {$skipchecks}): item record engaging action {$record->action}.", LOG_NOTICE);

			switch ($record->action)
			{
				case "recovery":
					return $this->Incoming_EC_Payment($record);

				case "recovery-reversal":
					return $this->Incoming_EC_Reverse($record);

				case "recovery-writeoff":
					$this->Incoming_EC_Payment($record);

				case "writeoff":
					return $this->Incoming_EC_Writeoff($record);

				case "bankruptcy-verified":
					return $this->Incoming_EC_Bankruptcy($record);

				case "other":
				default:
					$this->Inc_Coll_Item_Set_Message($record->incoming_collections_item_id, "Trust Code / Status Code Mismatch or Exception as defined by code.");
					return $record;
			}
		} 
		catch ( Exception $e ) 
		{
//				var_dump($e); exit;
			get_log("collections")->Write(__FILE__.":".__LINE__.":".__METHOD__."({$batch_id}, {$skipchecks}): Exception Caught: {$e->getMessage()}.", LOG_ERROR);
			$this->Inc_Coll_Item_Set_Message($record->incoming_collections_item_id, "Exception Caught: {$e->getMessage()}");
			return $record;
		}
	}

	private function Incoming_EC_Payment( stdClass $record )
	{
		get_log("collections")->Write(__FILE__.":".__LINE__.":".__METHOD__."({$record})", LOG_NOTICE);
		/*
		 * register_single_Event()
		 * request->amount
		 * request->action
		 * request->payment_description
		 */
		// loan_data->Save_Recovery(request,app_id);

		$request = new stdClass;
		$request->amount = $record->correction_amount;
		$request->action = 'recovery';
		$request->payment_description = "recovery payment";

		Register_Single_Event($record->application_id, $request, $this->db);

		return true;

	}

	private function Incoming_EC_Reverse( stdClass $record )
	{
		get_log("collections")->Write(__FILE__.":".__LINE__.":".__METHOD__."({$record})", LOG_NOTICE);
		/*
		 * req'd: posted_fees
		 * 			posted_principal
		 * 			posted_total
		 * 			action = ext_recovery_reversal
		 * 			schedule_effect = shorten
		 * 			adjustment_target = fees
		 * 			action_type = save
		 */
		// loan_data->Set_RecoveryReversal(request,app_id);

		$request = new stdClass;
		$request->amount = $record->correction_amount;
		$request->action = 'ext_recovery_reversal';
		$request->payment_description = "recovery reversal";
		$this->ld->Save_RecoveryReversal($request,$record->application_id);

		return true;

	}

	private function Incoming_EC_Writeoff( stdClass $record )
	{
		get_log("collections")->Write(__FILE__.":".__LINE__.":".__METHOD__."({$record})", LOG_NOTICE);
		/*
		 * req'd: posted_fees
		 * 			posted_principal
		 * 			posted_total
		 * 			action = writeoff
		 * 			schedule_effect = shorten
		 * 			adjustment_target = fees
		 * 			action_type = save
		 */
		// loan_data->Set_Writeoff(request);

		$request = new stdClass;
		$request->amount = $record->correction_amount;
		$request->action = 'writeoff';
		$request->payment_description = "writeoff";
		$request->schedule_effect = 'shorten';

		Register_Single_Event($record->application_id, $request, $this->db);
		$schedule = Fetch_Schedule($record->application_id);
		$data = Get_Transactional_Data($record->application_id, $this->db);
		
		// We've removed Repaint_Schedule, this code isn't even used, and even if it were used, 
		// the account shouldn't need any schedule adjustments, they're in 2nd Tier! [BR]
		//$schedule = Repaint_Schedule($schedule, $data->info, $data->rules, $request->schedule_effect, $this->db);
		//Update_Schedule($record->application_id, $schedule, $this->db);

		// If the schedule is "complete", i.e. they have no more balance,
		// set them to inactive
		Check_Inactive($record->application_id);

		return true;

	}

	private function Incoming_EC_Bankruptcy( stdClass $record )
	{
		get_log("collections")->Write(__FILE__.":".__LINE__.":".__METHOD__."({$record})", LOG_NOTICE);

		// change status only
		Update_Status($this->server, $record->application_id, array('verified', 'bankruptcy', 'collections', 'customer', '*root' ));

		return true;

	}

}

?>
