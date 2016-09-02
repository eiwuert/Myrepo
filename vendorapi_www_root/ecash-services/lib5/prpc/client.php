<?php

require_once ('prpc/base.php');
require_once ('prpc/message.php');

/**
	@publicsection
	@public
	@brief
		Prpc client class.

	Overloaded class that marshalls method calls to a remote object.

	@version
		1.0.0 2003-07-25 - Rodric Glaser
			- Initial revision

	@todo
		- Nothing
*/
class Prpc_Client extends Prpc_Base
{
	
	var $_prpc_url;
	var $_prpc_url_user;
	var $_prpc_url_pw;
	var $_prpc_url_host;
	var $_prpc_url_port;
	var $_prpc_url_path;
	var $socket_timeout = 600;

	function Prpc_Client ($url = NULL, $use_debug = FALSE)
	{
		$this->_Prpc_Set_Url ($url);
		$this->_prpc_use_debug = $use_debug;

		//added this to get rid of a notice in PHP5.
		//If this is a hack, feel free to fix it yourself -- JRF
		$this->_prpc_use_trace = '';

		switch (TRUE)
		{
			case (extension_loaded ('zlib') || @dl ('zlib')):
				$this->_prpc_use_pack = PRPC_PACK_GZ;
				break;
			case (extension_loaded ('bz2') || @dl ('bz2')):
				$this->_prpc_use_pack = PRPC_PACK_BZ;
				break;
			default:
				$this->_prpc_use_pack = PRPC_PACK_NO;
		}
	}

	function _Prpc_Set_Url ($url = NULL)
	{
		
		if (is_null ($url))
		{
			$this->_prpc_url = NULL;
			return TRUE;
		}
		
		if (! preg_match ('/^prpc:\/\/(?:([^:]+):([^@]+)@)?([^\/:]+)(?::(\d+))?(\/.*)?/', $url, $m))
		{
			throw new Exception('Invalid url ('.$url.")");
		}
		
		$this->_prpc_url = $url;
		$this->_prpc_url_user = $m[1] ? $m[1] : NULL;
		$this->_prpc_url_pw = $m[2] ? $m[2] : NULL;
		$this->_prpc_url_host = $m[3];
		$this->_prpc_url_port = $m[4] ? $m[4] : 80;
		$this->_prpc_url_path = $m[5];

		return TRUE;
	}

	function _Prpc_Call ($call)
	{
		
		if (! ($sock = @fsockopen ($this->_prpc_url_host, $this->_prpc_url_port, $errno, $errstr)))
		{
			throw new Exception('fsockopen failed '.$errno.' - '.$errstr);
		}

		if (is_numeric($this->socket_timeout))
		{
			socket_set_timeout($sock, $this->socket_timeout);
		}
		
		$content = $this->_Prpc_Pack ($call);
		$length = strlen ($content);
		
		$head =
			"POST ".$this->_prpc_url_path." HTTP/1.0\r\n".
			"Host: ".$this->_prpc_url_host.(($this->_prpc_url_port != 80) ? ':'.$this->_prpc_url_port : '')."\r\n".
			"User-Agent: PRPC ".PRPC_PROT_VER."\r\n".
			"Connection: close\r\n".
			"Content-Type: form-data\r\n".
			"Content-Transfer-Encoding: binary\r\n".
			"Content-Length: ".$length."\r\n".
			"X-Prpc-Debug: ".$this->_prpc_use_debug."\r\n".
			"X-Prpc-Depth: ".(isset ($_SERVER ["HTTP_X_PRPC_DEPTH"]) ? $_SERVER ["HTTP_X_PRPC_DEPTH"] + 1 : 1)."\r\n".
			"X-Prpc-Pack: ".$this->_prpc_use_pack."\r\n".
			"X-Prpc-Trace: ".$this->_prpc_use_trace."\r\n".
			(($this->_prpc_url_user !== NULL) ? 'Authorization: Basic '.base64_encode($this->_prpc_url_user.':'.$this->_prpc_url_pw)."\r\n" : '').
			"\r\n";
			
		$full_msg = $head.$content;
		$num_bytes = strlen ($full_msg);

		while ($num_bytes)
		{
			if ( ($rc = fwrite ($sock, $full_msg, $num_bytes)) === FALSE )
			{
				throw new Exception('fwrite to '.$this->_prpc_url_host.' failed');
			}
			$num_bytes -= $rc;
			$full_msg = substr ($full_msg, $rc, $num_bytes);
		}
		
		do
		{
			# Get the response
			$data = ''; 
			if(phpversion() < 5) {
				$data = stream_get_contents($sock);
			} else {
				while (!feof($sock)) {
					if (!$feed = fread($sock, 4096)) break;
					$data .= $feed;
				}
			}
	
			if(strlen($data) == 0)
				break;

			if( !isset($http_response_head) && strstr($data, "\r\n\r\n"))
			{
				$buffers = explode("\r\n\r\n", $data, 2);
				$http_response_head = $buffers[0];

				if (! preg_match ('/^HTTP\/1\.\d (\d+) (.*)/', $http_response_head, $m))
				{
					throw new Exception("No HTTP response header from ".$this->_prpc_url."\nGot ".$http_response_head."\n");					
				}
				
				$http_response_code = $m[1];
				$http_response_msg = $m[2];
				$http_response_head .= "<br>\n"; 
				
				$pack = $buffers[1];
			}
			elseif(isset($file))
				$pack .= $data;

		} while(true);
		
		fclose($sock);
		
		if(!isset($http_response_code))
		{
			throw new Exception("No HTTP response.");
		}
		elseif ($http_response_code != 200)
		{
			throw new Exception("Bad HTTP response ".$http_response_code." - ".$http_response_msg);
		}

		$rpc = $this->_Prpc_Unpack ($pack);

		if (! $rpc instanceof Prpc_Message)
		{
			throw new Exception("Did not receive a Prpc_Message :: <br> ".$pack);
		}

		$rpc->debug = preg_replace ("/(.*?)\n/s", "\t\\1\n", $rpc->debug);

		if (isset ($_SERVER ["HTTP_X_PRPC_DEPTH"]) && $_SERVER ["HTTP_X_PRPC_DEPTH"])
		{
			echo $rpc->debug;
			return isset ($rpc->result) ? $rpc->result : $rpc;
		}
		else
		{
			$this->_prpc_debug .= $rpc->debug;
			if ($rpc instanceof prpc_fault)
			{
				echo "<pre>";
				print_r ($rpc);
				echo "\n", $this->_prpc_debug, "\n\n";
				echo "</pre>";
				exit;
			}

			if ($rpc->result instanceof Exception)
			{
				throw $rpc->result;
			}

			return isset ($rpc->result) ? $rpc->result : $rpc;
		}
	}

	function __call ($method, $arg)
	{
		if (method_exists ($this, $method))
		{
			return call_user_func_array (array (&$this, $method), $arg);
		}

		try
		{
			$result = $this->_Prpc_Call (new Prpc_Call ($method, $arg));
		}		
		catch(Exception $e)
		{
			throw $e;
		}
		
		return $result;
	}
}

?>
