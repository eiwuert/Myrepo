<?php

require_once ('prpc/base.php');
require_once ('prpc/message.php');
require_once ('prpc/client.php');

/**
	@publicsection
	@public
	@brief
		Prpc server class.

	Responds to messages from a Prpc client.

	@version
		1.0.0 2003-07-25 - Rodric Glaser
			- Initial revision

	@todo
		- Nothing
*/
class Prpc_Server extends Prpc_Base
{
	function Prpc_Server ($process = TRUE, $strict = FALSE)
	{
		$this->_prpc_use_pack = @$_SERVER ['HTTP_X_PRPC_PACK'];
		$this->_prpc_use_debug = @$_SERVER ['HTTP_X_PRPC_DEBUG'];
		$this->_prpc_use_trace = @$_SERVER ['HTTP_X_PRPC_TRACE'];

		$this->_prpc_strict = $strict;

		//ini_set ('display_errors', 1);
		//set_error_handler (array (&$this, '_Error_Handler'));

		if ($process)
			$this->Prpc_Process ();
	}

	function Prpc_Process ()
	{
		ob_start ();
		$call = $this->_Prpc_Unpack (file_get_contents('php://input'));

		$this->_Trace (2, PRPC_TRACE_FILE_SERVER, $call);

		if (method_exists ($this, $call->method))
		{
			$result = call_user_func_array (array (&$this, $call->method), $call->arg);
		}
		else
		{
			$result = 'unknown method ('.$call->method.')';
		}

		$this->_prpc_debug = ob_get_clean ();

		$pack = $this->_Prpc_Pack (new Prpc_Result ($result, $this->_prpc_debug));

		header ("Content-Type: octet/stream");
		header ("Content-Transfer-Encoding: binary");
		header ("Content-Length: ".strlen($pack));

		echo $pack;
		exit (0);
	}

	/**
		@publicsection
		@public
		@fn string Prpc_Proxy ($url)
		@brief
			Obtains a Prpc_Client for a subserver and sets its debug/trace options.

		Obtains a Prpc_Client for a subserver and sets its debug/trace options.

		@version
			1.0.0

		@param url string \n Url of the server.

		@return & Prpc_Client
	*/
	function & Prpc_Proxy ($url)
	{
		return new Prpc_Client ($url, $this->_prpc_use_debug, $this->_prpc_use_trace);
	}

	function _Error_Handler ($errno, $errstr, $errfile, $errline)
	{
		if (! $this->_prpc_strict)
		{
			if ($errno == E_NOTICE)
			{
				return FALSE;
			}
		}
		
		echo $this->_Prpc_Pack (new Prpc_Fault ($errno, $errstr, $this->_Prpc_Get_Host (), $errfile, $errline, $this->_prpc_debug));
		exit (0);
	}
}
?>
