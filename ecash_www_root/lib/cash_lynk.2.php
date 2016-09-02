<?PHP
/**
	@publicsection
	@public
	@brief
	
	@version
		1.0.6 2005-02-10 - Andyh

	@change_log
		1.0.6 :: modified version of cash_lynk.1
			- Add authentication_source_id to the db2 insert because it's required in the table
			- Modify db2 update using parameterized query.
			- Modify Combine_Data function to combine data into object.
			- Modify setup, add data into record object and unset the data before combine them
			- Modify unique MSGID, divide by 100 to fit the msgid range of cashlynk
			- Add date_expiration to the return object on create_card_account
			- Change the cashlynk Development source url, and put new bin number

			2005-02-18: Change Pin works
						Change Card Status works
						Get Short Summary works
						Get Card Account Balance works
						Get Card Transaction Detail works
						View Card Detail works
			2005-02-25: Change Exp.Date +5 years ahead
	@todo
*/

class Cash_Lynk
{
	
	public $db2;
	public $mysql;
	public $mssql;
	public $table;
	public $db;
	public $site;
	
	private $last_sent;
	private $last_url;
	private $last_received;

	/**
	* @return bool
	* @param $db2 obj
	* @param $mysql obj
	* @param $mssql obj
	* @param $db string
	* @param $site string
	* @param $debug bool
	* @param $db_type string
	* @desc Cash Lynk constructor
 	*/
	function __construct($db2 = NULL, $mysql = NULL, $mssql = NULL, $db = NULL, $table, $site, $customer_id = NULL, $debug = FALSE)
	{
		$this->db2 = $db2;
		$this->mysql = $mysql;
		$this->mssql = $mssql;
		$this->table = $table;
		$this->db = $db;
		$this->site = $site;
		$this->customer_id = $customer_id;
		
		switch(TRUE)
		{
			case (!is_null($this->mysql)):
			$this->db_type = "MYSQL";
			break;
			
			case (!is_null($this->mssql)):
			$this->db_type = "MSSQL";
			break;
			
			case (!is_null($this->db2)):
			$this->db_type = "DB2";
			break;
			
			default:
			$this->db_type = '';
		}
				
		if(!$debug)
		{
			// Live
			$this->url = 'https://www.cashlynk.com/AccessLynk/WebAPI.aspx?';
			$this->msg_head = array('CID' => '250','CUSR' => 'Webapi250','CPWD' => 'Lynk123');
			$this->economic_program = 606;
			$this->funding_program = 606;
		}
		else
		{
			// Dev
			$this->url = 'https://apidev.cashlynk.com/Issuing/AccessLynk/WebAPI.aspx?';
			$this->msg_head = array('CID' => '102','CUSR' => 'Webapi102','CPWD' => '123456');
			$this->economic_program = 165;
			$this->funding_program = 165;
			
			$this->debug = TRUE;
		}
		
		return TRUE;
	}
			
	/**
	* @return object
	* @param object
	* @desc Build the initial arrays required by Cash Lynk API's - Required data is SSN and DOB
 	*/
	public function Setup($data)
	{
		$this->error_codes = array(
		'000' => 'Success',
		'001' => 'Unspecified Error',
		'002' => 'Login Error',
		'003' => 'Message ID record not found',
		'004' => 'Message ID already filed',
		'005' => 'Invalid Message ID Format',
		'006' => 'Function number is not supported',
		'007' => 'Invalid Client Number',
		'008' => 'Missing required parameters',
		'009' => 'Reserved',
		'010' => 'Reserved',
		'011' => 'Invalid Card Number',
		'012' => 'Invalid Account Number/SSN',
		'013' => 'Invalid Amount',
		'014' => 'Invalid transaction type',
		'015' => 'Invalid Old PIN',
		'016' => 'Invalid New PIN',
		'017' => 'Invalid Start Date',
		'018' => 'Invalid End Date',
		'019' => 'The start date is earlier then the end date',
		'020' => 'You may only request a months worth of transactions',
		'021' => 'No Card Accounts for this CardPAN',
		'022' => 'Invalid Card Account Number',
		'023' => 'Batch ID Not Found');
		
		$record = new stdClass();
		// set record object from $data
		foreach ($data as $key=>$val) {
			$record->$key = $val;
		}

		$record->cardholder_id = $data['social_security_number'];
		$record->primary_card_holder = $record->cardholder_id;
		$record->dob = $data['date_of_birth'];
		$record->middle_name = $data['name_middle'];
		$record->opt_data_1 = $data['opt_data_1'];
		$record->opt_data_2 = $data['opt_data_2'];
		$record->opt_data_3 = $data['opt_data_3'];
		
		// unset the data if not an object before combine them
		if(!is_object($data)){ unset($data); }
		return $this->Combine_Data($record,$data);
	}
		
	/**
	* @return obj
	* @param $data obj
	* @desc Create card holder
 	*/
	public function Create_Card_Holder($data)
	{
		$msg = $this->msg_head;
		$msg['FUNC'] = '001';
		$msg['MSGID'] = '';

		// Always force this to a primary card holder
		$data->primary_card_holder = TRUE;
		if($data->cl_response->cardholder_id) { $msg ['P1'] = trim($data->cl_response->cardholder_id); }
		if($data->cl_response->name_first) { $msg['P2'] = $data->cl_response->name_first; }
		if($data->cl_response->name_middle) { $msg['P3'] = $data->cl_response->name_middle; }
		if($data->cl_response->name_last) { $msg['P4'] = $data->cl_response->name_last; }
		if($data->cl_response->address_1) { $msg['P5'] = $data->cl_response->address_1; }
		if($data->cl_response->address_2) { $msg['P6'] = $data->cl_response->address_2; }
		if($data->cl_response->city) { $msg['P7'] = $data->cl_response->city; }
		if($data->cl_response->state) { $msg['P8'] = $data->cl_response->state; }
		if($data->cl_response->zip) { $msg['P9'] = $data->cl_response->zip; }
		if($data->cl_response->phone_home) { $msg['P10'] = $data->cl_response->phone_home; }
		if($data->cl_response->dob) { $msg['P11'] = $data->cl_response->dob; }
		$msg['P12'] = '0'; // If this is the primary card holder then 0 ... should always be the primary card holder
		if($data->cl_response->opt_data_1) { $msg['P13'] = $data->cl_response->opt_data_1; }
		if($data->cl_response->opt_data_2) { $msg['P14'] = $data->cl_response->opt_data_2; }
		if($data->cl_response->opt_data_3) { $msg['P15'] = $data->cl_response->opt_data_3; }
		if($data->cl_response->notes) { $msg['P16'] = $data->cl_response->notes; }
		if($data->cl_response->email) { $msg['P17'] = $data->cl_response->email; }

		// Send & receive the data
		$res = $this->Send_Get($msg);

		if($res['P1'] == '000')
		{
			$record->card_holder_id = $res['P2'];
			$data = $this->Combine_Data($record,$data);
		}
		else
		{
			// Record the error
			$data = $this->Combine_Data($res,$data,TRUE);		
		}
		
		return $data;
	}
	
	/**
	* @return obj
	* @param $data obj
	* @desc Create a cash lynk card
 	*/
	public function Create_Card($data)
	{
		$msg = $this->msg_head;
		$msg['FUNC'] = '002';
		$msg['MSGID'] = '';
		
		if($data->cl_response->social_security_number) { $msg['P1'] = trim($data->cl_response->social_security_number); }	// SSN 1,9
		if($data->card_stock) { $msg['P2'] = $data->card_stock; }  else { $msg['P2'] = 0001;}	// Card Stock 1,4	
		if($data->card_bin) { $msg['P3'] = $data->card_bin; } else { $msg['P3'] = 6034110;}		// Card BIN 6,6
		if($data->exp_month) { $msg['P4'] = $data->exp_month; } else { $msg['P4'] = date('m'); }		// Exp Month MM 1,2
		if($data->exp_year) { $msg['P5'] = $data->exp_year; } else { $msg['P5'] = (date('Y')+5); }		// Exp Year YYYY 4,4
		if($data->pin) { $msg['P6'] = $data->pin; } else { $msg['P6'] = 6578; } 				// PIN 4-10
		if($data->cl_response->opt_data_1) { $msg['P7'] = $data->cl_response->opt_data_1; }
		if($data->cl_response->option_2) { $msg['P8'] = $data->cl_response->option_2; }
		if($data->cl_response->option_3) { $msg['P9'] = $data->cl_response->option_3; } 
		if($data->cl_response->name_first) { $msg['P10'] = ucfirst($data->cl_response->name_first)." ".ucfirst($data->cl_response->name_last); }
		if($data->cl_response->emboss_3) { $msg['P11'] = $data->cl_response->emboss_3; }
		if($data->cl_response->name_first) { $msg['P12'] = date("m/y"); }
		if($data->cl_response->emboss_5) { $msg['P13'] = $data->cl_response->emboss_5; }
		$msg['P14'] = $data->cl_response->name_first." ".$data->cl_response->name_last;
		$msg['P15'] = $data->cl_response->address_1." ".$data->cl_response->address_2;
		$msg['P16'] = $data->cl_response->city; 
		$msg['P17'] = $data->cl_response->state; 
		$msg['P18'] = $data->cl_response->zip;
		if($data->member_number) { $msg['P19'] = $data->member_number; } 		// Card Number numeric
				
		// Send & receive the data
		$res = $this->Send_Get($msg);
				
		if($res['P1'] == '000')
		{
			$record->card_number = $res['P2'];
			$record->date_expiration = $msg['P4'] ."/". $msg['P5'];
			$record->card_bin = $msg['P3'];
			$record->card_stock = $msg['P2'];
			$data = $this->Combine_Data($record,$data);
		}
		else
		{
			// Record the error
			$data = $this->Combine_Data($res,$data,TRUE);		
		}
		
		return $data;
	}
	
	/**
	* @return obj
	* @param $data object
	* @desc Create an account for the card
 	*/
	public function Create_Card_Account($data)
	{
		$msg = $this->msg_head;
		$msg['FUNC'] = '003';
		$msg['MSGID'] = '';
		
		$msg['P1'] = 1;
		if($data->economic_program) { $msg['P2'] = $data->economic_program; } else { $msg['P2'] = $this->economic_program; }
		if($data->funding_program) { $msg['P3'] = $data->funding_program; } else { $msg['P3'] = $this->funding_program; }
		$msg['P4'] = $data->cl_response->card_number;
		$msg['P5'] = 0;
		$msg['P11'] = 500;
			
		// Send & receive the data
		$res = $this->Send_Get($msg);
		
		if($res['P1'] == '000')
		{
			$record->card_account = $res['P2'];
			$data = $this->Combine_Data($record,$data);
		}
		else
		{
			// Record the error
			$data = $this->Combine_Data($res,$data,TRUE);		
		}
		
		return $data;
	}
	
	/**
	* @return obj
	* @param $data obj
	* @desc Change customer card status
 	*/
	public function Change_Card_Status($data)
	{
		$msg = $this->msg_head;
		$msg['FUNC'] = '004';
		$msg['MSGID'] = '';

		$msg['P1'] = $data->card_number;
		$msg['P2'] = 0;
		$msg['P3'] = $data->new_card_status;
		
		// Send & receive the data
		$res = $this->Send_Get($msg);
		print_r($res);
		$res['P1'] == '000' ? NULL : $data = $this->Combine_Data($res,$data,TRUE);
		
		return $data;
	}
	
	/**
	* @return obj
	* @param $data obj
	* @desc Change customers pin number
 	*/
	public function Change_Pin($data)
	{
		$msg = $this->msg_head;
		$msg['FUNC'] = '005';
		$msg['MSGID'] = '';

		$msg['P1'] = $data->card_number;
		$msg['P2'] = $data->old_pin;
		$msg['P3'] = $data->new_pin;
		
		// Send & receive the data
		$res = $this->Send_Get($msg);
		print_r($res);
		$res['P1'] == '000' ? NULL : $data = $this->Combine_Data($res,$data,TRUE);
		
		return $data;
	}
	
	/**
	* @return bool
	* @param $data obj
	* @desc View cardholder statement
 	*/
	public function View_Card_Details($data)
	{
		$msg = $this->msg_head;
		$msg['FUNC'] = '009';
		$msg['MSGID'] = '';

		$msg['P1'] = $data->card_number;
		!$msg['P2'] ? $msg['P2'] = date('m-d-Y') : $msg['P2'] = $data->start_date; 
		!$msg['P3'] ? $msg['P3'] = date('m-d-Y') : $msg['P3'] = $data->end_date;

		// Send & receive the data
		$res = $this->Send_Get($msg,FALSE);
		return $res;

		if($res['P1'] == '000')
		{
			$record->card_detail_xml = $res['P2'];
			$data = $this->Combine_Data($record,$data);
		}
		else
		{
			// Record the error
			$data = $this->Combine_Data($res,$data,TRUE);		
		}
		
		return $data;
	}
	
	/**
	* @return obj
	* @param $data obj
	* @desc Validate that the pin number given is correct
 	*/
	public function Validate_Pin($data)
	{
		$msg = $this->msg_head;
		$msg['FUNC'] = '012';
		$msg['MSGID'] = '';
		
		$msg['P1'] = $data->card_number;
		$msg['P2'] = $data->pin;
		
		// Send & receive the data
		$res = $this->Send_Get($msg);
		
		if($res['P1'] == '000')
		{
			$record->pin_validation = TRUE;
			$data = $this->Combine_Data($record,$data);
		}
		else
		{
			$record->pin_validation = FALSE;
			$data = $this->Combine_Data($record,$data);	
		}
		
		return $data;
	}
	
	/**
	* @return obj
	* @param $data object
			
			$data = stdClass;
			$data->card_number = string(16);
			$data->deposit_amount = float(0.00);
			$data->transaction_type = 0; // 0 = deposit, 1 = reversal
			
	* @desc Deposit a specific amount of money to a card.
 	*/
	public function Deposit_To_Account($data)
	{
		
		$msg = $this->msg_head;
		$msg['FUNC'] = '007';
		$msg['MSGID'] = '';
		
		if(isset($data->cl_response->card_number))
		{ 
			$msg['P1'] = $data->cl_response->card_number;
		}
		elseif($data->card_number)
		{
			$msg['P1'] = $data->card_number;	
		}
		
		$msg['P2'] = $data->deposit_amount;
		$msg['P3'] = $this->funding_program;
		
		if($data->transaction_type) { $msg['P4'] = $data->transaction_type; } else { $msg['P4'] = 0; }
		
		// Send & receive the data
		$res = $this->Send_Get($msg);
		
		if($res['P1'] == '000')
		{
			$record->deposit_id = $res['P2'];
			$data = $this->Combine_Data($record,$data);
		}
		else
		{
			// Record the error
			$data = $this->Combine_Data($res,$data,TRUE);		
		}
		
		return $data;
	}
	
	/**
	* @return obj
	* @param $data obj
	* @desc Deposit a specific amount of money to a card.
 	*/
 	public function Assign_Card_To_Cardholder($data)
 	{
 		$msg = $this->msg_head;
		$msg['FUNC'] = '011';
		$msg['MSGID'] = '';
		
		$msg['P1'] = $data->social_security_number;
		$msg['P2'] = $data->cl_response->card_number;
		$msg['P3'] = 0;
		
		// Send & receive the data
		$res = $this->Send_Get($msg);
		
		if($res['P1'] == '000')
		{
			NULL;	
		}
		else
		{
			// Record the error
			$data = $this->Combine_Data($res,$data,TRUE);		
		}
		
		// This function has no response
		return $data;
 	}
 	
 	/**
	* @return obj
	* @param $data
	* @desc Deposit a specific amount of money to the primary card account.
 	*/
 	public function Deposit_Primary_Card_Account($data)
 	{
 		
 		$msg = $this->msg_head;
		$msg['FUNC'] = '017';
		$msg['MSGID'] = '';
		
		$msg['P1'] = $data->cl_response->card_number;
		$msg['P2'] = $data->deposit_amount;
		$msg['P3'] = $this->funding_program;
		if ($data->transaction_type) { $msg['P4'] = $data->transaction_type; } else { $msg['P4'] = 0; }
		
		// Send & receive the data
		$res = $this->Send_Get($msg);
		
		if($res['P1'] == '000')
		{
			$record->deposit_id = $res['P2'];
			$data = $this->Combine_Data($record,$data);
		}
		else
		{
			// Record the errors
			$data = $this->Combine_Data($res,$data,TRUE);	
		}
		
		return $data;
		
 	}
 	
 	public function Get_Short_Summary($card_number)
 	{
 		$msg = $this->msg_head;
		$msg['FUNC'] = '018';
		$msg['MSGID'] = '';
		
		$msg['P1'] = $card_number;
		$msg['P2'] = 0;
		
		// Send & receive the data
		$res = $this->Send_Get($msg);
		if($res['P1'] == '000')
		{
			$record->cardholder_name = $res['P2'];
			$record->current_balance = $res['P3'];
			$record->deposit_today = $res['P4'];
			$record->authorize_today = $res['P5'];
			$record->phone_today = $res['P6'];
			$record->transaction_today = $res['P7'];
			$data = $this->Combine_Data($record,$data);
		}
		else
		{
			// Record the errors
			$data = $this->Combine_Data($res,$data,TRUE);	
		}
		return $data;
 	}
 	
  	public function Get_Card_Account_Balance($card_account)
 	{
 		$msg = $this->msg_head;
		$msg['FUNC'] = '019';
		$msg['MSGID'] = '';
		
		$msg['P1'] = $card_account;
		
		// Send & receive the data
		$res = $this->Send_Get($msg);
		if($res['P1'] == '000')
		{
			$record->card_account_balance = $res['P2'];
			$data = $this->Combine_Data($record,$data);
		}
		else
		{
			// Record the errors
			$data = $this->Combine_Data($res,$data,TRUE);	
		}
		return $data;
 	}
 	
 	/**
	* @return obj
	* @param $data obj
	* @desc Deposit a specific amount of money to the primary card accoutn.
 	*/
 	public function View_Program_Balance($data)
 	{
 		$msg = $this->msg_head;
		$msg['FUNC'] = '024';
		$msg['MSGID'] = '';
		
		if($data->funding_program) { $msg['P1'] = $data->funding_program; } else { $msg['P1'] = $this->funding_program; }
		
		$res = $this->Send_Get($msg);

		if($res['P1'] == '000')
		{
			$record->program_account_balance = $res['P2'];
			$record->program_fee_balance = $res['P3'];
			$data = $this->Combine_Data($record,$data);
		}
		else
		{
			// Record the error
			$data = $this->Combine_Data($res,$data,TRUE);
		}
		
		return $data;
 	}
 	
 	public function Create_Prepaid_Mc($data)
 	{
 		$msg = $this->msg_head;
 		$msg['FUNC'] = '026';
 		$msg['MSGID'] = '';
 		
 		if($data['ssn']) { $msg['P1'] = $data['ssn']; }
 		if($data['name_first']) { $msg['P2'] = $data['name_first']; }
 		if($data['name_last']) { $msg['P3'] = $data['name_last']; }
 		if($$data['address_1']) { $msg['P4'] = $data['address_1']; }
 		if($data['address_2']) { $msg['P5'] = $data['address_2']; }
 		if($data['city']) { $msg['P6'] = $data['city']; }
 		if($data['state']) { $msg['P7'] = $data['state']; }
 		if($data['zip']) { $msg['P8'] = $data['zip']; }
 		if($data['phone_home']) { $msg['P9'] = $data['phone_home']; }
 		if($data['dob']) { $msg['P10'] = $data['dob']; }
 		if($data['email']) { $msg['P11'] = $data['email']; }
 		//$msg['P12']; //Reserved
 		if($data['bank_routing_1']) { $msg['P13'] = $data['bank_routing_1']; }
 		if($data['bank_account_1']) { $msg['P14'] = $data['bank_account_1']; }
 		if($data['bank_account_type_1']) { $msg['P15'] = $data['bank_account_type_1']; }
 		if($data['bank_split_mode_1']) { $msg['P16'] = $data['bank_split_mode_1']; }
 		if($data['bank_split_amount_1']) { $msg['P17'] = $data['bank_split_amount_1']; }
 		if($data['bank_split_percentage_1']) { $msg['P18'] = $data['bank_split_percentage_1']; }
 		if($data['bank_routing_2']) { $msg['P19'] = $data['bank_routing_2']; }
 		if($data['bank_account_2']) { $msg['P20'] = $data['bank_account_2']; }
 		if($data['bank_account_type_2']) { $msg['P21'] = $data['bank_account_type_2']; }
 		if($data['bank_split_mode_2']) { $msg['P22'] = $data['bank_split_mode_2']; }
 		if($data['bank_split_amount_2']) { $msg['P23'] = $data['bank_split_amount_2']; }
 		if($data['bank_split_percentage_2']) { $msg['P24'] = $data['bank_split_percentage_2']; }
 		if($data['bank_routing_3']) { $msg['P25'] = $data['bank_routing_3']; }
 		if($data['bank_account_3']) { $msg['P26'] = $data['bank_account_3']; }
 		if($data['bank_account_type_3']) { $msg['P27'] = $data['bank_account_type_3']; }
 		if($data['bank_split_mode_3']) { $msg['P28'] = $data['bank_split_mode_3']; }
 		if($data['bank_split_amount_3']) { $msg['P29'] = $data['bank_split_amount_3']; }
 		if($data['bank_split_percentage_3']) { $msg['P30'] = $data['bank_split_percentage_3']; }
 		if($data['cardholder_first_name_2']) { $msg['P31'] = $data['cardholder_first_name_2']; }
 		if($data['cardholder_last_name_2']) { $msg['P32'] = $data['cardholder_last_name_2']; }
 		if($data['program_id']) { $msg['P33'] = $data['program_id']; }
 		if($data['bin_number']) { $msg['P34'] = $data['bin_number']; }
 		if($data['card_stock_id']) { $msg['P35'] = $data['card_stock_id']; }
 		if($data['card_pin']) { $msg['P36'] = $data['card_pin']; }
 		if($data['activation_card_number']) { $msg['P37'] = $data['activation_card_number']; }
 		if($data['ref_number']) { $msg['P38'] = $data['ref_number']; }
 		
 		$res = $this->Send_Get($msg);

		if($res['P1'] == '000')
		{
			$record->mc_ssn = $res['P2'];
			$data = $this->Combine_Data($record,$data);
		}
		else
		{
			// Record the error
			$data = $this->Combine_Data($res,$data,TRUE);
		}
 	}
	
	/**
	* @return obj
	* @param $args array
	* @desc Processs the data flow
 	*/
	private function Send_Get($args, $parse = TRUE)
	{
		
		// Get the message id
		$msg_id = $this->Get_Msg_Id();
		$args['MSGID'] = $msg_id;
		
		$nvp = array();
		foreach ($args as $k => $v)
		{
			$nvp[] = urlencode($k).'='.urlencode($v);
		}
		
		// build the URL
		$url = (substr($this->url, -1) == '?') ? $this->url: "{$this->url}?";
		$url = $this->url . implode ('&', $nvp);
		
		$start = microtime(TRUE);
		
		do
		{
			$response = @file_get_contents($url);
		}
		while (preg_match("/Server Application Unavailable/", $response));
		
		$elapsed = round((microtime(TRUE) - $start), 4);
		
		// save these
		$this->last_sent = $args;
		$this->last_url = $url;
		$this->last_received = $response;
		
		// Update the audit trail, insert the response
		$this->Audit_Trail($msg_id, $url, $response, $elapsed);
		
		if ($parse)
		{
			
			$output = array();
			
			if (preg_match_all('/([^=]+)=([^\r\n]*)\r?\n?/', $response, $m, PREG_SET_ORDER))
			{
				
				foreach ($m as $matches)
				{
					$output[$matches[1]] = urldecode($matches[2]);
				}
				
			}
			
		}
		else
		{
			$output = $response;
		}
		
		return($output);
		
	}
	
	private function Audit_Trail($msg_id, $sent, $received, $elapsed = 0)
	{
		
		switch($this->db_type)
		{
			
			case "MYSQL":
				
				$query = "INSERT INTO `{$this->table}` (date_created, message_id, sent, received, elapsed_time)
					VALUES (NOW(), '$msg_id', '".mysql_escape_string($sent)."', '".mysql_escape_string($received)."', '$elapsed')";
				$this->mysql->Query($this->db, $query);
				
				break;
				
			case "MSSQL":
				
				// Select the database
				mssql_select_db($this->db, $this->mssql);
				
				$query = "INSERT INTO `{$this->table}` (date_created, message_id, sent, received, elapsed_time)
					VALUES (NOW(), '$msg_id', '".mysql_escape_string($sent)."', '".mysql_escape_string($received)."', '$elapsed')";
				$result = mssql_query($query);
				
				break;
				
			case "DB2":
				
				$gzurl = gzcompress($sent);
				$gzresponse = gzcompress($received);
				
				$query = "
				INSERT INTO
					".$this->table."
					(date_modified, date_created, customer_id, AUTHENTICATION_SOURCE_ID, AUTHENTICATION_TYPE_ID,
						SENT_PACKAGE, RECEIVED_PACKAGE)
				VALUES 
					(CURRENT TIMESTAMP,CURRENT TIMESTAMP,".$this->customer_id.",
					(SELECT AUTHENTICATION_SOURCE_ID FROM AUTHENTICATION_SOURCE WHERE NAME='CASHLYNK'),
					(SELECT AUTHENTICATION_TYPE_ID FROM AUTHENTICATION_TYPE WHERE NAME='CASHLYNK'), ?, ?)";
				
				try 
				{
					
					$prepare = $this->db2->Prepare($query);
					$result = $prepare->Execute($gzurl, $gzresponse);
					
				}
				catch(Db2_Exception $e)
				{
					echo __FILE__."<br>";
					print_r($e);
				}
				
				break;
				
		}
		
		return(TRUE);
		
	}
	
	/**
	* @return string
	* @param $id int
	* @desc Return the message associated with the id
 	*/
	private function Get_Msg_Id()
	{
		
		// build a unique ID from the
		// current timestamp
		$time = split(' ', microtime());
		$time = (end($time).substr(reset($time), 2));
		
		// create the message ID according
		// to the CashLynk spec
		$msg_id = str_pad($this->msg_head['CID'], 5, "0", STR_PAD_LEFT);
		$msg_id .= substr($time, 0, 11);
		
		return($msg_id);
		
	}
	
	/**
	* @return obj
	* @param $record obj
	* @param $data obj
	* @param $error bool
	* @desc Combine the output of a CL function with the initial data object so you are alway dealing with one object of data
 	*/
	private function Combine_Data($record,$data,$error=FALSE)
	{
		
		if(!$error)
		{
			// Add to the data object
			foreach($record AS $rec=>$value)
			{
				//$data['cl_response'][$rec] = $value;	
				$data->cl_response->$rec = $value;
			}

		}
		else
		{
			// Add to the error object
			foreach($record AS $rec=>$value)
			{
				//$data['cl_error'][$rec] = $value;	
				$data->cl_error->$rec = $value;
			}
		}

		return $data;
	}
	
	/**
	* @return obj
	* @param $data obj
	* @param $type string
	* @param $value string
	* @desc Add to the CL response object a specific item and its value
 	*/
	public function Add_Element($data,$type,$value)
	{
		$data->cl_response->$type = $value;
		return $data;	
	}
	
	public function Last_Sent()
	{
		return($this->last_sent);
	}
	
	public function Last_Received()
	{
		return($this->last_received);
	}
	
	public function Last_URL()
	{
		return($this->last_url);
	}
	
}
?>