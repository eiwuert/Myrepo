<?PHP
/**
* @return ach_file
* @param $billing_obj
* @desc Build the ach formatted file for delivery to client or directly to intercept 
*/
class ACH
{
	
	function ACH ()
	{
		return true;
	}
	
	/*!
	Name: Build_Ach
	Return Value: $ach_file
	Purpose: To generate ach file
	Passed Values:
		$billing_obj: 
		$company_name: days to add
		$tax_id: 
		$bank_account_number: 
		$bank_aba: 
		$phone_number: 
		$method: 

	Comments:
		Will return the generated ach batch file content
	*/
	function Build_Ach($billing_obj, $company_name, $tax_id, $bank_account_number, $bank_aba, $bank_account_type, $phone_number, $method, $trace_number)
	{
	   	$mydate = $this->Add_Days(date('Y-m-d'),1);
	   	$mydate_2 = str_replace("-","",$mydate);
	   	$next_business_day = substr($mydate_2,2);
		$processed_count = 0;

		// set debit/credit vars
		$bill_method = (strtoupper($method) == 'DEBIT') ? 7 : 2;
		$master_method = (strtoupper($method) == 'DEBIT') ? 2 : 7;

		//  batch credits
		foreach($billing_obj AS $record)
		{

			$processed_count++;
			$ach_amount_total += $record->amount;

			$receiving_dfi = $this->Check_Digit_For_DFI($record->routing);
		  	$sum_of_receiving_dfi += $receiving_dfi;
		 	$sum_of_hash += substr($record->routing,0,8);

		 	// bank account type
		 	$account_type = (strtoupper($record->account_type) == 'CHECKING') ? 2 : 3;

			// name can be a maximum of 22 characters, according to the bank
			$name = strtoupper(trim($record->last_name)).",".strtoupper(trim($record->first_name));

			$record_code6 = "6".$account_type.$bill_method.substr($record->routing,0,8)."".$receiving_dfi."".$this->Set_Length_Right_Spaces($record->account,17)."".$this->Set_Length_Left_Zeros($this->Format_Amount($record->amount),10)."".$this->Set_Length_Right_Spaces($record->trace_number,15)."".$this->Set_Length_Right_Spaces($name,22)."  0".$this->Set_length_Left_Zeros($record->trace_number,15)."\n";

			$code6_batch_file .= $record_code6;	
		}

		$sum_of_hash += 9121484;

		
		//Master Account
		$bank_account_type = (strtoupper($bank_account_type) == 'CHECKING') ? 2 : 3; 
		$record_code6 = "6".$bank_account_type.$master_method.$this->Set_Length_Right_Spaces($bank_aba, 8).$this->Check_Digit_For_DFI($bank_aba).$this->Set_Length_Right_Spaces($bank_account_number,17)."".$this->Set_Length_Left_Zeros($this->Format_Amount($ach_amount_total),10)."".$this->Set_Length_Right_Spaces($trace_number, 15)."".$this->Set_Length_Right_Spaces($company_name,22)."  0".$this->Set_length_Left_Zeros($trace_number,15)."\n";
		$code6_batch_file .= $record_code6;

		//Header Record
		// spaces added to conform to new format
		$record_code1 = "101".$this->Set_Length_Left_Spaces($bank_aba, 10).$this->Set_Length_Left_Spaces($tax_id, 10).date('ymdHi')."A094101".$this->Set_Length_Right_Spaces('Internept', 23).$this->Set_Length_Right_Spaces($company_name, 23).$this->Set_Length_Right_Spaces($record->reference_id, 8)."\n";

		//Company Batch Header
		$record_code5 = "5200".$this->Set_Length_Right_Spaces($company_name, 16).$this->Set_Length_Right_Spaces($phone_number, 20)."1010578572PPD".$this->Set_Length_Right_Spaces(strtoupper($method), 10).date('ymd')."".$next_business_day."   1091214840000001\n";		

		//Batch Control Record
		$record_code8 = "8200".$this->Set_Length_Left_Zeros($processed_count,6)."".$this->Set_Length_Left_Zeros(substr($sum_of_hash,-9),10)."".$this->Set_Length_Left_Zeros(str_replace('.', '', number_format($ach_amount_total, 2, '', '')),12)."".$this->Set_Length_Left_Zeros(str_replace('.', '', number_format($ach_amount_total, 2, '', '')),12)."1010578572".$this->Set_Length_Right_Spaces(" ",25)."091214840000001\n";

		//File Control Record
		$record_code9 = "9000001".$this->Set_Length_Left_Zeros($processed_count,6)."".$this->Set_Length_Left_Zeros($processed_count,8)."".$this->Set_Length_Left_Zeros(substr($sum_of_hash,-9),10)."".$this->Set_Length_Left_Zeros(str_replace('.', '', number_format($ach_amount_total, 2, '', '')),12)."".$this->Set_Length_Left_Zeros(str_replace('.', '', number_format($ach_amount_total, 2, '', '')),12)."".$this->Set_Length_Right_Spaces(" ",39)."";

		//Combine All Parts
		$ach_file = $record_code1.$record_code5.$code6_batch_file.$record_code8.$record_code9;


		return $ach_file;
	}
	
	/*!
	Name: ACH_Return_Batch
	Return Value: $dbf_return
	Purpose: Parses return ach dbf data into array keyed by the return codes
	Passed Values:
		$return_dbf: dbf data

	Comments:
		Will return a array with the parsed dbf data
	*/
	function ACH_Return_Batch ( $return_dbf )
	{
		$return_dbf = split("\n", $return_dbf);
		foreach ($return_dbf as $line)
		{
			if ($line)
			{
				//  split the line data into array
				$tmp_dbf = preg_split('/","/', $line);
				
				// return dbf codes array
				$dbf_array_keys = array(
					'location_key',
					'company_key',
					'entry_key',
					'bank_name',
					'processor_name',
					'achid',
					'pin',
					'phone',
					'fax',
					'company_name',
					'entry_desc',
					'app_discretionary_data',
					'TF_flag1',
					'aba',
					'account',
					'corrected',
					'sec',
					'entry_date',
					'recipient_id',
					'recipient_name',
					'debit_amount',
					'credit_amount',
					'reason_desc',
					'reason_code',
					'account_type',
					'reason_desc2',
					'trans_code',
					'TF_flag2',
					'recipient_discretionary_data',
					'tracenum1',
					'tracenum2',
					'TF_flag3'
				);
				
				foreach ($tmp_dbf as $key => $dbf_data)
				{
					$dbf_return[$i][$dbf_array_keys[$key]] = ereg_replace('"', "", $dbf_data);
				}
				++$i;
			}
		}
		
		return $dbf_return;
	}
	
	

	/*!
	Name: Add_Days
	Return Value: $date
	Purpose: To add days to date
	Passed Values:
		$date: start date (Y-m-d)
		$days: days to add

	Comments:
		Will return a valid date
	*/
	function Add_Days($date, $days)
	{

		$date = explode("-", $date);
		$date = mktime (0,0,0,$date[1],$date[2],$date[0]);
		$date = strtotime("$days day", $date);

		return $this->Fix_Weekend (date('Y-m-d', $date));
	}
	
	function Check_Digit_For_DFI($number)
	{
		for($i =1; $i <= strlen($number);$i++)
		{
			if(($i == 2) || ($i == 5) ||($i == 8))
			{
				$weight = 7;
			}
			elseif(($i == 1) || ($i == 4) || ($i == 7))
			{
				$weight = 3;
			}
			elseif(($i == 3) || ($i == 6))
			{
				$weight = 1;
			}
			else
			{
				$weight = 0;
			}
	
			$result += $number[$i-1] * $weight;
		}
		if($result % 10)
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
		$data = substr($data, 0, $length);
		
		$tempdata=(string)$data;
		
		for($i=strlen($tempdata);$i<$length;$i++)
		{
			$spaces .= "0";
		}
			
		$tempdata = $spaces.$tempdata;
		$tempdata = (string)$tempdata;
		return $tempdata;
	}
	
	function Set_Length_Right_Zeros($data, $length)
	{
		$data = substr($data, 0, $length);
		
		$tempdata=(string)$data;
		
		for($i=strlen($tempdata);$i<$length;$i++)
		{
			$spaces .= "0";
		}
			
		$tempdata = $tempdata.$spaces;
		$tempdata = (string)$tempdata;
		return $tempdata;
	}
		
	function Set_Length_Right_Spaces($data, $length)
	{

		$data = substr($data, 0, $length);

		$tempdata=(string)$data;
	
		for($i=strlen($tempdata);$i<$length;$i++)
		{
			$spaces .= " ";
		}
		
		$tempdata = $tempdata.$spaces;
		$tempdata = (string)$tempdata;
		return $tempdata;
	}
	
	function Set_Length_Left_Spaces($data, $length)
	{
		$data = substr($data, 0, $length);
		
		$tempdata=(string)$data;
	
		for($i=strlen($tempdata);$i<$length;$i++)
		{
			$spaces .= " ";
		}
		
		$tempdata = $spaces. $tempdata;
		$tempdata = (string)$tempdata;
		return $tempdata;
	}
		
	function Format_Amount($amount)
	{
		$tempdata = explode (".",(string)$amount);
		if(strlen($tempdata[1]) == 1)
		{
			$tempdata[1] *=10;
		}
		else if(strlen($tempdata[1]) == 0)
		{
			$tempdata[1] ="00";
		}
		
		$tempdata = $tempdata[0].$tempdata[1];
		return $tempdata;
	}
	
	function HTTP_Post($post_vars)
	{
		$curl = curl_init($post_vars['url']);

		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_VERBOSE, 1);		
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $post_vars['fields']);
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); // this line makes it work under https
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
			
		$result = curl_exec($curl);

		$return_val["sent"] = $post_vars['fields'];
		$return_val["received"] = $result;
		return $return_val;
	}
	
		
	function Fix_Weekend ($date)
	{
		//echo "Fixing Weekend for date: {$pay_date}\n";
		$date_int = strtotime ($date);
		// Grab the day name
		$day_string = strtoupper (date ("D", $date_int));

		// Test if day is sat or sun
		while ($day_string == "SAT" || $day_string == "SUN")
		{
			$date_int = strtotime ("+1 day", $date_int);

			$day_string = strtoupper (date ("D", $date_int));
		}

		return date ("Y-m-d", $date_int);
	}
}
?>