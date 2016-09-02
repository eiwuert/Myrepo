<?PHP
class Billing_Master
{
	/**
	* @return boolean
	* @param $sql
	* @param $database
	* @param $account_master
	* @param $audit_trail
	* @desc Construct Billing Master class
	*/
	function Billing_Master($sql,$database,$account_master,$audit_trail)
	{
		$this->sql = $sql;
		$this->database = $database;
		$this->account_master = $account_master;
		$this->audit_trail = $audit_trail;
		
		return TRUE;
	}
	
	/**
	* @return boolean
	* @param $date
	* @desc Verify the integrity of a date
	*/
	function Is_Date($date)
	{
		$date = explode("-", $date);
		list($year, $month, $day) = $date;
		
		// Apply a blank year cutoff
		if($date[0] == '')
		{
			return FALSE;	
		}
		else
		{
			return  checkdate($month,$day,$year);	
		}
	}
	
	/**
	* @return boolean
	* @param $date
	* @desc Verify the integrity of a date
	*/
	function Is_Weekend($date)
	{
		$date = str_replace('-','',$date);
		$day = date('w', strtotime($date));
		
          // If it's a Saturday or Sunday
		if($day == 6 || $day == 0)
		{
			return TRUE;
		}
		else
		{
			return FALSE;	
		}	
	}
	
	/**
	* @return boolean
	* @param $date
	* @desc Verify if the date given is a holiday
	*/
	function Is_Holiday($date)
	{

		$query = "
		SELECT
			date
		FROM
			holidays
		WHERE
			date = '$date'";

		$result = $this->sql->Query($this->database, $query, Debug_1::Trace_Code(__FILE__, __LINE__));

		return ($this->sql->Row_Count($result) > 0 ? true : false);
	}
	
	/**
	* @return boolean
	* @param $date
	* @desc Check the ach date and verifys if it is a valid date.
	*/
	function Is_Ach_Date($date)
	{
		$increment = 1;
		
		list($year, $month, $day) = explode('-', $date);
		
		$day -=2;
		
		$date = mktime (0,0,0,$month,$day,$year);
		$date = date('Y-m-d',$date = strtotime("+$increment day", $date));
		
		if($this->Is_Weekend($date))
		{
			return TRUE;	
		} 
		else
		{
			return $this->Is_Holiday($date);	
		}
	}
	
	/**
	* @return boolean
	* @param $date
	* @desc Add a day and verify continue until valid date is hit
	*/
	function Add_Day($date, $increment)
	{
		
		list($year, $month, $day) = explode("-", $date);
		
		$date = mktime (0,0,0,$month,$day,$year);
		$date = date('Y-m-d',$date = strtotime("+$increment day", $date));
		
		$date = $this->Validate_Date($date);
		
		return $date;
	}
	
	/**
	* @return $date
	* @param $date
	* @param $increment
	* @desc Takes the given day and subtracts the given increment from it in days.
	*/
	function Sub_Day($date, $increment)
	{
		return $this->Date_Shift ($date, (-1 * $increment));
	}
	
	/**
	* @return $date
	* @param $date
	* @param $increment
	* @desc Takes the given day and adds or subtracts the given increment from it in days.
	*/
	function Date_Shift ($date, $increment)
	{
		$inc_string = ($increment >= 0 ? "+" : "-").abs($increment);
		return date ('Y-m-d', strtotime ($inc_string." day", strtotime ($date)));
	}
	
	/**
	* @return boolean
	* @param $date
	* @param $increment
	* @desc Add $incremented amount of months to the given date
	*/
	function Add_Month($date, $increment)
	{
		$date = explode("-", $date);
		list($year, $month, $day) = $date;
			
		for($i=0; $i<$increment; $i++)
		{
			if ($month == 12)
			{
				$month = 01;  
				$year++;
			}
			else
			{
				$month++;
			}
		}
			
		$date = date('Y-m-d', mktime (0,0,0,$month,$day,$year));
		$date = $this->Validate_Date($date);
			
		return  $date;
	}
	
	/**
	* @return date
	* @param $date
	* @desc Verify the date given is valid, (no weekend, holidays, day prior weekends)
	*/
	function Validate_Date($date)
	{
		$date_validation = $this->Is_Date($date);  // Is the date a valid date

			if ($date_validation == TRUE)
			{
				$weekend_validation = $this->Is_Weekend($date); // Is the date on a  weekend
				while ($weekend_validation == TRUE)
				{
					$date = $this->Add_Day($date, '1');
					$weekend_validation = $this->Is_Weekend($date);
				}
				
				$holiday_validation = $this->Is_Holiday($date); // Is the date on a holiday
				
				if ($holiday_validation == TRUE)
				{
					$date = $this->Add_Day($date, '1');	
				}
			}
			else
			{
				$date = $this->Add_Day($date, '1');
				$date = $this->Validate_Date($date);
			}
		
		$ach_date = $this->Is_Ach_Date($date); // Is the prior (ACH day) on a weekend
		if ($ach_date == TRUE)
		{
			$date = $this->Add_Day($date, '1');	
		}
		return $date;
	}
	
	/**
	* @return date
	* @param $date
	* @desc Returns the day of payment date in numerical value
	*/
	function Get_Payment_Day($date)
	{
		$date = str_replace('-','',$date);
		$date = date('d', strtotime($date));
	
		return $date;
	}

   	/**
	* @return bool
	* @param $cc_number
	* @desc Add the standard monthly fee to a customers account.
	*/
	function Add_Monthly_Fee($cc_number)
	{
		$trans_obj = new stdClass();
		$trans_obj->cc_number = $cc_number;
		$trans_obj->ach_amount = 10;
		$trans_obj->cc_amount = 0;
		$trans_obj->transaction_total = 10;
		$trans_obj->transaction_source = 'EGC';
		$trans_obj->transaction_type = 'FEE';
		
		$this->account_master->Create_Transaction($trans_obj);		
		return TRUE;
	}
	
	/**
	* @return date
	* @param $stamp
	* @desc Converts a time stamp into a readable Y-m-d date.
	*/
	function Stamp_Convert($stamp)
	{
		return $date = date('Y-m-d',mktime($stamp));
	}
	
	/**
	* @return prev_balance
	* @param $cc_number
	* @desc Get an unpaid balance from prev billing cycle
	*/
	function Get_Prev_Balance($cc_number)
	{
		$query = "
		SELECT
			SUM(billing_balance) AS billing_balance
		FROM 
			`billing`
		WHERE
			cc_number = '$cc_number'";

		$result = $this->sql->Query($this->database, $query, Debug_1::Trace_Code(__FILE__, __LINE__));
		
		$prev_balance_object = $this->sql->Fetch_Object_Row($result);
		$prev_balance = $prev_balance_object->billing_balance;
		
		if ($prev_balance == '')
		{
			$prev_balance = 0;	
		}
		
		return $prev_balance;
	}
	
	
	/**
	* @return payble_balance
	* @param $cc_number
	* @desc Get the correct balance due for monthly payment
	*/
	function Get_Billing_Balance($record_obj)
	{
		$balance = $this->account_master->Get_Billing_Balance($record_obj->cc_number);

		// Add the monthly fee as a transaction
		$this->Add_Monthly_Fee($record_obj->cc_number);
		
		// Total Fees
		$fee_balance = $this->account_master->Get_Billing_Fee($record_obj->cc_number);

		// Prev Balance
		$prev_balance = $this->Get_Prev_Balance($record_obj->cc_number);

		// charge the whole thing if it's less than $10 or if they've been cancelled
		// and we want to finish off the account
		if ($balance <= 10 || $record_obj->account_status == 'CANCELLED')
		{
			$payable_balance = $balance;	
		}
		elseif (  $balance > 10 )
		{
			if( ($balance * 0.05) <= 10 )
				$payable_balance = 10;
			else 
				$payable_balance = round($balance*.05,2);
		}
		/*else
		{
			$payable_balance = round($balance*.15,2);	
		}*/
		
		// Add full fee amounts to balance due, not percentage.
		$payable_balance = $payable_balance+$fee_balance;
		$payable_balance = $payable_balance+$prev_balance;
		
		return $payable_balance;
	}
	
	/**
	* @return bool
	* @desc Based on the current billing information in the database, generate a bill based on the cycle dates in the
	* @desc database.
	*/
	function Process_Monthly_Billing($is_hammer = FALSE, $start_date = NULL, $end_date = NULL)
	{
		if ($start_date != $end_date)
		{
			// Determine if the dates are in the right order
			if (strtotime ($start_date) > strtotime ($end_date))
			{
				// The order is wrong
				$temp = $start_date;
				$start_date = $end_date;
				$end_date = $temp;
			}
		}
				
		if (!$is_hammer)
		{
			require_once ("prpc/client.php");
			$mail = new Prpc_Client ("prpc://smtp.2.soapdataserver.com/smtp.1.php");
		}
		else
		{
			Debug_1::Raw_Dump ("Started Process_Monthly_Billing");
			Debug_1::Raw_Dump ("Start Date: ".$start_date);
			Debug_1::Raw_Dump ("End Date: ".$end_date);
		}
		
		// run billing
		$this->Billing_Process_Loop ($is_hammer, $start_date, $end_date);
		
		
		$cycle_start_date = date('Y-m-d', strtotime ($start_date));
		$cycle_end_date = date('Y-m-d', strtotime ($end_date));
		$query = "
		SELECT
			billing_data.cc_number,
			billing_data.billing_cycle_date,
			billing_data.payment_day,
			account.cc_number,
			account.modified_date,
			account.credit_limit,
			account.available_balance,
			account.account_status,
			account.ach_routing_number,
			account.ach_account_number,
			account.bank_name,
			account.bank_phone,
			account.sign_up,
			account.activation,
			account.stat_hit,
			customer.cc_number,
			customer.first_name,
			customer.last_name,
			customer.maiden_name,
			customer.email,
			customer.address_1,
			customer.address_2,
			customer.city,
			customer.state,
			customer.zip,
			customer.home_phone,
			customer.work_phone,
			customer.ssn,
			customer.bankruptcy,
			customer.discharged,
			customer.date_of_birth,
			customer.income,
			customer.promo_id,
			customer.promo_sub_code,
			customer.extract,
			billing.id,
			billing.cc_number,
			billing.payment_date,
			billing.ach_date,
			billing.billing_amount,
			billing.billing_balance,
			billing.type
		FROM
			`billing_data`,
			`account`,
			`customer`,
			`billing` 
		WHERE
			billing.ach_date ".($start_date == $end_date ? "= '".$cycle_start_date."'" : "between '".$cycle_start_date."' and '".$cycle_end_date."'")."
		AND
			account.cc_number = billing_data.cc_number
		AND
			account.cc_number = customer.cc_number
		AND
			account.cc_number = billing.cc_number
		AND
			account.account_status IN('ACTIVE','INACTIVE','CANCELLED')";
			//removed COLLECTIONS
		
		$result = $this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));

		if ($is_hammer)
		{
			Debug_1::Raw_Dump ($query);
			Debug_1::Raw_Dump ($result);
		}
		
		while (FALSE !== ($row_data = $this->sql->Fetch_Object_Row ($result)))
		{
			$key = $row_data->cc_number;
			$account_obj->{$key}=$row_data;
			$loop = TRUE;
		}
		
		if($is_hammer)
		{
			Debug_1::Raw_Dump($account_obj);
		}
		
		$billing_obj = new stdClass();
		
		if($loop)
		{
            			foreach($account_obj AS $record)
			{
				$billing_obj->{"id_".$record->cc_number}->cc_number=$record->cc_number;
				$billing_obj->{"id_".$record->cc_number}->amount = $record->billing_balance;
				$billing_obj->{"id_".$record->cc_number}->routing = $record->ach_routing_number;
				$billing_obj->{"id_".$record->cc_number}->account = $record->ach_account_number;
				$billing_obj->{"id_".$record->cc_number}->first_name = $record->first_name;
				$billing_obj->{"id_".$record->cc_number}->last_name = $record->last_name;
				$billing_obj->{"id_".$record->cc_number}->transaction_id = $record->id;
				$billing_obj->{"id_".$record->cc_number}->payment_date = $record->payment_date;
				$billing_obj->{"id_".$record->cc_number}->promo_id = $record->promo_id;
				$billing_obj->{"id_".$record->cc_number}->promo_sub_code = $record->promo_sub_code;
			     
				$fields = "cc_number,amount,ach_date,modified_date,origination_date";
				$values = "'".$record->cc_number."','".$record->billing_balance."',NOW(),NOW()";
				$query = "INSERT INTO `billing_batch` (".$fields.") VALUES(".$values.")";
				$result = $this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				
				$loop_2 = TRUE;
			}
			
			if ($loop_2)
			{
			    $ach_file = $this->Build_Ach($billing_obj);
				$xls_file = $this->Build_Xls($billing_obj);
				
				$batch_fields = "modified_date,origination_date,file,employee_id,batch_type";
				$ach_values = "NOW(),NOW(),'".base64_encode($ach_file)."',0,'BILLING_ACH'";
				$xls_values = "NOW(),NOW(),'".base64_encode($xls_file)."',0,'BILLING_XLS'";
                   
				// Send the user an email
				if (!$is_hammer)
				{
					// Build the header
					$header = new stdClass ();
					$header->port = 25;
					$header->url = "expressgoldcard.com";
					$header->subject = "EGC Billing Files  - ".date("Y-m-d h:i:s")."";
					$header->sender_name = "Express Gold Card";
					$header->sender_address = "no-reply@expressgoldcard.com";
				
					// Build the recipient
					
					$recipient2 = new stdClass ();
					$recipient2->type = "to";
					$recipient2->name = 'EGC';
					$recipient2->address = 'approval-department@expressgoldcard.com';
					
					
					$recipient1 = new stdClass ();
					$recipient1->type = "to";
					$recipient1->name = 'Beaker';
					$recipient1->address = 'nickw@sellingsource.com';
					
					// Build the message
					$message = new stdClass ();
					$message->text = "Attached File";
				
					// Build the attachment
					$attachment1 = new StdClass ();
					$attachment1->name = "ACH File";
					$attachment1->content = base64_encode ($ach_file);
					$attachment1->content_type = "text/plain";
					$attachment1->content_length = strlen ($ach_file);
					$attachment1->encoded = "TRUE";
					
					$attachment2 = new StdClass ();
					$attachment2->name = "Report File";
					$attachment2->content = base64_encode ($xls_file);
					$attachment2->content_type = "application/vnd.ms-excel";
					$attachment2->content_length = strlen ($xls_file);
					$attachment2->encoded = "TRUE";
				
					$mailing_id = $mail->CreateMailing ("EGC_BILLING_FILES", $header, NULL, NULL);

					if(!$mailing_id)
						{
							echo "No Mailing Id Created";
						}
				
					$package_id = $mail->AddPackage ($mailing_id, array ($recipient1,$recipient2), $message, array ($attachment1,$attachment2));
					$sender = $mail->SendMail($mailing_id);
				}
				
				$query = "INSERT INTO `batch_file` (".$batch_fields.") VALUES(".$ach_values."),(".$xls_values.")";
				echo $query;
				$result = $this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				
				if (!$is_hammer)
				{
					$this->Count_Stats($billing_obj);
				}
				else
				{
					Debug_1::Raw_Dump ("ACH FILE: ".$ach_file);
					Debug_1::Raw_Dump ("XLS FILE: ".$xls_file);
				}
			}
		}
		if ($is_hammer)
		{
			Debug_1::Raw_Dump ("Finished Process_Monthly_Billing");
		}
		
		return TRUE;
	}
	
	function Count_Stats($billing_obj)
	{
		foreach($billing_obj AS $record)
		{
			$promo_status = new stdclass();
			$promo_status->valid = "valid";
			
			$column = "amount_billed";
			$base = "egc_stat";

			$stat_data = Set_Stat_1::Setup_Stats('1833', '0', '1835', $record->promo_id, $record->promo_sub_code, $this->sql, $base, $promo_status->valid, $batch_id = NULL);
			Set_Stat_1::Set_Stat ($stat_data->block_id, $stat_data->tablename, $this->sql, $base, $column, $record->amount);   
		}		
	}
	
	
	/**
	* @return bool
	* @desc Determine the billing amount for the next cycle based on todays being the customers cycle date.
	* @desc This runs when a customers cycle date is yesterday so we can generate a new billing amount for them.
	*/
	function Billing_Process_Loop ($is_hammer = FALSE, $start_date = NULL, $end_date = NULL)
	{
		if ($is_hammer)
		{
			Debug_1::Raw_Dump ("Starting Billing_Process_Loop");
			Debug_1::Raw_Dump ($start_date);
			Debug_1::Raw_Dump ($end_date);
		}
		
		if ($start_date != $end_date)
		{
			$num_days = (int)((strtotime ($end_date) - strtotime ($start_date))/86400);
			
			if ($is_hammer)
			{
				Debug_1::Raw_Dump ($num_days);
			}
			
			// The dates are different, process once for each day
			for ($i = 0; $i < $num_days; $i++)
			{
				$this->Generate_Monthly_Billing ($is_hammer, $this->Date_Shift ($start_date, $i));
			}
		}
		else
		{
			// The dates are the same, process once
			$this->Generate_Monthly_Billing ($is_hammer, $start_date);
		}
		
		if ($is_hammer)
		{
			Debug_1::Raw_Dump ("Finished Billing_Process_Loop");
		}
		
	}
	
	/**
	* @return bool
	* @desc Determine the billing amount for the next cycle based on todays being the customers cycle date.
	* @desc This runs when a customers cycle date is yesterday so we can generate a new billing amount for them.
	*/
	function Generate_Monthly_Billing ($is_hammer=TRUE, $process_date=NULL)
	{
		if (!is_null ($process_date))
		{
			$cycle_date = $this->Sub_Day(date('Y-m-d', strtotime ($process_date)),1);
		}
		else
		{
			$cycle_date = $this->Sub_Day(date('Y-m-d'),1);
		}
		
		$query = "SELECT billing_data.*,account.*,customer.* FROM `billing_data`,`account`,`customer` 
		WHERE billing_data.billing_cycle_date = '".$cycle_date."' AND account.cc_number = billing_data.cc_number AND 
		account.cc_number = customer.cc_number AND account.account_status IN('ACTIVE','INACTIVE')";
				
		// COLLLECTIONS state removed
		$result = $this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
		
       		 // Create an object of customers who's cycle date is up...
		while (FALSE !== ($row_data = $this->sql->Fetch_Object_Row ($result)))
		{ 
            			$key = $row_data->cc_number;
			$account_obj->{$key}=$row_data;
			$loop = TRUE;
		}
		
		$billing_obj = new stdClass();
		if($loop)
		{	
		     // Take those customers and generate them next billing cycle numbers
			foreach($account_obj AS $record)
			{
			 	$next_payment_date = $this->Add_Day($record->billing_cycle_date,'11');			
				
				$next_billing_cycle = $this->Sub_Day($this->Add_Month($next_payment_date,'1'),'11');
				
				$next_ach_date = $this->Sub_Day($next_payment_date,'1');
				
				$billing_balance = $this->Get_Billing_Balance($record);
				
				$query = "INSERT INTO `billing` (cc_number,payment_date,ach_date,billing_amount,billing_balance) VALUES('".$record->cc_number."','".$next_payment_date."','".$next_ach_date."','".$billing_balance."','".$billing_balance."')";
				
				$result = $this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				$insert_id = $this->sql->Insert_Id();
				
				$query = "UPDATE `billing_data` SET billing_cycle_date = '".$next_billing_cycle."' WHERE cc_number = '".$record->cc_number."'";
				$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				
				$billing_obj->{"id_".$record->cc_number}->cc_number=$record->cc_number;
				$billing_obj->{"id_".$record->cc_number}->amount = $billing_balance;
				$billing_obj->{"id_".$record->cc_number}->routing = $record->ach_routing_number;
				$billing_obj->{"id_".$record->cc_number}->account = $record->ach_account_number;
				$billing_obj->{"id_".$record->cc_number}->first_name = $record->first_name;
				$billing_obj->{"id_".$record->cc_number}->last_name = $record->last_name;
				$billing_obj->{"id_".$record->cc_number}->email = $record->email;
				$billing_obj->{"id_".$record->cc_number}->transaction_id = $insert_id;
				$billing_obj->{"id_".$record->cc_number}->payment_date = $next_payment_date;
			}
		}
		unset($loop);
		
		$select = "account.cc_number,DATE_FORMAT(account.sign_up, '%Y-%m-%d') AS sign_up";
		$from = "`transaction_0`, account LEFT JOIN billing_data ON account.cc_number=billing_data.cc_number";
		$where = "account.cc_number = transaction_0.cc_number AND transaction_0.transaction_type = 'ENROLLMENT' ".
		$where .= "AND  transaction_0.transaction_status ='APPROVED' AND account.account_status IN('ACTIVE','INACTIVE') AND billing_data.cc_number IS NULL";
		
		$query = "SELECT ".$select." FROM ".$from." WHERE ".$where."";
		$result = $this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
		
		// Generate an object of customers that are currently not in the billing tables that need to be
		while (FALSE !== ($row_data = $this->sql->Fetch_Object_Row ($result)))
		{
			$key = $row_data->cc_number;
			$account_obj->{"id_".$key}=$row_data;
			$loop = TRUE;
		}
		
		if($loop)
		{
		     // Add these new customers into the billing cycle
			foreach($account_obj AS $record)
			{
				$payment_day = $this->Get_Payment_Day($record->sign_up);
				$record->sign_up = "".date('Y')."-".date('m')."-".$payment_day."";
				$next_billing_cycle = $this->Add_Month($record->sign_up,'1');
				
				$query = "INSERT INTO `billing_data` (cc_number,billing_cycle_date,payment_day) VALUES('".$record->cc_number."','".$next_billing_cycle."','".$payment_day."')";
				
				$this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
			}
		}
		
		if (!$is_hammer)
		{
			$this->Send_Bill($billing_obj);
		}
		
		// This may need to be moved into the if above
		$this->Set_Comment ($billing_obj, $process_date);
		
		return TRUE;
	}
	
	
	/**
	* @return bool
	* @param $billing_obj
	* @desc Inserts the billing comment into the database comments table
	*/
	function Set_Comment($billing_obj, $effective_date = NULL)
	{
		foreach($billing_obj AS $record)
		{
			$record->employee = 0;
			$record->follow_up = "0000-00-00";
			$record->comment = "[MONTHLY BILL][".$record->transaction_id."] generated and sent on ".date('m-d-Y h:i:s', strtotime ($effective_date))."";
			$this->audit_trail->Insert_Comment($record);		
		}
		
		return TRUE;
	}
	
	
	/**
	* @return bool
	* @param $billing_obj
	* @desc Send the bill to the customer and the ach files to clients via SOAP
	*/
	function Send_Bill($billing_obj)
	{
		//require ("/virtualhosts/lib/soap_smtp_client.3.php"); // for first setup run this is on once done, then turn off.
		$mail = new Prpc_Client ("prpc://smtp.2.soapdataserver.com/smtp.1.php");
		
		// Build the header
		$header = new stdClass ();
		$header->port = 25;
		$header->url = "expressgoldcard.com";
		$header->subject = "Express Gold Card Statement";
		$header->sender_name = "Express Gold Card";
		$header->sender_address = "no-reply@expressgoldcard.com";
		
		$mailing_id = $mail->CreateMailing ("EGC_BILLING", $header, NULL, NULL);
		
		if(!$mailing_id)
		{
			echo "No Mailing Id Created";
		}
					
		foreach($billing_obj AS $record)
		{
			$recipient1 = new stdClass ();
			$recipient1->type = "to";
			$recipient1->name = ucwords($record->first_name);
			$recipient1->address = $record->email;
               //$recipient1->address = 'nickw@sellingsource.com';
			
			// Build the message
			$message = new stdClass ();
			$message->text = "
Dear " . ucwords($record->first_name . " " . $record->last_name) . "

We show that your current balance on your Express Gold Card is $".$this->account_master->Get_Credit_Balance($record->cc_number,1).".

Your amount due is the total of your fees and minimum monthly payment, amount due is $".number_format($record->amount, 2, '.',',').".

Your payment due date is ".date('m-d-Y',strtotime($record->payment_date)).".

The amount due will be electronically debited from your account on the payment due date.  If you wish to make other payment arrangements 
(such as paying by Money Order, Cashier's Check or to pay more than the Amount Due), you must contact us at least 3 days prior to your Payment Due
Date.

You can view the details of your bill on our Customer Service site via this link http://www.expressgoldcard.com/customerservice/

If you have any questions concerning this bill, please email us at customerservice@expressgoldcard.com or call us at 1-800-290-7483
(between 6:00am - 2:30pm PST).

Thank you,
ExpressGoldCard.com";

		$package_id = $mail->AddPackage ($mailing_id, array($recipient1), $message, array ());
			
		}
		
		$sender = $mail->SendMail($mailing_id);
		
		return TRUE;	
	}
	
	/**
	* @return xls_file
	* @param $billing_obj
	* @desc Build and the xls file of billed customers
	*/
	function Build_Xls($billing_obj)
	{
		$total_amount = 0;
		
		foreach ($billing_obj AS $record)
		{
			$total_amount += $record->amount;
			$xls_data .= "
			<tr>
				<td>".strtoupper($record->first_name)." ".strtoupper($record->last_name)."</td>
				<td>".chunk_split($record->cc_number, 4, ' ')."</td>
				<td><font color=white>\"</font>".$record->routing."<font color=white>\"</font></td>
				<td><font color=white>\"</font>".$record->account."<font color=white>\"</font></td>
				<td>$".number_format($record->amount, 2, '.', ',')."</td>
				<td>".date('m-d-Y', strtotime($record->payment_date))."</td>
			</tr>";
		}
		
		$today = date("m-d-Y");
		$xls_file = "
		<html>
		<body>
			
		<table border=2>
		<tr>
		<td colspan=5 align=center><b>BILLING REPORT</b><br><i>Date: ".$today."</i></td>
		</tr>
		</table>
		
		<table>
		<tr>
		<td colspan=5 align=center>&nbsp;</td>
		</tr>
		</table>
			
		<table>
		<tr>
		<td align=center><b>CUSTOMER NAME</b></td>
		<td align=center><b>CC NUMBER</b></td>
		<td align=center><b>ROUTING NUMBER</b></td>
		<td align=center><b>ACCOUNT NUMBER</b></td>
		<td align=center><b>AMOUNT DUE</b></td>
		<td align=center><b>DUE DATE</b></td>
		</tr>
		</table>
			
		<table>
		".$xls_data."
		<tr>
		<td colspan=4>&nbsp;</td>
		<td><b>$".number_format($total_amount, 2, '.', ',')."</b></td>			
		</tr>
		</body>
		</html>";

		return $xls_file;	
	}
	
	/**
	* @return ach_file
	* @param $billing_obj
	* @desc Build the ach formatted file for delivery to client
	*/
	function Build_Ach($billing_obj)
	{
        $mydate = $this->Add_Day(date('Y-m-d'),1);
        $mydate_2 = str_replace("-","",$mydate);
        $next_business_day = substr($mydate_2,2);
		$processed_count = 0;
		
		foreach ($billing_obj AS $record)
		{
			$processed_count++;
			$ach_amount_total += $record->amount;
	
			$receiving_dfi = $this->Check_Digit_For_DFI($record->routing);
		  	$sum_of_receiving_dfi += $receiving_dfi;
		 	$sum_of_hash += substr($record->routing,0,8);
	
			// name can be a maximum of 22 characters, according to the bank
			$name = substr(strtoupper($record->last_name).",".strtoupper($record->first_name), 0, 22);
			$record_code6 = "627".substr($record->routing,0,8)."".$receiving_dfi."".$this->Set_Length_Right_Spaces($record->account,17)."".$this->Set_Length_Left_Zeros($this->Format_Amount($record->amount),10)."".$this->Set_Length_Right_Spaces($record->transaction_id,15)."".$this->Set_Length_Right_Spaces($name,22)."  009121484".$this->Set_length_Left_Zeros($processed_count,7)."\n";
	
			$code6_batch_file .= $record_code6;
			
		}
						
		$sum_of_hash += 9121484;
			
		//Credit To NMS Account
		$record_code6 = "632091214847".$this->Set_Length_Right_Spaces("24715",17)."".$this->Set_Length_Left_Zeros(str_replace('.', '', number_format($ach_amount_total, 2, '', '')),10)."".$this->Set_Length_Right_Spaces(1010578572,15)."".$this->Set_Length_Right_Spaces('EXPRESSGOLDCARDCOM',22)."  009121484".$this->Set_length_Left_Zeros($processed_count,7)."\n";
		$code6_batch_file .= $record_code6;
				
		//Header Record
		// spaces added to conform to new format
		$record_code1 = "101 091214847 010578572".date('ymdHi')."A094101INTERCEPT              ExpressGoldCard                \n";
		
		//Company Batch Header
		$record_code5 = "5200ExpressGoldCard 18776740644         1010578572PPDDEBIT     ".date('ymd')."".$next_business_day."   1091214840000001\n";		
				
		//Batch Control Record
		$record_code8 = "8200".$this->Set_Length_Left_Zeros($processed_count,6)."".$this->Set_Length_Left_Zeros(substr($sum_of_hash,-9),10)."".$this->Set_Length_Left_Zeros(str_replace('.', '', number_format($ach_amount_total, 2, '', '')),12)."".$this->Set_Length_Left_Zeros(str_replace('.', '', number_format($ach_amount_total, 2, '', '')),12)."1010578572".$this->Set_Length_Right_Spaces(" ",25)."091214840000001\n";
				
		//File Control Record
		$record_code9 = "9000001".$this->Set_Length_Left_Zeros($processed_count,6)."".$this->Set_Length_Left_Zeros($processed_count,8)."".$this->Set_Length_Left_Zeros(substr($sum_of_hash,-9),10)."".$this->Set_Length_Left_Zeros(str_replace('.', '', number_format($ach_amount_total, 2, '', '')),12)."".$this->Set_Length_Left_Zeros(str_replace('.', '', number_format($ach_amount_total, 2, '', '')),12)."".$this->Set_Length_Right_Spaces(" ",39)."";
	
		//Combine All Parts
		$ach_file = $record_code1.$record_code5.$code6_batch_file.$record_code8.$record_code9;
		// ::-- END ACH CREATION --:: // 
		
		return $ach_file;
	}
				
	function Check_Digit_For_DFI($number)
   	{
		for ($i =1; $i <= strlen($number);$i++)
		{
			if (($i == 2) || ($i == 5) ||($i == 8))
			{
				$weight = 7;
			}
			else if (($i == 1) || ($i == 4) || ($i == 7))
			{
				$weight = 3;
			}
			else if (($i == 3) || ($i == 6))
			{
				$weight = 1;
			}
			else
			{
				$weight = 0;
			}

			$result += $number[$i-1] * $weight;
		}
		if ($result % 10)
		{
			$result = ($result + (10 - ($result % 10))) - $result;
	    	}
		else
		{
			$result = 0;
		}
		return $result;
    	}
		
	function Set_Length_Left_Zeros($data, $length)
	{
		$tempdata=(string)$data;
		
		for($i=strlen($tempdata);$i<$length;$i++)
		{
			$spaces .= "0";
		}
			
		$tempdata = $spaces.$tempdata;
		$tempdata = (string)$tempdata;
		return $tempdata;
	}
		
	function Set_Length_Right_Spaces($data, $length)
	{
		$tempdata=(string)$data;

		for($i=strlen($tempdata);$i<$length;$i++)
		{
			$spaces .= " ";
		}
		
		$tempdata = $tempdata.$spaces;
		$tempdata = (string)$tempdata;
		return $tempdata;
	}
		
	function Format_Amount($amount)
	{
		$tempdata = explode (".",(string)$amount);
		if (strlen($tempdata[1]) == 1) $tempdata[1] *=10;
		else if (strlen($tempdata[1]) == 0) $tempdata[1] ="00";
		$tempdata = $tempdata[0].$tempdata[1];
		return $tempdata;
	}
	
	/**
	 * @return object
	 * @param $cc_number
	 * @returns an object of current billing information
	 */
	function Rpt_Show_Current($cc_number)
	{
		$today = date('Y-m-d');
		$query = "SELECT * FROM `billing` WHERE cc_number = '".$cc_number."' AND payment_date > ".$today."";
		
		$result = $this->sql->Query($this->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
		$current_billing = $this->sql->Fetch_Object_Row ($result);
		$billing_cycle = $this->Sub_Day($current_billing->payment_date,11);
		$current_billing->billing_cycle = $this->Validate_Date($billing_cycle);
		
		if($current_billing->billing_balance == '')
		{
			$current_billing->billing_balance = 0;
			$current_billing->id = 0; 
			$current_billing->billing_cycle = date('Y-m-d');
		}
		
		return $current_billing;
	}
}
?>