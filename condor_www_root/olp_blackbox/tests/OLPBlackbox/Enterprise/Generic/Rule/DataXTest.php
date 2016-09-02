<?php
require_once('OLPBlackboxTestSetup.php');

/**
 * PHPUnit test class for the OLPBlackbox_Rule_DataX class.
 *
 * @author Rob Voss <rob.voss@sellingsource.com>
 * 
 * @group datax_tests
 */
class OLPBlackbox_Enterprise_Generic_Rule_DataXTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Good times here with fake data
	 *
	 * @param OLPBlackbox_Data $blackbox_data 	The Blackbox Data object we are populating
	 * @return OLPBlackbox_Data $blackbox_data 	The Blackbox Data object we populated
	 */
	public function populateData($blackbox_data)
	{
		$blackbox_data->name_first 			= 'Testfirst';
		$blackbox_data->name_middle 		= 'G';
		$blackbox_data->name_last 			= 'Testlast';

		$blackbox_data->home_street 		= '1234 Street St.';
		$blackbox_data->home_unit 			= '#714';
		$blackbox_data->home_city 			= 'Cityplace';
		$blackbox_data->home_state 			= 'NV';
		$blackbox_data->home_zip 			= '89052';
		
		$blackbox_data->phone_home 			= '7025554321';
		$blackbox_data->phone_cell 			= '7025554322';
		$blackbox_data->ext_work 			= '';
		$blackbox_data->email_primary 		= 'rob.voss@sellingsource.com';
		$blackbox_data->client_ip_address	= '127.1.0.1';
		$blackbox_data->state_id_number 	= '1234NV';

		$blackbox_data->date_dob_y 			= '1977';
		$blackbox_data->date_dob_m 			= '03';
		$blackbox_data->date_dob_d 			= '23';

		$blackbox_data->bank_name 			= 'Test Bank';
		$blackbox_data->bank_aba 			= '303085476';
		$blackbox_data->bank_account 		= '24113521';
		$blackbox_data->bank_account_type 	= 'Checking';
		
		$blackbox_data->employer_name 		= 'Self';
		$blackbox_data->ssn_part_1 			= '123';
		$blackbox_data->ssn_part_2 			= '99';
		$blackbox_data->ssn_part_3 			= '9876';
		
		$blackbox_data->social_security_number = NULL;
		$blackbox_data->income_monthly_net = NULL;
		$blackbox_data->income_direct_deposit = NULL;
		
		return $blackbox_data;
	}

	/**
	 * Data Provider Array format is:
	 * 
	 * string 	Call_Type we are making.
	 * string 	Account we are using
	 * string 	Blackbox Mode
	 * bool 	allow_datax_rework flag
	 * string 	Track_hash
	 * XML 		Expected DataX XML Packet
	 * bool 	Expected return (TRUE/FALSE)
	 * 
	 * @return void
	 **/
	public static function isValidGenericDataProvider()
	{
		return array(
			// This is a IDV_PW call with an expected fail.
			array(
				OLPBlackbox_Enterprise_Generic_Rule_DataX::TYPE_IDV_PW, 
				'BB', 
				OLPBlackbox_Config::MODE_BROKER,
				FALSE,
				'',
				'Generic_Pass.xml',
				TRUE
				),
			// This is a IDV_PW call with an expected fail.
			array(
				OLPBlackbox_Enterprise_Generic_Rule_DataX::TYPE_IDV_PW, 
				'BB', 
				OLPBlackbox_Config::MODE_BROKER,
				FALSE,
				'',
				'Generic_Fail.xml',
				FALSE
				)
		);
	}
	
	/**
	 * Data Provider Array format is:
	 * 
	 * string 	Call_Type we are making.
	 * string 	Account we are using
	 * string 	Blackbox Mode
	 * bool 	allow_datax_rework flag
	 * string 	Track_hash
	 * XML 		Expected DataX XML Packet
	 * bool 	Expected return (TRUE/FALSE)
	 * 
	 * @return void
	 **/
	public static function getHistoryGenericDataProvider()
	{
		return array(
			// This is a IDV_PW call with an expected fail.
			array(
				OLPBlackbox_Enterprise_Generic_Rule_DataX::TYPE_IDV_PW, 
				'BB', 
				OLPBlackbox_Config::MODE_BROKER,
				FALSE,
				'',
				'Generic_Fail.xml',
				FALSE
				),
			// This is a IDV_PW call with an expected fail.
			array(
				OLPBlackbox_Enterprise_Generic_Rule_DataX::TYPE_IDV_PW, 
				'BB', 
				OLPBlackbox_Config::MODE_BROKER,
				FALSE,
				'b0d0490e9eda3b277cbef18df7cb9dc1',
				'Generic_Fail.xml',
				FALSE
				)
		);
	}
	
	/**
	 * Data Provider Array format is:
	 * 
	 * string 	Call_Type we are making.
	 * string 	Account we are using
	 * string 	Blackbox Mode
	 * bool 	allow_datax_rework flag
	 * string 	Track_hash
	 * XML 		Expected DataX XML Packet
	 * bool 	Expected return (TRUE/FALSE)
	 * 
	 * @return void
	 **/
	public static function exceptionDataProvider()
	{
		return array(
			// This is a IDV_PW call with an expected fail.
			array(
				OLPBlackbox_Enterprise_Generic_Rule_DataX::TYPE_IDV_PW, 
				'BB', 
				OLPBlackbox_Config::MODE_BROKER,
				FALSE,
				'',
				'Provider_Exception.xml',
				FALSE
				),
			// This is a IDV_PW call with an expected fail.
			array(
				OLPBlackbox_Enterprise_Generic_Rule_DataX::TYPE_IDV_PW, 
				'BB', 
				OLPBlackbox_Config::MODE_BROKER,
				FALSE,
				'',
				'DataX_Exception.xml',
				TRUE
				)
		);
	}
	
	/**
	 * Generic DataX Test function.
	 *
	 * @param string 	$call_type 				Call_Type we are making.
	 * @param string 	$account 				Account we are using
	 * @param string 	$blackbox_mode 			Blackbox Mode
	 * @param bool 		$allow_datax_rework 			allow_datax_rework flag
	 * @param string 	$track_hash 			Track_hash
	 * @param XML 		$expected_packet_file 	Expected DataX XML Packet
	 * @param bool 		$expected 				Expected return (TRUE/FALSE)
	 * 
	 * @dataProvider isValidGenericDataProvider
	 * 
	 * @return void
	 */
	public function testIsValidGeneric(
		$call_type,
		$account,
		$blackbox_mode,
		$allow_datax_rework,
		$track_hash,
		$expected_packet_file,
		$expected)
	{
		// Set up our objects
		$blackbox_data 	= new OLPBlackbox_Data();
		$state_data = new OLPBlackbox_StateData();
		$config_data = OLPBlackbox_Config::getInstance();
		
		// Populate our $blackbox_data
		$blackbox_data = $this->populateData($blackbox_data);
		
		$config_data->blackbox_mode = $blackbox_mode;
		
		// Calls the function for simulating the DataX XML packets.
		$datax_return = GET_DATAX_XML_FILE(dirname(__FILE__), $expected_packet_file);
		
		// Mock up the Authentication Object.
		$authentication = $this->getMock(
			'Authentication', 
			array(),
			array(NULL, NULL, NULL, NULL, NULL, NULL));
		
		// Mock up the DataX Object so that I can get a return from a file instead of the real deal.
		$lib_datax = $this->getMock('Data_X');
		
		// Set up the Mock DataX to return the supplied XML
		$lib_datax->expects($this->any())
			->method('Datax_Call')
			->will($this->returnValue(array()));
		$lib_datax->expects($this->any())
			->method('Get_Sent_Packet')
			->will($this->returnValue(''));
		$lib_datax->expects($this->any())
			->method('Get_Received_Packet')
			->will($this->returnValue($datax_return));
		
		// Mock the Generic DataX Rule
		$datax = $this->getMock(
			'OLPBlackbox_Enterprise_Generic_Rule_DataX', 
			array('initDataX', 'getHistory', 'initConfig', 'initAuthentication', 'hitEvent', 'hitStat'), 
			array($call_type, $account, $blackbox_data));
			
		// Have the initDataX call return our mock DataX object.
		$datax->expects($this->any())
			->method('initDataX')
			->will($this->returnValue($lib_datax));
			
		// Have the initConfig call return our mock Config object.
		$datax->expects($this->any())
			->method('initConfig')
			->will($this->returnValue($config_data));
	
		// Have the initAuthentication call return our mock DataX object.
		$datax->expects($this->any())
			->method('initAuthentication')
			->will($this->returnValue($authentication));
							
		$datax->track_hash = $track_hash;
		
		$tmp = $datax->isValid($blackbox_data, $state_data);
		
		$this->assertEquals($expected, $tmp);
	}
	
	/**
	 * Generic DataX Test function.
	 *
	 * @param string 	$call_type 				Call_Type we are making.
	 * @param string 	$account 				Account we are using
	 * @param string 	$blackbox_mode 			Blackbox Mode
	 * @param bool 		$allow_datax_rework 			allow_datax_rework flag
	 * @param string 	$track_hash 			Track_hash
	 * @param XML 		$expected_packet_file 	Expected DataX XML Packet
	 * @param bool 		$expected 				Expected return (TRUE/FALSE)
	 * 
	 * @dataProvider getHistoryGenericDataProvider
	 * 
	 * @return void
	 */
	public function testGetHistoryGeneric(
		$call_type,
		$account,
		$blackbox_mode,
		$allow_datax_rework,
		$track_hash,
		$expected_packet_file,
		$expected)
	{
		// Set up our objects
		$blackbox_data 	= new OLPBlackbox_Data();
		$state_data = new OLPBlackbox_StateData();
		$config_data = OLPBlackbox_Config::getInstance();
		
		// Populate our $blackbox_data
		$blackbox_data = $this->populateData($blackbox_data);
		
		$config_data->allow_datax_rework = $allow_datax_rework;
		$config_data->blackbox_mode = $blackbox_mode;
		
		// Calls the function for simulating the DataX XML packets.
		$datax_return = GET_DATAX_XML_FILE(dirname(__FILE__), $expected_packet_file);

		// Mock up the DataX Object so that I can get a return from a file instead of the real deal.
		$lib_datax = $this->getMock('Data_X');
		
		// Mock up the Authentication Object.
		$authentication = $this->getMock(
			'Authentication', 
			array(),
			array(NULL, NULL, NULL, NULL, NULL, NULL));
			
		// Set up the Mock DataX to return the supplied XML
		$lib_datax->expects($this->any())
			->method('Datax_Call')
			->will($this->returnValue(array()));
		$lib_datax->expects($this->any())
			->method('Get_Sent_Packet')
			->will($this->returnValue(''));
		$lib_datax->expects($this->any())
			->method('Get_Received_Packet')
			->will($this->returnValue($datax_return));
		
		// Mock the Generic DataX Rule
		$datax = $this->getMock(
			'OLPBlackbox_Enterprise_Generic_Rule_DataX', 
			array('initDataX', 'initConfig', 'initAuthentication', 'hitEvent', 'hitStat'), 
			array($call_type, $account, $blackbox_data));
			
		// Have the initDataX call return our mock DataX object.
		$datax->expects($this->any())
			->method('initDataX')
			->will($this->returnValue($lib_datax));
				
		// Have the initConfig call return our mock Config object.
		$datax->expects($this->any())
			->method('initConfig')
			->will($this->returnValue($config_data));
			
		// Have the initAuthentication call return our mock DataX object.
		$datax->expects($this->any())
			->method('initAuthentication')
			->will($this->returnValue($authentication));
					
		$datax->track_hash = $track_hash;
		
		$tmp = $datax->isValid($blackbox_data, $state_data);
		
		$this->assertEquals($expected, $tmp);
	}
	
	/**
	 * Generic DataX ProviderException Test function.
	 *
	 * @param string 	$call_type 				Call_Type we are making.
	 * @param string 	$account 				Account we are using
	 * @param string 	$blackbox_mode 			Blackbox Mode
	 * @param bool 		$allow_datax_rework 			allow_datax_rework flag
	 * @param string 	$track_hash 			Track_hash
	 * @param XML 		$expected_packet_file 	Expected DataX XML Packet
	 * @param bool 		$expected 				Expected return (TRUE/FALSE)
	 * 
	 * @dataProvider exceptionDataProvider
	 * 
	 * @return void
	 */
	public function testExceptions(
		$call_type,
		$account,
		$blackbox_mode,
		$allow_datax_rework,
		$track_hash,
		$expected_packet_file,
		$expected)
	{
		// Set up our objects
		$blackbox_data 	= new OLPBlackbox_Data();
		$state_data = new OLPBlackbox_StateData();
		$config_data = OLPBlackbox_Config::getInstance();
		
		// Populate our $blackbox_data
		$blackbox_data = $this->populateData($blackbox_data);
		
		$config_data->allow_datax_rework = $allow_datax_rework;
		$config_data->blackbox_mode = $blackbox_mode;
		
		// Calls the function for simulating the DataX XML packets.
		$datax_return = GET_DATAX_XML_FILE(dirname(__FILE__), $expected_packet_file);

		// Mock up the DataX Object so that I can get a return from a file instead of the real deal.
		$lib_datax = $this->getMock('Data_X');
		
		// Mock up the Authentication Object.
		$authentication = $this->getMock(
			'Authentication', 
			array(),
			array(NULL, NULL, NULL, NULL, NULL, NULL));
			
		// Set up the Mock DataX to return the supplied XML
		$lib_datax->expects($this->any())
			->method('Datax_Call')
			->will($this->returnValue(array()));
		$lib_datax->expects($this->any())
			->method('Get_Sent_Packet')
			->will($this->returnValue(''));
		$lib_datax->expects($this->any())
			->method('Get_Received_Packet')
			->will($this->returnValue($datax_return));
		
		// Mock the Generic DataX Rule
		$datax = $this->getMock(
			'OLPBlackbox_Enterprise_Generic_Rule_DataX', 
			array('initDataX', 'getHistory', 'initConfig', 'initAuthentication', 'hitEvent', 'hitStat'), 
			array($call_type, $account, $blackbox_data));
			
		// Have the initDataX call return our mock DataX object.
		$datax->expects($this->any())
			->method('initDataX')
			->will($this->returnValue($lib_datax));
				
		// Have the initConfig call return our mock Config object.
		$datax->expects($this->any())
			->method('initConfig')
			->will($this->returnValue($config_data));
			
		// Have the initAuthentication call return our mock DataX object.
		$datax->expects($this->any())
			->method('initAuthentication')
			->will($this->returnValue($authentication));
				
		$datax->track_hash = $track_hash;
		
		$tmp = $datax->isValid($blackbox_data, $state_data);
		
		$this->assertEquals($expected, $tmp);
	}
	
	/**
	 * Generic DataX ProviderException Test function.
	 *
	 * @param string 	$call_type 				Call_Type we are making.
	 * @param string 	$account 				Account we are using
	 * @param string 	$blackbox_mode 			Blackbox Mode
	 * @param bool 		$allow_datax_rework 			allow_datax_rework flag
	 * @param string 	$track_hash 			Track_hash
	 * @param XML 		$expected_packet_file 	Expected DataX XML Packet
	 * @param bool 		$expected 				Expected return (TRUE/FALSE)
	 * 
	 * @dataProvider exceptionDataProvider
	 * @expectedException InvalidArgumentException
	 * 
	 * @return void
	 */
	public function testInvalidArgumentException(
		$call_type,
		$account,
		$blackbox_mode,
		$allow_datax_rework,
		$track_hash,
		$expected_packet_file,
		$expected)
	{
		$this->markTestIncomplete('Test was broken...');
		// Set up our objects
		$blackbox_data 	= new OLPBlackbox_Data();
		$state_data = new OLPBlackbox_StateData();
		$config_data = OLPBlackbox_Config::getInstance();
		
		// Set the track_hash
		$state_data->track_hash = $track_hash;
		
		// Populate our $blackbox_data
		$blackbox_data = $this->populateData($blackbox_data);
		
		$config_data->allow_datax_rework = $allow_datax_rework;
		$config_data->blackbox_mode = $blackbox_mode;
		
		// Mock up the Authentication Object.
		$authentication = $this->getMock(
			'Authentication', 
			array(),
			array(NULL, NULL, NULL, NULL, NULL, NULL));
		
		// Mock up the DataX Object so that I can get a return from a file instead of the real deal.
		$lib_datax = $this->getMock('Data_X');
		
		// Set up the Mock DataX to return the supplied XML
		$lib_datax->expects($this->any())
			->method('Datax_Call')
			->will($this->returnValue(array()));
		$lib_datax->expects($this->any())
			->method('Get_Sent_Packet')
			->will($this->returnValue(''));
		$lib_datax->expects($this->any())
			->method('Get_Received_Packet')
			->will($this->returnValue($datax_return));
		
		// Mock the Generic DataX Rule
		$datax = $this->getMock(
			'OLPBlackbox_Enterprise_Generic_Rule_DataX', 
			array('initDataX', 'getHistory', 'initConfig', 'hitEvent', 'hitStat', 'initAuthentication'), 
			array($call_type, $account, $blackbox_data));

		// Have the initConfig call return our mock Config object.
		$datax->expects($this->any())
			->method('initConfig')
			->will($this->returnValue($config_data));
			
		// Have the initDataX call return our mock DataX object.
		$datax->expects($this->any())
			->method('initDataX')
			->will($this->returnValue($lib_datax));
			
		// Have the initAuthentication call return our mock DataX object.
		$datax->expects($this->any())
			->method('initAuthentication')
			->will($this->returnValue($authentication));
		
		$tmp = $datax->isValid($blackbox_data, $state_data);
		
		$this->assertEquals($expected, $tmp);
	}
}
?>
