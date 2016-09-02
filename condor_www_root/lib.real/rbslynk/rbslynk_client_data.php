<?php



class Rbslynk_Client_Data
{

	// These constants are the possible transaction types.  The type tells
	// what kind of API transaction will be called which tells which fields
	// are needed.  The value of $type must be one of these constants and will
	// be used as the subscript into an array.
	
	const TYPE_MIN                         =  1;  // smallest possible value for $type
	const TYPE_MOTO_SALE                   =  1;
	const TYPE_REGULAR_SALE                =  2;
	const TYPE_REGULAR_VERIFY_RESERVE      =  3;
	const TYPE_REGULAR_SETTLEMENT_REQUEST  =  4;
	const TYPE_REGULAR_FORCE_CAPTURE_VOICE =  5;
	const TYPE_REGULAR_REFUND_PREV_ORDER   =  6;
	const TYPE_REGULAR_REFUND_CARD_NUMBER  =  7;
	const TYPE_MOTO_VERIFY_RESERVE         =  8;
	const TYPE_MOTO_SETTLEMENT_REQUEST     =  9;
	const TYPE_MOTO_FORCE_CAPTURE_VOICE    = 10;
	const TYPE_MOTO_REFUND_PREV_ORDER      = 11;
	const TYPE_MOTO_REFUND_CARD_NUMBER     = 12;
	const TYPE_MAX                         = 12;  // largest possible value for $type
	
	
	// These constants define if a field is applicable to a particular transaction
	// type or not and if so, whether or not the field is required, optional or
	// required and should not be reset when Reset_State() is called.  A field
	// that should not be reset when Reset_State() is called is one containing
	// login information.
	
	const RBSLYNK_NOT_APPLICABLE      = 0;  // Field not applicable to type/api.
	const RBSLYNK_REQUIRED            = 1;  // Field is required.
	const RBSLYNK_REQUIRED_NORESET    = 2;  // Field is required DO NOT RESET IN Reset_State().
	const RBSLYNK_OPTIONAL            = 3;  // Field is optional.
	
	
	private $type;
	
	private $fields;  	// Array of arrays; key => field name, value => array containing
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
 			 1  => 'TYPE_MOTO_SALE'
			,2  => 'TYPE_REGULAR_SALE'
			,3  => 'TYPE_REGULAR_VERIFY_RESERVE'
			,4  => 'TYPE_REGULAR_SETTLEMENT_REQUEST'
			,5  => 'TYPE_REGULAR_FORCE_CAPTURE_VOICE'
			,6  => 'TYPE_REGULAR_REFUND_PREV_ORDER'
			,7  => 'TYPE_REGULAR_REFUND_CARD_NUMBER'
			,8  => 'TYPE_MOTO_VERIFY_RESERVE'
			,9  => 'TYPE_MOTO_SETTLEMENT_REQUEST'
			,10 => 'TYPE_MOTO_FORCE_CAPTURE_VOICE'
			,11 => 'TYPE_MOTO_REFUND_PREV_ORDER'
			,12 => 'TYPE_MOTO_REFUND_CARD_NUMBER'
		);
	
		return $types;
	}
	
	
	private function Init_Fields()
	{
	
		// These fields are just shorthand and are used just to reduce the amount
		// of text in order to make the big array more readable.
	
		$na     = self::RBSLYNK_NOT_APPLICABLE;
		$req    = self::RBSLYNK_REQUIRED;
		$sticky = self::RBSLYNK_REQUIRED_NORESET;
		$opt    = self::RBSLYNK_OPTIONAL;
	
	
		// The following array defines all possible fields for RBS Lynk
		// API calls for regular credit card transactions and for MOTO
		// transactions.
		//
		// Each row in the array has a key that gives the name
		// of one field for use in an RBS Lynk API call.  Not all fields
		// are used or required in all RBS Lynk API calls so each field
		// is associated with an array that tells which API calls the field
		// is valid for and whether or not the field is required or optional
		// for that particular type of API call.
		//
		// The data structure is set up this way so that we don't have to
		// create a new array and write new code each time we want
		// to add a new type of RBS Lynk API call.  Adding a new field or
		// a new data structure for a new RBS Lynk API call should be
		// very simple with this approach.
		//
		//     sub-array[0] == value for field represented by this row.
		//     sub-array[1] == flag MOTO SALE
		//     sub-array[2] == flag REGULAR CREDIT CARD SALE
		//
		//     * The flag can have values of:
		//       NOT_APPLICABLE,
		//       REQUIRED,
		//       REQUIRED AND DO NOT RESET WHEN CALLING Reset_State(),
		//       OPTIONAL.
		//
		//     If the flag in a particular subscript is not set it will be
		//     treated the same as NOT_APPLICABLE.  This way, it will be possible
		//     to easily add a new field for a new type when that field is not
		//     used in any preexisting types.  Simply add the new type sequentiall
		//     in the list of types, update TYPE_MAX, add the new field in the list
		//     with NOT_APPLICABLE in all the preexisting type subscripts and then
		//     tack on the new OPTIONAL or REQUIRED or REQUIRED_NORESET.
	
		$this->fields = array
		(
		//                                      1        2        3        4        5        6        7        8        9       10        11       12
		//                                      moto     regular  regular  regular  regular  regular  regular  moto     moto     moto      moto     moto
		//                                      sale     sale     reserve  stlmnt   forceCap refund   refund   reserve  stlmnt   forceCap  refund   refund
		//                                                                                   prevOrd  cardNum                              prevOrd  cardNum
		//                                      ----     -------  -------  -------  -------- -------  -------- -------  ------   --------  -------  -------
			'SvcType'              => array( '',  $sticky, $sticky, $sticky, $sticky, $sticky, $sticky, $sticky, $sticky, $sticky, $sticky,  $sticky, $sticky ),
			'StoreId'              => array( '',  $sticky, $sticky, $sticky, $sticky, $sticky, $sticky, $sticky, $sticky, $sticky, $sticky,  $sticky, $sticky ),
			'MerchantID'           => array( '',  $sticky, $sticky, $sticky, $sticky, $sticky, $sticky, $sticky, $sticky, $sticky, $sticky,  $sticky, $sticky ),
			'TerminalId'           => array( '',  $sticky, $sticky, $sticky, $sticky, $sticky, $sticky, $sticky, $sticky, $sticky, $sticky,  $sticky, $sticky ),
			'OrderId'              => array( '',  $na,     $na,     $na,     $req,    $na,     $req,    $na,     $na,     $req,    $na,      $req,    $na     ),
			'SellerId'             => array( '',  $na,     $na,     $na,     $req,    $req,    $req,    $req,    $na,     $req,    $req,     $req,    $req    ),
			'Password'             => array( '',  $na,     $na,     $na,     $req,    $req,    $req,    $req,    $na,     $req,    $req,     $req,    $req    ),
			'ApprovalCode'         => array( '',  $na,     $na,     $na,     $na,     $req,    $na,     $na,     $na,     $na,     $req,     $na,     $na     ),
			'ShipperName'          => array( '',  $na,     $na,     $na,     $opt,    $na,     $na,     $na,     $na,     $opt,    $na,      $na,     $na     ),
			'TrackingNumber'       => array( '',  $na,     $na,     $na,     $opt,    $na,     $na,     $na,     $na,     $opt,    $na,      $na,     $na     ),
			'CustOrderId'          => array( '',  $opt,    $opt,    $opt,    $na,     $opt,    $na,     $opt,    $opt,    $na,     $opt,     $na,     $opt    ),
			'FirstName'            => array( '',  $opt,    $req,    $req,    $na,     $req,    $na,     $req,    $opt,    $na,     $opt,     $na,     $opt    ),
			'MiddleName'           => array( '',  $opt,    $opt,    $opt,    $na,     $opt,    $na,     $opt,    $opt,    $na,     $opt,     $na,     $opt    ),
			'LastName'             => array( '',  $opt,    $opt,    $opt,    $na,     $req,    $na,     $opt,    $opt,    $na,     $opt,     $na,     $opt    ),
			'BusinessName'         => array( '',  $opt,    $opt,    $opt,    $na,     $opt,    $na,     $opt,    $opt,    $na,     $opt,     $na,     $opt    ),
			'StreetAddress'        => array( '',  $opt,    $req,    $req,    $na,     $req,    $na,     $req,    $opt,    $na,     $opt,     $na,     $opt    ),
		//                                      1        2        3        4        5        6        7        8        9       10        11       12
		//                                      moto     regular  regular  regular  regular  regular  regular  moto     moto     moto      moto     moto
		//                                      sale     sale     reserve  stlmnt   forceCap refund   refund   reserve  stlmnt   forceCap  refund   refund
		//                                                                                   prevOrd  cardNum                              prevOrd  cardNum
		//                                      ----     -------  -------  -------  -------- -------  -------- -------  ------   --------  -------  -------
			'City'                 => array( '',  $opt,    $req,    $req,    $na,     $req,    $na,     $req,    $opt,    $na,     $opt,     $na,     $opt    ),
			'State'                => array( '',  $opt,    $req,    $req,    $na,     $req,    $na,     $req,    $opt,    $na,     $opt,     $na,     $opt    ),
			'Zip'                  => array( '',  $opt,    $req,    $req,    $na,     $req,    $na,     $req,    $opt,    $na,     $opt,     $na,     $opt    ),
			'Country'              => array( '',  $opt,    $req,    $req,    $na,     $req,    $na,     $req,    $opt,    $na,     $opt,     $na,     $opt    ),
			'Email'                => array( '',  $opt,    $opt,    $opt,    $na,     $opt,    $na,     $opt,    $opt,    $na,     $opt,     $na,     $opt    ),
			'Phone'                => array( '',  $opt,    $opt,    $opt,    $na,     $opt,    $na,     $opt,    $opt,    $na,     $opt,     $na,     $opt    ),
			'CardNumber'           => array( '',  $req,    $req,    $req,    $na,     $req,    $na,     $req,    $req,    $na,     $req,     $na,     $req    ),
			'ExpirationDate'       => array( '',  $req,    $req,    $req,    $na,     $req,    $na,     $req,    $req,    $na,     $req,     $na,     $req    ),     // MM/YY or MM/YYYY
			'CVV2'                 => array( '',  $opt,    $opt,    $opt,    $na,     $na,     $na,     $na,     $opt,    $na,     $na,      $na,     $na     ),
			'EntryMode'            => array( '',  $sticky, $opt,    $opt,    $na,     $opt,    $na,     $opt,    $req,    $na,     $req,     $na,     $req    ),
			'UCData'               => array( '',  $na,     $opt,    $opt,    $na,     $na,     $na,     $na,     $na,     $na,     $na,      $na,     $na     ),
			'IDMethod'             => array( '',  $opt,    $opt,    $opt,    $na,     $opt,    $na,     $na,     $opt,    $na,     $opt,     $na,     $na     ),
			'PC2On'                => array( '',  $na,     $na,     $opt,    $na,     $na,     $na,     $na,     $opt,    $na,     $na,      $na,     $na     ),
			'PC2CustId'            => array( '',  $opt,    $opt,    $opt,    $opt,    $opt,    $na,     $na,     $opt,    $opt,    $opt,     $na,     $na     ),
			'TaxAmount'            => array( '',  $opt,    $opt,    $opt,    $opt,    $opt,    $na,     $na,     $opt,    $opt,    $opt,     $na,     $na     ),
		//                                      1        2        3        4        5        6        7        8        9       10        11       12
		//                                      moto     regular  regular  regular  regular  regular  regular  moto     moto     moto      moto     moto
		//                                      sale     sale     reserve  stlmnt   forceCap refund   refund   reserve  stlmnt   forceCap  refund   refund
		//                                                                                   prevOrd  cardNum                              prevOrd  cardNum
		//                                      ----     -------  -------  -------  -------- -------  -------- -------  ------   --------  -------  -------
			'TaxExempt'            => array( '',  $opt,    $opt,    $opt,    $opt,    $opt,    $na,     $na,     $opt,    $opt,    $opt,     $na,     $na     ),
			'ShipToFirstName'      => array( '',  $opt,    $opt,    $opt,    $na,     $opt,    $na,     $na,     $opt,    $na,     $opt,     $na,     $na     ),
			'ShipToMiddleName'     => array( '',  $opt,    $opt,    $opt,    $na,     $opt,    $na,     $na,     $opt,    $na,     $opt,     $na,     $na     ),
			'ShipToLastName'       => array( '',  $opt,    $opt,    $opt,    $na,     $opt,    $na,     $na,     $opt,    $na,     $opt,     $na,     $na     ),
			'ShipToStreetAddress'  => array( '',  $opt,    $opt,    $opt,    $na,     $opt,    $na,     $na,     $opt,    $na,     $opt,     $na,     $na     ),
			'ShipToCity'           => array( '',  $opt,    $opt,    $opt,    $na,     $opt,    $na,     $na,     $opt,    $na,     $opt,     $na,     $na     ),
			'ShipToState'          => array( '',  $opt,    $opt,    $opt,    $na,     $opt,    $na,     $na,     $opt,    $na,     $opt,     $na,     $na     ),
			'ShipToZipCode'        => array( '',  $opt,    $opt,    $opt,    $na,     $opt,    $na,     $na,     $opt,    $na,     $opt,     $na,     $na     ),
			'ShipToCountry'        => array( '',  $opt,    $opt,    $opt,    $na,     $opt,    $na,     $na,     $opt,    $na,     $opt,     $na,     $na     ),
			'ShipToEmail'          => array( '',  $opt,    $opt,    $opt,    $na,     $opt,    $na,     $na,     $opt,    $na,     $opt,     $na,     $na     ),
			'ShipToPhone'          => array( '',  $opt,    $opt,    $opt,    $na,     $opt,    $na,     $na,     $opt,    $na,     $opt,     $na,     $na     ),
			'OrderDesc'            => array( '',  $opt,    $opt,    $opt,    $na,     $opt,    $na,     $opt,    $opt,    $na,     $opt,     $na,     $opt    ),
			'Amount'               => array( '',  $req,    $req,    $req,    $req,    $req,    $req,    $req,    $req,    $req,    $req,     $req,    $req    )
		);
	}
	
	
	// This function must be called whenever the transaction type field
	// changes in order to make sure all default values are set for the
	// new transaction type.
	
	public function Reset_State()
	{
		foreach( $this->fields as $key => $val )
		{
			if ( !isset( $val[$this->type] ) )
			{
				$this->fields[$key][0] = '';
			}
			else if ( $val[$this->type] == self::RBSLYNK_REQUIRED_NORESET )
			{
				// nothing required, field is marked DO NOT RESET for this type.
			}
			else
			{
				$this->fields[$key][0] = '';
			}
		}
	
		// Set default values according to $type.
	
		switch( $this->type )
		{
			// 1
			case self::TYPE_MOTO_SALE:
				$this->Set_Field('SvcType', 'Sale');
				$this->Set_Field('EntryMode', '1');   // 1 for MOTO, 2 for recurring payment.
				break;
			// 2
			case self::TYPE_REGULAR_SALE:
				$this->Set_Field('SvcType', 'Sale');
				break;
			// 3
			case self:: TYPE_REGULAR_VERIFY_RESERVE:
				$this->Set_Field('SvcType', 'Authorize');
				break;
			// 4
			case self:: TYPE_REGULAR_SETTLEMENT_REQUEST:
				$this->Set_Field('SvcType', 'Settle');
				break;
			// 5
			case self:: TYPE_REGULAR_FORCE_CAPTURE_VOICE:
				$this->Set_Field('SvcType', 'ForceSettle');
				$this->Set_Field('EntryMode', '7');   // 7 for E-commerce (default)
				break;
			// 6
			case self:: TYPE_REGULAR_REFUND_PREV_ORDER:
				$this->Set_Field('SvcType', 'CreditOrder');
				break;
			// 7
			case self:: TYPE_REGULAR_REFUND_CARD_NUMBER:
				$this->Set_Field('SvcType', 'Credit');
				$this->Set_Field('EntryMode', '7');   // 7 for E-commerce (default)
				break;
			// 8
			case self:: TYPE_MOTO_VERIFY_RESERVE:
				$this->Set_Field('SvcType', 'Authorize');
				$this->Set_Field('EntryMode', '1');   // 1 for MOTO, 2 for reoccurring payment
				break;
			// 9
			case self:: TYPE_MOTO_SETTLEMENT_REQUEST:
				$this->Set_Field('SvcType', 'Settle');
				break;
			// 10
			case self:: TYPE_MOTO_FORCE_CAPTURE_VOICE:
				$this->Set_Field('SvcType', 'ForceSettle');
				$this->Set_Field('EntryMode', '1');   // 1 for MOTO
				break;
			// 11
			case self:: TYPE_MOTO_REFUND_PREV_ORDER:
				$this->Set_Field('SvcType', 'CreditOrder');
				break;
			// 11
			case self:: TYPE_MOTO_REFUND_CARD_NUMBER:
				$this->Set_Field('SvcType', 'Credit');
				$this->Set_Field('EntryMode', '1');   // 1 for MOTO
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
		if ( isset($val[$this->type]) && ( $val[$this->type] == self::RBSLYNK_REQUIRED || $val[$this->type] == self::RBSLYNK_REQUIRED_NORESET ) )
		{
			if ( !$this->is_field_populated($val[0]) ) {
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
		if ( isset($val[$this->type]) && ($val[$this->type] == self::RBSLYNK_REQUIRED || $val[$this->type] == self::RBSLYNK_REQUIRED_NORESET) )
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
		if ( isset($val[$this->type]) && $val[$this->type] != self::RBSLYNK_NOT_APPLICABLE )
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
		if ( isset($val[$this->type]) && $val[$this->type] != self::RBSLYNK_NOT_APPLICABLE && $this->is_field_populated($val[0]) )
		{
			$result[$key] = $val[0];
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
		if ( isset($val[$this->type]) && ($val[$this->type] == self::RBSLYNK_REQUIRED || $val[$this->type] == self::RBSLYNK_REQUIRED_NORESET) )
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
		if ( isset($val[$this->type]) && $val[$this->type] != self::RBSLYNK_NOT_APPLICABLE )
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
		if ( isset($val[$this->type]) && $val[$this->type] != self::RBSLYNK_NOT_APPLICABLE && $this->is_field_populated($val[0]) )
		{
			$result .= ($result == '' ? '' : $separator_str) . "$key=$val[0]";
		}
		}
		return $result;
	}
	
	
	private function is_field_populated ( &$field )
	{
		if ( isset($field) && strlen($field) > 0 ) return true;
		return false;
	}
  
}
?>