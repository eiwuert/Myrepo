<?php

	$debug = false;

	$wsdl = 'http://ds57.tss:8080/wsdl_parser/soap_screen_sample.php?wsdl';

	ini_set("soap.wsdl_cache_enabled", "0");  // For testing purposes, do not cache wsdl.

	$debug_control = array('trace' => 1, 'exceptions' => 0);
	
	$client = $debug ? new SoapClient($wsdl, $debug_control) : new SoapClient($wsdl);

	$result = $client->Start_Session('my Client Id', 'My Password');
	Display_Info( $debug, $client, $result );

	$result = $client->ODS_Enrollment( 'My Session_id', 'account_id', '123456789', '1234567890123456', '123456789', 'name_first', 'name_last', 'name_suffix', 'email@sellingsource.com', '101.102.103.104', 'yes', '1969-01-01', 'employer_name', 'yes', 'day', 'yes', 'employment', 'yes', '1000', 'weekly', '2005-11-14', '2005-11-21', 'rent', '123 easy street', '', 'Las Vegas', 'NV', '89123', '8005550001', '8005550002', '8005550003', '69', 'legal_id_number', 'NV', 'ref_01_name', '8005550004', 'ref_01_relationship', 'ref_02_name', '8005550005', 'ref_02_relationship' );
	Display_Info( $debug, $client, $result );

	$result = $client->ODS_Transaction_Acknowledgement( 'My Session_id', 'account_id', '1000', '2005-11-09' );
	Display_Info( $debug, $client, $result );

	$result = $client->is_Valid_Card_Id( 'My Session_id', '121' );
	Display_Info( $debug, $client, $result );

	$result = $client->Run_Debit_Credit_Transaction( 'My Session_id', '114', '23.45', 'credit' );
	Display_Info( $debug, $client, $result );

	$result = $client->Close_Session( 'my Client Id', 'My Password', 'My Session_id' );
	Display_Info( $debug, $client, $result );

	exit;


	function Display_Info( $debug, &$client, &$result )
	{
		echo "\r\n\r\n"; 
		if ( $debug ) {
			print "\nRequest :\n" . $client->__getLastRequest() . "\n";
			print "\nResponse:\n" . $client->__getLastResponse() . "\n";
		}
		echo "result = \r\n$result\r\n";
	}
	

?>