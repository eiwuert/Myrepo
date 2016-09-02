<?php
/**
	An object that is remotely accessible via prpc.
*/

abstract class SourcePro_Prpc_Server extends SourcePro_Prpc_Base
{
	public function __construct ()
	{
        //set_error_handler (array (&$this, '_handle_error'), E_ALL & ~E_NOTICE);
        set_exception_handler (array (&$this, '_handle_exception'));

		$this->_compress_method = @$_SERVER ['HTTP_X_PRPC_COMPRESS_METHOD'];
		$this->_serialize_method = @$_SERVER ['HTTP_X_PRPC_SERIALIZE_METHOD'];
		$this->_exception_method = @$_SERVER ['HTTP_X_PRPC_EXCEPTION_METHOD'];
	}

	protected function _process ()
	{
		ob_start();
		$call = $this->_unpack (file_get_contents('php://input'));

		if (method_exists($this, $call->method))
		{
			$res = @call_user_func_array(array($this, $call->method), $call->arg);
	    	$ob = ob_get_clean();
			$pack = $this->_pack(new SourcePro_Prpc_Message_Return($res, $ob));
		}
		else
		{
			$ob = ob_get_clean();
			$pack = $this->_pack(new SourcePro_Prpc_Message_Except(new SourcePro_Exception("invalid method ({$call->name})", 1000), $ob));
		}
		$this->_return($pack);
	}

	private function _return ($pack)
	{
		header ("Content-Type: octet/stream");
		header ("Content-Transfer-Encoding: binary");
		header ("Content-Length: ".strlen($pack));
		echo $pack;
		exit(0);
	}

    public function _handle_error ($errno, $errstr, $errfile, $errline)
    {
    	$this->_handle_exception (new SourcePro_Exception("$errfile:$errline - $errstr", $errno));
    }

    public function _handle_exception ($e)
    {
		$ret_exp = new SourcePro_Prpc_Utility_Exception (new SourcePro_Prpc_Message_Except ($e, ob_get_clean()), $this->_exception_method);
		if ($this->_exception_method == SourcePro_Prpc_Base::PRPC_EXCEPTION_PHP5)
		{
        	$this->_return ($this->_pack ($ret_exp->result));
		}
		else
		{
        	$this->_return ($this->_pack (new SourcePro_Prpc_Message_Return ($ret_exp->result)));
		}
    }
}


?>
