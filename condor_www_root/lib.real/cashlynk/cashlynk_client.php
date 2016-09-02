<?
/**
  @publicsection
  @public
  @brief
    A class to wrap api calls to Cashlynk.

    CashLynk is a third-party credit card processor used by SellingSource.com.
    It's part of the company Lynk Systems, Inc.

    Example call 1:
      $cashlynk = new Cashlynk_Client();
      $cashlynk->Set_Operating_Mode( Cashlynk_Client::RUN_MODE_LIVE );
      $cashlynk->Set_Transaction_Type( Cashlynk_Client_Data::TYPE_CREATE_CARD_HOLDER );
      $cashlynk->Set_Field ( 'CardholderID/SSN', '123121234' );
      $result = $cashlynk->Run_Transaction();

    Example call 2 (using convenience method):
      $cashlynk = new Cashlynk_Client();
      $cashlynk->Set_Operating_Mode( Cashlynk_Client::RUN_MODE_LIVE );
	  $result = $cashlynk->Create_Card_Holder( $ssn );

  @version
  $Revision: 2494 $

  @todo
    1.  In parsing xml, I'm assuming that authnumber is the appropriate unique
        identifier for a transaction.  Arrays $transaction_detail and
        $transaction_description are both keyed by authnumber. Double check
        this assumption.

    2.  The documentation on the xml returned from transactions 009, 010, 020
        had some typo errors in the xml.  I fixed the errors in my sample xml.
        For example, for transaction 009 the documented xml had "totaldedits".
        I'm assuming they meant "totaldebits".  Also, for transation 010
        the documented xml had </ primarycardaccount>.  The space after the "/"
        crashed the xml parser so I'm assuming that was a typo.
*/

defined('DIR_LIB') || define ('DIR_LIB', '/virtualhosts/lib/');

// require_once( DIR_LIB . 'dlhdebug.php' );
require_once( DIR_LIB . 'http_client.1.php' );
require_once( DIR_LIB . 'cashlynk/cashlynk_client_data.php' );


class Cashlynk_Client
{
	const RUN_MODE_MIN                       = 1;
	const RUN_MODE_LIVE                      = 1;    // 500 Fast Cash
	const RUN_MODE_TEST                      = 2;    // 500 Fast Cash
	const RUN_MODE_NO_CALL                   = 3;
	const RUN_MODE_TEST_FCP                  = 4;    // FCP
	const RUN_MODE_LIVE_FCP                  = 5;    // FCP
	const RUN_MODE_MAX                       = 5;
	
	const CASHLYNK_URL_TEST = 'https://apidev.cashlynk.com/Issuing/AccessLynk/WebAPI.aspx'; // Used for testing.
	// const CASHLYNK_URL_LIVE = 'https://www.cashlynkMC.com/AccessLynk/WebAPI.aspx';       // LIVE processing. Works same as cashlynk.com.
	const CASHLYNK_URL_LIVE = 'https://www.cashlynk.com/AccessLynk/WebAPI.aspx';            // LIVE processing. Works same as cashlynkMC.com
	const NO_CALL_TEXT      = 'Cashlynk_Client: NO URL Because run_mode = NO_CALL_TEXT';    // For debugging, use this text as the response from RBS Lynk.
		
	// Transactions 009, 010, and 020 return XML.  The convenience methods for these transactions automatically
	// parse the XML into a variety of arrays making it extremely easy to get at information.
	// The convenience methods will return the detail arrays in a master associative array
	// keyed by the following subscripts.
	
	const CARDPANS                = 'CARDPANS';
	const CARDACCOUNTS            = 'CARDACCOUNTS';
	const SUMMARY                 = 'SUMMARY';
	const TRANSACTIONS_LIST       = 'TRANSACTIONS_LIST';
	const TRANSACTION_DETAIL      = 'TRANSACTION_DETAIL';
	const TRANSACTION_DESCRIPTION = 'TRANSACTION_DESCRIPTION';
	const CARDACCOUNTS_BY_NUMBER  = 'CARDACCOUNTS_BY_NUMBER';
	const P1_RETURN_CODE          = 'P1';
	const ERRORMSG_RETURN_CODE    = 'ErrorMsg';
	
		
	private $http_client;             // holds an instantiated Http_Client object.
	private $cashlynk_client_data;    // holds an instantiated Cashlynk_Client_Data object.
	private $run_mode;                // holds value of RUN_RUN_MODE_LIVE or RUN_RUN_MODE_TEST or RUN_MODE_NO_CALL.
	private $raw_response;            // holds the raw, unparsed response.
	private $raw_data_sent;           // holds the raw data that was sent to cashlynk.
	private $msg_id;                  // holds the last message id.
	
	private $cardpans                = array();  // see documentation for function Get_Xml_Result_Array_Cardpans()
	private $cardaccounts            = array();  // see documentation for function Get_Xml_Result_Array_Cardaccounts()
	private $summary                 = array();  // see documentation for function Get_Xml_Result_Array_Summary()
	private $transactions_list       = array();  // see documentation for function Get_Xml_Result_Array_Transaction_List()
	private $transaction_detail      = array();  // see documentation for function Get_Xml_Result_Array_Transaction_Detail()
	private $transaction_description = array();  // see documentation for function Get_Xml_Result_Array_Transaction_Description()
	private $cardaccounts_by_number  = array();  // see documentation for function Get_Xml_Card_Accounts_By_Number()

	private $program_id;          // Don't know what this is.  Don't know if it's same as economic_program_id or funding_program_id.  
	private $card_member_number;  // 0 means primary card, 1 means secondary card; both on same bank account. We don't currently have secondary cards.
	private $card_stock;          // Don't know what this is.
	private $card_bin;            // Bank identification number.
	
	
  
	public function __construct()
	{
		$this->http_client = new Http_Client_1();
		$this->cashlynk_client_data = new Cashlynk_Client_Data( Cashlynk_Client_Data::TYPE_CREATE_CARD_HOLDER ); // for default, set datastructure for MOTO SALE.
		$this->Set_Operating_Mode( self::RUN_MODE_NO_CALL );
	}
  
  
	public function Set_Operating_Mode( $mode )
	{
		$this->run_mode = $mode;
		
		$this->raw_response = '';
		$this->Init_Xml_Result_Arrays();
		$this->cashlynk_client_data->Reset_State();
		
		switch ( $this->run_mode )
		{
			case self::RUN_MODE_TEST:
				// $this->Set_Required_Login_Info('102', 'Webapi102', '123456');
				// $this->Set_Required_Login_Info('102', 'Webapi98', '123456');
				$this->Set_Required_Login_Info('114', 'webapi114', '123456789');

				$this->Set_Program_Id( '178' );
				$this->Set_Card_Member_Number( '0' );
				$this->Set_Card_Stock( '1' );
				$this->Set_Card_BIN( '6034110' );
	
				break;
				
			case self::RUN_MODE_LIVE:
				// $this->Set_Required_Login_Info('250', 'Webapi250', 'Lynk123');     // Unspecified Error. You do not have permission to perform this function
				// $this->Set_Required_Login_Info('306', 'Webapi250', 'Lynk123');     // Unspecified Error. Permission Denied, You do not have permission to perform this function for this client
				// $this->Set_Required_Login_Info('0306', 'Webapi250', 'Lynk123');    // Unspecified Error. Permission Denied, You do not have permission to perform this function for this client
				// $this->Set_Required_Login_Info('306', 'Webapi306', 'Lynk123');     // Unspecified Error. Login failed.
				// $this->Set_Required_Login_Info('306', 'webapi306', 'cl596506');
				// $this->Set_Required_Login_Info('306', 'webapi306', 'cl596506');
				// $this->Set_Required_Login_Info('306', 'Webapi250', 'cl596506');
				// $this->Set_Required_Login_Info('306', 'webapi250', 'cl596506');
				// $this->Set_Required_Login_Info('0306', 'Webapi0306', 'Lynk123');   // Unspecified Error - Contact support
				// $this->Set_Required_Login_Info('306', 'webapi114', 'Lynk123');     // Unspecified Error. Login failed.
				// $this->Set_Required_Login_Info('306', 'webapi114', '123456789');   // Unspecified Error. Login failed.
				$this->Set_Required_Login_Info('306', 'webapi306', '3065965nm');      // This password worked better!  (2005.09.22)

				$this->Set_Program_Id( '3853' );
				$this->Set_Card_Member_Number( '0' );
				$this->Set_Card_Stock( '132' );
				$this->Set_Card_BIN( '5151260' );
	
				break;

			case self::RUN_MODE_TEST_FCP:
				// $this->Set_Required_Login_Info('250', 'Webapi250', 'Lynk123');
				$this->Set_Required_Login_Info('110', 'Webapi110', 'lynk1234');  // new from Lee Butler 2006.01.17
				$this->Set_Program_Id( '173' );
				$this->Set_Card_Member_Number( '0' );
				$this->Set_Card_Stock( '1' );
				$this->Set_Card_BIN( '6034110' );
				break;
			
			case self::RUN_MODE_LIVE_FCP:
				$this->Set_Required_Login_Info('250', 'Webapi250', 'Lynk123');
				$this->Set_Program_Id( '606' );
				$this->Set_Card_Member_Number( '0' );
				$this->Set_Card_Stock( '1' );
				$this->Set_Card_BIN( '6034110' );
				break;
				
			default:
				$this->run_mode = self::RUN_MODE_NO_CALL;
				$this->Set_Required_Login_Info('114', 'webapi114', '123456789');
	
				$this->Set_Program_Id( '178' );
				$this->Set_Card_Member_Number( '0' );
				$this->Set_Card_Stock( '1' );
				$this->Set_Card_BIN( '6034110' );
	
				
				break;
		}
	}
	
	
	public function Set_Required_Login_Info( $cid = '114', $cusr = 'webapi114', $cpwd = '123456789' )
	{
		$this->cashlynk_client_data->Set_Field('CID',  $cid);
		$this->cashlynk_client_data->Set_Field('CUSR', $cusr);
		$this->cashlynk_client_data->Set_Field('CPWD', $cpwd);
	}
  
  
	public function Set_Transaction_Type( $type )
	{
		$this->cashlynk_client_data->Set_Type($type);
		$this->Init_Xml_Result_Arrays();
	}
	
	
	public function Init_Xml_Result_Arrays()
	{
		$this->cardpans                = array();
		$this->cardaccounts            = array();
		$this->summary                 = array();
		$this->transactions_list       = array();
		$this->transaction_detail      = array();
		$this->transaction_description = array();
	}
  
  
	public function Get_Url()
	{
		switch ( $this->run_mode )
		{
			case self::RUN_MODE_LIVE :         return self::CASHLYNK_URL_LIVE; break;
			case self::RUN_MODE_TEST :         return self::CASHLYNK_URL_TEST; break;
			case self::RUN_MODE_TEST_FCP:      return self::CASHLYNK_URL_TEST; break;
			case self::RUN_MODE_LIVE_FCP:      return self::CASHLYNK_URL_LIVE; break;
			default:                           return self::NO_CALL_TEXT;
		}
	}
  
  
	public function Get_Transaction_Types()
	{
		return $this->cashlynk_client_data->Get_Types();
	}
	
	
	public function Validate_Fields_Are_Populated ( &$fields_in_error )
	{
		return $this->cashlynk_client_data->Validate_Fields_Are_Populated( $fields_in_error );
	}


	public function Get_Raw_Response()
	{
		return $this->raw_response;
	}


	public function Get_Raw_Data_Sent()
	{
		return $this->raw_data_sent;
	}


	public function Get_Msg_Id()
	{
		return $this->msg_id;
	}


	public function Get_Program_Id()
	{
		return $this->program_id;
	}


	public function Set_Program_Id( $program_id )
	{
		$this->program_id = $program_id;
	}
  
  
	public function Get_Card_Member_Number()
	{
		return $this->card_member_number;
	}


	public function Set_Card_Member_Number( $card_member_number )
	{
		$this->card_member_number = $card_member_number;
	}
  
  
	public function Get_Card_Stock()
	{
		return $this->card_stock;
	}


	public function Set_Card_Stock( $card_stock )
	{
		$this->card_stock = $card_stock;
	}
  
  
	public function Get_Card_BIN()
	{
		return $this->card_bin;
	}


	public function Set_Card_BIN( $card_bin )
	{
		$this->card_bin = $card_bin;
	}
  
  
	// ***********************************************************************************
	// The following are convenience methods for executing transaction and getting
	// results as easily as possible.  You can call these convenience methods or you
	// can do exactly what the convenience method does - set the fields
	// using Set_Field(...) and then call Run_Transaction().
	//
	// The easy way to find out exactly which fields are required or optional
	// and exactly what those fields are named is to use the
	// screen "cashlynk_client_tester.php".  Using that screen, simply
	// pick a transaction type and the screen will tell you which fields
	// are required or optional and what their names are.  You can enter
	// data on that screen for the fields and submit the transaction and get
	// the result interactively.
	// ***********************************************************************************
	
	// 001
	public function Create_Card_Holder( $cardHolderId, $primaryCardholderId='0', $question='', $answer='', $firstName='', $middleInitial='', $lastName='', $address1='', $address2='', $city='', $state='', $zip='', $phone='', $dob='', $optionalData1='', $optionalData2='', $optionalData3='', $notes='', $email='', $photoId='')
	{
		$this->Set_Transaction_Type( Cashlynk_Client_Data::TYPE_CREATE_CARD_HOLDER );
		
		$this->Set_Field ( 'CardholderID/SSN', $this->make_not_null($cardHolderId) );
		$this->Set_Field ( 'Primary CardholderID/SSN', $this->make_not_null($primaryCardholderId) );
		$this->Set_Field ( 'Question', $this->make_not_null($question) );
		$this->Set_Field ( 'Answer', $this->make_not_null($answer) );
		$this->Set_Field ( 'First Name', $this->make_not_null($firstName) );
		$this->Set_Field ( 'Middle Initial', $this->make_not_null($middleInitial) );
		$this->Set_Field ( 'Last Name', $this->make_not_null($lastName) );
		$this->Set_Field ( 'Address 1', $this->make_not_null($address1) );
		$this->Set_Field ( 'Address 2', $this->make_not_null($address2) );
		$this->Set_Field ( 'City', $this->make_not_null($city) );
		$this->Set_Field ( 'State', $this->make_not_null($state) );
		$this->Set_Field ( 'Zip Code', $this->make_not_null($zip) );
		$this->Set_Field ( 'Phone', $this->make_not_null($phone) );
		$this->Set_Field ( 'DOB', $this->make_not_null($dob) );
		$this->Set_Field ( 'Email', $this->make_not_null($email) );
		$this->Set_Field ( 'Optional Data1', $this->make_not_null($optionalData1) );
		$this->Set_Field ( 'Optional Data2', $this->make_not_null($optionalData2) );
		$this->Set_Field ( 'Optional Data3', $this->make_not_null($optionalData3) );
		$this->Set_Field ( 'Notes', $this->make_not_null($notes) );
		$this->Set_Field ( 'Photo ID', $this->make_not_null($photoId) );
	
		$result = $this->Run_Transaction();
		return $this->Get_Text_Response_Parsed( $result );
	}
	
	// 002
	public function Create_Card( $Cardholder_ID_SSN, $ExpMonthMM, $ExpYearYYYY, $PIN, $OptionalData1, $OptionalData2, $OptionalData3, $EmbossingLine2, $EmbossingLine3, $EmbossingLine4, $EmbossingLine5, $ShipName, $ShipAddress, $ShipCity, $ShipState, $ShipZipCode, $RefNo, $CardAccountNumber, $PrimaryCardNumber )
	{
		$this->Set_Transaction_Type( Cashlynk_Client_Data::TYPE_CREATE_CARD );
		
		$this->Set_Field ( 'CardholderID/SSN', $this->make_not_null($Cardholder_ID_SSN) );

		$this->Set_Field ( 'Card Stock', $this->Get_Card_Stock() );
		$this->Set_Field ( 'Card BIN', $this->Get_Card_BIN() );

		$this->Set_Field ( 'Exp Month (MM)', $this->make_not_null($ExpMonthMM) );
		$this->Set_Field ( 'Exp Year (YYYY)', $this->make_not_null($ExpYearYYYY) );
		$this->Set_Field ( 'PIN', $this->make_not_null($PIN) );
		$this->Set_Field ( 'Optional Data1', $this->make_not_null($OptionalData1) );
		$this->Set_Field ( 'Optional Data2', $this->make_not_null($OptionalData2) );
		$this->Set_Field ( 'Optional Data3', $this->make_not_null($OptionalData3) );
		$this->Set_Field ( 'Embossing Line 2', $this->make_not_null($EmbossingLine2) );
		$this->Set_Field ( 'Embossing Line 3', $this->make_not_null($EmbossingLine3) );
		$this->Set_Field ( 'Embossing Line 4  	P', $this->make_not_null($EmbossingLine4) );
		$this->Set_Field ( 'Embossing Line 5', $this->make_not_null($EmbossingLine5) );
		$this->Set_Field ( 'Ship Name', $this->make_not_null($ShipName) );
		$this->Set_Field ( 'Ship Address', $this->make_not_null($ShipAddress) );
		$this->Set_Field ( 'Ship City', $this->make_not_null($ShipCity) );
		$this->Set_Field ( 'Ship State', $this->make_not_null($ShipState) );
		$this->Set_Field ( 'Ship Zip', $this->make_not_null($ShipZipCode) );
		
		$this->Set_Field ( 'Card Member Number', $this->Get_Card_Member_Number() );

		$this->Set_Field ( 'RefNo', $this->make_not_null($RefNo) );
		$this->Set_Field ( 'Card Account Number', $this->make_not_null($CardAccountNumber) );
		$this->Set_Field ( 'Primary Card Number', $this->make_not_null($PrimaryCardNumber) );
		
		$result = $this->Run_Transaction();
		return $this->Get_Text_Response_Parsed( $result );
	}
	
	// 003
	public function Create_Card_Account( $cardAccountApplication, $cardNumber, $maximumBalance='', $depositCycleLimit='', $depositCycleLimit2='', $transferCycleLimit='', $transferCycleLimit2='', $cycle2Days='' )
	{
		$this->Set_Transaction_Type( Cashlynk_Client_Data::TYPE_CREATE_CARD_ACCOUNT );
		
		$this->Set_Field ( 'Card Member Number', $this->Get_Card_Member_Number() );
		$this->Set_Field ( 'Card Account Application', $this->make_not_null($cardAccountApplication) );
		$this->Set_Field ( 'Economic Program ID', $this->Get_Program_Id() );
		$this->Set_Field ( 'Funding Program ID', $this->Get_Program_Id() );
		$this->Set_Field ( 'Card Number', $this->make_not_null($cardNumber) );
		$this->Set_Field ( 'Maximum Balance', $this->make_not_null($maximumBalance) );
		
		$this->Set_Field ( 'Deposit Cycle Limit', $this->make_not_null($depositCycleLimit) );
		$this->Set_Field ( 'Deposit Cycle Limit2', $this->make_not_null($depositCycleLimit2) );
		$this->Set_Field ( 'Transfer Cycle Limit', $this->make_not_null($transferCycleLimit) );
		$this->Set_Field ( 'Transfer Cycle Limit2', $this->make_not_null($transferCycleLimit2) );
		$this->Set_Field ( 'Cycle 2 Days', $this->make_not_null($cycle2Days) );
		
		$result = $this->Run_Transaction();
		return $this->Get_Text_Response_Parsed( $result );
	}
	
	// 004
	public function Change_Card_Status( $cardNumber, $new_status )
	{
		$status = '';
		
		switch( $new_status )
		{
			case 'enabled'             : $status = 1; break;
			case 'disabled'            : $status = 2; break;
			case 'hold'                : $status = 3; break;
			case 'waiting_pin'         : $status = 4; break;
			case 'lost_stolen'         : $status = 5; break;
			case 'pin_fails'           : $status = 6; break;
			default                    : $status = 1; break;
		}
	
		$this->Set_Transaction_Type( Cashlynk_Client_Data::TYPE_CHANGE_CARD_STATUS );
		
		$this->Set_Field ( 'Card Number', $this->make_not_null($cardNumber) );
		$this->Set_Field ( 'Card Member Number', $this->Get_Card_Member_Number() );
		$this->Set_Field ( 'New Status', $status );
		
		$result = $this->Run_Transaction();
		return $this->Get_Text_Response_Parsed( $result );
	}
	
	// 005
	public function Change_Pin( $cardNumber, $oldPin, $newPin )
	{
		$this->Set_Transaction_Type( Cashlynk_Client_Data::TYPE_CHANGE_PIN );
		
		$this->Set_Field ( 'Card Number', $this->make_not_null($cardNumber) );
		$this->Set_Field ( 'Old PIN', $this->make_not_null($oldPin) );
		$this->Set_Field ( 'New PIN', $this->make_not_null($newPin) );
		
		$result = $this->Run_Transaction();
		return $this->Get_Text_Response_Parsed( $result );
	}
	
	// 006
	public function Transfer_To_Another_Card_Account_Same_Card_Pan( $sourceCardNumber, $sourceCardAccountNumber, $destinationCardAccountNumber, $transferAmmount )
	{
		$this->Set_Transaction_Type( Cashlynk_Client_Data::TYPE_XFER_OTHER_CARD_ACCT_SAME_PAN );
		
		$this->Set_Field ( 'Source Card Number', $this->make_not_null($sourceCardNumber) );
		$this->Set_Field ( 'Source Card Account Number', $this->make_not_null($sourceCardAccountNumber) );
		$this->Set_Field ( 'Destination Card Account Number', $this->make_not_null($destinationCardAccountNumber) );
		$this->Set_Field ( 'Transfer Amount', $this->make_not_null($transferAmmount) );
		$this->Set_Field ( 'Card Member Number', $this->Get_Card_Member_Number() );
		
		$result = $this->Run_Transaction();
		return $this->Get_Text_Response_Parsed( $result );
	}
	
	
	// 007
	public function Deposit_To_Card_Account( $cardAccountNumber, $amount, $transaction_type )
	{
		$type_code = '';

		switch( $transaction_type )
		{
			case 'deposit'  : $type_code = '0'; break;
			case 'reversal' : $type_code = '1'; break;
			default         : $type_code = '0'; break;
		}
	
		$this->Set_Transaction_Type( Cashlynk_Client_Data::TYPE_DEPOSIT_TO_CARD_ACCOUNT );
		
		$this->Set_Field ( 'Card Account Number', $this->make_not_null($cardAccountNumber) );
		$this->Set_Field ( 'Amount', $this->make_not_null($amount) );
		$this->Set_Field ( 'Funding Program ID', $this->Get_Program_Id() );
		$this->Set_Field ( 'Transaction Type', $type_code );
		
		$result = $this->Run_Transaction();
		return $this->Get_Text_Response_Parsed( $result );
	}
	
	
	// 009
	public function View_Card_Details( $cardNumber, $startDate, $endDate )
	{
		// CashLynk only allows up to a months worth of data.  Asking for more gives an error.
	
		// For documentation on the contents of the arrays returned, see the following methods:
		//   Get_Xml_Result_Array_Cardpans()
		//   Get_Xml_Result_Array_Cardaccounts()
		//   Get_Xml_Result_Array_Summary()
		//   Get_Xml_Result_Array_Transaction_List()
		//   Get_Xml_Result_Array_Transaction_Detail()
		//   Get_Xml_Result_Array_Transaction_Description()
	
		$this->Set_Transaction_Type( Cashlynk_Client_Data::TYPE_VIEW_CARD_DETAILS );
		
		$this->Set_Field ( 'Card Number', $this->make_not_null($cardNumber) );
		$this->Set_Field ( 'Start Date', $this->make_not_null($startDate) );
		$this->Set_Field ( 'End Date', $this->make_not_null($endDate) );
		$this->Set_Field ( 'Card Member Number', $this->Get_Card_Member_Number() );
		
		$xml = $this->Run_Transaction();
	
		$cardpans                = array();
		$cardaccounts            = array();
		$summary                 = array();
		$transactions_list       = array();
		$transaction_detail      = array();
		$transaction_description = array();
		$cardaccounts_by_number  = array();  // only applies to transaction 010
	
		$xml_error_array = $this->Get_XML_Response_Parsed( $xml, $cardpans, $cardaccounts, $summary, $transactions_list, $transaction_detail, $transaction_description, $cardaccounts_by_number );
		
		// I'm populating the member arrays along with the local arrays just in case the user calls
		// the methods that return the member arrays.  The only reason I keep the member arrays in this
		// class is because that's a good place to document what is actually in the arrays.
		
		$result = array();
		$result[self::P1_RETURN_CODE]          = $xml_error_array[self::P1_RETURN_CODE];
		$result[self::ERRORMSG_RETURN_CODE]    = $xml_error_array[self::ERRORMSG_RETURN_CODE];
		
		$result[self::CARDPANS]                = $this->cardpans                = $cardpans;
		$result[self::CARDACCOUNTS]            = $this->cardaccounts            = $cardaccounts;
		$result[self::SUMMARY]                 = $this->summary                 = $summary;
		$result[self::TRANSACTIONS_LIST]       = $this->transactions_list       = $transactions_list;
		$result[self::TRANSACTION_DETAIL]      = $this->transaction_detail      = $transaction_detail;
		$result[self::TRANSACTION_DESCRIPTION] = $this->transaction_description = $transaction_description;
		$result[self::CARDACCOUNTS_BY_NUMBER]  = $this->cardaccounts_by_number  = $cardaccounts_by_number;  // only applies to transaction 010

		return $result;
	}
	
	
	// 010
	public function Get_Card_Accounts_For_Card_Number( $cardNumber )
	{
		// For documentation on the contents of the arrays returned, see the following methods:
		//   Get_Xml_Result_Array_Cardpans()
		//   Get_Xml_Result_Array_Cardaccounts()
		//   Get_Xml_Result_Array_Summary()
		//   Get_Xml_Result_Array_Transaction_List()
		//   Get_Xml_Result_Array_Transaction_Detail()
		//   Get_Xml_Result_Array_Transaction_Description()
		
		$this->Set_Transaction_Type( Cashlynk_Client_Data::TYPE_GET_CARD_ACCOUNT_FOR_CARD_NUMBER );
		
		$this->Set_Field ( 'Card Number', $this->make_not_null($cardNumber) );
		$this->Set_Field ( 'Card Member Number', $this->Get_Card_Member_Number() );
		
		$xml = $this->Run_Transaction();

		$cardpans                = array();
		$cardaccounts            = array();
		$summary                 = array();
		$transactions_list       = array();
		$transaction_detail      = array();
		$transaction_description = array();
		$cardaccounts_by_number  = array();  // only applies to transaction 010
	
		$xml_error_array = $this->Get_XML_Response_Parsed( $xml, $cardpans, $cardaccounts, $summary, $transactions_list, $transaction_detail, $transaction_description, $cardaccounts_by_number );

		// I'm populating the member arrays along with the local arrays just in case the user calls
		// the methods that return the member arrays.  The only reason I keep the member arrays in this
		// class is because that's a good place to document what is actually in the arrays.
		
		$result = array();
		$result[self::P1_RETURN_CODE]          = $xml_error_array[self::P1_RETURN_CODE];
		$result[self::ERRORMSG_RETURN_CODE]    = $xml_error_array[self::ERRORMSG_RETURN_CODE];
		
		$result[self::CARDPANS]                = $this->cardpans                = $cardpans;
		$result[self::CARDACCOUNTS]            = $this->cardaccounts            = $cardaccounts;
		$result[self::SUMMARY]                 = $this->summary                 = $summary;
		$result[self::TRANSACTIONS_LIST]       = $this->transactions_list       = $transactions_list;
		$result[self::TRANSACTION_DETAIL]      = $this->transaction_detail      = $transaction_detail;
		$result[self::TRANSACTION_DESCRIPTION] = $this->transaction_description = $transaction_description;
		$result[self::CARDACCOUNTS_BY_NUMBER]  = $this->cardaccounts_by_number  = $cardaccounts_by_number;  // only applies to transaction 010

		return $result;
	}
	
	
	// 011
	public function Assign_Card_To_Cardholder( $cardholderSSN, $cardNumber )
	{
		$this->Set_Transaction_Type( Cashlynk_Client_Data::TYPE_ASSIGN_CARD_TO_CARD_HOLDER );
		
		$this->Set_Field ( 'Cardholder SSN', $this->make_not_null($cardholderSSN) );
		$this->Set_Field ( 'Card Number', $this->make_not_null($cardNumber) );
		$this->Set_Field ( 'Card Member Number', $this->Get_Card_Member_Number() );
		
		$result = $this->Run_Transaction();
		return $this->Get_Text_Response_Parsed( $result );
	}
	
	
	// 012
	public function Validate_Pin_For_Card_Number( $cardNumber, $PIN )
	{
		$this->Set_Transaction_Type( Cashlynk_Client_Data::TYPE_VALIDATE_PIN_FOR_CARD_NUMBER );
		
		$this->Set_Field ( 'Card Number', $this->make_not_null($cardNumber) );
		$this->Set_Field ( 'PIN', $this->make_not_null($PIN) );
		
		$result = $this->Run_Transaction();
		return $this->Get_Text_Response_Parsed( $result );
	}
	
	
	// 013
	public function Any_Card_Acct_To_Any_Card_Acct_Xfer_Via_Email ( $sourceCardNumber, $sourceCardAccountNumber, $destinationEmailAddress, $transferAmount )
	{
		$this->Set_Transaction_Type( Cashlynk_Client_Data::TYPE_ANY_CARD_ACCT_TO_ANY_CARD_ACCT_XFER_VIA_EMAIL );
		
		$this->Set_Field ( 'Source Card Number', $this->make_not_null($sourceCardNumber) );
		$this->Set_Field ( 'Source Card Account Number', $this->make_not_null($sourceCardAccountNumber) );
		$this->Set_Field ( 'Destination Email address', $this->make_not_null($destinationEmailAddress) );
		$this->Set_Field ( 'Transfer Amount', $this->make_not_null($transferAmount) );
		$this->Set_Field ( 'Card Member Number', $this->Get_Card_Member_Number() );
		
		$result = $this->Run_Transaction();
		return $this->Get_Text_Response_Parsed( $result );
	}
	
	
	// 014
	public function Replace_Card ( $cardNumber, $replaceCard, $replaceCardNumber, $chargeCardholderReplaceFee, $duplicateCardOption, $expMonth, $expYear, $shippingAddressCode )
	{
		$this->Set_Transaction_Type( Cashlynk_Client_Data::TYPE_REPLACE_CARD );
		
		$this->Set_Field ( 'Card Number', $this->make_not_null($cardNumber) );
		$this->Set_Field ( 'Card Member Number', $this->Get_Card_Member_Number() );
		$this->Set_Field ( 'Replace Card', $this->make_not_null($replaceCard) );
		$this->Set_Field ( 'Replace Card Number', $this->make_not_null($replaceCardNumber) );
		$this->Set_Field ( 'Replace Card Member Number', $this->Get_Card_Member_Number() );
		$this->Set_Field ( 'Charge Cardholder replace fee', $this->make_not_null($chargeCardholderReplaceFee) );
		$this->Set_Field ( 'Duplicate card option', $this->make_not_null($duplicateCardOption) );
		$this->Set_Field ( 'Exp Month (MM)', $this->make_not_null($expMonth) );
		$this->Set_Field ( 'Exp Year (YYYY)', $this->make_not_null($expYear) );
		$this->Set_Field ( 'Shipping Address Code', $this->make_not_null($shippingAddressCode) );
		
		$result = $this->Run_Transaction();
		return $this->Get_Text_Response_Parsed( $result );
	}
	
	
	// 015
	public function Edit_Cardholder ($cardholderIdSSN, $firstName, $middleInitial, $lastName, $address1, $address2, $city, $state, $zip, $phone, $dob, $primaryCardHolder, $optionalData1, $optionalData2, $optionalData3, $notes, $email )
	{
		$this->Set_Transaction_Type( Cashlynk_Client_Data::TYPE_EDIT_CARDHOLDER );
		
		$this->Set_Field ( 'CardholderID/SSN', $this->make_not_null($cardholderIdSSN) );
		$this->Set_Field ( 'First Name', $this->make_not_null($firstName) );
		$this->Set_Field ( 'Middle Initial', $this->make_not_null($middleInitial) );
		$this->Set_Field ( 'Last Name', $this->make_not_null($lastName) );
		$this->Set_Field ( 'Address 1', $this->make_not_null($address1) );
		$this->Set_Field ( 'Address 2', $this->make_not_null($address2) );
		$this->Set_Field ( 'City', $this->make_not_null($city) );
		$this->Set_Field ( 'State', $this->make_not_null($state) );
		$this->Set_Field ( 'Zip Code', $this->make_not_null($zip) );
		$this->Set_Field ( 'Phone', $this->make_not_null($phone) );
		$this->Set_Field ( 'DOB', $this->make_not_null($dob) );
		$this->Set_Field ( 'Primary CardholderID/SSN    ', $this->make_not_null($primaryCardHolder) );
		$this->Set_Field ( 'Optional Data1', $this->make_not_null($optionalData1) );
		$this->Set_Field ( 'Optional Data2', $this->make_not_null($optionalData2) );
		$this->Set_Field ( 'Optional Data3', $this->make_not_null($optionalData3) );
		$this->Set_Field ( 'Notes', $this->make_not_null($notes) );
		$this->Set_Field ( 'Email', $this->make_not_null($email) );
		
		$result = $this->Run_Transaction();
		return $this->Get_Text_Response_Parsed( $result );
	}
	
	
	// 016
	public function Move_Cardholder ( $existingCardholderIdSSN, $newCardholderIdSSN )
	{
		$this->Set_Transaction_Type( Cashlynk_Client_Data::TYPE_MOVE_CARDHOLDER );
		
		$this->Set_Field ( 'Existing Cardholder ID/SSN', $this->make_not_null($existingCardholderIdSSN) );
		$this->Set_Field ( 'New Cardholder ID/SSN', $this->make_not_null($newCardholderIdSSN) );
		
		$result = $this->Run_Transaction();
		return $this->Get_Text_Response_Parsed( $result );
	}
	
	
	// 017
	public function Deposit_To_Primary_Card_Acct ( $cardNumber, $amount, $transaction_type )
	{
		$type_code = '';

		switch( $transaction_type )
		{
			case 'deposit'  : $type_code = '0'; break;
			case 'reversal' : $type_code = '1'; break;
			default         : $type_code = '0'; break;
		}
	
		$this->Set_Transaction_Type( Cashlynk_Client_Data::TYPE_DEPOSIT_TO_PRIMARY_CARD_ACCT );
		
		$this->Set_Field ( 'Card Number', $this->make_not_null($cardNumber) );
		$this->Set_Field ( 'Amount', $this->make_not_null($amount) );
		$this->Set_Field ( 'Program Code', $this->Get_Program_Id() );
		$this->Set_Field ( 'Transaction Type', $type_code );
		
		$result = $this->Run_Transaction();
		return $this->Get_Text_Response_Parsed( $result );
	}
	
	
	// 018
	public function Get_Short_Summary_For_Card ( $cardNumber )
	{
		$this->Set_Transaction_Type( Cashlynk_Client_Data::TYPE_GET_SHORT_SUMMARY_FOR_CARD );
		
		$this->Set_Field ( 'Card Number', $this->make_not_null($cardNumber) );
		$this->Set_Field ( 'Card Member Number', $this->Get_Card_Member_Number() );
		
		$result = $this->Run_Transaction();
		return $this->Get_Text_Response_Parsed( $result );
	}
	
	
	// 019
	public function Get_Card_Account_Balance ( $cardAccountNumber )
	{
		$this->Set_Transaction_Type( Cashlynk_Client_Data::TYPE_GET_CARD_ACCOUNT_BALANCE );
		
		$this->Set_Field ( 'Card Account Number', $this->make_not_null($cardAccountNumber) );
		
		$result = $this->Run_Transaction();
		return $this->Get_Text_Response_Parsed( $result );
	}
	
	
	// 020
	public function Get_Card_Transaction_Detail ( $cardNumber, $startDate, $endDate )
	{
		// For documentation on the contents of the arrays returned, see the following methods:
		//   Get_Xml_Result_Array_Cardpans()
		//   Get_Xml_Result_Array_Cardaccounts()
		//   Get_Xml_Result_Array_Transaction_List()
		//   Get_Xml_Result_Array_Transaction_Detail()
		//   Get_Xml_Result_Array_Transaction_Description()
	
		$this->Set_Transaction_Type( Cashlynk_Client_Data::TYPE_GET_CARD_TRANSACTION_DETAIL );
		
		$this->Set_Field ( 'Card Number', $this->make_not_null($cardNumber) );
		$this->Set_Field ( 'Start Date', $this->make_not_null($startDate) );
		$this->Set_Field ( 'End Date', $this->make_not_null($endDate) );
		$this->Set_Field ( 'Card Member Number', $this->Get_Card_Member_Number() );
		
		$cardpans                = array();
		$cardaccounts            = array();
		$summary                 = array();
		$transactions_list       = array();
		$transaction_detail      = array();
		$transaction_description = array();
		$cardaccounts_by_number  = array();  // only applies to transaction 010
	
		$xml = $this->Run_Transaction();
	
		$xml_error_array = $this->Get_XML_Response_Parsed( $xml, $cardpans, $cardaccounts, $summary, $transactions_list, $transaction_detail, $transaction_description, $cardaccounts_by_number );

		// I'm populating the member arrays along with the local arrays just in case the user calls
		// the methods that return the member arrays.  The only reason I keep the member arrays in this
		// class is because that's a good place to document what is actually in the arrays.
		
		$result = array();
		$result[self::P1_RETURN_CODE]          = $xml_error_array[self::P1_RETURN_CODE];
		$result[self::ERRORMSG_RETURN_CODE]    = $xml_error_array[self::ERRORMSG_RETURN_CODE];
		
		$result[self::CARDPANS]                = $this->cardpans                = $cardpans;
		$result[self::CARDACCOUNTS]            = $this->cardaccounts            = $cardaccounts;
		$result[self::SUMMARY]                 = $this->summary                 = $summary;
		$result[self::TRANSACTIONS_LIST]       = $this->transactions_list       = $transactions_list;
		$result[self::TRANSACTION_DETAIL]      = $this->transaction_detail      = $transaction_detail;
		$result[self::TRANSACTION_DESCRIPTION] = $this->transaction_description = $transaction_description;
		$result[self::CARDACCOUNTS_BY_NUMBER]  = $this->cardaccounts_by_number  = $cardaccounts_by_number;  // only applies to transaction 010

		return $result;
	}
	
	
	// 021
	public function Request_For_Program_Reversal ( $depositDescriptionId )
	{
		$this->Set_Transaction_Type( Cashlynk_Client_Data::TYPE_REQUEST_FOR_PROGRAM_REVERSAL );
		
		$this->Set_Field ( 'Funding Program ID', $this->Get_Program_Id() );  // don't know if funding program id is same as program id
		$this->Set_Field ( 'Deposit Description ID', $this->make_not_null($depositDescriptionId) );
		
		$result = $this->Run_Transaction();
		return $this->Get_Text_Response_Parsed( $result );
	}
	
	
	// 022
	public function Get_Program_Reversal_Status ( $batchId )
	{
		$this->Set_Transaction_Type( Cashlynk_Client_Data::TYPE_GET_PROGRAM_REVERSAL_STATUS );
		
		$this->Set_Field ( 'Batch ID', $this->make_not_null($batchId) );
		
		$result = $this->Run_Transaction();
		return $this->Get_Text_Response_Parsed( $result );
	}
	
	
	// 023
	public function View_Mirror_Account_Bal ()
	{
		$this->Set_Transaction_Type( Cashlynk_Client_Data::TYPE_VIEW_MIRROR_ACCOUNT_BAL );
		
		
		$result = $this->Run_Transaction();
		return $this->Get_Text_Response_Parsed( $result );
	}
	
	
	// 024
	public function View_Program_Avail_Bal ()
	{
		$this->Set_Transaction_Type( Cashlynk_Client_Data::TYPE_VIEW_PROGRAM_AVAIL_BAL );
		
		$this->Set_Field ( 'Program ID', $this->Get_Program_Id() );
		
		$result = $this->Run_Transaction();
		return $this->Get_Text_Response_Parsed( $result );
	}
	
	
	// 025
	public function Reverse_All_Funds_From_A_Card ( $cardPan )
	{
		$this->Set_Transaction_Type( Cashlynk_Client_Data::TYPE_REVERSE_ALL_FUNDS_FROM_A_CARD );
		
		$this->Set_Field ( 'CardPAN', $this->make_not_null($cardPan) );
		$this->Set_Field ( 'Program ID', $this->Get_Program_Id() );
		
		$result = $this->Run_Transaction();
		return $this->Get_Text_Response_Parsed( $result );
	}
	
	
	// 026
	public function Create_Prepaid_Mc_With_Cardholder_Validation ( $ssnIdentifier, $firstName, $lastName, $address1, $address2, $city, $state, $zip, $phone, $dob, $email, $bankAcct1RoutingNumber, $bankAcct1AcctNumber, $bankAcct1AcctType, $bankAcct1SplitMode, $bankAcct1SplitAmount, $bankAcct1SplitPercentage, $bankAcct2RoutingNumber, $bankAcct2AcctNumber, $bankAcct2AcctType, $bankAcct2SplitMode, $bankAcct2SplitAmount, $bankAcct2SplitPercentage, $bankAcct3RoutingNumber, $bankAcct3AcctNumber, $bankAcct3AcctType, $bankAcct3SplitMode, $bankAcct3SplitAmount, $bankAcct3SplitPercentage, $secondCardholderFirstName, $secondCardholderLastName, $cardPin, $activationCardNumber, $refNo )
	{
		$this->Set_Transaction_Type( Cashlynk_Client_Data::TYPE_CREATE_PREPAID_MC_WITH_CARDHOLDER_VALIDATION );
		
		$this->Set_Field ( 'SSN/Identifier', $this->make_not_null($ssnIdentifier) );
		$this->Set_Field ( 'First Name', $this->make_not_null($firstName) );
		$this->Set_Field ( 'Last Name', $this->make_not_null($lastName) );
		$this->Set_Field ( 'Address 1', $this->make_not_null($address1) );
		$this->Set_Field ( 'Address 2', $this->make_not_null($address2) );
		$this->Set_Field ( 'City', $this->make_not_null($city) );
		$this->Set_Field ( 'State', $this->make_not_null($state) );
		$this->Set_Field ( 'Zip Code', $this->make_not_null($zip) );
		$this->Set_Field ( 'Phone', $this->make_not_null($phone) );
		$this->Set_Field ( 'DOB', $this->make_not_null($dob) );
		$this->Set_Field ( 'Email', $this->make_not_null($email) );
		$this->Set_Field ( 'Bank Account 1 Routing Number   ', $this->make_not_null($bankAcct1RoutingNumber    ) );
		$this->Set_Field ( 'Bank Account 1 Account Number   ', $this->make_not_null($bankAcct1AcctNumber       ) );
		$this->Set_Field ( 'Bank Account 1 Account Type', $this->make_not_null($bankAcct1AcctType         ) );
		$this->Set_Field ( 'Bank Account 1 Split Mode', $this->make_not_null($bankAcct1SplitMode        ) );
		$this->Set_Field ( 'Bank Account 1 Split Amount', $this->make_not_null($bankAcct1SplitAmount      ) );
		$this->Set_Field ( 'Bank Account 1 Split Percentage', $this->make_not_null($bankAcct1SplitPercentage  ) );
		$this->Set_Field ( 'Bank Account 2 Routing Number', $this->make_not_null($bankAcct2RoutingNumber    ) );
		$this->Set_Field ( 'Bank Account 2 Account Number   ', $this->make_not_null($bankAcct2AcctNumber       ) );
		$this->Set_Field ( 'Bank Account 2 Account Type', $this->make_not_null($bankAcct2AcctType         ) );
		$this->Set_Field ( 'Bank Account 2 Split Mode', $this->make_not_null($bankAcct2SplitMode        ) );
		$this->Set_Field ( 'Bank Account 2 Split Amount', $this->make_not_null($bankAcct2SplitAmount      ) );
		$this->Set_Field ( 'Bank Account 2 Split Percentage', $this->make_not_null($bankAcct2SplitPercentage  ) );
		$this->Set_Field ( 'Bank Account 3 Routing Number', $this->make_not_null($bankAcct3RoutingNumber    ) );
		$this->Set_Field ( 'Bank Account 3 Account Number', $this->make_not_null($bankAcct3AcctNumber       ) );
		$this->Set_Field ( 'Bank Account 3 Account Type', $this->make_not_null($bankAcct3AcctType         ) );
		$this->Set_Field ( 'Bank Account 3 Split Mode', $this->make_not_null($bankAcct3SplitMode        ) );
		$this->Set_Field ( 'Bank Account 3 Split Amount', $this->make_not_null($bankAcct3SplitAmount      ) );
		$this->Set_Field ( 'Bank Account 3 Split Percentage', $this->make_not_null($bankAcct3SplitPercentage  ) );
		$this->Set_Field ( 'Second Cardholder First Name', $this->make_not_null($secondCardholderFirstName) );
		$this->Set_Field ( 'Second Cardholder Last Name', $this->make_not_null($secondCardholderLastName) );
		$this->Set_Field ( 'Program ID', $this->Get_Program_Id() );
		$this->Set_Field ( 'BIN Number', $this->Get_Card_BIN() );
		$this->Set_Field ( 'Card Stock ID', $this->Get_Card_Stock() );
		$this->Set_Field ( 'Card PIN', $this->make_not_null($cardPin) );
		$this->Set_Field ( 'Activation Card Number', $this->make_not_null($activationCardNumber) );
		$this->Set_Field ( 'Ref No', $this->make_not_null($refNo) );
		
		$result = $this->Run_Transaction();
		return $this->Get_Text_Response_Parsed( $result );
	}
	
	
	// 027
	public function Create_Pseudo_Dda_Number ( $SSN )
	{
		$this->Set_Transaction_Type( Cashlynk_Client_Data::TYPE_CREATE_PSEUDO_DDA_NUMBER );
		
		$this->Set_Field ( 'SSN', $this->make_not_null($SSN) );
		
		$result = $this->Run_Transaction();
		return $this->Get_Text_Response_Parsed( $result );
	}
	
	
	// 028
	public function Program_Statement ()
	{
		$this->Set_Transaction_Type( Cashlynk_Client_Data::TYPE_PROGRAM_STATEMENT );
		
		$this->Set_Field ( 'Program ID', $this->Get_Program_Id() );
		
		$result = $this->Run_Transaction();
		return $this->Get_Text_Response_Parsed( $result );
	}
	
	
	// 029
	public function Any_Card_Acct_To_Any_Card_Acct_Xfer ( $sourceCardNumber, $sourceCardAccountNumber, $destinationCardAccountNumber, $transferAmount )
	{
		$this->Set_Transaction_Type( Cashlynk_Client_Data::TYPE_ANY_CARD_ACCT_TO_ANY_CARD_ACCT_XFER );
		
		$this->Set_Field ( 'Source Card Number', $this->make_not_null($sourceCardNumber) );
		$this->Set_Field ( 'Source Card Account Number', $this->make_not_null($sourceCardAccountNumber) );
		$this->Set_Field ( 'Destination Card Account Number   ', $this->make_not_null($destinationCardAccountNumber) );
		$this->Set_Field ( 'Transfer Amount', $this->make_not_null($transferAmount) );
		$this->Set_Field ( 'Card Member Number', $this->Get_Card_Member_Number() );
		
		$result = $this->Run_Transaction();
		return $this->Get_Text_Response_Parsed( $result );
	}
	

	// 030
	public function View_CardPans_by_SSN  ( $ssn )
	{
		$this->Set_Transaction_Type( Cashlynk_Client_Data::TYPE_VIEW_CARDPANS_BY_SSN );
		
		$this->Set_Field ( 'CardholderID/SSN', $this->make_not_null($ssn) );
		
		$result = $this->Run_Transaction();
		return $this->Get_Text_Response_Parsed( $result );
	}
	

	// 031
	public function View_Card_Status  ( $card_number )
	{
		$this->Set_Transaction_Type( Cashlynk_Client_Data::TYPE_VIEW_CARD_STATUS );
		
		$this->Set_Field ( 'Card_Number', $this->make_not_null($card_number) );
		$this->Set_Field ( 'Card_Member_Number', $this->Get_Card_Member_Number() );
		
		$result = $this->Run_Transaction();
		return $this->Get_Text_Response_Parsed( $result );
	}
	

	// ***********************************************************************************
	// The following are the raw methods for executing a transaction.  You can use the
	// raw methods or you can use the simpler convenience methods above.
	// ***********************************************************************************
	
	public function Get_Xml_Result_Array_Cardpans()
	{
		// This array tells you all the cardpan account numbers returned in the xml result.
		// The key to the associative array is cardpan account number but the value
		// depends on which transaction was processed.
		//
		// For transaction type 009 and 020, the value part is this:
		// Associative array with key = cardpan account number and
		// value = associative subarray as follows:
		//   startdate    => start date
		//   enddate      => end date
		//   reportdate   => report date
		//
		// For transaction type 010, the value part is simply the memberno.
	
		return $this->$cardpans;
	}
	
	public function Get_Xml_Result_Array_Cardaccounts()
	{
		// This array tells you all the card account numbers associated with
		// each cardpan account.
		// Associative array with key = cardpan account number and
		// value = simple subarray consisting of one or more card account numbers
		// associated with the cardpan account in the key.
		
		return $this->$cardaccounts;
	}
	
	public function Get_Xml_Result_Array_Summary()
	{
		// This array tells you the summary information by card account number.
		// Associative array with key = card account number and
		// value = associative subarray as follows:
		//   beginbalance    => the numeric beginning balance.
		//   totalcredits    => the numeric value of the total credits.
		//   totaldebits     => the numeric value of the total debits.
		//   totalfees       => numeric value of total fees.
		//   endbalance      => numeric value of ending balance.
		
		return $this->$summary;
	}
	
	public function Get_Xml_Result_Array_Transaction_List()
	{
		// This array gives a list of authorization numbers by
		// card account number.  I ASSUME THE AUTHNUMBER IS THE UNIQUE IDENFIER
		// FOR EACH TRANSACTION UNDER A CARD ACCOUNT NUMBER.
		// Associative array with key = card account number and value
		// is a simple array list of authnumbers.
	
		return $this->$transactions_list;
	}
	
	public function Get_Xml_Result_Array_Transaction_Detail()
	{
		// This array gives transaction detail by authnumber.
		// Associative array with key = authnumber and value is
		// associative array as follows:
		//   date     => date value
		//   amount   => numeric amount
		//   fee      => numeric fee amount
		//   balance  => numeric balance
	
		return $this->$transaction_detail;
	}
	
	public function Get_Xml_Result_Array_Transaction_Description()
	{
		// This array gives transaction detail descriptions by authnumber.
		// Associative array with key = authnumber and value is
		// simple array list of text descriptions.
		
		return $this->$transaction_description;
	}
	
	
	public function Get_Xml_Card_Accounts_By_Number()
	{
		// This array only applies to transaction 010.
		// This array gives the primary card account, application name
		// and current balance for each cardaccount.
		// Associative array with key = cardaccount number and value is
		// an associative array as follows:
		//   primarycardaccount =>   primarycardaccount value
		//   applicationname    =>   applicationname value  
		//   currentbalance     =>   currentbalance value   
		
		return $this->$cardaccounts_by_number;
	}
  

	public function Set_Field( $field_name, $field_value )
	{
		// I used to have spaces embedded in the field names.  That didn't work to well
		// for retrieving the fields from an HTML form so I replaced the spaces with underscores.
		// In order to be backward compatible with everything else I coded, I just change
		// spaces to underscores here.
		$this->cashlynk_client_data->Set_Field(str_replace(' ', '_', $field_name), $field_value);
	}
	
	
	public function Get_Field( $field_name )
	{
		return $this->cashlynk_client_data->Get_Field($field_name);
	}
  
  
	public function Run_Transaction()
	{
		// Some example responses:
		// tran=001, result = "P1=000 P2=000000001" (quotes not part of reponse)
		// tran=001, result = "P1=001|Unspecified Error. The specified cardholder already exists."
		//           I tried to run the same ssn again ("000000000") an naturally got the
		//           error that the cardholder already existed.
		//
	
		if ( $this->run_mode == self::RUN_MODE_NO_CALL )
		{
			$result = self::NO_CALL_TEXT;
			// dlhlog( "Run_Transaction: result=$result, url=$url, data=" . dlhvardump($this->cashlynk_client_data->Get_Populated_Fields_P_Names_Array(), false) );
			return $result;
		}
	
		$this->cashlynk_client_data->Set_Field('MSGID', $this->Get_Msgid());
		$result = null;
		$url = $this->Get_Url();
		$result = $this->http_client->Http_Get( $url, $this->cashlynk_client_data->Get_Populated_Fields_P_Names_Array() );
		$this->raw_data_sent = $this->http_client->Get_Data_Sent();
		// dlhlog( "Run_Transaction: result=$result, url=$url, data=" . dlhvardump($this->cashlynk_client_data->Get_Populated_Fields_P_Names_Array(), false) );
		return $result;
	}
  
  
	public function Get_Required_Fields_Array()
	{
		return $this->cashlynk_client_data->Get_Required_Fields_Array();
	}
	
	
	public function Get_All_Fields_Array()
	{
		return $this->cashlynk_client_data->Get_All_Fields_Array();
	}
	
	
	public function Get_Populated_Fields_Array()
	{
		return $this->cashlynk_client_data->Get_Populated_Fields_Array();
	}
	
	
	public function Get_Populated_Fields_P_Names_Array()
	{
		return $this->cashlynk_client_data->Get_Populated_Fields_P_Names_Array();
	}
	
	
	// Used for debugging
	public function Get_Required_Fields_String( $separator_str = ',' )
	{
		return $this->cashlynk_client_data->Get_Required_Fields_String($separator_str);
	}
  
  
	// Used for debugging
	public function Get_All_Fields_String( $separator_str = ',' )
	{
		return $this->cashlynk_client_data->Get_All_Fields_String($separator_str);
	}
	
	
	// Used for debugging
	public function Get_Populated_Fields_String( $separator_str = ',' )
	{
		return $this->cashlynk_client_data->Get_Populated_Fields_String($separator_str);
	}
	
	
	public function Get_Field_Details( $field_name, &$query_name, &$value, &$requirement )
	{
		return $this->cashlynk_client_data->Get_Field_Details( $field_name, $query_name, $value, $requirement );
	}
	
  
	public function Get_Msgid()
	{
		// This function was copied from /virtualhosts/lib/cash_lynk.2.php
		// I don't know if that was a working program or not and this calculation
		// looks kind of weird to me but I'll go with it for now.  The thing I think
		// is weird is that a high-order portion of the time is appended at the rear.
		// Also, it seems like this value should be derived from a database
		// next-value table with a way to go back and find details of the transaction
		// that was issued based on the MSGID.
		
		// build a unique ID from the
		// current timestamp
		$time = split(' ', microtime());
		$time = (end($time).substr(reset($time), 2));
		
		$cid = $this->Get_Field('CID');
	
		// create the message ID according
		// to the CashLynk spec
		$msg_id = str_pad($this->Get_Field('CID'), 5, "0", STR_PAD_LEFT);
		$msg_id .= substr($time, 0, 11);

		$this->msg_id = $msg_id;
		
		return($msg_id);
		
	}
	
	
	public function Get_Text_Response_Parsed( $res )
	{
		// dlhlog( "Get_Text_Response_Parsed: res=$res, len=" . strlen($res) );
			
		$this->raw_response = $res;
	
		$output = array();

		if ( !isset($res) || $res == '' ) return $output;  // return empty array.
	
		$res = strtr($res, "\r\n", '  ');
		$k   = null;
		$v   = null;

		if ( substr($res, 3, 3) == '000' )
		{
			// No error; was successful; just return all PNN=XXX values
			$tempArray = explode(' ', $res);
			foreach( $tempArray as $key => $val )
			{
				if ( trim($val) != '' )
				{
					$this->Get_Key_Value_Urldecoded( $val, $k, $v );
					if ( $k == '' ) $k = 'unk';
					$output[$k] = $v;
				}
			}
		}
		else
		{
			$tempArray = explode('|', $res);
			
			$this->Get_Key_Value_Urldecoded( array_shift($tempArray), $k, $v );
			if ( $k == '' ) $k = 'unk';
			$output[$k] = $v;
			
			$this->Get_Key_Value_Urldecoded( implode(', ', $tempArray), $k, $v );
			$output[self::ERRORMSG_RETURN_CODE] = $v;
		}
		
		return $output;
	}
	

	// Unfortunately, I didn't know about simple_xml at the time that I created this class.  Simple_xml would
	// certainly be a lot simpler.  As long as this is working, I won't convert to simple_xml but as soon as
	// there is a bug or a change required, I'll convert to simple_xml.

	function Get_XML_Response_Parsed( &$xml_input, &$cardpans, &$cardaccounts, &$summary, &$transactions_list, &$transaction_detail, &$transaction_description, &$cardaccounts_by_number )
	{
		$this->raw_response = $xml_input;

		$vals = null;
		$tags = null;
		$xml  = trim($xml_input);
		
		$xml_parser = xml_parser_create();
		xml_parser_set_option($xml_parser,XML_OPTION_CASE_FOLDING,0);
		xml_parser_set_option($xml_parser,XML_OPTION_SKIP_WHITE,1);
		$parseReturnCode = xml_parse_into_struct($xml_parser, $xml, $vals, $tags);
		xml_parser_free($xml_parser);
	
		$result_array = $this->Get_XML_Data( $vals, $tags, $cardpans, $cardaccounts, $summary, $transactions_list, $transaction_detail, $transaction_description, $cardaccounts_by_number );
		
		return $result_array;
	}
	

	function Get_XML_Data( &$vals, &$tags, &$cardpans, &$cardaccounts, &$cardaccount_summary, &$cardaccount_transactions, &$transaction_detail, &$transaction_detail_description, &$cardaccounts_by_number )
	{
	
		$current_state = array(
			'func'                      => '',
			'p1_errnumber'              => '',
			'p1_errdescription'         => '',
			'cardpan_account'           => '',
			'cardaccount'               => '',
			'summary_beginbalance'      => '',
			'summary_totalcredits'      => '',
			'summary_totaldebits'       => '',
			'summary_totalfees'         => '',
			'summary_endbalance'        => '',
			'transaction'               => '',
			'transaction_date'          => '',
			'transaction_authnumber'    => '',
			'transaction_amount'        => '',
			'transaction_fee'           => '',
			'transaction_balance'       => '',
			'primarycardaccount'        => '',
			'applicationname'           => '',
			'currentbalance'            => ''
		);
	
		foreach( $vals as $key => $val )
		{
			$tag           = $val['tag'];
			$tagtype       = $val['type'];
			$taglevel      = $val['level'];
			$tagvalue      = isset($val['value']) ? $val['value'] : '';
			$tagattributes = isset($val['attributes']) ? $val['attributes'] : '';
		
			switch ( $tag )
			{
				case 'cashlynk'           : $this->process_cashlynk    ($current_state, $tag, $tagtype, $taglevel, $tagvalue, $tagattributes, $cardpans, $cardaccounts, $cardaccount_summary, $cardaccount_transactions, $transaction_detail, $transaction_detail_description, $cardaccounts_by_number); break;
				case 'p1'                 : $this->process_p1          ($current_state, $tag, $tagtype, $taglevel, $tagvalue, $tagattributes, $cardpans, $cardaccounts, $cardaccount_summary, $cardaccount_transactions, $transaction_detail, $transaction_detail_description, $cardaccounts_by_number); break;
				case 'cardpan'            : $this->process_cardpan     ($current_state, $tag, $tagtype, $taglevel, $tagvalue, $tagattributes, $cardpans, $cardaccounts, $cardaccount_summary, $cardaccount_transactions, $transaction_detail, $transaction_detail_description, $cardaccounts_by_number); break;
				case 'cardaccount'        : $this->process_cardaccount ($current_state, $tag, $tagtype, $taglevel, $tagvalue, $tagattributes, $cardpans, $cardaccounts, $cardaccount_summary, $cardaccount_transactions, $transaction_detail, $transaction_detail_description, $cardaccounts_by_number); break;
				case 'beginbalance'       : $this->process_beginbalance($current_state, $tag, $tagtype, $taglevel, $tagvalue, $tagattributes, $cardpans, $cardaccounts, $cardaccount_summary, $cardaccount_transactions, $transaction_detail, $transaction_detail_description, $cardaccounts_by_number); break;
				case 'totalcredits'       : $this->process_totalcredits($current_state, $tag, $tagtype, $taglevel, $tagvalue, $tagattributes, $cardpans, $cardaccounts, $cardaccount_summary, $cardaccount_transactions, $transaction_detail, $transaction_detail_description, $cardaccounts_by_number); break;
				case 'totaldebits'        : $this->process_totaldebits ($current_state, $tag, $tagtype, $taglevel, $tagvalue, $tagattributes, $cardpans, $cardaccounts, $cardaccount_summary, $cardaccount_transactions, $transaction_detail, $transaction_detail_description, $cardaccounts_by_number); break;
				case 'totalfees'          : $this->process_totalfees   ($current_state, $tag, $tagtype, $taglevel, $tagvalue, $tagattributes, $cardpans, $cardaccounts, $cardaccount_summary, $cardaccount_transactions, $transaction_detail, $transaction_detail_description, $cardaccounts_by_number); break;
				case 'endbalance'         : $this->process_endbalance  ($current_state, $tag, $tagtype, $taglevel, $tagvalue, $tagattributes, $cardpans, $cardaccounts, $cardaccount_summary, $cardaccount_transactions, $transaction_detail, $transaction_detail_description, $cardaccounts_by_number); break;
				case 'transaction'        : $this->process_transaction ($current_state, $tag, $tagtype, $taglevel, $tagvalue, $tagattributes, $cardpans, $cardaccounts, $cardaccount_summary, $cardaccount_transactions, $transaction_detail, $transaction_detail_description, $cardaccounts_by_number); break;
				case 'date'               : $this->process_date        ($current_state, $tag, $tagtype, $taglevel, $tagvalue, $tagattributes, $cardpans, $cardaccounts, $cardaccount_summary, $cardaccount_transactions, $transaction_detail, $transaction_detail_description, $cardaccounts_by_number); break;
				case 'authnumber'         : $this->process_authnumber  ($current_state, $tag, $tagtype, $taglevel, $tagvalue, $tagattributes, $cardpans, $cardaccounts, $cardaccount_summary, $cardaccount_transactions, $transaction_detail, $transaction_detail_description, $cardaccounts_by_number); break;
				case 'description'        : $this->process_description ($current_state, $tag, $tagtype, $taglevel, $tagvalue, $tagattributes, $cardpans, $cardaccounts, $cardaccount_summary, $cardaccount_transactions, $transaction_detail, $transaction_detail_description, $cardaccounts_by_number); break;
				case 'amount'             : $this->process_amount      ($current_state, $tag, $tagtype, $taglevel, $tagvalue, $tagattributes, $cardpans, $cardaccounts, $cardaccount_summary, $cardaccount_transactions, $transaction_detail, $transaction_detail_description, $cardaccounts_by_number); break;
				case 'fee'                : $this->process_fee         ($current_state, $tag, $tagtype, $taglevel, $tagvalue, $tagattributes, $cardpans, $cardaccounts, $cardaccount_summary, $cardaccount_transactions, $transaction_detail, $transaction_detail_description, $cardaccounts_by_number); break;
				case 'balance'            : $this->process_balance     ($current_state, $tag, $tagtype, $taglevel, $tagvalue, $tagattributes, $cardpans, $cardaccounts, $cardaccount_summary, $cardaccount_transactions, $transaction_detail, $transaction_detail_description, $cardaccounts_by_number); break;
				case 'primarycardaccount' : $this->process_primcardacct($current_state, $tag, $tagtype, $taglevel, $tagvalue, $tagattributes, $cardpans, $cardaccounts, $cardaccount_summary, $cardaccount_transactions, $transaction_detail, $transaction_detail_description, $cardaccounts_by_number); break;
				case 'applicationname'    : $this->process_appname     ($current_state, $tag, $tagtype, $taglevel, $tagvalue, $tagattributes, $cardpans, $cardaccounts, $cardaccount_summary, $cardaccount_transactions, $transaction_detail, $transaction_detail_description, $cardaccounts_by_number); break;
				case 'currentbalance'     : $this->process_currentbal  ($current_state, $tag, $tagtype, $taglevel, $tagvalue, $tagattributes, $cardpans, $cardaccounts, $cardaccount_summary, $cardaccount_transactions, $transaction_detail, $transaction_detail_description, $cardaccounts_by_number); break;
			}
		}

		return array( self::P1_RETURN_CODE => $current_state['p1_errnumber'], self::ERRORMSG_RETURN_CODE => $current_state['p1_errdescription'] );
	}


	function process_cashlynk ( &$current_state, &$tag, &$tagtype, &$taglevel, &$tagvalue, &$tagattributes, &$cardpans, &$cardaccounts, &$cardaccount_summary, &$cardaccount_transactions, &$transaction_detail, &$transaction_detail_description, &$cardaccounts_by_number )
	{
		if ( $tagtype == 'open' )
		{
			$current_state['cardpan_account']           = '';
			$current_state['cardaccount']               = '';
			$current_state['summary_beginbalance']      = '';
			$current_state['summary_totalcredits']      = '';
			$current_state['summary_totaldebits']       = '';
			$current_state['summary_totalfees']         = '';
			$current_state['summary_endbalance']        = '';
			$current_state['transaction_date']          = '';
			$current_state['transaction_authnumber']    = '';
			$current_state['transaction_amount' ]       = '';
			$current_state['transaction_fee']           = '';
			$current_state['transaction_balance']       = '';
			
			$current_state['func'] = $tagattributes['func'];
		}
	
	}
	
	function process_p1 ( &$current_state, &$tag, &$tagtype, &$taglevel, &$tagvalue, &$tagattributes, &$cardpans, &$cardaccounts, &$cardaccount_summary, &$cardaccount_transactions, &$transaction_detail, &$transaction_detail_description, &$cardaccounts_by_number )
	{
		$current_state['p1_errnumber']      = $tagattributes['errnumber'];
		$current_state['p1_errdescription'] = $tagattributes['errdescription'];
	}
	
	function process_cardpan ( &$current_state, &$tag, &$tagtype, &$taglevel, &$tagvalue, &$tagattributes, &$cardpans, &$cardaccounts, &$cardaccount_summary, &$cardaccount_transactions, &$transaction_detail, &$transaction_detail_description, &$cardaccounts_by_number )
	{
		if ( $tagtype == 'open' )
		{
			$current_state['cardpan_account']           = '';
			$current_state['cardaccount']               = '';
			$current_state['summary_beginbalance']      = '';
			$current_state['summary_totalcredits']      = '';
			$current_state['summary_totaldebits']       = '';
			$current_state['summary_totalfees']         = '';
			$current_state['summary_endbalance']        = '';
			$current_state['transaction_date']          = '';
			$current_state['transaction_authnumber']    = '';
			$current_state['transaction_amount' ]       = '';
			$current_state['transaction_fee']           = '';
			$current_state['transaction_balance']       = '';
			
			$current_state['cardpan_account'] = $tagattributes['account'];
		
			if ( $current_state['func'] == '010' )
			{
				$cardpans[$current_state['cardpan_account']] = $tagattributes['memberno'];
			}
			else {
				// This applies to transactions 009 and 020.
				$cardpans[$current_state['cardpan_account']] = array( 'startdate' => $tagattributes['startdate'], 'enddate' => $tagattributes['enddate'], 'reportdate' => $tagattributes['reportdate'] );
			}
		}
	}

	function process_cardaccount ( &$current_state, &$tag, &$tagtype, &$taglevel, &$tagvalue, &$tagattributes, &$cardpans, &$cardaccounts, &$cardaccount_summary, &$cardaccount_transactions, &$transaction_detail, &$transaction_detail_description, &$cardaccounts_by_number )
	{
		if ( $tagtype == 'open' )
		{
			$current_state['cardaccount']               = '';
			$current_state['summary_beginbalance']      = '';
			$current_state['summary_totalcredits']      = '';
			$current_state['summary_totaldebits']       = '';
			$current_state['summary_totalfees']         = '';
			$current_state['summary_endbalance']        = '';
			$current_state['transaction_date']          = '';
			$current_state['transaction_authnumber']    = '';
			$current_state['transaction_amount' ]       = '';
			$current_state['transaction_fee']           = '';
			$current_state['transaction_balance']       = '';
			
			$current_state['cardaccount'] = $tagattributes['number'];
			$cardaccounts[$current_state['cardpan_account']][] = $tagattributes['number'];
		}
		else if ( $tagtype == 'close' && $current_state['func'] == '010' )
		{
			$cardaccounts_by_number[$current_state['cardaccount']] = array('primarycardaccount'=>$current_state['primarycardaccount'], 'applicationname'=>$current_state['applicationname'], 'currentbalance'=>$current_state['currentbalance']);
		}
	}

	function process_beginbalance ( &$current_state, &$tag, &$tagtype, &$taglevel, &$tagvalue, &$tagattributes, &$cardpans, &$cardaccounts, &$cardaccount_summary, &$cardaccount_transactions, &$transaction_detail, &$transaction_detail_description, &$cardaccounts_by_number )
	{
		$current_state['transaction_date']          = '';
		$current_state['transaction_authnumber']    = '';
		$current_state['transaction_amount' ]       = '';
		$current_state['transaction_fee']           = '';
		$current_state['transaction_balance']       = '';
	
		$current_state['summary_beginbalance'] = $tagvalue;
		$cardaccount_summary[$current_state['cardaccount']]['beginbalance'] =  $tagvalue;
	}

	function process_totalcredits ( &$current_state, &$tag, &$tagtype, &$taglevel, &$tagvalue, &$tagattributes, &$cardpans, &$cardaccounts, &$cardaccount_summary, &$cardaccount_transactions, &$transaction_detail, &$transaction_detail_description, &$cardaccounts_by_number )
	{
		$current_state['transaction_date']          = '';
		$current_state['transaction_authnumber']    = '';
		$current_state['transaction_amount' ]       = '';
		$current_state['transaction_fee']           = '';
		$current_state['transaction_balance']       = '';
	
		$current_state['summary_totalcredits'] = $tagvalue;
		$cardaccount_summary[$current_state['cardaccount']]['totalcredits'] =  $tagvalue;
	}
	
	function process_totaldebits ( &$current_state, &$tag, &$tagtype, &$taglevel, &$tagvalue, &$tagattributes, &$cardpans, &$cardaccounts, &$cardaccount_summary, &$cardaccount_transactions, &$transaction_detail, &$transaction_detail_description, &$cardaccounts_by_number )
	{
		$current_state['transaction_date']          = '';
		$current_state['transaction_authnumber']    = '';
		$current_state['transaction_amount' ]       = '';
		$current_state['transaction_fee']           = '';
		$current_state['transaction_balance']       = '';
	
		$current_state['summary_totaldedits'] = $tagvalue;
		$cardaccount_summary[$current_state['cardaccount']]['totaldedits'] =  $tagvalue;
	}

	function process_totalfees ( &$current_state, &$tag, &$tagtype, &$taglevel, &$tagvalue, &$tagattributes, &$cardpans, &$cardaccounts, &$cardaccount_summary, &$cardaccount_transactions, &$transaction_detail, &$transaction_detail_description, &$cardaccounts_by_number )
	{
		$current_state['transaction_date']          = '';
		$current_state['transaction_authnumber']    = '';
		$current_state['transaction_amount' ]       = '';
		$current_state['transaction_fee']           = '';
		$current_state['transaction_balance']       = '';
	
		$current_state['summary_totalfees'] = $tagvalue;
		$cardaccount_summary[$current_state['cardaccount']]['totalfees'] =  $tagvalue;
	}
	
	function process_endbalance ( &$current_state, &$tag, &$tagtype, &$taglevel, &$tagvalue, &$tagattributes, &$cardpans, &$cardaccounts, &$cardaccount_summary, &$cardaccount_transactions, &$transaction_detail, &$transaction_detail_description, &$cardaccounts_by_number )
	{
		$current_state['transaction_date']          = '';
		$current_state['transaction_authnumber']    = '';
		$current_state['transaction_amount' ]       = '';
		$current_state['transaction_fee']           = '';
		$current_state['transaction_balance']       = '';
	
		$current_state['summary_endbalance'] = $tagvalue;
		$cardaccount_summary[$current_state['cardaccount']]['endbalance'] =  $tagvalue;
	}
	
	function process_transaction ( &$current_state, &$tag, &$tagtype, &$taglevel, &$tagvalue, &$tagattributes, &$cardpans, &$cardaccounts, &$cardaccount_summary, &$cardaccount_transactions, &$transaction_detail, &$transaction_detail_description, &$cardaccounts_by_number )
	{
		
		if ( $tagtype == 'open' )
		{
			$current_state['transaction_date']          = '';
			$current_state['transaction_authnumber']    = '';
			$current_state['transaction_amount' ]       = '';
			$current_state['transaction_fee']           = '';
			$current_state['transaction_balance']       = '';
		}
		else if ( $tagtype == 'close' )
		{
			$transaction_detail[$current_state['transaction_authnumber']][] = array( 'date' => $current_state['transaction_date'], 'amount' => $current_state['transaction_amount' ], 'fee' => $current_state['transaction_fee'], 'balance' => $current_state['transaction_balance'] );
		}
	}
	
  
	function process_date ( &$current_state, &$tag, &$tagtype, &$taglevel, &$tagvalue, &$tagattributes, &$cardpans, &$cardaccounts, &$cardaccount_summary, &$cardaccount_transactions, &$transaction_detail, &$transaction_detail_description, &$cardaccounts_by_number )
	{
		$current_state['transaction_date'] = $tagvalue;
	}
	
	function process_authnumber ( &$current_state, &$tag, &$tagtype, &$taglevel, &$tagvalue, &$tagattributes, &$cardpans, &$cardaccounts, &$cardaccount_summary, &$cardaccount_transactions, &$transaction_detail, &$transaction_detail_description, &$cardaccounts_by_number )
	{
		$current_state['transaction_authnumber'] = $tagvalue;
		$cardaccount_transactions[$current_state['cardaccount']][] = $tagvalue;
	}
	
	function process_description ( &$current_state, &$tag, &$tagtype, &$taglevel, &$tagvalue, &$tagattributes, &$cardpans, &$cardaccounts, &$cardaccount_summary, &$cardaccount_transactions, &$transaction_detail, &$transaction_detail_description, &$cardaccounts_by_number )
	{
		$transaction_detail_description[$current_state['transaction_authnumber']][] = $tagvalue;
	}
	
	function process_amount ( &$current_state, &$tag, &$tagtype, &$taglevel, &$tagvalue, &$tagattributes, &$cardpans, &$cardaccounts, &$cardaccount_summary, &$cardaccount_transactions, &$transaction_detail, &$transaction_detail_description, &$cardaccounts_by_number )
	{
		$current_state['transaction_amount'] = $tagvalue;
	}
	
	function process_fee ( &$current_state, &$tag, &$tagtype, &$taglevel, &$tagvalue, &$tagattributes, &$cardpans, &$cardaccounts, &$cardaccount_summary, &$cardaccount_transactions, &$transaction_detail, &$transaction_detail_description, &$cardaccounts_by_number )
	{
		$current_state['transaction_fee'] = $tagvalue;
	}
  
	function process_balance ( &$current_state, &$tag, &$tagtype, &$taglevel, &$tagvalue, &$tagattributes, &$cardpans, &$cardaccounts, &$cardaccount_summary, &$cardaccount_transactions, &$transaction_detail, &$transaction_detail_description, &$cardaccounts_by_number )
	{
		$current_state['transaction_balance'] = $tagvalue;
	}
	
	function process_primcardacct ( &$current_state, &$tag, &$tagtype, &$taglevel, &$tagvalue, &$tagattributes, &$cardpans, &$cardaccounts, &$cardaccount_summary, &$cardaccount_transactions, &$transaction_detail, &$transaction_detail_description, &$cardaccounts_by_number )
	{
		$current_state['primarycardaccount'] = $tagvalue;
	}
	
	
	function process_appname ( &$current_state, &$tag, &$tagtype, &$taglevel, &$tagvalue, &$tagattributes, &$cardpans, &$cardaccounts, &$cardaccount_summary, &$cardaccount_transactions, &$transaction_detail, &$transaction_detail_description, &$cardaccounts_by_number )
	{
		$current_state['applicationname'] = $tagvalue;
	}
	
	
	function process_currentbal ( &$current_state, &$tag, &$tagtype, &$taglevel, &$tagvalue, &$tagattributes, &$cardpans, &$cardaccounts, &$cardaccount_summary, &$cardaccount_transactions, &$transaction_detail, &$transaction_detail_description, &$cardaccounts_by_number )
	{
		$current_state['currentbalance'] = $tagvalue;
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

		// for length values of zero, substr() returns the original string rather than '' (weird!)
		$number_chars_after_separator = ($len - $pos - 1);
		$val = $number_chars_after_separator > 0 ? urldecode(substr( $str, -$number_chars_after_separator )) : '';
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
    




}
