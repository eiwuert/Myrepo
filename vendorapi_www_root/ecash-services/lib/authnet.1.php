<?

class Authnet_1
{

	protected $key;
	protected $login;
	protected $property_short;
	protected $application_id;
	protected $transaction_id;
	
	function __construct($mode, $property_short, $application_id, $transaction_id = null)
	{
		
		// check for live trans
		if ($mode != 'LIVE')
		{
			// set dev flag 
			$this->dev = TRUE;
		}
			
		// set login/key
		switch ($property_short)
		{				
			case 'payo':
			default:
				$this->login 	= 'gre481202256';
				$this->key		= 'ADOqtXYOUH558iHc';
				break;
		}	
		
		$this->_mode = $mode;
		$this->property_short = $property_short;
		$this->application_id = $application_id;
		$this->transaction_id = $transaction_id;
	}
	
	
	public function Set_Transaction_ID($transaction_id)
	{
		$this->transaction_id = $transaction_id;
	}
	
	
	public function Debit($data)
	{
		// prepare post fields
		$fields = array(
			"x_first_name"			=> $data["name_first"],
			"x_last_name" 			=> $data["name_last"],
			"x_address" 			=> $data["home_street"]." ".$data["home_unit"],
			"x_city"				=> $data["home_city"],
			"x_state" 				=> $data["home_state"],
			"x_zip" 				=> $data["home_zip"],
			
			"x_ship_to_first_name"	=> ($data["ship_name_first"]) ? $data["ship_name_first"] : $data['name_first'],
			"x_ship_to_last_name" 	=> ($data["ship_name_last"]) ? $data["ship_name_last"] : $data['name_last'],
			"x_ship_to_address" 	=> ($data["ship_address"]) ? $data["ship_street"] : $data['street'],
			"x_ship_to_city" 		=> $data["ship_city"],
			"x_ship_to_state" 		=> $data["ship_state"],
			"x_ship_to_zip" 		=> $data["ship_zip"],
			
			"x_email"				=> $data["email_primary"],
			"x_phone" 				=> $data["phone_home"],
			
			"x_card_type" 			=> $data["cc_type"],
			"x_card_num"			=> $data["cc_number"],
			"x_exp_date"			=> $data["cc_exp_month"] . "/" . $data["cc_exp_year"],
			"x_card_code" 			=> $data["cc_cvv2"],
	
			"x_po_num" 				=> $data["transaction_id"],
	
			"x_login" 				=> $this->login,
			"x_country" 			=> "US",
			"x_cust_id" 			=> $data["customer_id"],
			"x_fp_timestamp" 		=> time(),
			"x_fp_sequence" 		=> rand(1,1000),
			"x_invoice_num" 		=> $data["customer_id"],		
			"x_description" 		=> $data["description"],
			"x_amount" 				=> $data["amount"]
		);

		// prepare login data
		$login_data  = $fields[x_login]."^";
		$login_data .= $fields[x_fp_sequence]."^";
		$login_data .= $fields[x_fp_timestamp]."^";
		$login_data .= $fields[x_amount]."^";
		
		// generate and set login hash
		$fields["x_fp_hash"] = $this->hmac($this->key, $login_data);
		
		// get post url
		$post_url = $this->Get_Post_URL('debit', $data['transaction_id']);

		// run trans
		$trans_response = $this->Run_Transaction($fields, $post_url);
		
		// return response
		return $trans_response;
	}
	
	
	public function Credit($data)
	{
		// prepare xml package
		$xml_package = $this->Prepare_XML_Credit(
										date("Y-m-d G:i:s").".000",
										$data['application_id'],
										$data['transaction_id'],
										$data['name_first'],
										$data['name_last'],
										$data['total_amount'],
										$data['date'],
										$data['bank_aba'],
										$data['bank_account_type'],
										$data['bank_account_number']);

		// get post url
		$post_url = $this->Get_Post_URL('credit');

		// run trans
		$trans_response = $this->Run_Transaction($xml_package, $post_url);
		
		// return response
		return $trans_response;

	}

	
	public function Run_Transaction($fields, $post_url)
	{
		
		$attempts = 0;
		// run transaction in three attempts
		while($attempts < 3 && !$stop)
		{

			// post transaction
			$post_response = $this->Post_Transaction($fields, $post_url);

			// parse xml_response
			$response = $this->Process_Results($post_response['received']);

			// TEST STUFF NEEDS TO BE REMOVED!!
			if($fields['x_card_num'] == '9999999999999991')
			{
				$approved = TRUE;
				$success = $stop = TRUE;
				break;
			}
			///////////////////////////////////////
			
			
			switch($response['Result'])
			{
				// accepted
				case '0':
					$approved = TRUE;
					$success = $stop = TRUE;
					break;
		
				// denied
				case '1':
				case '2':
					$approved = FALSE;
					$success = $stop = FALSE;
					break;
			}
			
			++$attempts;
		}
		
		$trans_response = array (
								'success' => $success,
								'approved' => $approved,
								'message' => $response['x_response_reason_text'],
								'sent' => $post_response['sent'],
								'received' => $post_response['received'],
								);
					
		return $trans_response;
	}
	
	
	public function Get_Post_URL($type, $transaction_id = null)
	{
		switch (strtolower($type))
		{
			case 'debit':			
			case 'credit':
			default:
				$url = "https://secure.authorize.net/gateway/transact.dll";
			break;
			
		}
		
		return $url;
	}

	
	public function Post_Transaction($fields, $url)
	{

		$content="";
		
		foreach($fields as $key => $val)
		{
			$content .= $key."=".urlencode($val)."&";
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
		$result = curl_exec($ch);
		
		$response = curl_getinfo($ch);
		$response['sent'] = $content;
		$response['received'] = $result;		
		
		return $response;
	}
	
	 /**
	 * Method hmac
	 *
	 * RFC 2104 HMAC implementation for php.
	 * Creates an md5 HMAC. Eliminates the need
	 * to install mhash to compute a HMAC
	 *
	 * @param 	string	$key
	 * @param 	string	$data
	 * @return	String
	 * 
	 */
	public static function hmac($key, $data)
	{
	   $b = 64; // byte length for md5
	   
	   if (strlen($key) > $b)
	   {
	       $key = pack("H*",md5($key));
	   }
	   
	   $key  	= str_pad($key, $b, chr(0x00));
	   $ipad 	= str_pad('', $b, chr(0x36));
	   $opad 	= str_pad('', $b, chr(0x5c));
	   $k_ipad 	= $key ^ $ipad ;
	   $k_opad	= $key ^ $opad;
	
	   return md5($k_opad  . pack("H*",md5($k_ipad . $data)));
	}
	
	/**
	 * Method _processResults
	 *
	 * Processes the data received from authnet 
	 * and sets them into the session.
	 *
	 * @return	void
	 * 
	 */
	private function Process_Results($result)
	{
		$authnet_response = unserialize($result);
		
		foreach($authnet_response as $key => $val)
		{
			 $results[$key] = $val;
		}
		
		if ($results["x_response_code"] == 1)
		{
			$results["Result"] = 0;
		}
		else
		{
			$results["Result"] = 1;
		}
		
		return $results;
	}
}
