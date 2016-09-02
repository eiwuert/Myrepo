<?php
	
	include('../../lib/callback.php');
	include('../../lib/hylafax_callback.php');
	include('../../lib/hylafax_job.php');
	
	//$id = HylaFax_Callback::Request();
	$id = '84035e0e400075053b2ec00f31bc31d2';
	
	//var_dump(HylaFax_Callback::Register($id, 'http://www.ecash.com', 100));
	
	$tokens = array('blah' => 'aksdlnksdf');
	
	$job = new HylaFax_Job('/tmp/blah');
	
	$callback = HylaFax_Callback::Find_By_ID($id);
	$callback->Process($job, 'blocked');
	
?>