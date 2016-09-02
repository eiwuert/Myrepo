<?php

	require_once ('prpc/client.php');

	function _log_request_1 ()
	{
		$srv = array();
		foreach (array ('VHOST', 'HTTP_HOST', 'SERVER_ADDR', 'SERVER_PORT', 'REMOTE_ADDR', 'REQUEST_METHOD', 'REQUEST_URI') as $k)
		{
			$srv [$k] = $_SERVER[$k];
		}

		$log = new Prpc_Client ('prpc://log.1.soapdataserver.com/');
		$log->Request (session_id (), $srv, $_REQUEST);
	}

	_log_request_1 ();

?>