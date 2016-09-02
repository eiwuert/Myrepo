<?php
     //Set the doc root
     $outside_web_space = realpath ("../")."/";
     $inside_web_space = realpath ("./")."/";
     define ("OUTSIDE_WEB_SPACE", $outside_web_space);
	define ("DATABASE", "expressgoldcard");

     //Include the required files
	require_once ("/virtualhosts/lib/debug.1.php");
	require_once ("/virtualhosts/lib/error.2.php");
	require_once ("/virtualhosts/lib/mysql.3.php");
	require_once ("/virtualhosts/lib/xmlrpc_client.2.php");
	require_once("/virtualhosts/lib/setstat.1.php");
	require_once ("audit_trail.class.php");
	require_once ("account_master.class.php");
	require_once ("billing_master.class.php");
	
     
     //Live connection
     
	$server = new stdClass ();
	$server->host = "read1.iwaynetworks.net";
	$server->user = "sellingsource";
	$server->pass = "%selling\$_db";

	
	/*
	//Local test connection
	$server = new stdClass ();
	$server->host = "localhost";
	$server->user = "root";
	$server->pass = "";	
	*/
	
	//Make the sql connection
	$sql = new MySQL_3 ();
	$result = $sql->Connect (NULL, $server->host, $server->user, $server->pass, Debug_1::Trace_Code (__FILE__, __LINE__));
	
	//Instantiate the classes
	$audit_trail = new Audit_Trail($sql, DATABASE);
	$account_master = new Account_Master($sql, DATABASE, $audit_trail);
	$billing_master = new Billing_Master($sql, DATABASE, $account_master, $audit_trail);
	
	// Connection Info
	define('SSO_SOAP_SERVER_PATH', '/');
	define('SSO_SOAP_SERVER_URL', 'smartshopperonline.soapdataserver.com');
	define('SSO_SOAP_SERVER_PORT', 80);
	
	//Run this one the first time to generate bills from nothing.
     //$billing_master->Generate_Monthly_Billing();
     
     //Then run this one from that point on and comment the other one out.
	$run = $billing_master->Process_Monthly_Billing(); 
?>