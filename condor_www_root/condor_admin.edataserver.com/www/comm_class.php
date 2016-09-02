<?php

require_once WWW_DIR . 'config.php';
require_once LIB_DIR . 'comm.iface.php';

Class Comm_Class implements Comm
{
	public function Process_Data($request, $session_id = NULL, $files = NULL)
	{
		
		$server = new Server($session_id);
		
		// !!! WARNING WARNING WARNING !!!
		// THIS WILL NOT WORK IN A PRPC ENVIRONMENT
		// GIVEN TIME CONSTRAINTS I WILL NOT WRITE CODE TO READ ACTUAL FILE DATA
		// THIS IS GOING TO PASS THE FILES SUPERGLOBAL TO THE BACKEND, WHICH IN A PRPC
		// ENVRIONMENT MAY BE A SEPARATE SERVER AND THUS THE FILES WILL NOT EXIST ON THE BACKEND.
		// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
		$server->files = $files;
		// !!! WARNING WARNING WARNING !!!
		
		
		
		
		$thisObj = clone $server->Process_Data($request);
		
		return $thisObj;
	}
}