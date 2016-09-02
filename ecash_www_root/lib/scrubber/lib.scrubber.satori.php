<?php

//NOTE: this library requires lib.scrubber.php be included in the script running it

// these are required for the satori call
require_once ("/virtualhosts/lib/debug.1.php");
require_once ("/virtualhosts/lib/prpc/client.php");
//require_once ("/virtualhosts/satori.1.soapdataserver.com/www/satori.php");	// require local copy of satori interface
require_once ("/virtualhosts/satori.1.soapdataserver.com/www/satori.noprpc.php");// require local copy of satori interface

//define('URL_SATORI',	'prpc://satori.1.soapdataserver.com.ds05.tss/satori.php');

// run address data through satori
function address_data_cleanup($data)
{

	//debug_print("address_cleanup()...");
	$satori = new Satori_1();

	if ($satori)
	{

		//debug_dump($data);
	
		// run some basic cleanups on the address data
		$data[T_ADDR1] = name_cleanup($data[T_ADDR1]);
		$data[T_ADDR2] = name_cleanup($data[T_ADDR2]);
		$data[T_CITY] = name_cleanup($data[T_CITY]);
		$data[T_STATE] = strtoupper($data[T_STATE]);
	
		//debug_dump($data);
		
		// map $data to $req object
		$req = new stdClass();
		$req->request_id = 123;
		$req->organization = '';
		$req->address_1 = $data[T_ADDR1];
		$req->address_2 = $data[T_ADDR2];
		$req->city = $data[T_CITY];
		$req->state = $data[T_STATE];
		$req->zip = $data[T_ZIPCODE];
	
		$rs = $satori->Validate_Address($req);//, Debug_1::Trace_Code(__FILE__, __LINE__));

		if (is_object($rs))
		{
	
			//debug_dump($rs);
		
			// map $rs back to $data
			$data[T_ADDR1] = $rs->address_1;
			$data[T_ADDR2] = $rs->address_2;
			$data[T_CITY] = $rs->city;
			$data[T_STATE] = $rs->state;
			list($data[T_ZIP], $data[T_ZIP4]) = explode('-', $rs->zip);
			$data[T_ZIPCODE] = $rs->zip;

		}
		else
		{
			$conn_problem = TRUE;
			echo "rs: ";
			var_dump($rs);
		}
	
	}
	else
	{
		$conn_problem = TRUE;
	}

	$is_valid = $rs->valid == "TRUE";

	return array($is_valid, $data, $conn_problem);
}

?>
