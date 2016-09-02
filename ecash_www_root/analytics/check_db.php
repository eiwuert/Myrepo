<?php


	require_once('mysql.3.php');
require_once('../virtualhosts/verihub.com/serenity/live/code/cingular.php');

	$sql = new MySQL_3();

$err=	@$sql->Connect(NULL, "localhost", "serenity", "firefly", 3306, Debug_1::Trace_Code(__FILE__,__LINE__));
	

	if (Error_2::Check($err))
{
		
		echo "Down.";
		cingular("Unable to connect to local db on serenity. (".date("Y-m-d H:i:s").")");

		mail("7024234683@vtext.com", "SERENITY_ERROR", "Unable to connect to local db on Serenity. (" . date("Y-m-d H:i:s") . ")", "From: Administrator@serenity.verihub.com");
}
