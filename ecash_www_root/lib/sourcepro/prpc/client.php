<?php
/**
	A client for accesing remote objects via prpc.
*/

class SourcePro_Prpc_Client extends SourcePro_Prpc_Base
{
	private $url;
	private $prot;
	private $host;
	private $port;
	private $path;
	private $debug;
	private $sock;
	private $timeout_sec = 30;
	private $timeout_usec = 0;

	function __construct (
		$url, 
		$debug = FALSE, 
		$exception_method = SourcePro_Prpc_Base::PRPC_EXCEPTION_PHP5, 
		$serialize_method = SourcePro_Prpc_Base::PRPC_SERIALIZE_STANDARD, 
		$compress_method = SourcePro_Prpc_Base::PRPC_COMPRESS_ANY)
	{
		if (! preg_match ('/^(https?):\/\/([^\/:]+)(?::(\d+))?(\/.*)?/', $url, $m))
		{
			throw new SourcePro_Exception('invalid url ('.$url.")", 1000);
		}
		
		$this->url = $url;
		$this->prot = $m[1];
		$this->host = $m[2];
		$this->port = $m[3] ? $m[3] : 80;
		$this->path = $m[4];
		$this->debug = $debug;

		switch ($compress_method)
		{
			case SourcePro_Prpc_Base::PRPC_COMPRESS_NO:
			case SourcePro_Prpc_Base::PRPC_COMPRESS_GZ:
			case SourcePro_Prpc_Base::PRPC_COMPRESS_BZ:
				$this->_compress_method = $compress_method;
			break;
				
			case SourcePro_Prpc_Base::PRPC_COMPRESS_ANY:
			default:
				switch (TRUE)
				{
					case (extension_loaded ('zlib') || @dl ('zlib')):
						$this->_compress_method = SourcePro_Prpc_Base::PRPC_COMPRESS_GZ;
						break;
					case (extension_loaded ('bz2') || @dl ('bz2')):
						$this->_compress_method = SourcePro_Prpc_Base::PRPC_COMPRESS_BZ;
						break;
					default:
						$this->_compress_method = SourcePro_Prpc_Base::PRPC_COMPRESS_NO;
				}
			break;
		}

		$this->_serialize_method = $serialize_method;
		$this->_exception_method = $exception_method;
	}
	
	function Set_Timeout ($sec, $usec = 0)
	{
		$this->timeout_sec = $sec;
		$this->timeout_usec = $usec;
	}

	function Connect ()
	{
		$errno = $errstr = NULL;
		if (! ($this->sock = @fsockopen($this->host, $this->port, $errno, $errstr, 30)))
		{
			throw new SourcePro_Exception("fsockopen error #$errno - $errstr", 1000);
		}
		stream_set_timeout ($this->sock, $this->timeout_sec, $this->timeout_usec);
	}

	function __call ($name, $args)
	{
		try
		{
			return $this->_call(new SourcePro_Prpc_Message_Call($name, $args));
		}
		catch (Exception $e)
		{
			throw $e;
		}
	}

	private function _call ($call)
	{
		if (!isset ($this->sock))
		{
			$this->Connect ();
		}
		
		$pack = $this->_pack($call);
		$size = strlen($pack);

		$head =
			"POST ".$this->path." HTTP/1.0\r\n".
			"Host: ".$this->host."\r\n".
			"User-Agent: sprClient\r\n".
			"X-Prpc-Compress-Method: ".$this->_compress_method."\r\n".
			"X-Prpc-Serialize-Method: ".$this->_serialize_method."\r\n".
			"X-Prpc-Exception-Method: ".$this->_exception_method."\r\n".
			"X-Prpc-Pack: ".$this->_compress_method."\r\n".
			"Connection: close\r\n".
			"Content-Type: form-data\r\n".
			"Content-Transfer-Encoding: binary\r\n".
			"Content-Length: $size\r\n".
			"\r\n";

		$data = $head.$pack;
		$bytes_left = strlen($data);

		while ($bytes_left)
		{
			if (($bytes_done = fwrite($this->sock, $data, $bytes_left)) === FALSE)
			{
				throw new SourcePro_Exception("fwrite failed", 1000);
			}

			$data = substr($data, $bytes_done);
			$bytes_left = strlen($data);
		}

		$http_response_head = trim(fgets($this->sock));
		if (! preg_match ('/^HTTP\/1\.\d (\d+) (.*)/', $http_response_head, $m))
		{
			throw new SourcePro_Exception("no response header from {$this->url}", 1000);
		}
		$http_response_code = $m[1];
		//$http_response_msg = $m[2];

		while (! feof($this->sock) && (($head = trim(fgets($this->sock))) != ''))
		{
			// could process other headers here
		}

		for ($data = '' ; ! feof($this->sock) ; )
		{
			$data .= fread($this->sock, 2048);
		}
		fclose ($this->sock);
		$this->sock = NULL;

		if ($http_response_code != 200)
		{
			throw new SourcePro_Exception("bad response header from {$this->url} ($http_response_head)", 1000);
		}

		$mesg = $this->_unpack($data);

		if ($this->debug)
		{
			echo $mesg->output;
		}
		switch (TRUE)
		{
			case ($mesg instanceof SourcePro_Prpc_Message_Return):
				return $mesg->args;
				break;

			case ($mesg instanceof SourcePro_Prpc_Message_Except):
				if ($mesg->except instanceof Exception)
					throw $mesg->except;
				else
					throw new Exception("invalid exception in sourcepro_prpc_message_except\n".print_r($mesg->except, 1)."\n", 1000);	
				break;

			default:
				throw new SourcePro_Exception("invalid SourcePro_Prpc_Message object", 1000);
		}
	}
}


?>
