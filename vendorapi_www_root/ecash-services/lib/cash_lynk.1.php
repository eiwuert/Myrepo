<?PHP
/**
	@publicsection
	@public
	@brief
	
	@version
		1.0.0 2004-5-18 - Nick
		1.0.1 2004-7-22 - Nick
		1.0.2 2004-8-12 - Nick
		1.0.3 2004-10-14 - Nick
		1.0.4 2004-10-21 - Nick
		1.0.5 2004-11-01 - Nick

	@change_log
		1.0.0 
			- Initial creation of class - Cash Lynk Class
		1.0.1
			- Added the rest of the API's into the class
		1.0.2
			- Modified to write to DB2 and not MySQL
		1.0.3
			- Modified to write to DB2 / MySQL / MSSQL
		1.0.4
			- PHP5 compliant
		1.0.5
			- Added GZ compression to the url and response.

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
			default:
			$this->db_type = "DB2";
			break;
		}
				
		if(!$debug)
		{
			// Live
			$this->url = 'https://www.cashlynk.com/AccessLynk/WebAPI.asp?';
			$this->msg_head = array('CID' => '250','CUSR' => 'Webapi250','CPWD' => 'Lynk123');		
			$this->economic_program = 606;
			$this->funding_program = 606;
		}
		else
		{
			// Dev
			$this->url = 'https://apidev.cashlynk.com/AccessLynk/WebAPI.asp?';
			$this->msg_head = array('CID' => '102','CUSR' => 'Webapi102','CPWD' => '123456');
			$this->economic_program = 165;
			$this->funding_program = 165;
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
		
		$record->cardholder_id = $data['social_security_number']['value'];
		$record->primary_card_holder = $record->cardholder_id;
		$record->dob = $data['date_of_birth']['value'];
		$record->middle_name = $data['name_middle']['value'];
		$record->opt_data_1 = $data['opt_data_1']['value'];
		$record->opt_data_2 = $data['opt_data_2']['value'];
		$record->opt_data_3 = $data['opt_data_3']['value'];
		
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
		if($data->name_first) { $msg['P2'] = $data->name_first; }
		if($data->name_middle) { $msg['P3'] = $data->name_middle; }
		if($data->name_last) { $msg['P4'] = $data->name_last; }
		if($data->address_1) { $msg['P5'] = $data->address_1; }
		if($data->address_2) { $msg['P6'] = $data->address_2; }
		if($data->city) { $msg['P7'] = $data->city; }
		if($data->state) { $msg['P8'] = $data->state; }
		if($data->zip) { $msg['P9'] = $data->zip; }
		if($data->phone) { $msg['P10'] = $data->phone; }
		if($data->cl_response->dob) { $msg['P11'] = $data->cl_response->dob; }
		$msg['P12'] = '0'; // If this is the primary card holder then 0 ... should always be the primary card holder
		if($data->cl_response->opt_data_1) { $msg['P13'] = $data->cl_response->opt_data_1; }
		if($data->cl_response->opt_data_2) { $msg['P14'] = $data->cl_response->opt_data_2; }
		if($data->cl_response->opt_data_3) { $msg['P15'] = $data->cl_response->opt_data_3; }
		if($data->notes) { $msg['P16'] = $data->notes; }
		if($data->email) { $msg['P17'] = $data->email; }
			
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
		
		if($data->social_security_number) { $msg['P1'] = trim($data->social_security_number); }	// SSN 1,9
		if($data->card_stock) { $msg['P2'] = $data->card_stock; }  else { $msg['P2'] = 0001;}	// Card Stock 1,4	
		if($data->card_bin) { $msg['P3'] = $data->card_bin; } else { $msg['P3'] = 603411;}		// Card BIN 6,6
		if($data->exp_month) { $msg['P4'] = $data->exp_month; } else { $msg['P4'] = 12; }		// Exp Month MM 1,2
		if($data->exp_year) { $msg['P5'] = $data->exp_year; } else { $msg['P5'] = 2010; }		// Exp Year YYYY 4,4
		if($data->pin) { $msg['P6'] = $data->pin; } else { $msg['P6'] = 6578; } 				// PIN 4-10
		if($data->opt_data_1) { $msg['P7'] = $data->opt_data_1; }
		if($data->option_2) { $msg['P8'] = $data->option_2; }
		if($data->option_3) { $msg['P9'] = $data->option_3; } 
		if($data->name_first) { $msg['P10'] = ucfirst($data->name_first)." ".ucfirst($data->name_last); }
		if($data->emboss_3) { $msg['P11'] = $data->emboss_3; }
		if($data->name_first) { $msg['P12'] = date("m/y"); }
		if($data->emboss_5) { $msg['P13'] = $data->emboss_5; }
		$msg['P14'] = $data->name_first." ".$data->name_last;
		$msg['P15'] = $data->address_1." ".$data->address_2;
		$msg['P16'] = $data->city; 
		$msg['P17'] = $data->state; 
		$msg['P18'] = $data->zip;
		if($data->member_number) { $msg['P19'] = $data->member_number; } 		// Card Number numeric
				
		// Send & receive the data
		$res = $this->Send_Get($msg);
				
		if($res['P1'] == '000')
		{
			$record->card_number = $res['P2'];
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
		$res = $this->Send_Get($msg);
		
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
	* @desc Deposit a specific amount of money to a card.
 	*/
	public function Deposit_To_Account($data)
	{
		$msg = $this->msg_head;
		$msg['FUNC'] = '007';
		$msg['MSGID'] = '';
		
		if($data->cl_response->card_number) 
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
	private function Send_Get($args)
	{
		// Start the audit and get and id
		$msg_id = $this->Audit_Trail();
		
		// Get the message id
		$args['MSGID'] = $this->Get_Msg_Id($msg_id);

		$nvp = array();
		foreach ($args as $k => $v)
		{
			$nvp[] = urlencode($k).'='.urlencode($v);
		}

		$url = $this->url . implode ('&', $nvp);
		
		ini_set('auto_detect_line_endings', 1);
		$response = @file($url);
		ini_set('auto_detect_line_endings', 0);

		// Update the audit trail, insert the response
		$this->Audit_Trail("update",$url,$response,$msg_id);

		$output = array();
		foreach($response as $line)
		{
			if(preg_match ('/^([^=]+)=(.*?)\r?\n?$/', $line, $m))
			{
				$output [$m[1]] = $m[2];
			}
		}

		return $output;
	}
	
	private function Audit_Trail($type="insert", $url=NULL, $response=NULL, $msg_id=NULL)
	{
		switch($this->db_type)
		{
			case "MYSQL":
			switch($type)	
			{
				case "update":
				$query = "
				UPDATE
					'".$this->table."'
				SET
					message = '".mysql_escape_string($url)."',
					message_response = '".mysql_escape_string(implode("\n", $response))."'
				WHERE
					id = '".$msg_id."'";
			
				$this->mysql->Query($this->db, $query);
				break;
				
				default:
				$query = "
				INSERT INTO
					`".$this->table."`
					(date_modified,date_created)
				VALUES 
					(NOW(),NOW())";
				
				$this->mysql->Query($this->db, $query);
						
				// Assign the id to the unique MSGID
				return $this->mysql->Insert_Id();
				break;
			}
			break;
			
			case "MSSQL":
			// Select the database
			mssql_select_db($this->db,$this->mssql);
			
			switch($type)	
			{
				case "update":
				$query = "
				UPDATE
					".$this->table."
				SET
					message = '".mysql_escape_string($url)."',
					message_response = '".mysql_escape_string(implode("\n", $response))."'
				WHERE
					id = '".$msg_id."'";
			
				$result = mssql_query($query);
				break;
				
				default:
				$query = "
				INSERT INTO
					".$this->table."
					(date_modified,date_created)
				VALUES 
					(GETDATE(),GETDATE())";
				
				$result = mssql_query($query);
						
				// Assign the id to the unique MSGID
				$query = "select @@IDENTITY";
				return mssql_query($query);
				break;
			}
			break;
			
			case "DB2":
			default:
			switch($type)	
			{
				case "update":
				
				// Compress the data to be stored.
				$url = gzcompress($url);
				$response = gzcompress($response);
				
				$query = "
				UPDATE
					`".$this->table."`
				SET
					sent_package = '".$url."',
					received_package = '".implode("\n", $response)."'
				WHERE
					message_id = '".$msg_id."'";

				try 
				{
					$this->db2->Execute($query);
				}
				catch(Db2_Exception $e)
				{
					echo __FILE__."<br>";
					print_r($e);	
				}
				break;
				
				case "insert":
				default:
				// Assign the id to the unique MSGID
				$unique_msg_id = str_replace('.','',microtime(TRUE));
				
				$query = "
				INSERT INTO
					`".$this->table."`
					(date_modified,date_created,customer_id,message_id)
				VALUES 
					(CURRENT TIMESTAMP,CURRENT TIMESTAMP,".$this->customer_id.",".$unique_msg_id.")";

				try 
				{
					$this->db2->Execute($query);
				}
				catch(Db2_Exception $e)
				{
					echo __FILE__."<br>";
					print_r($e);	
				}
						
				return $unique_msg_id;
				break;
			}
			break;
		}

		return TRUE;
	}
	
	/**
	* @return string
	* @param $id int
	* @desc Return the message associated with the id
 	*/
	private function Get_Msg_Id($id)
	{
		return str_pad($this->msg_head['CID'], 5, "0", STR_PAD_LEFT).str_pad($id, 11, "0", STR_PAD_LEFT);
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
				$data['cl_response'][$rec] = $value;	
			}
		}
		else
		{
			// Add to the error object
			foreach($record AS $rec=>$value)
			{
				$data['cl_error'][$rec] = $value;	
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
}
?>