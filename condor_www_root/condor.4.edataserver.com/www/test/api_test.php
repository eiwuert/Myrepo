<?php
	
	require('prpc/client.php');
	include('../../lib/hylafax_job.php');
	
	$hylafax = new PRPC_Client('prpc://condor2.ds38.tss/api.php');
	$job = $hylafax->Query_Status(20);
	
	var_dump($job);
	
?>