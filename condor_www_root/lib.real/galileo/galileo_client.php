<?
/**
  @publicsection
  @public
  @brief
    A class to wrap api calls to Galileo.

  @version
  $Revision: 2762 $

  @todo


	NOTES:
		From:?"Ryan Berringer" <rberringer@galileoprocessing.com>
		To:?"Tom Clay" <tclay@galileoprocessing.com>, "Tony Morales"?<tony.morales@sellingsource.com>
		Date:?Tue, 20 Sep 2005 13:50:17 -0600
		Subject:?RE: loadhistory question

		Andy, Here is the login information we promised you.

		https://secure1.galileoprocessing.com/wstest/GalileoIntegration/GalileoIntegration.asmx
		provider_id = 43
		user_id = cubis
		password = password	


	NOTES:
		1.  I think our live IP address is:  71.4.57.131  (2005.12.08)

*/

include_once 'dlhdebug.php';
include_once 'http_client.1.php';


class Galileo_Client
{
	const RUN_MODE_MIN     = 1;
	const RUN_MODE_LIVE    = 1;
	const RUN_MODE_TEST    = 2;
	const RUN_MODE_NO_CALL = 3;
	const RUN_MODE_MAX     = 3;
	
	const GALILEO_URL_TEST  = 'https://secure1.galileoprocessing.com/wstest/GalileoIntegration/GalileoIntegration.asmx';     // Used for ptesting.
	const GALILEO_URL_LIVE  = 'https://secure1.galileoprocessing.com/ws/GalileoIntegration/GalileoIntegration.asmx';     // LIVE processing.
	const NO_CALL_TEXT      = 'Galileo_Client: NO URL Because run_mode = NO_CALL_TEXT';  // For debugging.
		
	private $http_client;     // holds an instantiated Http_Client object.
	private $run_mode;        // holds value of RUN_MODE_LIVE or RUN_MODE_TEST or RUN_MODE_NO_CALL.
	private $debug          = false;

	private $call_data_array; // An array of key=>value pairs of data to POST to Galileo.
	private $raw_response;    // Keeps the raw response from executing a call.
	private $providerUID;     // Web Service Username provided by Galileo (tied to IP address)
	private $providerPWD;     // Web Service Password provided by Galileo (tied to IP address)
	private $providerID;      // Unique providerID identifier (provided by Galileo???)
	private $sessionID;       // Required once connection is authorized.  Not sure if we
	                          //   want to keep $sessionID between calls or get a new one
	                          //   each time.  If we get a new one each time, we'll have to
	                          //   call OpenSession() ... do functions ... CloseSession() for
	                          //   each function call.  If we want to keep it between calls
	                          //   we'll have to store it in a database or memcache.
	                         
	public $desired_loadhistory_descriptions;  // list of loadhistory xml element descriptions we should return
	                                           // upon parsing xml from GetLoadHistory().  Change the list in
	                                           // the constructor to configure which elements we are interested
	                                           // in.

	protected static $galileo_aba_numbers = array(
		'124303065'
	);  // I have no idea what this is about.
	                                       
	protected $is_session_open;	                                       

	
  
	public function __construct()
	{
		if ($this->debug) dlhlog("__construct: entering method: ");
		$this->desired_loadhistory_descriptions = array( 'Payroll Load', 'ACH Load', 'Direct Deposit', 'TAB Direct Deposit', 'Mellon Bank Direct Deposit' );
		                                   
		$this->http_client = new Http_Client_1();
		$this->Set_Operating_Mode( self::RUN_MODE_LIVE );
		$this->is_session_open = false;
		// $this->Open_Session();  // We are no longer automatically opening the session when the this class
		                           // is instantiated because someone might first call the method Is_ABA_Number_Banned(&number)
		                           // and if that method returns true, there's no need to waste time opening a session.
	}
  
  
	public function __destruct()
	{
		
		if ($this->debug) dlhlog("__destruct: entering method: ");
		
		if ($this->is_session_open)
		{
			$this->Close_Session();
		}
		
		return;
		
	}
  
  
	public function Set_Operating_Mode( $mode )
	{
		if ($this->debug) dlhlog("Set_Operating_Mode: entering method: mode=$mode");
		
		$this->run_mode = $mode;
		
		switch ( $this->run_mode )
		{
			case self::RUN_MODE_TEST:
				$this->Set_Required_Login_Info();  // don't know test values
				break;
			case self::RUN_MODE_LIVE:
				$this->Set_Required_Login_Info('cubis', 'cubis123', '43');
				break;
			case self::RUN_MODE_NO_CALL:  // fall through to default
			default:
				$this->run_mode = self::RUN_MODE_NO_CALL;  // init in case an invalid value was passed in.
				$this->Set_Required_Login_Info();  
				break;
		}
	}
	
	
	public function Set_Required_Login_Info( $providerUID = 'cubis', $providerPWD = 'cubis123', $providerID = '43' )
	{
		// Test Values are:  $providerUID = '?????', $providerPWD = '????????', $providerID = '??'
		// Live Values are:  $providerUID = 'cubis', $providerPWD = 'cubis123', $providerID = '43'
	
		if ($this->debug) dlhlog("Set_Required_Login_Info: entering method: providerUID=$providerUID, providerPWD=$providerPWD, providerID=$providerID");

		$this->providerUID = $providerUID;
		$this->providerPWD = $providerPWD;
		$this->providerID  = $providerID;
	}
  
  
	public function Get_Url()
	{
		$url = '';
	
		switch ( $this->run_mode )
		{
			case self::RUN_MODE_LIVE : $url = self::GALILEO_URL_LIVE; break;
			case self::RUN_MODE_TEST : $url = self::GALILEO_URL_TEST; break;
			default                  : $url = self::NO_CALL_TEXT;     break;
		}

		return $url;
	}
  
  
	public function Run_Transaction( $method_name, $data_array = NULL )
	{
	
		if ( $data_array == NULL ) $data_array = $this->call_data_array;
		
		$url = $this->Get_Url() . '/' . $method_name;
	
		if ( $this->run_mode == self::RUN_MODE_NO_CALL ) return self::NO_CALL_TEXT;
	
		$result = $this->http_client->Http_Post( $url, $data_array );

		if ($this->debug) dlhlog( "Run_Transaction: result=$result, url=$url, data_array=" . dlhvardump($data_array) );
		
		return $result;
	}


	public function Get_Raw_Response()
	{
		return $this->raw_response;
	}


	public function Open_Session()
	{
		$this->Set_Login_Credentials();
		$response = $this->Run_Transaction( 'OpenSession' );

		// Old approach before I learned about simple_xml.
		// ------------------------------------------------
		// $result_array = array( 'string' => '' );
		// $this->Parse_Response( $response, $result_array );
		// $this->sessionID = $result_array['string'];
		// $this->call_data_array = array('SessionID' => $this->sessionID);  // reinitialize the call_data_array with just the session_id present.

		$xml = $this->Filter_Crap_From_Xml( $response );
		@$xmlobject = simplexml_load_string( $xml );      
		$this->sessionID = (string) $xmlobject;
		$this->is_session_open = true;

		if ($this->debug) dlhlog( "Open_Session: sessionID=" . $this->sessionID . ", response=$response" );
	}


	// The calling code can use this to determine if a valid galileo session id was obtained.
	// It appears that if a valid session id can NOT be obtained, it simply comes back as 0.
	// If the galileo session id is 0, then subsequent calls (like Unload) may simply return
	// absolutely nothing rather than indicating the session id is invalid.  Or subsequent
	// calls (like Load) might come back with a message that "Session timed out."
	
	public function Get_Session_Id()
	{
		return $this->sessionID;
	}


	public function Close_Session()
	{
		$tempArray = array('SessionID' => $this->sessionID);
		
		// I don't want to change $this->call_data_array so I'm using $tempArray.
		// I want to keep $this->call_data_array intact so that it can be
		// returned to show exactly what data was used in a call.
		
		$response = $this->Run_Transaction( 'CloseSession', $tempArray );
		$this->is_session_open = false;

		return $response;
		
		// expecting 'Retval' in response.
	}


	public function Get_Call_Data()
	{
		return $this->call_data_array;
	}


	// ************************************************************************************************************************
	// The following methods implement the various function calls available with galileo.  Galileo seems to prefer SOAP
	// processing but we are doing POST processing since they say they also support POST and GET.  Unfortunately, their
	// API docs indicate the SOAP method names to use but they don't tell us how to pass along a method indicator if
	// we're using POST or GET processing.
	//
	// The following method names copy the galileo SOAP method names.  We need to find out from galileo how to
	// pass along some kind of indicator to tell them which method we're trying to execute.
	// ************************************************************************************************************************
	
	public function Create( $transactionId, $product, $idType, $id, $idverify, $location, $locationType, $language, $firstName, $lastName, $middleName, $dob, $address1, $address2, $city, $state, $zip, $country, $primaryPhone, $otherPhone, $email, $webUID, $webPWD, $secretQuestion, $secretAnswer, $instantIssue )
	{
		$this->Set_Session_Id();   // This initialized the $call_data_array and makes sure the session is open.
		
		$this->call_data_array['TransactionId'] = $transactionId;
		$this->call_data_array['Product'] = $product;
		$this->call_data_array['IDType'] = $idType;
		$this->call_data_array['ID'] = $id;
		$this->call_data_array['IDVerify'] = $idverify;
		$this->call_data_array['Location'] = $location;
		$this->call_data_array['LocationType'] = $locationType;
		$this->call_data_array['Language'] = $language;
		$this->call_data_array['FirstName'] = $firstName;
		$this->call_data_array['LastName'] = $lastName;
		$this->call_data_array['MiddleName'] = $middleName;
		$this->call_data_array['DOB'] = $dob;
		$this->call_data_array['Address1'] = $address1;
		$this->call_data_array['Address2'] = $address2;
		$this->call_data_array['City'] = $city;
		$this->call_data_array['State'] = $state;
		$this->call_data_array['Zip'] = $zip;
		$this->call_data_array['Country'] = $country;
		$this->call_data_array['PrimaryPhone'] = $primaryPhone;
		$this->call_data_array['OtherPhone'] = $otherPhone;
		$this->call_data_array['Email'] = $email;
		$this->call_data_array['WebUID'] = $webUID;
		$this->call_data_array['WebPWD'] = $webPWD;
		$this->call_data_array['SecretQuestion'] = $secretQuestion;
		$this->call_data_array['SecretAnswer'] = $secretAnswer;
		$this->call_data_array['InstantIssue'] = $instantIssue;

		$response = $this->Run_Transaction( 'Create' );
		$this->raw_response = $response;
		return $response;

		// expecting Code, Desc, RetVal in response.
	}
	

	public function AddCard( $transactionId, $product, $location, $locationType, $idType, $id, $cardAssigned, $linkedCard, $shareBalance )
	{
		$this->Set_Session_Id();   // This initialized the $call_data_array and makes sure the session is open.
		
		$this->call_data_array['TransactionId'] = $transactionId;
		$this->call_data_array['Product'] = $product;
		$this->call_data_array['Location'] = $location;
		$this->call_data_array['LocationType'] = $locationType;
		$this->call_data_array['IDType'] = $idType;
		$this->call_data_array['ID'] = $id;
		$this->call_data_array['CardAssigned'] = $cardAssigned;
		$this->call_data_array['LinkedCard'] = $linkedCard;
		$this->call_data_array['ShareBalance'] = $shareBalance;

		$response = $this->Run_Transaction( 'AddCard' );
		$this->raw_response = $response;
		return $response;

		// expecting Code, Desc, RetVal in response.
	}
	

	public function Load( $transactionId, $account, $amount, $loadMethod, $location, $locationType )
	{
		$this->Set_Session_Id();   // This initialized the $call_data_array and makes sure the session is open.
		
		$this->call_data_array['TransactionId'] = $transactionId;
		$this->call_data_array['Account'] = $account;
		$this->call_data_array['Amount'] = $amount;
		$this->call_data_array['LoadMethod'] = $loadMethod;
		$this->call_data_array['Location'] = $location;
		$this->call_data_array['LocationType'] = $locationType;

		$response = $this->Run_Transaction( 'Load' );
		$this->raw_response = $response;
		return $response;

		// expecting Code, Desc, RetVal in response.
	}
	

	public function ReverseLoad( $transactionId, $account, $amount, $loadMethod, $location, $locationType )
	{
		$this->Set_Session_Id();   // This initialized the $call_data_array and makes sure the session is open.
		
		$this->call_data_array['TransactionId'] = $transactionId;
		$this->call_data_array['Account'] = $account;
		$this->call_data_array['Amount'] = $amount;
		$this->call_data_array['LoadMethod'] = $loadMethod;
		$this->call_data_array['Location'] = $location;
		$this->call_data_array['LocationType'] = $locationType;

		$response = $this->Run_Transaction( 'ReverseLoad' );
		$this->raw_response = $response;
		return $response;

		// expecting Code, Desc in response.
	}
	

	public function Balance( $transactionId, $account )
	{
		$this->Set_Session_Id();   // This initialized the $call_data_array and makes sure the session is open.
		
		$this->call_data_array['TransactionId'] = $transactionId;
		$this->call_data_array['Account'] = $account;

		$response = $this->Run_Transaction( 'Balance' );
		$this->raw_response = $response;
		return $response;

		// expecting Code, Desc, RetVal in response.
	}
	

	public function Verify( $transactionId, $account )
	{
		$this->Set_Session_Id();   // This initialized the $call_data_array and makes sure the session is open.
		
		$this->call_data_array['TransactionId'] = $transactionId;
		$this->call_data_array['Account'] = $account;

		$response = $this->Run_Transaction( 'Verify' );
		$this->raw_response = $response;
		return $response;

		// expecting Code, Desc, RetVal in response.
	}
	

	public function ModifyStatus( $transactionId, $account, $status )
	{
		$this->Set_Session_Id();   // This initialized the $call_data_array and makes sure the session is open.
		
		$this->call_data_array['TransactionId'] = $transactionId;
		$this->call_data_array['Account'] = $account;
		$this->call_data_array['Status'] = $status;

		$response = $this->Run_Transaction( 'ModifyStatus' );
		$this->raw_response = $response;
		return $response;

		// expecting Code, Desc, RetVal in response.
	}
	

	public function ACHInfo( $transactionId, $account )
	{
		$this->Set_Session_Id();   // This initialized the $call_data_array and makes sure the session is open.
		
		$this->call_data_array['TransactionId'] = $transactionId;
		$this->call_data_array['Account'] = $account;

		$response = $this->Run_Transaction( 'ACHInfo' );
		$this->raw_response = $response;
		return $response;

		// expecting Code, Desc, rt_no, acct_type, status_desc,
		// start_date, stop_date, freq_type, amt, acct_no in response.
	}
	

	public function Modify( $transactionId, $language, $firstName, $lastName, $middleName, $dob, $address1, $address2, $city, $state, $zip, $country, $primaryPhone, $otherPhone, $email, $webUID, $webPWD, $secretQuestion, $secretAnswer, $instantIssue )
	{
		$this->Set_Session_Id();   // This initialized the $call_data_array and makes sure the session is open.
		
		$this->call_data_array['TransactionId'] = $transactionId;
		$this->call_data_array['Language'] = $language;
		$this->call_data_array['FirstName'] = $firstName;
		$this->call_data_array['LastName'] = $lastName;
		$this->call_data_array['MiddleName'] = $middleName;
		$this->call_data_array['DOB'] = $dob;
		$this->call_data_array['Address1'] = $address1;
		$this->call_data_array['Address2'] = $address2;
		$this->call_data_array['City'] = $city;
		$this->call_data_array['State'] = $state;
		$this->call_data_array['Zip'] = $zip;
		$this->call_data_array['Country'] = $country;
		$this->call_data_array['PrimaryPhone'] = $primaryPhone;
		$this->call_data_array['OtherPhone'] = $otherPhone;
		$this->call_data_array['Email'] = $email;
		$this->call_data_array['WebUID'] = $webUID;
		$this->call_data_array['WebPWD'] = $webPWD;
		$this->call_data_array['SecretQuestion'] = $secretQuestion;
		$this->call_data_array['SecretAnswer'] = $secretAnswer;
		$this->call_data_array['InstantIssue'] = $instantIssue;

		$response = $this->Run_Transaction( 'Modify' );
		$this->raw_response = $response;
		return $response;

		// expecting Code, Desc, RetVal in response.
	}
	

	public function History( $transactionId, $account, $start, $end )
	{
		$this->Set_Session_Id();   // This initialized the $call_data_array and makes sure the session is open.
		
		$this->call_data_array['TransactionId'] = $transactionId;
		$this->call_data_array['Account'] = $account;
		$this->call_data_array['Start'] = $start;
		$this->call_data_array['End'] = $end;

		$response = $this->Run_Transaction( 'History' );
		$this->raw_response = $response;
		return $response;

		// expecting Code, Desc, trace_no, rt_no, acct_no, deb_cred_ind
		// achtype, status, amt, trans_date, desc, ach_type_desc
		// status_desc in response.
	}
	

	public function Account_Transfer( $transactionId, $senderAcct, $receiverAcct, $amount, $desc )
	{
		$this->Set_Session_Id();   // This initialized the $call_data_array and makes sure the session is open.
		
		$this->call_data_array['TransactionId'] = $transactionId;
		$this->call_data_array['SenderAcct'] = $senderAcct;
		$this->call_data_array['ReceiverAcct'] = $receiverAcct;
		$this->call_data_array['Amount'] = $amount;
		$this->call_data_array['Desc'] = $desc;

		$response = $this->Run_Transaction( 'Account_Transfer' );
		$this->raw_response = $response;
		return $response;

		// expecting Code, Desc, RetVal in response.
	}
	

	public function ACHSetupMethod( $transactionId, $validateProduct, $startDate, $stopDate, $frequencyType, $amount, $accountType, $bankAcctNo, $routingNo, $account, $status )
	{
		$this->Set_Session_Id();   // This initialized the $call_data_array and makes sure the session is open.
		
		$this->call_data_array['TransactionId'] = $transactionId;
		
		$this->call_data_array['ValidateProduct'] = $validateProduct;
		$this->call_data_array['startDate'] = $startDate;
		$this->call_data_array['stopDate'] = $stopDate;
		$this->call_data_array['FrequencyType'] = $frequencyType;
		$this->call_data_array['Amount'] = $amount;
		$this->call_data_array['accountType'] = $accountType;
		$this->call_data_array['BankAcctNo'] = $bankAcctNo;
		$this->call_data_array['routingNo'] = $routingNo;
		$this->call_data_array['account'] = $account;
		$this->call_data_array['status'] = $status;

		$this->raw_response = $this->Run_Transaction( 'ACHSetup_Method' );
		$this->raw_response = $response;
		return $response;

		// expecting Code, Desc, RetVal in response.
	}
	

	public function GetLoadHistory( $transactionId, $account )
	{
		$this->Set_Session_Id();   // This initialized the $call_data_array and makes sure the session is open.
		
		$this->call_data_array['TransactionId'] = $transactionId;
		$this->call_data_array['Account'] = $account;

		$response = $this->Run_Transaction( 'GetLoadHistory' );
		$this->raw_response = $response;
		return $response;

		// expecting Code, Desc, Pmt_id, Date, Amount, Pmt_desc in response.
	}
	

	// This is a special convenient version of GetLoadHistory().  It simply packages the
	// three items of interest for method GetLoadHistory() into a single associative
	// array.  The three items of interest are:  (1) package we sent to galileo,
	// (2) xml response we received from galileo, (3) xml response filtered down
	// to an array of loadhistory elements we are interested in as defined by
	// $desired_loadhistory_descriptions (see constructor).  For #3, the filtered
	// array of interesting loadhistory elements, each value is a subarray containing
	// elements: pmt_id, date, amount, pmt_desc.  Doing a count
	// on $result_array['FILTERED_PAYROLL_ITEMS'] will tell you how many deposits
	// were reported by galileo.
	
	public function Get_Payroll_Load_History( $transactionId, $account )
	{
		$this->Set_Session_Id();   // This initialized the $call_data_array and makes sure the session is open.
		
		$this->call_data_array['TransactionId'] = $transactionId;
		$this->call_data_array['Account'] = $account;

		$response = $this->Run_Transaction( 'GetLoadHistory' );
		$this->raw_response = $response;

		$result_array = array();
		$result_array['SENT']                   = $this->Get_Call_Data();
		$result_array['RECEIVED']               = $response;
		$result_array['FILTERED_PAYROLL_ITEMS'] = $this->Parse_GetLoadHistory_Xml( $response ); 

		return $result_array;

		// expecting Code, Desc, Pmt_id, Date, Amount, Pmt_desc in response.
	}
	

	public function Unload( $transactionId, $account, $amount, $location, $locationType )
	{
		$this->Set_Session_Id();   // This initialized the $call_data_array and makes sure the session is open.
		
		$this->call_data_array['TransactionId'] = $transactionId;
		$this->call_data_array['Account'] = $account;
		$this->call_data_array['Amount'] = $amount;
		$this->call_data_array['Location'] = $location;
		$this->call_data_array['LocationType'] = $locationType;

		$response = $this->Run_Transaction( 'Unload' );
		$this->raw_response = $response;
		return $response;

		// return $this->Parse_Response( $response );
		// expecting Code, Desc, Pmt_id, Date, Amount, Pmt_desc in response.
	}
	
	// ************************************************************************************************************************
	// That's the end of the galileo method calls.  The following are utility methods for this class.
	// ************************************************************************************************************************


	public function Parse_GetLoadHistory_Xml( $xml )
	{
		$filtered_loadhistory = array();
		$xml = $this->Filter_Crap_From_Xml( $xml );
		@$xmlobject = simplexml_load_string( $xml );

		if ( isset($xmlobject) && isset($xmlobject->response) && isset($xmlobject->response->loadhistory) )
		{ 
			foreach( $xmlobject->response->loadhistory as $loadhistory )
			{
				$pmt_id   = (string)$loadhistory->pmt_id;
				$date     = (string)$loadhistory->date;
				$amount   = (string)$loadhistory->amount;
				$pmt_desc = (string)$loadhistory->pmt_desc;
		
				if ( in_array($pmt_desc, $this->desired_loadhistory_descriptions) )
				{
					$filtered_loadhistory[] = array( 'pmt_id' => $pmt_id, 'date' => $date, 'amount' => $amount, 'pmt_desc' => $pmt_desc );
				}
			}
		}

		return $filtered_loadhistory;
	}
	

	// Not going to use this approach anymore.  Andrew told me about simple_xml and it's much easier.
	public function Parse_Response( &$xml_input, &$array_of_desired_values )
	{
		$vals = NULL;
		$tags = NULL;
	
		$xml  = trim($xml_input);
		$xml_parser = xml_parser_create();
		xml_parser_set_option($xml_parser,XML_OPTION_CASE_FOLDING,0);
		xml_parser_set_option($xml_parser,XML_OPTION_SKIP_WHITE,1);
		$parseReturnCode = xml_parse_into_struct($xml_parser, $xml, $vals, $tags);
		xml_parser_free($xml_parser);

		foreach( $array_of_desired_values as $key => $val )
		{
			$subscript = $tags[$key][0];
			if ($this->debug) dlhlog( "Parse_Response: processing key=$key, subscript=$subscript, vals[" . $subscript . "]=" . dlhvardump($vals[$subscript]) );
			$array_of_desired_values[$key] = $vals[$subscript]['value'];
		}
	}


	// The galileo xml response has some stuff in it that chokes simple_xml;
	function Filter_Crap_From_Xml( $xml )
	{
		$pos_xml_open_tag = strpos( $xml, '<?xml' );
		if ($pos_xml_open_tag === false)
		{
			// opening xml tag not found???  What to do with that???  Need to throw an exception or something.
		}
		else
		{
			$xml = substr( $xml, $pos_xml_open_tag );
		}
		
		$xml_return = str_replace( 'xmlns="ProgramWSDL"', '', $xml );
		$xml_return = str_replace( 'xmlns:xsd="http://www.w3.org/2001/XMLSchema"', '', $xml_return );
		$xml_return = str_replace( 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"', '', $xml_return );
		return $xml_return;
	}
	


	protected function Get_Key_Value_Urldecoded( $str, &$key, &$val, $separator='=' )
	{
		$key = '';
		$val = '';
		
		if ( !isset($str) || $str == '' ) return;
		$pos = strpos( $str, $separator );
		if ( !is_numeric($pos) ) {
			$val = $str;
			return;
		}

		$len = strlen($str);
		$key = urldecode(substr( $str, 0, $pos ));
		$val = urldecode(substr( $str, -($len - $pos - 1) ));
	}
	

	public function Set_Debug( $debug_value )
	{
		$this->debug = $debug_value ? true : false;
	}
	
	
	public static function Is_ABA_Number( $aba_number )
	{
		if ( !isset( $aba_number ) || !is_numeric($aba_number) ) return false;
		if ( in_array($aba_number, self::$galileo_aba_numbers) ) return true;
		return false;
	}


	protected function is_field_populated ( &$field )
	{
		if ( isset($field) && strlen($field) > 0 ) return true;
		return false;
	}
	
	
	protected function make_not_null ( $txt )
	{
		return isset($txt) ? $txt : '';
	}

	
	protected function Set_Login_Credentials()
	{
		// This will reinitialize the call_data_array with the login fields.
		$this->call_data_array = array();
		$this->call_data_array['provider_uid'] = $this->providerUID;
		$this->call_data_array['provider_pwd'] = $this->providerPWD;
		$this->call_data_array['provider_id']  = $this->providerID;
	}
  
  
	protected function Set_Session_Id()
	{
		if ( !$this->is_session_open )
		{
			$this->Open_Session();
		}
	
		// This will reinitialize the call_data_array and the only field in it will be SessionID.
		$this->call_data_array = array('SessionID' => $this->sessionID);
	}
  
  
}
