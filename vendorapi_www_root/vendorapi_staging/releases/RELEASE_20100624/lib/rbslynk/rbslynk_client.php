<?
/**
    @publicsection
  @public
  @brief
    A class to help with HTTPS POSTing
    a credit card charge to RBS Lynk.

    RBS Lynk is a third-party credit card
    processor used by SellingSource.com

    RBS Lynk has 5 different categories
    of credit card transactions:

    1. Regular credit card transaction.
    2. Restaurant transaction with a tip.
    3. GiftLynk transaction.
    4. MOTO transaction (Mail Order/Telephone Order).
    5. MOTO restaurant transaction.

    Within each of these 5 categories there are subcategories of
    transaction types, each with a slightly different set of required
    and optional fields.  The main subcategories are:

    1. Authorization request (to verify and reserve funds).
    2. Sale request (to verify funds and charge a card).
    3. Settlement Request (to charge funds reserved by an authorization request).
    4. Force Capture (using voice authorization) Request.
    5. Refund Request (using previous order).
    6. Refund Request (using card number).

    This class currently supports the all of the Regular credit card transactions
    and all of the MOTO credit card transactions for a total of 12 different
    api calls (and 12 different record layouts).

    Example call:
      $rbslynk = new Rbslynk_Client();
      $rbslynk->Set_Operating_Mode( Rbslynk_Client::RUN_MODE_LIVE );
	  $rbslynk->Set_Transaction_Type( Rbslynk_Client_Data::TYPE_MOTO_SALE );
	  $rbslynk->Set_Field ( 'CardNumber', '4446661234567892' );
	  $rbslynk->Set_Field ( 'ExpirationDate', '12/2005' );  // MM/YY or MM/YYYY
	  $rbslynk->Set_Field ( 'Amount', 5.65 );
      $result = $rbslynk->Run_Post();

  @version
    $Revision: 2013 $
    
  @todo
    1.  Need to find out correct value to use for field: $rbslynk_url_live

    2.  Find out if my assumption that merchant identifying information never changes
        is true.  If this assumption is NOT true then method Set_Required_Login_Info(...)
        MUST be called and should not provide default values.

    3.  Find out default SellingSource.com merchant identifying information
        and place into function Set_Required_Login_Info(...) if the assumption
        in #2 was valid.

    4.  Find out the proper place to put this code and how to set includes appropriately.

  
*/

include_once( 'http_client.php' );
include_once( 'rbslynk_client_data.php' );


class Rbslynk_Client
{
	const RUN_MODE_MIN     = 1;
	const RUN_MODE_LIVE    = 1;
	const RUN_MODE_TEST    = 2;
	const RUN_MODE_NO_CALL = 3;
	const RUN_MODE_MAX     = 3;
	
	const RBSLYNK_URL_TEST = 'https://sundev.lynk-systems.com/testPmt'; // Used for testing.
	const RBSLYNK_URL_LIVE = 'https://sundev.lynk-systems.com/testPmt'; // LIVE processing. Find out correct value!
	const NO_CALL_TEXT     = 'Rbslynk_Client: NO URL Because run_mode = NO_CALL_TEXT';     // For debugging, use this text as the response from RBS Lynk.
		
	private $http_client;           // holds an instantiated Http_Client object.
	private $rbslynk_client_data;   // holds an instantiated Rbslynk_Client_Data object.
	private $run_mode;              // holds value of RUN_RUN_MODE_LIVE or RUN_RUN_MODE_TEST or RUN_MODE_NO_CALL.
	private $debug         = false;
	private $debug_logfile = '/_log/rbslynk_client.log';
	
	
	public function __construct()
	{
		if ($this->debug) $this->dlhlog('entering: __construct');
		$this->http_client = new Http_Client();
		$this->rbslynk_client_data = new Rbslynk_Client_Data( Rbslynk_Client_Data::TYPE_MOTO_SALE ); // for default, set datastructure for MOTO SALE.
		$this->run_mode = self::RUN_MODE_NO_CALL;
		$this->debug = false;
		$this->rbslynk_client_data->Reset_State();
		$this->Set_Required_Login_Info();     // Establish default merchant identifying information.
	}


	public function Set_Operating_Mode( $mode )
	{
		if ($this->debug) $this->dlhlog("entering: Set_Operating_Mode: mode=$mode");
		$this->run_mode = isset($mode) && is_numeric($mode) && $mode >= self::RUN_MODE_MIN && $mode <= self::RUN_MODE_MAX ? $mode : self::RUN_MODE_NO_CALL;
	}
	
	
	public function Set_Transaction_Type( $type )
	{
		if ($this->debug) $this->dlhlog("entering: Set_Transaction_Type: type=$type");
		$this->rbslynk_client_data->Set_Type($type);
	}
	
	
	public function Get_Transaction_Types()
	{
		if ($this->debug) $this->dlhlog("entering: Get_Transaction_Types");
		return $this->rbslynk_client_data->Get_Types();
	}
	
	
	public function Get_Url()
	{
		if ($this->debug) $this->dlhlog("entering: Get_Url");
	
		switch ( $this->run_mode )
		{
			case self::RUN_MODE_LIVE : return self::RBSLYNK_URL_LIVE; break;
			case self::RUN_MODE_TEST : return self::RBSLYNK_URL_TEST; break;
			default                  : return self::NO_CALL_TEXT;
		}
	}

  
	public function Validate_Fields_Are_Populated ( &$fields_in_error )
	{
		return $this->rbslynk_client_data->Validate_Fields_Are_Populated( $fields_in_error );
	}
	
	
	public function Set_Required_Login_Info( $svc_type = 'Sale', $store_id = '302430', $merchant_id = '542929801106147', $terminal_id = 'LK344899', $entry_mode = '1')
	{
		if ($this->debug) $this->dlhlog("entering: Set_Required_Login_Info: svc_type=$svc_type, store_id=$store_id, merchant_id=$merchant_id, terminal_id=$terminal_id, entry_mode=$entry_mode");
		$this->Set_Field('SvcType', $svc_type);
		$this->Set_Field('StoreId', $store_id);
		$this->Set_Field('MerchantID', $merchant_id);
		$this->Set_Field('TerminalId', $terminal_id);
		$this->Set_Field('EntryMode', $entry_mode);
	}

	
	// ***********************************************************************************
	// The following are convenience methods for executing transaction and getting
	// results as easily as possible.  You can call these convenience methods or you
	// can do exactly what the convenience method does - set the fields
	// using Set_Field(...) and then call Run_Post().
	//
	// The easy way to find out exactly which fields are required or optional
	// and exactly what they're named is to use the
	// screen "rbslynk_client_tester.php".  Using that screen, simply
	// pick a transaction type and the screen will tell you which fields
	// are required or optional and what their names are.  You can enter
	// data on that screen for the fields and submit the transaction and get
	// the result conveniently and interactively.
	// ***********************************************************************************
	
	public function Regular_Verify_Reserve( $custOrderId, $firstName, $middleName, $lastName, $businessName, $streetAddress, $city, $state, $zip, $country, $email, $phone, $cardNumber, $expirationDate, $cvv2, $entryMode, $ucData, $idMethod, $pc2On, $pc2CustId, $taxAmount, $taxExempt, $shipToFirstName, $shipToMiddleName, $shipToLastName, $shipToStreetAddress, $shipToCity, $shipToState, $shipToZipCode, $shipToCountry, $shipToEmail, $shipToPhone, $orderDesc, $amount )
	{
		$this->Set_Transaction_Type( Rbslynk_Client_Data::TYPE_REGULAR_VERIFY_RESERVE );	
		
		$this->Set_Field  ( 'CustOrderId', $this->make_not_null($custOrderId) );
		$this->Set_Field  ( 'FirstName', $this->make_not_null($firstName) );
		$this->Set_Field  ( 'MiddleName', $this->make_not_null($middleName) );
		$this->Set_Field  ( 'LastName', $this->make_not_null($lastName) );
		$this->Set_Field  ( 'BusinessName', $this->make_not_null($businessName) );
		$this->Set_Field  ( 'StreetAddress', $this->make_not_null($streetAddress) );
		$this->Set_Field  ( 'City', $this->make_not_null($city) );
		$this->Set_Field  ( 'State', $this->make_not_null($state) );
		$this->Set_Field  ( 'Zip', $this->make_not_null($zip) );
		$this->Set_Field  ( 'Country', $this->make_not_null($country) );
		$this->Set_Field  ( 'Email', $this->make_not_null($email) );
		$this->Set_Field  ( 'Phone', $this->make_not_null($phone) );
		$this->Set_Field  ( 'CardNumber', $this->make_not_null($cardNumber) );
		$this->Set_Field  ( 'ExpirationDate', $this->make_not_null($expirationDate) );
		$this->Set_Field  ( 'CVV2', $this->make_not_null($cvv2) );
		$this->Set_Field  ( 'EntryMode', $this->make_not_null($entryMode) );
		$this->Set_Field  ( 'UCData', $this->make_not_null($ucData) );
		$this->Set_Field  ( 'IDMethod', $this->make_not_null($idMethod) );
		$this->Set_Field  ( 'PC2On', $this->make_not_null($pc2On) );
		$this->Set_Field  ( 'PC2CustId', $this->make_not_null($pc2CustId) );
		$this->Set_Field  ( 'TaxAmount', $this->make_not_null($taxAmount) );
		$this->Set_Field  ( 'TaxExempt', $this->make_not_null($taxExempt) );
		$this->Set_Field  ( 'ShipToFirstName', $this->make_not_null($shipToFirstName) );
		$this->Set_Field  ( 'ShipToMiddleName', $this->make_not_null($shipToMiddleName) );
		$this->Set_Field  ( 'ShipToLastName', $this->make_not_null($shipToLastName) );
		$this->Set_Field  ( 'ShipToStreetAddress', $this->make_not_null($shipToStreetAddress) );
		$this->Set_Field  ( 'ShipToCity', $this->make_not_null($shipToCity) );
		$this->Set_Field  ( 'ShipToState', $this->make_not_null($shipToState) );
		$this->Set_Field  ( 'ShipToZipCode', $this->make_not_null($shipToZipCode) );
		$this->Set_Field  ( 'ShipToCountry', $this->make_not_null($shipToCountry) );
		$this->Set_Field  ( 'ShipToEmail', $this->make_not_null($shipToEmail) );
		$this->Set_Field  ( 'ShipToPhone', $this->make_not_null($shipToPhone) );
		$this->Set_Field  ( 'OrderDesc', $this->make_not_null($orderDesc) );
		$this->Set_Field  ( 'Amount', $this->make_not_null($amount) );

	    return $this->Query_String_To_Array( $this->Run_Post() );
	}

	
	public function Regular_Sale( $custOrderId, $firstName, $middleName, $lastName, $businessName, $streetAddress, $city, $state, $zip, $country, $email, $phone, $cardNumber, $expirationDate, $cvv2, $entryMode, $ucData, $idMethod, $pc2CustId, $taxAmount, $taxExempt, $shipToFirstName, $shipToMiddleName, $shipToLastName, $shipToStreetAddress, $shipToCity, $shipToState, $shipToZipCode, $shipToCountry, $shipToEmail, $shipToPhone, $orderDesc, $amount )
	{
		$this->Set_Transaction_Type( Rbslynk_Client_Data::TYPE_REGULAR_SALE );	
		
		$this->Set_Field  ( 'CustOrderId', $this->make_not_null($custOrderId) );
		$this->Set_Field  ( 'FirstName', $this->make_not_null($firstName) );
		$this->Set_Field  ( 'MiddleName', $this->make_not_null($middleName) );
		$this->Set_Field  ( 'LastName', $this->make_not_null($lastName) );
		$this->Set_Field  ( 'BusinessName', $this->make_not_null($businessName) );
		$this->Set_Field  ( 'StreetAddress', $this->make_not_null($streetAddress) );
		$this->Set_Field  ( 'City', $this->make_not_null($city) );
		$this->Set_Field  ( 'State', $this->make_not_null($state) );
		$this->Set_Field  ( 'Zip', $this->make_not_null($zip) );
		$this->Set_Field  ( 'Country', $this->make_not_null($country) );
		$this->Set_Field  ( 'Email', $this->make_not_null($email) );
		$this->Set_Field  ( 'Phone', $this->make_not_null($phone) );
		$this->Set_Field  ( 'CardNumber', $this->make_not_null($cardNumber) );
		$this->Set_Field  ( 'ExpirationDate', $this->make_not_null($expirationDate) );
		$this->Set_Field  ( 'CVV2', $this->make_not_null($cvv2) );
		$this->Set_Field  ( 'EntryMode', $this->make_not_null($entryMode) );
		$this->Set_Field  ( 'UCData', $this->make_not_null($ucData) );
		$this->Set_Field  ( 'IDMethod', $this->make_not_null($idMethod) );
		$this->Set_Field  ( 'PC2CustId', $this->make_not_null($pc2CustId) );
		$this->Set_Field  ( 'TaxAmount', $this->make_not_null($taxAmount) );
		$this->Set_Field  ( 'TaxExempt', $this->make_not_null($taxExempt) );
		$this->Set_Field  ( 'ShipToFirstName', $this->make_not_null($shipToFirstName) );
		$this->Set_Field  ( 'ShipToMiddleName', $this->make_not_null($shipToMiddleName) );
		$this->Set_Field  ( 'ShipToLastName', $this->make_not_null($shipToLastName) );
		$this->Set_Field  ( 'ShipToStreetAddress', $this->make_not_null($shipToStreetAddress) );
		$this->Set_Field  ( 'ShipToCity', $this->make_not_null($shipToCity) );
		$this->Set_Field  ( 'ShipToState', $this->make_not_null($shipToState) );
		$this->Set_Field  ( 'ShipToZipCode', $this->make_not_null($shipToZipCode) );
		$this->Set_Field  ( 'ShipToCountry', $this->make_not_null($shipToCountry) );
		$this->Set_Field  ( 'ShipToEmail', $this->make_not_null($shipToEmail) );
		$this->Set_Field  ( 'ShipToPhone', $this->make_not_null($shipToPhone) );
		$this->Set_Field  ( 'OrderDesc', $this->make_not_null($orderDesc) );
		$this->Set_Field  ( 'Amount', $this->make_not_null($amount) );

	    return $this->Query_String_To_Array( $this->Run_Post() );
	}


	public function Regular_Settlement( $OrderId, $Amount, $SellerId, $Password, $PC2CustId, $TaxAmount, $TaxExempt, $ShipperName, $TrackingNumber )
	{
		$this->Set_Transaction_Type( Rbslynk_Client_Data::TYPE_REGULAR_SETTLEMENT_REQUEST );	
		
		$this->Set_Field  ( 'OrderId', $this->make_not_null($OrderId) );
		$this->Set_Field  ( 'Amount', $this->make_not_null($Amount) );
		$this->Set_Field  ( 'SellerId', $this->make_not_null($SellerId) );
		$this->Set_Field  ( 'Password', $this->make_not_null($Password) );
		$this->Set_Field  ( 'PC2CustId', $this->make_not_null($PC2CustId) );
		$this->Set_Field  ( 'TaxAmount', $this->make_not_null($TaxAmount) );
		$this->Set_Field  ( 'TaxExempt', $this->make_not_null($TaxExempt) );
		$this->Set_Field  ( 'ShipperName', $this->make_not_null($ShipperName) );
		$this->Set_Field  ( 'TrackingNumber', $this->make_not_null($TrackingNumber) );

	    return $this->Query_String_To_Array( $this->Run_Post() );
	}
	
	
	public function Regular_Force_Capture( $CustOrderId , $FirstName , $MiddleName , $LastName , $BusinessName , $StreetAddress , $City , $State , $Zip , $Country , $Email , $Phone , $CardNumber , $ExpirationDate , $EntryMode , $IDMethod , $PC2CustId , $TaxAmount , $TaxExempt , $ShipToFirstName , $ShipToMiddleName , $ShipToLastName , $ShipToStreetAddress , $ShipToCity , $ShipToState , $ShipToZipCode , $ShipToCountry , $ShipToEmail , $ShipToPhone , $OrderDesc , $Amount , $SellerId , $Password , $ApprovalCode )
	{
		$this->Set_Transaction_Type( Rbslynk_Client_Data::TYPE_REGULAR_FORCE_CAPTURE_VOICE );	

		$this->Set_Field  ( 'CustOrderId', $this->make_not_null($CustOrderId) );
		$this->Set_Field  ( 'FirstName', $this->make_not_null($FirstName) );
		$this->Set_Field  ( 'MiddleName', $this->make_not_null($MiddleName) );
		$this->Set_Field  ( 'LastName', $this->make_not_null($LastName) );
		$this->Set_Field  ( 'BusinessName', $this->make_not_null($BusinessName) );
		$this->Set_Field  ( 'StreetAddress', $this->make_not_null($StreetAddress) );
		$this->Set_Field  ( 'City', $this->make_not_null($City) );
		$this->Set_Field  ( 'State', $this->make_not_null($State) );
		$this->Set_Field  ( 'Zip', $this->make_not_null($Zip) );
		$this->Set_Field  ( 'Country', $this->make_not_null($Country) );
		$this->Set_Field  ( 'Email', $this->make_not_null($Email) );
		$this->Set_Field  ( 'Phone', $this->make_not_null($Phone) );
		$this->Set_Field  ( 'CardNumber', $this->make_not_null($CardNumber) );
		$this->Set_Field  ( 'ExpirationDate', $this->make_not_null($ExpirationDate) );
		$this->Set_Field  ( 'EntryMode', $this->make_not_null($EntryMode) );
		$this->Set_Field  ( 'IDMethod', $this->make_not_null($IDMethod) );
		$this->Set_Field  ( 'PC2CustId', $this->make_not_null($PC2CustId) );
		$this->Set_Field  ( 'TaxAmount', $this->make_not_null($TaxAmount) );
		$this->Set_Field  ( 'TaxExempt', $this->make_not_null($TaxExempt) );
		$this->Set_Field  ( 'ShipToFirstName', $this->make_not_null($ShipToFirstName) );
		$this->Set_Field  ( 'ShipToMiddleName', $this->make_not_null($ShipToMiddleName) );
		$this->Set_Field  ( 'ShipToLastName', $this->make_not_null($ShipToLastName) );
		$this->Set_Field  ( 'ShipToStreetAddress', $this->make_not_null($ShipToStreetAddress) );
		$this->Set_Field  ( 'ShipToCity', $this->make_not_null($ShipToCity) );
		$this->Set_Field  ( 'ShipToState', $this->make_not_null($ShipToState) );
		$this->Set_Field  ( 'ShipToZipCode', $this->make_not_null($ShipToZipCode) );
		$this->Set_Field  ( 'ShipToCountry', $this->make_not_null($ShipToCountry) );
		$this->Set_Field  ( 'ShipToEmail', $this->make_not_null($ShipToEmail) );
		$this->Set_Field  ( 'ShipToPhone', $this->make_not_null($ShipToPhone) );
		$this->Set_Field  ( 'OrderDesc', $this->make_not_null($OrderDesc) );
		$this->Set_Field  ( 'Amount', $this->make_not_null($Amount) );
		$this->Set_Field  ( 'SellerId', $this->make_not_null($SellerId) );
		$this->Set_Field  ( 'Password', $this->make_not_null($Password) );
		$this->Set_Field  ( 'ApprovalCode', $this->make_not_null($ApprovalCode) );

	    return $this->Query_String_To_Array( $this->Run_Post() );
	}
	
	
	public function Regular_Refund_Request_Using_Previous_Order( $OrderId, $Amount, $SellerId, $Password )
	{
		$this->Set_Transaction_Type( Rbslynk_Client_Data::TYPE_REGULAR_REFUND_PREV_ORDER );	

		$this->Set_Field  ( 'OrderId', $this->make_not_null($OrderId) );
		$this->Set_Field  ( 'Amount', $this->make_not_null($Amount) );
		$this->Set_Field  ( 'SellerId', $this->make_not_null($SellerId) );
		$this->Set_Field  ( 'Password', $this->make_not_null($Password) );

	    return $this->Query_String_To_Array( $this->Run_Post() );
	}
	
	
	public function Regular_Refund_Request_Using_Card_Number( $CustOrderId , $FirstName , $MiddleName , $LastName , $BusinessName , $StreetAddress , $City , $State , $Zip , $Country , $Email , $Phone , $CardNumber , $ExpirationDate , $EntryMode , $OrderDesc , $Amount , $SellerId , $Password )
	{
		$this->Set_Transaction_Type( Rbslynk_Client_Data::TYPE_REGULAR_REFUND_CARD_NUMBER );	

		$this->Set_Field  ( 'CustOrderId', $this->make_not_null($CustOrderId) );
		$this->Set_Field  ( 'FirstName', $this->make_not_null($FirstName) );
		$this->Set_Field  ( 'MiddleName', $this->make_not_null($MiddleName) );
		$this->Set_Field  ( 'LastName', $this->make_not_null($LastName) );
		$this->Set_Field  ( 'BusinessName', $this->make_not_null($BusinessName) );
		$this->Set_Field  ( 'StreetAddress', $this->make_not_null($StreetAddress) );
		$this->Set_Field  ( 'City', $this->make_not_null($City) );
		$this->Set_Field  ( 'State', $this->make_not_null($State) );
		$this->Set_Field  ( 'Zip', $this->make_not_null($Zip) );
		$this->Set_Field  ( 'Country', $this->make_not_null($Country) );
		$this->Set_Field  ( 'Email', $this->make_not_null($Email) );
		$this->Set_Field  ( 'Phone', $this->make_not_null($Phone) );
		$this->Set_Field  ( 'CardNumber', $this->make_not_null($CardNumber) );
		$this->Set_Field  ( 'ExpirationDate', $this->make_not_null($ExpirationDate) );
		$this->Set_Field  ( 'EntryMode', $this->make_not_null($EntryMode) );
		$this->Set_Field  ( 'OrderDesc', $this->make_not_null($OrderDesc) );
		$this->Set_Field  ( 'Amount', $this->make_not_null($Amount) );
		$this->Set_Field  ( 'SellerId', $this->make_not_null($SellerId) );
		$this->Set_Field  ( 'Password', $this->make_not_null($Password) );

	    return $this->Query_String_To_Array( $this->Run_Post() );
	}
	
	
	public function Moto_Verify_Reserve( $CustOrderId , $FirstName , $MiddleName , $LastName , $BusinessName , $StreetAddress , $City , $State , $Zip , $Country , $Email , $Phone , $CardNumber , $ExpirationDate , $CVV2 , $EntryMode , $IDMethod , $PC2On , $PC2CustId , $TaxAmount , $TaxExempt , $ShipToFirstName , $ShipToMiddleName , $ShipToLastName , $ShipToStreetAddress , $ShipToCity , $ShipToState , $ShipToZipCode , $ShipToCountry , $ShipToEmail , $ShipToPhone , $OrderDesc , $Amount )
	{
		$this->Set_Transaction_Type( Rbslynk_Client_Data::TYPE_MOTO_VERIFY_RESERVE );	

		$this->Set_Field  ( 'CustOrderId', $this->make_not_null($CustOrderId) );
		$this->Set_Field  ( 'FirstName', $this->make_not_null($FirstName) );
		$this->Set_Field  ( 'MiddleName', $this->make_not_null($MiddleName) );
		$this->Set_Field  ( 'LastName', $this->make_not_null($LastName) );
		$this->Set_Field  ( 'BusinessName', $this->make_not_null($BusinessName) );
		$this->Set_Field  ( 'StreetAddress', $this->make_not_null($StreetAddress) );
		$this->Set_Field  ( 'City', $this->make_not_null($City) );
		$this->Set_Field  ( 'State', $this->make_not_null($State) );
		$this->Set_Field  ( 'Zip', $this->make_not_null($Zip) );
		$this->Set_Field  ( 'Country', $this->make_not_null($Country) );
		$this->Set_Field  ( 'Email', $this->make_not_null($Email) );
		$this->Set_Field  ( 'Phone', $this->make_not_null($Phone) );
		$this->Set_Field  ( 'CardNumber', $this->make_not_null($CardNumber) );
		$this->Set_Field  ( 'ExpirationDate', $this->make_not_null($ExpirationDate) );
		$this->Set_Field  ( 'CVV2', $this->make_not_null($CVV2) );
		$this->Set_Field  ( 'EntryMode', $this->make_not_null($EntryMode) );
		$this->Set_Field  ( 'IDMethod', $this->make_not_null($IDMethod) );
		$this->Set_Field  ( 'PC2On', $this->make_not_null($PC2On) );
		$this->Set_Field  ( 'PC2CustId', $this->make_not_null($PC2CustId) );
		$this->Set_Field  ( 'TaxAmount', $this->make_not_null($TaxAmount) );
		$this->Set_Field  ( 'TaxExempt', $this->make_not_null($TaxExempt) );
		$this->Set_Field  ( 'ShipToFirstName', $this->make_not_null($ShipToFirstName) );
		$this->Set_Field  ( 'ShipToMiddleName', $this->make_not_null($ShipToMiddleName) );
		$this->Set_Field  ( 'ShipToLastName', $this->make_not_null($ShipToLastName) );
		$this->Set_Field  ( 'ShipToStreetAddress', $this->make_not_null($ShipToStreetAddress) );
		$this->Set_Field  ( 'ShipToCity', $this->make_not_null($ShipToCity) );
		$this->Set_Field  ( 'ShipToState', $this->make_not_null($ShipToState) );
		$this->Set_Field  ( 'ShipToZipCode', $this->make_not_null($ShipToZipCode) );
		$this->Set_Field  ( 'ShipToCountry', $this->make_not_null($ShipToCountry) );
		$this->Set_Field  ( 'ShipToEmail', $this->make_not_null($ShipToEmail) );
		$this->Set_Field  ( 'ShipToPhone', $this->make_not_null($ShipToPhone) );
		$this->Set_Field  ( 'OrderDesc', $this->make_not_null($OrderDesc) );
		$this->Set_Field  ( 'Amount', $this->make_not_null($Amount) );

	    return $this->Query_String_To_Array( $this->Run_Post() );
	}
	
	
	public function Moto_Sale( $CustOrderId, $FirstName, $MiddleName, $LastName, $BusinessName, $StreetAddress, $City, $State, $Zip, $Country, $Email, $Phone, $CardNumber, $ExpirationDate, $CVV2, $EntryMode, $IDMethod, $PC2CustId, $TaxAmount, $TaxExempt, $ShipToFirstName, $ShipToMiddleName, $ShipToLastName, $ShipToStreetAddress, $ShipToCity, $ShipToState, $ShipToZipCode, $ShipToCountry, $ShipToEmail, $ShipToPhone, $OrderDesc, $Amount )
	{
		$this->Set_Transaction_Type( Rbslynk_Client_Data::TYPE_MOTO_SALE );	

		$this->Set_Field  ( 'CustOrderId', $this->make_not_null($CustOrderId) );
		$this->Set_Field  ( 'FirstName', $this->make_not_null($FirstName) );
		$this->Set_Field  ( 'MiddleName', $this->make_not_null($MiddleName) );
		$this->Set_Field  ( 'LastName', $this->make_not_null($LastName) );
		$this->Set_Field  ( 'BusinessName', $this->make_not_null($BusinessName) );
		$this->Set_Field  ( 'StreetAddress', $this->make_not_null($StreetAddress) );
		$this->Set_Field  ( 'City', $this->make_not_null($City) );
		$this->Set_Field  ( 'State', $this->make_not_null($State) );
		$this->Set_Field  ( 'Zip', $this->make_not_null($Zip) );
		$this->Set_Field  ( 'Country', $this->make_not_null($Country) );
		$this->Set_Field  ( 'Email', $this->make_not_null($Email) );
		$this->Set_Field  ( 'Phone', $this->make_not_null($Phone) );
		$this->Set_Field  ( 'CardNumber', $this->make_not_null($CardNumber) );
		$this->Set_Field  ( 'ExpirationDate', $this->make_not_null($ExpirationDate) );
		$this->Set_Field  ( 'CVV2', $this->make_not_null($CVV2) );
		$this->Set_Field  ( 'EntryMode', $this->make_not_null($EntryMode) );
		$this->Set_Field  ( 'IDMethod', $this->make_not_null($IDMethod) );
		$this->Set_Field  ( 'PC2CustId', $this->make_not_null($PC2CustId) );
		$this->Set_Field  ( 'TaxAmount', $this->make_not_null($TaxAmount) );
		$this->Set_Field  ( 'TaxExempt', $this->make_not_null($TaxExempt) );
		$this->Set_Field  ( 'ShipToFirstName', $this->make_not_null($ShipToFirstName) );
		$this->Set_Field  ( 'ShipToMiddleName', $this->make_not_null($ShipToMiddleName) );
		$this->Set_Field  ( 'ShipToLastName', $this->make_not_null($ShipToLastName) );
		$this->Set_Field  ( 'ShipToStreetAddress', $this->make_not_null($ShipToStreetAddress) );
		$this->Set_Field  ( 'ShipToCity', $this->make_not_null($ShipToCity) );
		$this->Set_Field  ( 'ShipToState', $this->make_not_null($ShipToState) );
		$this->Set_Field  ( 'ShipToZipCode', $this->make_not_null($ShipToZipCode) );
		$this->Set_Field  ( 'ShipToCountry', $this->make_not_null($ShipToCountry) );
		$this->Set_Field  ( 'ShipToEmail', $this->make_not_null($ShipToEmail) );
		$this->Set_Field  ( 'ShipToPhone', $this->make_not_null($ShipToPhone) );
		$this->Set_Field  ( 'OrderDesc', $this->make_not_null($OrderDesc) );
		$this->Set_Field  ( 'Amount', $this->make_not_null($Amount) );

	    return $this->Query_String_To_Array( $this->Run_Post() );
	}
	
	
	public function Moto_Settlement_Request( $OrderId, $Amount, $SellerId, $Password, $PC2CustId, $TaxAmount, $TaxExempt, $ShipperName, $TrackingNumber )
	{
		$this->Set_Transaction_Type( Rbslynk_Client_Data::TYPE_MOTO_SETTLEMENT_REQUEST );	
		
		$this->Set_Field  ( 'OrderId', $this->make_not_null($OrderId) );
		$this->Set_Field  ( 'Amount', $this->make_not_null($Amount) );
		$this->Set_Field  ( 'SellerId', $this->make_not_null($SellerId) );
		$this->Set_Field  ( 'Password', $this->make_not_null($Password) );
		$this->Set_Field  ( 'PC2CustId', $this->make_not_null($PC2CustId) );
		$this->Set_Field  ( 'TaxAmount', $this->make_not_null($TaxAmount) );
		$this->Set_Field  ( 'TaxExempt', $this->make_not_null($TaxExempt) );
		$this->Set_Field  ( 'ShipperName', $this->make_not_null($ShipperName) );
		$this->Set_Field  ( 'TrackingNumber', $this->make_not_null($TrackingNumber) );

	    return $this->Query_String_To_Array( $this->Run_Post() );
	}
	
	
	public function Moto_Force_Capture_Using_Voice_Authorization( $CustOrderId, $FirstName, $MiddleName, $LastName, $BusinessName, $StreetAddress, $City, $State, $Zip, $Country, $Email, $Phone, $CardNumber, $ExpirationDate, $EntryMode, $IDMethod, $PC2CustId, $TaxAmount, $TaxExempt, $ShipToFirstName, $ShipToMiddleName, $ShipToLastName, $ShipToStreetAddress, $ShipToCity, $ShipToState, $ShipToZipCode, $ShipToCountry, $ShipToEmail, $ShipToPhone, $OrderDesc, $Amount, $SellerId, $Password, $ApprovalCode )
	{
		$this->Set_Transaction_Type( Rbslynk_Client_Data::TYPE_MOTO_FORCE_CAPTURE_VOICE );	

		$this->Set_Field  ( 'CustOrderId', $this->make_not_null($CustOrderId) );
		$this->Set_Field  ( 'FirstName', $this->make_not_null($FirstName) );
		$this->Set_Field  ( 'MiddleName', $this->make_not_null($MiddleName) );
		$this->Set_Field  ( 'LastName', $this->make_not_null($LastName) );
		$this->Set_Field  ( 'BusinessName', $this->make_not_null($BusinessName) );
		$this->Set_Field  ( 'StreetAddress', $this->make_not_null($StreetAddress) );
		$this->Set_Field  ( 'City', $this->make_not_null($City) );
		$this->Set_Field  ( 'State', $this->make_not_null($State) );
		$this->Set_Field  ( 'Zip', $this->make_not_null($Zip) );
		$this->Set_Field  ( 'Country', $this->make_not_null($Country) );
		$this->Set_Field  ( 'Email', $this->make_not_null($Email) );
		$this->Set_Field  ( 'Phone', $this->make_not_null($Phone) );
		$this->Set_Field  ( 'CardNumber', $this->make_not_null($CardNumber) );
		$this->Set_Field  ( 'ExpirationDate', $this->make_not_null($ExpirationDate) );
		$this->Set_Field  ( 'EntryMode', $this->make_not_null($EntryMode) );
		$this->Set_Field  ( 'IDMethod', $this->make_not_null($IDMethod) );
		$this->Set_Field  ( 'PC2CustId', $this->make_not_null($PC2CustId) );
		$this->Set_Field  ( 'TaxAmount', $this->make_not_null($TaxAmount) );
		$this->Set_Field  ( 'TaxExempt', $this->make_not_null($TaxExempt) );
		$this->Set_Field  ( 'ShipToFirstName', $this->make_not_null($ShipToFirstName) );
		$this->Set_Field  ( 'ShipToMiddleName', $this->make_not_null($ShipToMiddleName) );
		$this->Set_Field  ( 'ShipToLastName', $this->make_not_null($ShipToLastName) );
		$this->Set_Field  ( 'ShipToStreetAddress', $this->make_not_null($ShipToStreetAddress) );
		$this->Set_Field  ( 'ShipToCity', $this->make_not_null($ShipToCity) );
		$this->Set_Field  ( 'ShipToState', $this->make_not_null($ShipToState) );
		$this->Set_Field  ( 'ShipToZipCode', $this->make_not_null($ShipToZipCode) );
		$this->Set_Field  ( 'ShipToCountry', $this->make_not_null($ShipToCountry) );
		$this->Set_Field  ( 'ShipToEmail', $this->make_not_null($ShipToEmail) );
		$this->Set_Field  ( 'ShipToPhone', $this->make_not_null($ShipToPhone) );
		$this->Set_Field  ( 'OrderDesc', $this->make_not_null($OrderDesc) );
		$this->Set_Field  ( 'Amount', $this->make_not_null($Amount) );
		$this->Set_Field  ( 'SellerId', $this->make_not_null($SellerId) );
		$this->Set_Field  ( 'Password', $this->make_not_null($Password) );
		$this->Set_Field  ( 'ApprovalCode', $this->make_not_null($ApprovalCode) );

	    return $this->Query_String_To_Array( $this->Run_Post() );
	}
	
	
	public function Moto_Refund_Request_Using_Previous_Order( $OrderId, $SellerId, $Password, $Amount )
	{
		$this->Set_Transaction_Type( Rbslynk_Client_Data::TYPE_MOTO_REFUND_PREV_ORDER );	

		$this->Set_Field  ( 'OrderId', $this->make_not_null($OrderId) );
		$this->Set_Field  ( 'SellerId', $this->make_not_null($SellerId) );
		$this->Set_Field  ( 'Password', $this->make_not_null($Password) );
		$this->Set_Field  ( 'Amount', $this->make_not_null($Amount) );

	    return $this->Query_String_To_Array( $this->Run_Post() );
	}
	
	
	public function Moto_Refund_Request_Using_Card_Number( $CustOrderId, $FirstName, $MiddleName, $LastName, $BusinessName, $StreetAddress, $City, $State, $Zip, $Country, $Email, $Phone, $CardNumber, $ExpirationDate, $EntryMode, $OrderDesc, $Amount, $SellerId, $Password )
	{
		$this->Set_Transaction_Type( Rbslynk_Client_Data::TYPE_MOTO_REFUND_CARD_NUMBER );	

		$this->Set_Field  ( 'CustOrderId', $this->make_not_null($CustOrderId) );
		$this->Set_Field  ( 'FirstName', $this->make_not_null($FirstName) );
		$this->Set_Field  ( 'MiddleName', $this->make_not_null($MiddleName) );
		$this->Set_Field  ( 'LastName', $this->make_not_null($LastName) );
		$this->Set_Field  ( 'BusinessName', $this->make_not_null($BusinessName) );
		$this->Set_Field  ( 'StreetAddress', $this->make_not_null($StreetAddress) );
		$this->Set_Field  ( 'City', $this->make_not_null($City) );
		$this->Set_Field  ( 'State', $this->make_not_null($State) );
		$this->Set_Field  ( 'Zip', $this->make_not_null($Zip) );
		$this->Set_Field  ( 'Country', $this->make_not_null($Country) );
		$this->Set_Field  ( 'Email', $this->make_not_null($Email) );
		$this->Set_Field  ( 'Phone', $this->make_not_null($Phone) );
		$this->Set_Field  ( 'CardNumber', $this->make_not_null($CardNumber) );
		$this->Set_Field  ( 'ExpirationDate', $this->make_not_null($ExpirationDate) );
		$this->Set_Field  ( 'EntryMode', $this->make_not_null($EntryMode) );
		$this->Set_Field  ( 'OrderDesc', $this->make_not_null($OrderDesc) );
		$this->Set_Field  ( 'Amount', $this->make_not_null($Amount) );
		$this->Set_Field  ( 'SellerId', $this->make_not_null($SellerId) );
		$this->Set_Field  ( 'Password', $this->make_not_null($Password) );

	    return $this->Query_String_To_Array( $this->Run_Post() );
	}
	
	
	// ***********************************************************************************
	// The following are the raw methods for executing a transaction.  You can use the
	// raw methods or you can use the simpler convenience methods above.
	// ***********************************************************************************
	

	// MOTO Sale Minimum Fields:     CardNumber, Amount, ExpirationDate.
	// REGULAR Sale Minimum Fields:  CardNumber, Amount, Zip.
	public function Set_Field( $field_name, $field_value )
	{
		$this->rbslynk_client_data->Set_Field($field_name, $field_value);
	}


	// If this object is configured to actually POST a message to RBSLynk, then the
	// result will be a string of key=value pairs separated by '&' like a query string.
	// Example return value: TransactionStatus=0&OrderId=100313&AVSResponse=Y&ApprovalCode=030455&
	
	public function Run_Post()
	{
		switch ( $this->run_mode )
		{
			case self::RUN_MODE_LIVE :
				$this->dlhlog("calling post with live url=" . self::RBSLYNK_URL_LIVE);
				return $this->http_client->Http_Post( self::RBSLYNK_URL_LIVE, $this->rbslynk_client_data->Get_Populated_Fields_Array() );
				break;
			case self::RUN_MODE_TEST :
				$this->dlhlog("calling post with test url=" . self::RBSLYNK_URL_TEST);
				return $this->http_client->Http_Post( self::RBSLYNK_URL_TEST, $this->rbslynk_client_data->Get_Populated_Fields_Array() );
				break;
			default :
				$this->dlhlog("NOT calling post with NO CALL url=" . self::NO_CALL_TEXT);
				return self::NO_CALL_TEXT;
				break;
		}
	}
	
	
	public function Get_Required_Fields_Array()
	{
		return $this->rbslynk_client_data->Get_Required_Fields_Array();
	}


	public function Get_All_Fields_Array()
	{
		return $this->rbslynk_client_data->Get_All_Fields_Array();
	}
	
	
	public function Get_Populated_Fields_Array()
	{
		return $this->rbslynk_client_data->Get_Populated_Fields_Array();
	}
	
	
	// Used for debugging
	public function Get_Required_Fields_String( $separator_str = ',' )
	{
		return $this->rbslynk_client_data->Get_Required_Fields_String($separator_str);
	}
	
	
	// Used for debugging
	public function Get_All_Fields_String( $separator_str = ',' )
	{
		return $this->rbslynk_client_data->Get_All_Fields_String($separator_str);
	}


	// Used for debugging
	public function Get_Populated_Fields_String( $separator_str = ',' )
	{
		return $this->rbslynk_client_data->Get_Populated_Fields_String($separator_str);
	}
	
	
	public function Set_Debug( $debug_value )
	{
		$this->debug = $debug_value ? true : false;
	}


	public function Set_Debug_Logfile( $logfile )
	{
		if ( is_field_populated($logfile) ) $this->debug_logfile = $logfile;
	}


	function Query_String_To_Array( $str )
	{
		$resultArray = array();
	
		$tempArray = explode( '&', $str );
		$k = null;
		$v = null;
		$i = 0;
		foreach( $tempArray as $key => $val )
		{
			$this->Get_Key_Value_Urldecoded( $val, $k, $v );
			if ( $k == '' )
			{
				$i++;
				$k = "unk$i";
			}
			$resultArray[$k] = $v;	
		}

		return $resultArray;
	}	


	function Get_Key_Value_Urldecoded( $str, &$key, &$val, $separator='=' )
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
	

	protected function vardump( $var, $stripnewlines=false ) {
		ob_start();
		print_r( $var );
		$output = ob_get_contents();
		ob_end_clean();
		if ( $stripnewlines ) {
			$output = strtr( $output, "\r\n", '  ' );
			$output = preg_replace( '/(\s)+/', ' ', $output );
		}
		return $output;
	}
  

	protected function dlhlog( $msg ) {
		$fp = fopen( $this->debug_logfile, 'a+' );
		if ( $fp )
		{
			fwrite($fp, date('Y-m-d H:i:s: ') . "$msg\r\n");
			fclose($fp);
		}
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
