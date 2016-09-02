<?
/**
  	@publicsection
	@public
	@brief
		A Blackbox helper class for HTTP Posting
	@version
		$Revision: 13259 $
	@todo
		
*/

class Http_Client
{
	const DEFAULT_TIMEOUT = 22;
	const HTTP_GET        = 0;
	const HTTP_POST       = 1;
	private $cookie_jar   = NULL;
	private $loc          = NULL;
	private $head         = NULL;
	private $timeout      = 0;
	private $response     = NULL;
	private $fields       = NULL;
	private $send_xml     = FALSE;
	public $timeout_exceeded = FALSE;

	public function __construct()
	{
		$this->Reset_State();
	}

	public function Reset_State()
	{
		$this->cookie_jar = Array();
		$this->loc        = '';
		$this->head       = Array();
		$this->timeout    = Http_Client::DEFAULT_TIMEOUT;
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
		$curl = curl_init ();

		if (is_array ($fields))
		{
			$url = $url.'?'.Http_Client::Url_Encode($fields);
		}

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
		$this->loc = '';

		if (!is_array($fields))
		{
			$this->send_xml = TRUE;
		}

		$curl = curl_init ();

		curl_setopt ($curl, CURLOPT_URL, $url);
		curl_setopt ($curl, CURLOPT_VERBOSE, 0);
		curl_setopt ($curl, CURLOPT_HEADER, 1);
		curl_setopt ($curl, CURLOPT_FAILONERROR, 1);
		curl_setopt ($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($curl, CURLOPT_TIMEOUT, $this->timeout);
		curl_setopt ($curl, CURLOPT_POST, 1);
		curl_setopt ($curl, CURLOPT_POSTFIELDS, Http_Client::Url_Encode($fields));

		if (count ($this->cookie_jar))
		{
			curl_setopt ($curl, CURLOPT_HTTPHEADER, array ($this->Cookie_Header ()));
		}

		
		
		// AFAIK, the above curl_setopt never gets run.  Setting the content type
		// to application/xml for global rebates.
		if ($this->send_xml)
		{
			// If posting fails make sure XML document has no leading or trailing spaces.
			
			// If we have headers, set them here.  Used primarily for SOAP which requires
			// a specific SOAPAction header [CB]
			if(!empty($this->head))
			{
				$soap_header = $this->head;
			}
			else
			{
				$soap_header = array('Content-Type:application/xml');
			}

			$soap_header[] = 'Content-Length: ' . strlen($fields);
			
			curl_setopt($curl, CURLOPT_HTTPHEADER, $soap_header); 
		}
				
		if (preg_match ('/^https/', $url))
		{
			curl_setopt ($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt ($curl, CURLOPT_SSL_VERIFYHOST, 0);
		}
		

		$start = time();
		$result = curl_exec ($curl);
		$duration = time() - $start;
		
		if (!$result && ($duration>=($this->timeout-1)))
		{
			$this->timeout_exceeded = TRUE;
		}

		$lines = explode ("\n", str_replace ("\r\n", "\n", $result));

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

	public static function Url_Encode ($fields)
	{
		$re = '';
		if (is_array($fields))
		{
			foreach ($fields as $k => $v)
			{
				$re .= urlencode ($k).'='.urlencode ($v).'&';
			}
			$re = substr ($re, 0, -1);
		}
		else
		{
			// sending xml
			$re = $fields;
		}
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

	
	public function Set_Headers($headers)
	{
		if(is_array($headers))
		{
			$this->head = $headers;
		}
		elseif(!empty($headers))
		{
			$this->head = array($headers);
		}
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
