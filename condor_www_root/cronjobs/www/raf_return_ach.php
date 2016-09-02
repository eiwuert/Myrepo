<?
	/*
		Cronjob to download returned ach transactions from intercept, 
		update ach records, and notify RAF administrators
	*/
	
	require_once('refer_a_friend.1.php');
	require_once ("db2.1.php");
	
	// set db2 login vars
	$db2_user 		= 'web_raf';
	$db2_pw 		= 'raf_web';
	$db2_schema 	= strtoupper($db2_user);
	$db2_db			= 'OLP';

	// connect to db2 database
	$db2_raf = new Db2_1( $db2_db, $db2_user, $db2_pw );
	$result = $db2_raf->Connect();
	if( is_a( $result, "Error_2") )
	{
		die("We are currently upgrading the server, please check back in one hour.<br>");	
	}
	
	set_time_limit(0);

	// instantiate raf object
	$raf = new RAF_DB2($sql, $db2_raf, $db2_schema, 'live' );
	
	// run ach return function
	$raf->Process_ACH_Returns();
	
?>
