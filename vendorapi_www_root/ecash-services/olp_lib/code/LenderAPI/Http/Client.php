<?php
/**
	@package LenderAPI
	@version $Id$
*/

class LenderAPI_Http_Client
{
	const DEFAULT_TIMEOUT = 22;
	const HTTP_GET        = 0;
	const HTTP_POST       = 1;
	const HTTP_ERROR = CURLE_HTTP_NOT_FOUND;
	const HTTP_CONNECTION_REFUSED = CURLE_COULDNT_CONNECT;
	
	private $cookie_jar   = NULL;
	private $loc          = NULL;
	private $head         = NULL;
	private $timeout      = 0;
	private $response     = NULL;
	private $fields       = NULL;
	private $send_xml     = FALSE;
	public $timeout_exceeded = FALSE;
	
	/**
	 * The response time of the request.
	 *
	 * @var float
	 */
	protected $response_time = 0;
	
	protected $error_code = 0;
	protected $error_message = '';

	public function __construct()
	{
		$this->Reset_State();
	}

	public function Reset_State()
	{
		$this->cookie_jar = Array();
		$this->loc        = '';
		$this->head       = Array();
		$this->timeout    = self::DEFAULT_TIMEOUT;
		$this->response   = Array();
		$this->fields     = Array();
	}

	/**
	 * @param $url String The target URL
	 * @param $fields Array The fields to encode into the URI
	 * @desc Uses CURL to send/receive data using an HTTP GET request
	 */
	public function Http_Get($url, $fields = NULL)
	{
		$curl = curl_init ();

		if (is_array ($fields))
		{
			$url = $url.'?'.self::Url_Encode($fields);
		}

		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_VERBOSE, 0);
		curl_setopt($curl, CURLOPT_HEADER, 1);
		curl_setopt($curl, CURLOPT_FAILONERROR, 1);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);

		if (count($this->cookie_jar))
		{
			curl_setopt($curl, CURLOPT_HTTPHEADER, array($this->Cookie_Header()));
		}
		
		$this->addRequiredHeaders();
		$this->setCurlHeaders($curl, $this->head);

		if (preg_match('/^https/', $url))
		{
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		}

		$result = curl_exec($curl);
		
		$this->handleError($curl);

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
	
	protected function handleError($curl_resource)
	{
		if (curl_errno($curl_resource))
		{
			$this->error_code = curl_errno($curl_resource);
			$this->error_message = curl_error($curl_resource);
		}
	}

	public function Http_Post($url, $fields)
	{
		$this->loc = '';

		if (!is_array($fields))
		{
			$this->send_xml = TRUE;
		}

		$curl = curl_init ();

		curl_setopt ($curl, CURLOPT_URL, $url);
		curl_setopt ($curl, CURLOPT_VERBOSE, 0);
		curl_setopt ($curl, CURLOPT_HEADER, 0);
		curl_setopt ($curl, CURLOPT_FAILONERROR, 1);
		curl_setopt ($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($curl, CURLOPT_TIMEOUT, $this->timeout);
		curl_setopt ($curl, CURLOPT_POST, 1);
		curl_setopt ($curl, CURLOPT_POSTFIELDS, self::Url_Encode($fields));

		if (count ($this->cookie_jar))
		{
			curl_setopt ($curl, CURLOPT_HTTPHEADER, array ($this->Cookie_Header ()));
		}
		
		$this->addRequiredHeaders();

		// AFAIK, the above curl_setopt never gets run.  Setting the content type
		// to application/xml for global rebates.
		if ($this->send_xml)
		{
			// If posting fails make sure XML document has no leading or trailing spaces.	
			$this->addSOAPHeaders(strlen($fields));
		}
		$this->setCurlHeaders($curl, $this->head);
		
		if (preg_match ('/^https/', $url))
		{
			curl_setopt ($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt ($curl, CURLOPT_SSL_VERIFYHOST, 0);
		}
		
		$start_time = microtime(TRUE);
		$result = curl_exec($curl);
		$this->response_time = microtime(TRUE) - $start_time;
		
		$this->handleError($curl);

		if (!$result && ($this->response_time >= ($this->timeout - 1)))
		{
			$this->timeout_exceeded = TRUE;
		}
		
		return str_replace("\r\n", "\n", $result);
	}
	
	/**
	 * Returns the response time of the request in seconds.
	 *
	 * @return int
	 */
	public function getResponseTime()
	{
		return $this->response_time;
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
	
	/**
	 * Adds SOAP Headers to send as a composite of explicitly set headers for 
	 * this object and default soap headers.
	 * 
	 * NOTE: By default only Content-Type and Content-Length are set, any SOAP Action
	 * headers must currently be explicitly set.
	 *
	 * @param int $content_length The content length of the message we'll be 
	 * posting.
	 * @return void
	 */
	protected function addSOAPHeaders($content_length)
	{
		if(!is_array($this->head))
		{
			$this->head = array();
		}
		
		if ($this->findStartsWith('Content-Type', $this->head) === FALSE)
		{
			$this->head[] = 'Content-Type: application/xml'; 
		}
		
		if ($this->findStartsWith('Content-Length', $this->head) !== FALSE)
		{
			unset($this->head[$this->findStartsWith('Content-Length')]);
		}

		$this->head[] = 'Content-Length: ' . $content_length;
	}
	
	/**
	 * Add the required headers to the packet if it's not already set.
	 * 
	 * NOTE: This is primarily needed to set the User-Agent header to 
	 * 'Soap Client' when posting to bbxadmin to use the sample 
	 * responses in order to bypass the GSO page.
	 *
	 * @return void
	 */
	protected function addRequiredHeaders()
	{
		if(!is_array($this->head))
		{
			$this->head = array();
		}
		
		if ($this->findStartsWith('User-Agent', $this->head) === FALSE)
		{
			$this->head[] = 'User-Agent: SOAP Client'; 
		}
	}
	
	/**
	 * Loops through an array (http headers) and finds the key of an item which 
	 * begins with the string provided (case-insensitive).
	 *
	 * @param string $string The string we're trying to match.
	 * @param array|Traversable $array List of strings to check.
	 * @return mixed The key of the item found to match or FALSE if no items in
	 * the array matched.
	 */
	protected function findStartsWith($string, $array)
	{
		foreach ($array as $key => $value)
		{
			if (strtolower($string) == strtolower(substr($value, 0, strlen($string))))
			{
				return $key;
			}
		}
		
		return FALSE;
	}
	
	/**
	 * Equip the curl transport resource with a list of headers.
	 *
	 * @param resource $resource Curl resource made with curl_init()
	 * @param array $headers The headers to set as a list of strings.
	 * @return void
	 */
	protected function setCurlHeaders($resource, array $headers)
	{
		curl_setopt($resource, CURLOPT_HTTPHEADER, $headers);
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
	
	public function getErrorCode()
	{
		return $this->error_code;
	}
	
	public function getErrorMessage()
	{
		return $this->error_message;
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
