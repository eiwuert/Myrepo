<?php
	//######################################
	//Convert existing UCL datastructure to new OLP structure
	//######################################
	
	//set the xmlrpc variables
	include_once ("/virtualhosts/lib/xmlrpc.1.php");
	include_once("/virtualhosts/ucl.soapdataserver.com/includes/code/convert_to_olp.php");
	$path = "/ucl/ucl_app.php";
	$host = "olp.2.soapdataserver.com";
	$port = 80;
	
	set_time_limit(0);
	include_once "/virtualhosts/lib/mysql.3.php";
	$sql = new MySQL_3 () ;
	$result = $sql->Connect (NULL, 'write1.iwaynetworks.net', 'sellingsource', 'password', Debug_1::Trace_Code (__FILE__, __LINE__));
	//$result = $sql->Connect (NULL, 'localhost', 'root', '', Debug_1::Trace_Code (__FILE__, __LINE__));
	
	$query = "SELECT * from affiliate_dump";
	$result = $sql->Query ("ucl_visitor", $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test($result, TRUE);
	
	while ($app = $sql->Fetch_Array_Row($result))
	{
		$soap_array["app"] = Convert_To_Olp($app["application_id"]);
		$soap_array["request"] = "convert";
		
		$response = xmlrpc_request ($host, $port, $path, "Affiliate_Dump", $soap_array);
		if($response["response"])
		{
			$query = "DELETE from affiliate_dump where application_id = ".$app["application_id"]."";
			$result_set = $sql->Wrapper ($query, NULL, "\t".__FILE__."->".__LINE__."\n");
		}
	}
	//echo "<PRE>";
	//print_r($response);
	//######################################
?>