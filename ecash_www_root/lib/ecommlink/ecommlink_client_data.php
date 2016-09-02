<?php


class Cashlynk_Client_Data
{
	// These constants are the possible transaction types.  The type tells
	// what kind of API transaction will be called which tells which fields
	// are needed.  The value of $type must be one of these constants and will
	// be used as the subscript into an array.

	// NOTE:  Apparently, there is no transaction 008
	
	const TYPE_MIN                                           =  1;  // smallest possible value for $type
	const TYPE_CREATE_CARD_HOLDER                            =  1;
	const TYPE_CREATE_CARD                                   =  2;
	const TYPE_CREATE_CARD_ACCOUNT                           =  3;
	const TYPE_CHANGE_CARD_STATUS                            =  4;
	const TYPE_CHANGE_PIN                                    =  5;
	const TYPE_XFER_OTHER_CARD_ACCT_SAME_PAN                 =  6;
	const TYPE_DEPOSIT_TO_CARD_ACCOUNT                       =  7;
	const TYPE_VIEW_CARD_DETAILS                             =  9;
	const TYPE_GET_CARD_ACCOUNT_FOR_CARD_NUMBER              = 10;
	const TYPE_ASSIGN_CARD_TO_CARD_HOLDER                    = 11;
	const TYPE_VALIDATE_PIN_FOR_CARD_NUMBER                  = 12;
	const TYPE_ANY_CARD_ACCT_TO_ANY_CARD_ACCT_XFER_VIA_EMAIL = 13;
	const TYPE_REPLACE_CARD                                  = 14;
	const TYPE_EDIT_CARDHOLDER                               = 15;
	const TYPE_MOVE_CARDHOLDER                               = 16;
	const TYPE_DEPOSIT_TO_PRIMARY_CARD_ACCT                  = 17;
	const TYPE_GET_SHORT_SUMMARY_FOR_CARD                    = 18;
	const TYPE_GET_CARD_ACCOUNT_BALANCE                      = 19;
	const TYPE_GET_CARD_TRANSACTION_DETAIL                   = 20;
	const TYPE_REQUEST_FOR_PROGRAM_REVERSAL                  = 21;
	const TYPE_GET_PROGRAM_REVERSAL_STATUS                   = 22;
	const TYPE_VIEW_MIRROR_ACCOUNT_BAL                       = 23;
	const TYPE_VIEW_PROGRAM_AVAIL_BAL                        = 24;
	const TYPE_REVERSE_ALL_FUNDS_FROM_A_CARD                 = 25;
	const TYPE_CREATE_PREPAID_MC_WITH_CARDHOLDER_VALIDATION  = 26;
	const TYPE_CREATE_PSEUDO_DDA_NUMBER                      = 27;
	const TYPE_PROGRAM_STATEMENT                             = 28;
	const TYPE_ANY_CARD_ACCT_TO_ANY_CARD_ACCT_XFER           = 29;
	const TYPE_VIEW_CARDPANS_BY_SSN                          = 30;
	const TYPE_VIEW_CARD_STATUS                              = 31;
	const TYPE_MAX                                           = 31;  // largest possible value for $type
	

	// These constants are used to indicate if a field is required, optional, or fixed
	// for a transaction type.
	
	const FIELD_IS_REQUIRED = 'R';
	const FIELD_IS_FIXED    = 'F';  // Indicates field is required and should not be reset by Reset_State().
	const FIELD_IS_OPTIONAL = 'O';

	
	private $type;
	
	private $fields;	// Array of arrays; key => field name, value => array containing
						// value corresponding to field name and a list of flags indicating
						// which transaction types this field applies to.
	
	
	public function __construct( $type )
	{
		$this->Init_Fields();
		$this->Set_Type( $type );
	}
	
	
	public function Set_Type( $type )
	{
		if ( !isset($type) || !is_numeric($type) || $type < self::TYPE_MIN || $type > self::TYPE_MAX )
		{
			throw new Exception("Invalid value for type:$type");
		}
	
		$this->type = $type;
		$this->Reset_State(); // Make sure defaults are set for new transaction type.
	}
	
	
	public function Get_Types()
	{
		$types = array
		(
			  1 => '001-TYPE_CREATE_CARD_HOLDER'
			, 2 => '002-TYPE_CREATE_CARD'
			, 3 => '003-TYPE_CREATE_CARD_ACCOUNT'
			, 4 => '004-TYPE_CHANGE_CARD_STATUS'
			, 5 => '005-TYPE_CHANGE_PIN'
			, 6 => '006-TYPE_XFER_OTHER_CARD_ACCT_SAME_PAN'
			, 7 => '007-TYPE_DEPOSIT_TO_CARD_ACCOUNT'
			, 9 => '009-TYPE_VIEW_CARD_DETAILS'
			,10 => '010-TYPE_GET_CARD_ACCOUNT_FOR_CARD_NUMBER'
			,11 => '011-TYPE_ASSIGN_CARD_TO_CARD_HOLDER'
			,12 => '012-TYPE_VALIDATE_PIN_FOR_CARD_NUMBER'
			,13 => '013-TYPE_ANY_CARD_ACCT_TO_ANY_CARD_ACCT_XFER_VIA_EMAIL'
			,14 => '014-TYPE_REPLACE_CARD'
			,15 => '015-TYPE_EDIT_CARDHOLDER'
			,16 => '016-TYPE_MOVE_CARDHOLDER'
			,17 => '017-TYPE_DEPOSIT_TO_PRIMARY_CARD_ACCT'
			,18 => '018-TYPE_GET_SHORT_SUMMARY_FOR_CARD'
			,19 => '019-TYPE_GET_CARD_ACCOUNT_BALANCE'
			,20 => '020-TYPE_GET_CARD_TRANSACTION_DETAIL'
			,21 => '021-TYPE_REQUEST_FOR_PROGRAM_REVERSAL'
			,22 => '022-TYPE_GET_PROGRAM_REVERSAL_STATUS'
			,23 => '023-TYPE_VIEW_MIRROR_ACCOUNT_BAL'
			,24 => '024-TYPE_VIEW_PROGRAM_AVAIL_BAL'
			,25 => '025-TYPE_REVERSE_ALL_FUNDS_FROM_A_CARD'
			,26 => '026-TYPE_CREATE_PREPAID_MC_WITH_CARDHOLDER_VALIDATION'
			,27 => '027-TYPE_CREATE_PSEUDO_DDA_NUMBER'
			,28 => '028-TYPE_PROGRAM_STATEMENT'
			,29 => '029-TYPE_ANY_CARD_ACCT_TO_ANY_CARD_ACCT_XFER'
			,30 => '030-TYPE_VIEW_CARDPANS_BY_SSN'
			,31 => '031-TYPE_VIEW_CARD_STATUS'
		);
		
		return $types;
	}
	
	
	private function Init_Fields()
	{
		// The following array of key=>value pairs represents all the fields possible in 
		// CashLynk api transactions.  The key gives a descriptive name for the field but
		// is NOT the actual field name used in the query string; those are named P1...P30.
		//
		// The subarray contains the value for the field in subscript[0].  Subscripts [1]...[29]
		// correspond to the CashLynk api transactions 001 ... 029.  The value located in
		// subscript [1] ... [29] indicates if the field represented by this row is required
		// or optional for the transaction type represented by the column of the field.  The
		// first character can be F, O, or R (not case sensitive) where F (for fixed) indicates
		// the field is required and should not be cleared when Reset_State() is called; O
		// indicates the field is optional; R indicates the field is required.  If the field
		// at subscript [1] ... [29] is blank, the the field at this row is not used for
		// the transaction at this column.
		//
		// The rest of the characters in subscripts [1] ... [29] after the first character
		// indicate the actual field name to be used in the query string to represent the
		// field at this row unless the characters are 'p0' which indicates that the query
		// string will use the descriptive field name used as the key into the top level
		// of the array of fields.
		//
		// Why configure the data this way?
		// 1.  It makes it very easy to create an interactive html screen for
		//     submitting api calls which then makes it very easy to test the
		//     interface to CashLynk.  This data-driven approach to configuring
		//     the fields used for the api calls makes adding or changing api
		//     calls a simple matter of configuring this table.  An interactive
		//     screen has been created named "cashlynk_client_tester.php" which
		//     makes it easy to test the CashLynk api calls using just a browser.
		//
		// 2.  Because it separates our internal api from the CashLynk API.
		//     For example, if CashLynk decides that First Name should for
		//     transaction type 001 should be called P33 instead of P2, we only have
		//     to change 2 characters here but any in-house code calling the
		//     cashlynk_client.php wrapper does not have to change at all.
		//
		// 3.  It provides an easy to read table so that we can see at a glance
		//     which fields are used by which transactions.
		
	
		$this->fields = array
		(
			//                                              001      002      003      004      005      006      007      008      009      010      011      012      013      014      015      016      017      018      019      020      021      022      023      024      025      026      027      028     029     030    031
			//                                              ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---     ---     ---    ---
			 'CID'                             => array('', 'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'  ,'Fp0'  ,'Fp0' ,'Fp0'  )
			,'CUSR'                            => array('', 'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'  ,'Fp0'  ,'Fp0' ,'Fp0'  )
			,'CPWD'                            => array('', 'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'   ,'Fp0'  ,'Fp0'  ,'Fp0' ,'Fp0'  )
			,'FUNC'                            => array('', 'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'  ,'rp0'  ,'rp0' ,'rp0'  )
			,'MSGID'                           => array('', 'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'   ,'rp0'  ,'rp0'  ,'rp0' ,'rp0'  )
			
			,'CardholderID/SSN'                => array('', 'rp1'   ,'rp1'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'rp1'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,'rp1' ,''     )
			,'First_Name'                      => array('', 'op2'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op2'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'rp2'   ,''      ,''     ,''     ,''    ,''     )
			,'Middle_Initial'                  => array('', 'op3'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op3'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Last_Name'                       => array('', 'op4'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op4'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'rp3'   ,''      ,''     ,''     ,''    ,''     )
			,'Address_1'                       => array('', 'op5'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op5'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'rp4'   ,''      ,''     ,''     ,''    ,''     )
			,'Address_2'                       => array('', 'op6'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op6'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op5'   ,''      ,''     ,''     ,''    ,''     )
			,'City'                            => array('', 'op7'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op7'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'rp6'   ,''      ,''     ,''     ,''    ,''     )
			,'State'                           => array('', 'op8'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op8'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'rp7'   ,''      ,''     ,''     ,''    ,''     )
			,'Zip_Code'                        => array('', 'op9'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op9'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'rp8'   ,''      ,''     ,''     ,''    ,''     )
			,'Phone'                           => array('', 'op10'  ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op10'  ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'rp9'   ,''      ,''     ,''     ,''    ,''     )
			,'DOB'                             => array('', 'op11'  ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op11'  ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'rp10'  ,''      ,''     ,''     ,''    ,''     )
			,'Primary_CardholderID/SSN'        => array('', 'rp12'  ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op12'  ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Optional_Data1'                  => array('', 'op13'  ,'op7'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op13'  ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Optional_Data2'                  => array('', 'op14'  ,'op8'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op14'  ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Optional_Data3'                  => array('', 'op15'  ,'op9'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op15'  ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Notes'                           => array('', 'op16'  ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op16'  ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Email'                           => array('', 'op17'  ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op17'  ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op11'  ,''      ,''     ,''     ,''    ,''     )
			,'Reserved'                        => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Photo_ID'                        => array('', 'op19'  ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Question'                        => array('', 'op20'  ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Answer'                          => array('', 'op21'  ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Card_Stock'                      => array('', ''      ,'rp2'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Card_BIN'                        => array('', ''      ,'rp3'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Exp_Month_(MM)'                  => array('', ''      ,'rp4'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op8'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Exp_Year_(YYYY)'                 => array('', ''      ,'rp5'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op9'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'PIN'                             => array('', ''      ,'rp6'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'rp2'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Embossing_Line_2'                => array('', ''      ,'op10'  ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Embossing_Line_3'                => array('', ''      ,'op11'  ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
		
			//                                              001      002      003      004      005      006      007      008      009      010      011      012      013      014      015      016      017      018      019      020      021      022      023      024      025      026      027      028     029     030    031
			//                                              ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---     ---     ---    ---
		
			,'Embossing_Line_4'                => array('', ''      ,'op12'  ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Embossing_Line_5'                => array('', ''      ,'op13'  ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Ship_Name'                       => array('', ''      ,'rp14'  ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Ship_Address'                    => array('', ''      ,'rp15'  ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Ship_City'                       => array('', ''      ,'rp16'  ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Ship_State'                      => array('', ''      ,'rp17'  ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Ship_Zip'                        => array('', ''      ,'rp18'  ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Card_Member_Number'              => array('', ''      ,'op19'  ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'RefNo'                           => array('', ''      ,'op20'  ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Card_Account_Application'        => array('', ''      ,''      ,'rp1'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Economic_Program_ID'             => array('', ''      ,''      ,'rp2'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Funding_Program_ID'              => array('', ''      ,''      ,'rp3'   ,''      ,''      ,''      ,'rp3'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'rp1'   ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Card_Number'                     => array('', ''      ,''      ,'rp4'   ,'rp1'   ,'rp1'   ,''      ,''      ,''      ,'rp1'   ,'rp1'   ,'rp2'   ,'rp1'   ,''      ,'rp1'   ,''      ,''      ,'rp1'   ,'rp1'   ,''      ,'rp1'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,'rp1'  )
			,'Card_Member_Number'              => array('', ''      ,'op19'  ,'rp5'   ,'rp2'   ,''      ,'rp5'   ,''      ,''      ,'op4'   ,'op2'   ,'op3'   ,''      ,'rp5'   ,'op2'   ,''      ,''      ,''      ,'rp2'   ,''      ,'op4'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,'rp5'  ,''    ,'rp2'  )
			,'Deposit_Cycle_Limit'             => array('', ''      ,''      ,'op6'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Deposit_Cycle_Limit2'            => array('', ''      ,''      ,'op7'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Transfer_Cycle_Limit'            => array('', ''      ,''      ,'op8'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Transfer_Cycle_Limit2'           => array('', ''      ,''      ,'op9'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Cycle_2_Days'                    => array('', ''      ,''      ,'op10'  ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Maximum_Balance'                 => array('', ''      ,''      ,'op11'  ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Card_Account_Number'             => array('', ''      ,'op21'  ,'op21'  ,''      ,''      ,''      ,'rp1'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'rp1'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Amount'                          => array('', ''      ,''      ,''      ,''      ,''      ,''      ,'rp2'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'rp2'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Transaction_Type'                => array('', ''      ,''      ,''      ,''      ,''      ,''      ,'rp4'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'rp4'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Cardholder_SSN'                  => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'rp1'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Replace_Card'                    => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'rp3'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Replace_Card Number'             => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op4'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Replace_Card_Member_Number'      => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op5'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Charge_Cardholder_replace_fee'   => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op6'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Duplicate_card_option'           => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op7'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Shipping_Address_Code'           => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op10'  ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Existing_Cardholder_ID/SSN'      => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'rp1'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'New_Cardholder_ID/SSN'           => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'rp2'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
		
			//                                              001      002      003      004      005      006      007      008      009      010      011      012      013      014      015      016      017      018      019      020      021      022      023      024      025      026      027      028     029     030    031
			//                                              ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---     ---     ---    ---
		
			,'Program_Code'                    => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'rp3'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Start_Date'                      => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'rp2'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'rp2'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'End_Date'                        => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'rp3'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'rp3'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Deposit_Description_ID'          => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'rp2'   ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Batch_ID'                        => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'rp1'   ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Program_ID'                      => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'rp1'   ,'rp2'   ,'rp33'  ,''      ,'rp1'  ,''     ,''    ,''     )
			,'CardPAN'                         => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'rp1'   ,''      ,''      ,''     ,''     ,''    ,''     )
			,'New_Status'                      => array('', ''      ,''      ,''      ,'rp3'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Old_PIN'                         => array('', ''      ,''      ,''      ,''      ,'rp2'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'New_PIN'                         => array('', ''      ,''      ,''      ,''      ,'rp3'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Source_Card_Number'              => array('', ''      ,''      ,''      ,''      ,''      ,'rp1'   ,''      ,''      ,''      ,''      ,''      ,''      ,'rp1'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,'rp1'  ,''    ,''     )
			,'Source_Card_Account_Number'      => array('', ''      ,''      ,''      ,''      ,''      ,'rp2'   ,''      ,''      ,''      ,''      ,''      ,''      ,'rp2'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,'rp2'  ,''    ,''     )
			,'Destination_Card_Account_Number' => array('', ''      ,''      ,''      ,''      ,''      ,'rp3'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,'rp3'  ,''    ,''     )
			,'Transfer_Amount'                 => array('', ''      ,''      ,''      ,''      ,''      ,'rp4'   ,''      ,''      ,''      ,''      ,''      ,''      ,'rp4'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,'rp4'  ,''    ,''     )
			,'Destination_Email_address'       => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'rp3'   ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
			,'Bank_Account_1_Routing_Number'   => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op13'  ,''      ,''     ,''     ,''    ,''     )
			,'Bank_Account_1_Account_Number'   => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op14'  ,''      ,''     ,''     ,''    ,''     )
			,'Bank_Account_1_Account_Type'     => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op15'  ,''      ,''     ,''     ,''    ,''     )
			,'Bank_Account_1_Split_Mode'       => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op16'  ,''      ,''     ,''     ,''    ,''     )
			,'Bank_Account_1_Split_Amount'     => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op17'  ,''      ,''     ,''     ,''    ,''     )
			,'Bank_Account_1_Split_Percentage' => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op18'  ,''      ,''     ,''     ,''    ,''     )
			,'Bank_Account_2_Routing_Number'   => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op19'  ,''      ,''     ,''     ,''    ,''     )
			,'Bank_Account_2_Account_Number'   => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op20'  ,''      ,''     ,''     ,''    ,''     )
			,'Bank_Account_2_Account_Type'     => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op21'  ,''      ,''     ,''     ,''    ,''     )
			,'Bank_Account_2_Split_Mode'       => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op22'  ,''      ,''     ,''     ,''    ,''     )
		
			//                                              001      002      003      004      005      006      007      008      009      010      011      012      013      014      015      016      017      018      019      020      021      022      023      024      025      026      027      028     029     030    031
			//                                              ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---      ---     ---     ---    ---
		
			,'Bank_Account_2_Split_Amount'     => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op23'  ,''      ,''     ,''     ,''    ,''     )
			,'Bank_Account_2_Split_Percentage' => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op24'  ,''      ,''     ,''     ,''    ,''     )
			,'Bank_Account_3_Routing_Number'   => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op25'  ,''      ,''     ,''     ,''    ,''     )
			,'Bank_Account_3_Account_Number'   => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op26'  ,''      ,''     ,''     ,''    ,''     )
			,'Bank_Account_3_Account_Type'     => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op27'  ,''      ,''     ,''     ,''    ,''     )
			,'Bank_Account_3_Split_Mode'       => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op28'  ,''      ,''     ,''     ,''    ,''     )
			,'Bank_Account_3_Split_Amount'     => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op29'  ,''      ,''     ,''     ,''    ,''     )
			,'Bank_Account_3_Split_Percentage' => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op30'  ,''      ,''     ,''     ,''    ,''     )
			,'Second_Cardholder_First_Name'    => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op31'  ,''      ,''     ,''     ,''    ,''     )
			,'Second_Cardholder_Last_Name'     => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op32'  ,''      ,''     ,''     ,''    ,''     )
			,'BIN_Number'                      => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'rp34'  ,''      ,''     ,''     ,''    ,''     )
			,'Card_Stock_ID'                   => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'rp35'  ,''      ,''     ,''     ,''    ,''     )
			,'Card_PIN'                        => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'rp36'  ,''      ,''     ,''     ,''    ,''     )
			,'Activation_Card_Number'          => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op37'  ,''      ,''     ,''     ,''    ,''     )
			,'Ref_No'                          => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'op38'  ,''      ,''     ,''     ,''    ,''     )
			,'SSN'                             => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'rp1'   ,''     ,''     ,''    ,''     )
			,'SSN/Identifier'                  => array('', ''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,'rp1'   ,''      ,''     ,''     ,''    ,''     )
			,'Primary_Card_Number'             => array('', ''      ,'op22'  ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''      ,''     ,''     ,''    ,''     )
		);
	
	}
	
	
	// This function must be called whenever the transaction type field
	// changes in order to make sure all default values are set for the
	// new transaction type.
	
	public function Reset_State()
	{
		foreach( $this->fields as $key => $val )
		{
			if ( !isset( $val[$this->type] ) || !$this->Is_Field_Used_In_Transaction($val[$this->type]) )
			{
				$this->fields[$key][0] = '';
			}
			else if ( $this->Is_Value_Fixed($val[$this->type]) )
			{
				// nothing required, field is marked DO NOT RESET for this type.
			}
			else
			{
				$this->fields[$key][0] = '';
			}
		}
	
		// Set default values according to $type.
	
		$this->Set_Field( 'FUNC', str_pad($this->type, 3, '0', STR_PAD_LEFT) );

		// There isn't anything to be done here at this point.  Maybe I should delete this stuff.
		switch( $this->type )
		{
			// 1
			case self::TYPE_CREATE_CARD_HOLDER:
				break;
			// 2
			case self::TYPE_CREATE_CARD:
				break;
			// 3
			case self::TYPE_CREATE_CARD_ACCOUNT:
				break;
			// 4
			case self::TYPE_CHANGE_CARD_STATUS:
				break;
			// 5
			case self::TYPE_CHANGE_PIN:
				break;
			// 6
			case self::TYPE_XFER_OTHER_CARD_ACCT_SAME_PAN:
				break;
			// 7
			case self::TYPE_DEPOSIT_TO_CARD_ACCOUNT:
				break;
			// 8
			case self::TYPE_DEPOSIT_TO_CARD_ACCOUNT:
				break;
			// 9
			case self::TYPE_VIEW_CARD_DETAILS:
				break;
			// 10
			case self::TYPE_GET_CARD_ACCOUNT_FOR_CARD_NUMBER:
				break;
			// 11
			case self::TYPE_ASSIGN_CARD_TO_CARD_HOLDER:
				break;
			// 12
			case self::TYPE_VALIDATE_PIN_FOR_CARD_NUMBER:
				break;
			// 13
			case self::TYPE_ANY_CARD_ACCT_TO_ANY_CARD_ACCT_XFER_VIA_EMAIL:
				break;
			// 14
			case self::TYPE_REPLACE_CARD:
				break;
			// 15
			case self::TYPE_EDIT_CARDHOLDER:
				break;
			// 16
			case self::TYPE_MOVE_CARDHOLDER:
				break;
			// 17
			case self::TYPE_DEPOSIT_TO_PRIMARY_CARD_ACCT:
				break;
			// 18
			case self::TYPE_GET_SHORT_SUMMARY_FOR_CARD:
				break;
			// 19
			case self::TYPE_GET_CARD_ACCOUNT_BALANCE:
				break;
			// 20
			case self::TYPE_GET_CARD_TRANSACTION_DETAIL:
				break;
			// 21
			case self::TYPE_REQUEST_FOR_PROGRAM_REVERSAL:
				break;
			// 22
			case self::TYPE_GET_PROGRAM_REVERSAL_STATUS:
				break;
			// 23
			case self::TYPE_VIEW_MIRROR_ACCOUNT_BAL:
				break;
			// 24
			case self::TYPE_VIEW_PROGRAM_AVAIL_BAL:
				break;
			// 25
			case self::TYPE_REVERSE_ALL_FUNDS_FROM_A_CARD:
				break;
			// 26
			case self::TYPE_CREATE_PREPAID_MC_WITH_CARDHOLDER_VALIDATION:
				break;
			// 27
			case self::TYPE_CREATE_PSEUDO_DDA_NUMBER:
				break;
			// 28
			case self::TYPE_PROGRAM_STATEMENT:
				break;
			// 29
			case self::TYPE_ANY_CARD_ACCT_TO_ANY_CARD_ACCT_XFER:
				break;
		
		}
	}
	
	
	public function Set_Field( $field_name, $field_value )
	{
		if ( isset( $this->fields[$field_name] ) )
		{
			if ( !isset($field_value) ) $field_value = '';
			$this->fields[$field_name][0] = $field_value;
		}
	}
	
	
	public function Get_Field( $field_name )
	{
		if ( isset( $this->fields[$field_name] ) )
		{
			return $this->fields[$field_name][0];
		}
		else
		{
			return '';
		}
	}
	
	
	public function Set_Fields_From_Array( &$arr )
	{
		foreach( $arr as $key => $val ) $this->Set_Field( $key, $val );
	}
	
	
	// This routine returns true if all required fields are populated, false otherwise.
	// A list of the fields in error is passed in by reference and will be
	// populated with a comma-separated list of required fields that are empty.
	
	public function Validate_Fields_Are_Populated ( &$fields_in_error )
	{
		if ( isset($fields_in_error) ) $fields_in_error = '';
	
		$result = true;
	
		foreach( $this->fields as $key => $val )
		{
			if ( $this->Is_Value_Required_Or_Fixed($val[$this->type]) )
			{
				if ( !$this->is_field_populated($val[0]) )
				{
					$result = false;
					if ( isset($fields_in_error) )
					{
						$fields_in_error .= ($fields_in_error == '' ? '' : ', ') . $key;
					}
					else
					{
						return false;
					}
				}
			}
		}
	
		return $result;
	}
	
	
	public function Get_Required_Fields_Array()
	{
		$result = array();
	
		foreach( $this->fields as $key => $val )
		{
			if ( $this->Is_Value_Required_Or_Fixed($val[$this->type]) )
			{
				$result[$key] = $val[0];
			}
		}
		return $result;
	}
	
	
	public function Get_All_Fields_Array()
	{
		$result = array();
	
		foreach( $this->fields as $key => $val )
		{
			if ( $this->Is_Field_Used_In_Transaction($val[$this->type]) )
			{
				$result[$key] = $val[0];
			}
		}
		return $result;
	}
	
	
	public function Get_Populated_Fields_Array()
	{
		$result = array();
	
		foreach( $this->fields as $key => $val )
		{
			if ( $this->Is_Field_Used_In_Transaction($val[$this->type]) && $this->is_field_populated($val[0]) )
			{
				$result[$key] = $val[0];
			}
		}
		return $result;
	}
	
	
	public function Get_Populated_Fields_P_Names_Array()
	{
		$result = array();
	
		$p_name      = '';
		$value       = '';
		$requirement = '';

		foreach( $this->fields as $key => $val )
		{
			if ( $this->Is_Field_Used_In_Transaction($val[$this->type]) && $this->is_field_populated($val[0]) )
			{
				$this->Get_Field_Details( $key, $p_name, $value, $requirement );
				$result[$p_name] = $value;
			}
		}
		return $result;
	}
	
	
	// Used for debugging
	
	public function Get_Required_Fields_String( $separator_str = ',' )
	{
		$result = '';
		
		foreach( $this->fields as $key => $val )
		{
			if ( $this->Is_Value_Required_Or_Fixed($val[$this->type]) )
			{
				$result .= ($result == '' ? '' : $separator_str) . "$key=$val[0]";
			}
		}
		return $result;
	}
	
	
	// Used for debugging
	
	public function Get_All_Fields_String( $separator_str = ',' )
	{
		$result = '';
		
		foreach( $this->fields as $key => $val )
		{
			if ( $this->Is_Field_Used_In_Transaction($val[$this->type]) )
			{
				$result .= ($result == '' ? '' : $separator_str) . "$key=$val[0]";
			}
		}
		return $result;
	}
	
	
	// Used for debugging
	
	public function Get_Populated_Fields_String( $separator_str = ',' )
	{
		$result = '';
		
		foreach( $this->fields as $key => $val )
		{
			if ( $this->Is_Field_Used_In_Transaction($val[$this->type]) && $this->is_field_populated($val[0]) )
			{
				$result .= ($result == '' ? '' : $separator_str) . "$key=$val[0]";
			}
		}
		return $result;
	}


	public function Get_Field_Details( $field_name, &$query_name, &$value, &$requirement )
	{
		if ( !isset($this->fields[$field_name]) )
		{
			$query_name = 'P99';
			$value = '';
			$requirement = 'MissingSubarrayValue';
			return false;
		}

		$val_array = $this->fields[$field_name];

		if ( !$this->Is_Field_Used_In_Transaction($val_array[$this->type]) ) return false;

		$query_name = $this->Get_Field_Query_String_Name( $field_name, $val_array[$this->type] );

		$value = $val_array[0];

		if ( $this->Is_Value_Fixed($val_array[$this->type]) )
		{
			$requirement = 'Fixed';
		}
		else if ( $this->Is_Value_Required($val_array[$this->type]) )
		{
			$requirement = 'Required';
		}
		else if ( $this->Is_Value_Optional($val_array[$this->type]) )
		{
			$requirement = 'Optional';
		}
		else
		{
			$requirement = 'NotApplicable';
		}		
		
		return true;
	}


	private function Is_Value_Required_Or_Fixed( $val )
	{
		if ( !isset($val) || $val == '' ) return false;
		return ( $this->Is_Value_Required($val) || $this->Is_Value_Fixed($val) );
	}
	
	
	private function Is_Value_Required( $val )
	{
		if ( !isset($val) || $val == '' ) return false;
		$indicator = strtoupper(substr($val,0,1));
		if ( $indicator == self::FIELD_IS_REQUIRED ) return true;
		return false;
	}
	
	
	private function Is_Value_Fixed( $val )
	{
		if ( !isset($val) || $val == '' ) return false;
		$indicator = strtoupper(substr($val,0,1));
		if ( $indicator == self::FIELD_IS_FIXED ) return true;
		return false;
	}
	
	
	private function Is_Value_Optional( $val )
	{
		if ( !isset($val) || $val == '' ) return false;
		$indicator = strtoupper(substr($val,0,1));
		if ( $indicator == self::FIELD_IS_OPTIONAL ) return true;
		return false;
	}
	
	
	private function Is_Field_Used_In_Transaction( $val )
	{
		if ( !isset($val) || $val == '' ) return false;
		return ( $this->Is_Value_Required($val) || $this->Is_Value_Fixed($val) || $this->Is_Value_Optional($val) );
	}
	
	
	private function Get_Field_Query_String_Name( $descriptive_field_name, $val )
	{
		if ( !isset($val) || $val == '' || strlen($val) < 2 ) return 'P99';  // This should never happen; coding error if it does happen.
		$code = strtoupper(substr($val,1));
		if ( $code == 'P0' ) return $descriptive_field_name;
		return $code;
	}
	
	
	private function is_field_populated ( &$field )
	{
		if ( isset($field) && strlen($field) > 0 ) return true;
		return false;
	}

}
?>