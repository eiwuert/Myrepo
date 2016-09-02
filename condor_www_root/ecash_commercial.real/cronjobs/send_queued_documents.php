<?php

/**
 * Send documents from the document_queue table.
 */
function Send_Queued_Documents($server) 
{
	$documents = eCash_Document_AutoEmail::Send_Queued_Documents($server);
}

function Main()
{
	global $server;
	
	require_once(LIB_DIR."common_functions.php");
	require_once (LIB_DIR . "/Document/AutoEmail.class.php");
	require_once (LIB_DIR . "/Document/Document.class.php");

	Send_Queued_Documents($server);
}

?>