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
	var $socket_timeout = 600;
	var $connect_timeout = 60;

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
				$this->_prpc_use_pack = Prpc_Base2::PRPC_PACK_GZ;
				$this->_compress_method = PRPC_COMPRESS_GZ;
				break;
			case (extension_loaded ('bz2') || @dl ('bz2')):
				$this->_prpc_use_pack = Prpc_Base2::PRPC_PACK_BZ;
				$this->_compress_method = PRPC_COMPRESS_BZ;
				break;
			default:
				$this->_prpc_use_pack = Prpc_Base2::PRPC_PACK_NO;
				$this->_compress_method = PRPC_COMPRESS_NO;
		}
	}

	function _Prpc_Set_Url ($url = NULL)
	{
		if (is_null ($url))
		{
			$this->_prpc_url = NULL;
			return TRUE;
		}

		if (! preg_match ('/^(ssl|tcp|prpc|https?):\/\/([^\/:]+)(?::(\d+))?(\/.*)?/', $url, $m))
		{
			if ($this->die) die ('FATAL: Invalid url ('.$url.")\n");
			else return false;
		}

		$scheme = array(
			'prpc' => 'tcp',
			'http' => 'tcp',
			'https' => 'ssl',
		);

		$this->_prpc_url = $url;
		$this->_prpc_url_scheme = isset($scheme[$m[1]]) ? $scheme[$m[1]] : $m[1];
		$this->_prpc_url_host = $m[2];
		$this->_prpc_url_port = $m[3] ? $m[3] : $this->_prpc_url_scheme == 'ssl' ? 443 : 80;
		$this->_prpc_url_path = $m[4];

		return TRUE;
	}

	function _Prpc_Call ($call)
	{
		$url = $this->_prpc_url_scheme.'://'.$this->_prpc_url_host.':'.$this->_prpc_url_port;
		if (! ($sock = @stream_socket_client($url, $errno, $errstr, $this->connect_timeout)))
		{
			echo 'WARN: stream_socket_client open of '.$url.' failed ', $errno, ' - ', $errstr, "\n";
			return FALSE;
		}

		$content = $this->_Prpc_Pack ($call);
		$length = strlen ($content);

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
		$num_bytes = strlen ($full_msg);



		while ($num_bytes > 0)
		{
			if ( ($rc = fwrite ($sock, $full_msg, $num_bytes)) === FALSE )
			{
				echo 'WARN: fwrite to '.$this->_prpc_url_host.' failed ', "\n";
				return FALSE;
			}
			$num_bytes -= $rc;
			$full_msg = substr ($full_msg, $rc, $num_bytes);
		}

		stream_set_timeout($sock, $this->socket_timeout);

		$http_response_head = trim(fgets($sock));
		if (! preg_match ('/^HTTP\/1\.\d (\d+) (.*)/', $http_response_head, $m))
		{
			$stm_data = stream_get_meta_data($sock);
			if($stm_data['timed_out'] && $this->die) die ("FATAL: Socket Timeout from ".$this->_prpc_url);

			if ($this->die) die ("FATAL: No HTTP response header from ".$this->_prpc_url."\nGot ".$http_response_head."\n");
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
			if ($this->die) die ("FATAL: Bad HTTP response ".$http_response_code." - ".$http_response_msg."\n".$pack."\n");
			else return false;
		}

		$rpc = $this->_Prpc_Unpack ($pack);

		if (! is_object ($rpc))
		{
			 print_r ($pack); flush();
			 if ($this->die) die ("FATAL: Did not recieve an object\n");
			 else return false;
		}

		if (isset ($_SERVER ["HTTP_X_PRPC_DEPTH"]) && $_SERVER ["HTTP_X_PRPC_DEPTH"])
		{
			echo $rpc->output;
			return isset ($rpc->args) ? $rpc->args : $rpc;
		}
		else
		{
			$this->_prpc_debug .= $rpc->output;
			if (is_a ($rpc, 'prpc_fault2'))
			{
				echo "<pre>\n";
				print_r ($rpc);
				echo "\n", $this->_prpc_debug, "\n\n";
				echo "</pre>\n";
				exit;
			}

			if (is_a ($rpc, 'SourcePro_Prpc_Message_Except'))
			{
				if ($rpc->except instanceOf Exception)
				{
					throw $rpc->except;
				}
				else
				{
					throw new Exception("invalid exception!\n".print_r($rpc->except, 1));
				}
			}

			return isset ($rpc->args) ? $rpc->args : $rpc;
		}
	}


	function __call ($method, $arg)
	{
		if (method_exists ($this, $method))
		{
			$result = call_user_func_array (array (&$this, $method), $arg);
			return TRUE;
		}

		return $this->_Prpc_Call (new SourcePro_Prpc_Message_Call ($method, $arg));
	}

	/**
		Magic __sleep function.
	*/
	function __sleep ()
	{
		return array_keys(get_object_vars($this));
	}

	/**
		Magic __wakeup function.
	*/
	function __wakeup ()
	{

	}
}

?>
