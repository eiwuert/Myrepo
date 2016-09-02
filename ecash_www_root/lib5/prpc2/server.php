<?php

require_once ('prpc2/base.php');
require_once ('prpc2/message.php');
require_once ('prpc2/client.php');

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
class Prpc_Server2 extends Prpc_Base2
{
	function Prpc_Server2 ($process = TRUE, $strict = FALSE)
	{
		$this->_prpc_use_pack = isset($_SERVER ['HTTP_X_PRPC_PACK']) ? $_SERVER ['HTTP_X_PRPC_PACK'] : @$_SERVER ['HTTP_X_PRPC_COMPRESS_METHOD'];
		$this->_prpc_use_debug = @$_SERVER ['HTTP_X_PRPC_DEBUG'];
		$this->_prpc_strict = $strict;

		if ($process)
			$this->Prpc_Process ();
	}

	function Prpc_Process ()
	{
		ini_set('display_errors', 0);
		set_error_handler (array (&$this, '_Error_Handler'), $this->_prpc_strict ? E_ALL : E_ALL & ~E_NOTICE);
		set_exception_handler (array (&$this, '_Exception_Handler'));

		ob_start ();
		$call = $this->_Prpc_Unpack (file_get_contents('php://input'));

		$cb = array($this, $call->method);
		if ($this->__exists($call->method))
		{
			$result = call_user_func_array ($cb, $call->arg);
		}
		else
		{
			$result = 'method ('.$call->method.') is not callable';
		}

		$this->_prpc_debug = ob_get_clean ();

		$pack = $this->_Prpc_Pack (new SourcePro_Prpc_Message_Return ($result, $this->_prpc_debug));

		header ("Content-Type: octet/stream");
		header ("Content-Transfer-Encoding: binary");
		header ("Content-Length: ".strlen($pack));

		echo $pack;
		exit (0);
	}

	protected function __exists($function)
	{
		return method_exists($this, $function);
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
		return new Prpc_Client2 ($url, $this->_prpc_use_debug, $this->_prpc_use_trace);
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

		$this->_Exception_Handler(new Exception("Server Side Error (#{$errno}) in {$errfile} on line ${errline}: {$errstr}", $errno));
	}

	function _Exception_Handler ($e)
	{
		$this->_prpc_debug = ob_get_clean ();

		$pack = $this->_Prpc_Pack (new SourcePro_Prpc_Message_Except ($e, $this->_prpc_debug));

		header ("Content-Type: octet/stream");
		header ("Content-Transfer-Encoding: binary");
		header ("Content-Length: ".strlen($pack));

		echo $pack;
		exit (0);
	}
}
?>
