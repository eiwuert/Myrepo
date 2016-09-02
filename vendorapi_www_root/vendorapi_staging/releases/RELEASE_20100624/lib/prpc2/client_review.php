<?php

require_once ('prpc2/base.php');
require_once ('prpc2/message.php');

/**
	@publicsection
	@public
	@brief
		Prpc client class.

	Overloaded class that marshalls method calls to a remote object.

	@version
		1.0.0 2003-07-25 - Rodric Glaser
			- Initial revision
		2.0.0 2004-11-19 - Paul Strange
			- Update to support PHP5 version

	@todo
		- Nothing
*/
class Prpc_Client2 extends Prpc_Base2
{
	var $_prpc_url;
	var $die;

	function setPrpcDieToFalse()
	{
		$this->die = false;
	}

	function Prpc_Client2 ($url = NULL, $use_debug = FALSE, $use_trace = PRPC_TRACE_NONE)
	{
		$this->die = true;
		$this->_Prpc_Set_Url ($url);
		$this->_prpc_use_debug = $use_debug;
		$this->_prpc_use_trace = PRPC_TRACE_NONE;

		// Added for PHP5 compatibility
		$this->_serialize_method = PRPC_SERIALIZE_STANDARD;
		$this->_exception_method = PRPC_EXCEPTION_PHP4;

		switch (TRUE)
		{
			case (extension_loaded ('zlib') || @dl ('zlib')):
				$this->_prpc_use_pack = PRPC_PACK_GZ;
				$this->_compress_method = PRPC_COMPRESS_GZ;
				break;
			case (extension_loaded ('bz2') || @dl ('bz2')):
				$this->_prpc_use_pack = PRPC_PACK_BZ;
				$this->_compress_method = PRPC_COMPRESS_BZ;
				break;
			default:
				$this->_prpc_use_pack = PRPC_PACK_NO;
				$this->_compress_method = PRPC_COMPRESS_NO;
		}
		
		// Lets turn off compression for now.
		$this->_prpc_use_pack = PRPC_PACK_NO;
		$this->_compress_method = PRPC_COMPRESS_NO;
		
		$this->_Trace (3, PRPC_TRACE_FILE_CLIENT);
	}

	function _Prpc_Set_Url ($url = NULL)
	{
		if (is_null ($url))
		{
			$this->_prpc_url = NULL;
			return TRUE;
		}

		if (! preg_match ('/^prpc:\/\/([^\/:]+)(?::(\d+))?(\/.*)?/', $url, $m))
		{
			if ($this->die) die ('FATAL: Invalid url ('.$url.")\n");
			else return false;
		}

		$this->_prpc_url = $url;
		$this->_prpc_url_host = $m[1];
		$this->_prpc_url_port = $m[2] ? $m[2] : 80;
		$this->_prpc_url_path = $m[3];

		return TRUE;
	}

	function _Prpc_Call ($call)
	{
		
		if (! ($sock = @fsockopen($this->_prpc_url_host, $this->_prpc_url_port, $errno, $errstr)))
		{
			//echo 'WARN: fsockopen of '.$this->_prpc_url_host.' failed ', $errno, ' - ', $errstr, "\n";
			return FALSE;
		}
		
		$content = $this->_Prpc_Pack($call);
		$length = strlen($content);
		
		// build HTTP header
		$head =
			"POST ".$this->_prpc_url_path." HTTP/1.0\r\n".
			"Host: ".$this->_prpc_url_host."\r\n".
			"User-Agent: PRPC ".PRPC2_PROT_VER."\r\n".
			"Connection: close\r\n".
			"Content-Type: form-data\r\n".
			"Content-Transfer-Encoding: binary\r\n".
			"Content-Length: ".$length."\r\n".
			"X-Prpc-Debug: ".$this->_prpc_use_debug."\r\n".
			"X-Prpc-Depth: ".(isset ($_SERVER ["HTTP_X_PRPC_DEPTH"]) ? $_SERVER ["HTTP_X_PRPC_DEPTH"] + 1 : 1)."\r\n".
			"X-Prpc-Pack: ".$this->_prpc_use_pack."\r\n".
			"X-Prpc-Trace: ".$this->_prpc_use_trace."\r\n".

			// Added for PHP5 compatibility
			"X-Prpc-Compress-Method: ".$this->_compress_method."\r\n".
			"X-Prpc-Serialize-Method: ".$this->_serialize_method."\r\n".
			"X-Prpc-Exception-Method: ".$this->_exception_method."\r\n".
			"\r\n";

		$full_msg = $head.$content;
		$num_bytes = strlen($full_msg);
		
		while ($num_bytes > 0)
		{
			if ( ($rc = fwrite ($sock, $full_msg, $num_bytes)) === FALSE )
			{
				if ($this->die) echo 'WARN: fwrite to ', $this->_prpc_url_host, ' failed ', "\n";
				return FALSE;
			}
			$num_bytes -= $rc;
			$full_msg = substr ($full_msg, $rc, $num_bytes);
		}

		
		// don't know if this works
		stream_set_timeout($sock, 5);
		
		$http_response_head = trim(fgets($sock));
		if (! preg_match ('/^HTTP\/1\.\d (\d+) (.*)/', $http_response_head, $m))
		{
			if ($this->die) die ("FATAL: No HTTP response header from ".$this->_prpc_url."\nSent".$head."\nGot ".$http_response_head."\n");
			else return false;
		}
		$http_response_code = $m[1];
		$http_response_msg = $m[2];

		while (! feof ($sock) && (($h = trim(fgets($sock))) != ''))
		{
			// Could process other headers here
			// echo "HEAD: ", $h, "\n";
		}

		// Read in the rest
		for ($pack = '' ; ! feof ($sock) ; )
		{
			$pack .= fread ($sock, 2048);
		}
		fclose ($sock);
		
		if ($http_response_code != 200)
		{
			if ($this->die)
			{
				die("FATAL: Bad HTTP response ".$http_response_code." - ".$http_response_msg."\n".$pack."\n");
			}
			else return(FALSE);
		}
		
		$this->_Trace(3, PRPC_TRACE_CALL|PRPC_TRACE_ARGS, $call);
		
		$rpc = $this->_Prpc_Unpack($pack);
		
		if (! is_object ($rpc))
		{
			
			if ($this->die)
			{
				print_r($pack); flush();
				die("FATAL: Did not receive an object\n");
			}
			else
			{
				return(FALSE);
			}
			
		}

		$rpc->output = preg_replace ("/(.*?)\n/s", "\t\\1\n", $rpc->output);

		if (isset ($_SERVER ["HTTP_X_PRPC_DEPTH"]) && $_SERVER ["HTTP_X_PRPC_DEPTH"])
		{
			echo $rpc->output;
			$this->_Trace (3, PRPC_TRACE_RESULT, array ('method' => $call->method, 'result' => $rpc->result));
			return isset ($rpc->args) ? $rpc->args : $rpc;
		}
		else
		{
			
			$this->_prpc_debug .= $rpc->output;
			$this->_Trace (3, PRPC_TRACE_RESULT, array ('method' => $call->method, 'result' => $rpc->result));
			
			if (is_a($rpc, 'prpc_fault'))
			{
				
				if ($this->die)
				{
					echo "<pre>\n";
					print_r ($rpc);
					echo "\n", $this->_prpc_debug, "\n\n";
					echo "</pre>\n";
					exit;
				}
				
				return(FALSE);
				
			}
			else
			{
				
				return isset ($rpc->args) ? $rpc->args : $rpc;
				
			}
			
		}
	}


	function __call ($method, $arg, &$result)
	{
		
		if (method_exists($this, $method))
		{
			$result = call_user_func_array (array(&$this, $method), $arg);
			return TRUE;
		}
		
		$result = $this->_Prpc_Call (new SourcePro_Prpc_Message_Call ($method, $arg));
		return TRUE;
	}
	
}

overload ('Prpc_Client2');

?>
