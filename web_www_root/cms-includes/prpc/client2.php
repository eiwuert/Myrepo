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
	var $die;

	function setPrpcDieToFalse()
	{
		$this->die = false;
	}

	function Prpc_Client ($url = NULL, $use_debug = FALSE, $use_trace = PRPC_TRACE_NONE)
	{
		$this->die = true;
		$this->_Prpc_Set_Url ($url);
		$this->_prpc_use_debug = $use_debug;

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
			echo 'WARN: fsockopen of '.$this->_prpc_url_host.' failed ', $errno, ' - ', $errstr, "\n";
			return FALSE;
		}

		$content = $this->_Prpc_Pack ($call);
		$length = strlen ($content);

		$use_port = $this->_prpc_url_port == '80' ? '' : ":$this->_prpc_url_port";

		$head =
			"POST ".$this->_prpc_url_path." HTTP/1.0\r\n".
			"Host: ".$this->_prpc_url_host."$use_port\r\n".
			"User-Agent: PRPC ".PRPC_PROT_VER."\r\n".
			"Connection: close\r\n".
			"Content-Type: form-data\r\n".
			"Content-Transfer-Encoding: binary\r\n".
			"Content-Length: ".$length."\r\n".
			"X-Prpc-Debug: ".$this->_prpc_use_debug."\r\n".
			"X-Prpc-Depth: ".(isset ($_SERVER ["HTTP_X_PRPC_DEPTH"]) ? $_SERVER ["HTTP_X_PRPC_DEPTH"] + 1 : 1)."\r\n".
			"X-Prpc-Pack: ".$this->_prpc_use_pack."\r\n".
			(($this->_prpc_url_user !== NULL) ? 'Authorization: Basic '.base64_encode($this->_prpc_url_user.':'.$this->_prpc_url_pw)."\r\n" : '').
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

		$http_response_head = trim(fgets($sock));
		if (! preg_match ('/^HTTP\/1\.\d (\d+) (.*)/', $http_response_head, $m))
		{
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

var_dump($pack);
var_dump($http_response_code);

		if ($http_response_code != 200)
		{
			if ($this->die) die ("FATAL: Bad HTTP response ".$http_response_code." - ".$http_response_msg."\n".$pack."\n");
			else return false;
		}

		$rpc = $this->_Prpc_Unpack ($pack);

		if (! is_a ($rpc, 'Prpc_Message'))
		{
			 print_r ($pack); flush();
			 if ($this->die) die ("FATAL: Did not receive a Prpc_Message\n");
			 else return false;
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
			if (is_a ($rpc, 'prpc_fault'))
			{
				echo "<pre>\n";
				print_r ($rpc);
				echo "\n", $this->_prpc_debug, "\n\n";
				echo "</pre>\n";
				exit;
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
		else
		{
			return $this->_Prpc_Call (new Prpc_Call ($method, $arg));
		}
	}
}

?>