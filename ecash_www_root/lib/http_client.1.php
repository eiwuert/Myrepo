<?
/**
  	@publicsection
	@public
	@brief
		A Blackbox helper class for HTTP Posting
	@version
		$Revision: 2490 $
	@todo
		
*/

// DLH, 2005.09.22, Created a new copy of http_client for /virtualhosts/lib/galileo/galileo_client.php
// in order to avoid name and class conflicts.

// require_once('dlhdebug.php');

class Http_Client_1
{
	const DEFAULT_TIMEOUT = 30;
	const HTTP_GET        = 0;
	const HTTP_POST       = 1;
	private $cookie_jar   = NULL;
	private $loc          = NULL;
	private $head         = NULL;
	private $timeout      = 0;
	private $response     = NULL;
	private $fields       = NULL;
	private $datasent     = NULL;       // This only applies to GET function.

	public function __construct()
	{
		$this->Reset_State();
	}

	public function Reset_State()
	{
		$this->cookie_jar = Array();
		$this->loc        = '';
		$this->head       = '';
		$this->timeout    = Http_Client_1::DEFAULT_TIMEOUT;
		$this->response   = Array();
		$this->fields     = Array();
	}

	/**
	 * @param $url String The target URL
	 * @param $fields Array The fields to encode into the URI
	 * @desc Uses CURL to send/receive data using an HTTP GET request
	 */
	public function Http_Get ($url, $fields = NULL)
	{
		$this->head = '';

		$curl = curl_init ();

		if (is_array ($fields))
		{
			$url = $url.'?'.Http_Client_1::Url_Encode($fields);
		}
		
		// dlhlog(__METHOD__ . ": entering, url=$url, fields=" . dlhvardump($fields));

		$this->datasent = $url;

		curl_setopt ($curl, CURLOPT_URL, $url);
		curl_setopt ($curl, CURLOPT_VERBOSE, 0);
		curl_setopt ($curl, CURLOPT_HEADER, 1);
		curl_setopt ($curl, CURLOPT_FAILONERROR, 1);
		curl_setopt ($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($curl, CURLOPT_TIMEOUT, $this->timeout);

		if (count ($this->cookie_jar))
		{
			curl_setopt ($curl, CURLOPT_HTTPHEADER, array ($this->Cookie_Header ()));
		}

		if (preg_match ('/^https/', $url))
		{
			curl_setopt ($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt ($curl, CURLOPT_SSL_VERIFYHOST, 0);
		}

		$result = curl_exec ($curl);
		
		// dlhlog(__METHOD__ . ": result=$result");

		$lines = explode ("\n", str_replace ("\r\n", "\n", $result));

		do
		{
			$line = array_shift ($lines);
			$this->Process_Header ($line);
		}
		while (trim ($line));

		$this->response = $lines;

		return $this->Get_Response_As_String();
	}

	public function Http_Post ($url, $fields)
	{
		// dlhlog(__METHOD__ . ": entering, url=$url, fields=" . dlhvardump($fields));
		$this->loc = '';
		$this->head = '';

		$curl = curl_init ();

		curl_setopt ($curl, CURLOPT_URL, $url);
		curl_setopt ($curl, CURLOPT_VERBOSE, 0);
		curl_setopt ($curl, CURLOPT_HEADER, 1);
		curl_setopt ($curl, CURLOPT_FAILONERROR, 1);
		curl_setopt ($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($curl, CURLOPT_TIMEOUT, $this->timeout);
		curl_setopt ($curl, CURLOPT_POST, 1);
		curl_setopt ($curl, CURLOPT_POSTFIELDS, Http_Client_1::Url_Encode($fields));

		if (count ($this->cookie_jar))
		{
			curl_setopt ($curl, CURLOPT_HTTPHEADER, array ($this->Cookie_Header ()));
		}

		if (preg_match ('/^https/', $url))
		{
			curl_setopt ($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt ($curl, CURLOPT_SSL_VERIFYHOST, 0);
		}

		$result = curl_exec ($curl);
		// dlhlog(__METHOD__ . ": result=$result");

		$lines = explode ("\n", str_replace ("\r\n", "\n", $result));
		
		// strip off first two lines if there is a header continue HACK
		if ($lines[0] == 'HTTP/1.1 100 Continue')
			for($i = 1; $i<=2; $i++) array_shift($lines);
				
		do
		{
			$line = array_shift ($lines);
			$this->Process_Header ($line);
		}
		while (trim ($line));
		
		$result = implode ("\n", $lines);
		if ($this->loc)
		{
			if (! preg_match ('/^http/i', $this->loc))
			{
				if (preg_match ('/^\//', $this->loc))
				{
					preg_match ('/(https?:\/\/[^\/]+\/)/', $url, $m);
				}
				else
				{
					preg_match ('/(https?:\/\/.+\/)/', $url, $m);
				}
				$loc = $m[1].$this->loc;
			}
			else
			{
				$loc = $this->loc;
			}
			$result = $this->Http_Get ($loc);
		}

		return $result;
	}


	public function Get_Data_Sent()
	{
		return $this->datasent;
	}
	

	public static function Url_Encode ($fields)
	{
		$re = '';
		foreach ($fields as $k => $v)
		{
			$re .= urlencode ($k).'='.urlencode ($v).'&';
		}
		$re = substr ($re, 0, -1);
		return $re;
	}

	private function Process_Header ($line)
	{
		switch (TRUE)
		{
			case (preg_match ('/set-cookie:\s*([^=]+)=([^; ]+)/i', $line, $m)):
						       $this->cookie_jar [$m[1]] = $m[2];
						       break;

			case (preg_match ('/^location:\s*(\S+)\s*$/i', $line, $m)):
						      $this->loc = $m[1];
						      break;
		}
	}

	private function Cookie_Header ()
	{
		$re = 'Cookie: ';
		foreach ($this->cookie_jar as $k => $v)
		{
			$re .= $k.'='.$v.'; ';
		}
		return substr ($re, 0, -2);
	}

	public function Set_Timeout($timeout)
	{
		$this->timeout = $timeout;
	}

	public function Get_Timeout()
	{
		return $this->timeout;
	}

	public function Get_Cookies()
	{
		return $this->cookie_jar;
	}

	public function Get_Response_As_Array()
	{
		return $this->response;
	}

	public function Get_Response_As_String()
	{
		return implode ("\n", $this->response);
	}
}
