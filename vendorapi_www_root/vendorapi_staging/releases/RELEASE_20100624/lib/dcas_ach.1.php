<?

class dcas_ach {

	function ach_debit($data)
	{
		
		$url = "https://www.ezpaycenters.net/swspost.asp";
		
		## The credentials used so DCAS knows what account the transaction is for.
		$fields['Company'] = $data['Company'];
		$fields['Username'] = $data['Username'];
		$fields['Password'] = $data['Password'];
		
		## Because the iData passed relies on commas to seprate the fields, we need
		##   to make sure there are no commas in our data.  We also strip out some 
		##   other characters just incase they are in the data so the transaction 
		##   isnt accidentally declined.
		foreach($data['iData'] as $key => $val) {
			$val = str_replace(',', ' ', $val);
			//$val = str_replace('.', ' ', $val);  // Cant strip periods, it messes up the amount.
			$val = str_replace('\\', '', $val);
			$val = str_replace('\'', '', $val);
			$data['iData'][$key] = $val;
		}
		
		## The iData is basically a csv line that is passed in the iData field.
		$fields['iData'] = "CA,"; ## Record Key, dont know what it is, just leave it CA.
		$fields['iData'] .= $data['iData']['ach_routing_number'] . ","; ## Routing Number
		$fields['iData'] .= $data['iData']['ach_account_number'] . ","; ## Account Number
		$fields['iData'] .= $data['iData']['ach_check_number'] . ","; ## Check Number
		$fields['iData'] .= $data['iData']['ach_amount'] . ","; ## ACH Debit Amount
		$fields['iData'] .= $data['iData']['invoice_number'] . ","; ## Optional Invoice Number
		$fields['iData'] .= $data['iData']['name'] . ","; ## Customers Name
		$fields['iData'] .= $data['iData']['address'] . ","; ## Customers Address
		$fields['iData'] .= $data['iData']['city'] . ","; ## Optional City
		$fields['iData'] .= $data['iData']['state'] . ","; ## Optional State
		$fields['iData'] .= $data['iData']['zip'] . ","; ## Optional Zip
		$fields['iData'] .= $data['iData']['phone'] . ","; ## Optional Phone Number
		$fields['iData'] .= $data['iData']['dl_number'] . ","; ## Optional DL Number
		$fields['iData'] .= $data['iData']['dl_state'] . ","; ## Optional DL State
		$fields['iData'] .= $data['iData']['third_party_check'] . ","; ## Optional 1=Yes, 0=No
		$fields['iData'] .= $data['iData']['transaction_id'] . ","; ## Optional CustTraceCode (reference number)
		
		$result = dcas_ach::process_request($fields, $url);
		
		$result_array = explode("\n", $result);
		
		$return['result'] = $result_array[0];
		$return['error'] = $result_array[1];
		$oData = explode(",", $result_array[2]); ## Explode the 3rd line
		$return['oData']['record_key'] = $oData[0];
		$return['oData']['ach_routing_number'] = $oData[1];
		$return['oData']['ach_account_number'] = $oData[2];
		$return['oData']['return_code'] = trim(substr($oData[3], 0, 4));
		$return['oData']['description'] = trim(substr($oData[3], 5, strlen($oData[3])));
		$return['oData']['confirmation_number'] = $oData[4];
		$return['data_sent'] = serialize($fields);
		$return['data_received'] = $result;
		
    return $return;
	}

	
	function process_request ($fields, $url)
	{
		
		$content="";
		foreach($fields as $key => $val) {
			$content .= $key."=".urlencode($val)."&";
		}
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
		$result = curl_exec($ch);
		
		return $result;
	}

  
}

?>