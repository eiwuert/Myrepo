<?php

require_once('general_exception.1.php');
require_once('prpc/client.php');

	
$hostname = 'condor.4.edataserver.com';
$login    = 'tss_api';
$password = 'c0nd0rb0unc35';

$url = "prpc://$login:$password@$hostname/condor_api.php";


$condor_api = new Prpc_Client($url);
		
if($condor_api->Add_Bounce('31167739', 'brian.ronald@sellingsource.com','SMTP Response'))
{
	echo "Added Bounce\n";
}
else
{
	echo "Failed adding Bounce\n";
}
				
