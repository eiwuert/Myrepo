<?php
/**
 * LenderAPI_Transport
 *
 * @uses Object_1
 * @package LendorAPI
 * @version $Id: Transport.php 40026 2009-11-12 04:24:55Z olp_release $
 */
class LenderAPI_Transport extends Object_1
{
	/**
	 * The transport method (get,post,xml,post_soap,soap)
	 *
	 * @var string
	 */
	protected $method;
	
	public function getMethod() 
	{ 
		return $this->method; 
	}
	
	public function setMethod($data) 
	{ 
		$this->method = strtolower($data); 
	}

	/**
	 * The url
	 *
	 * @var string
	 */
	protected $url;
	public function getUrl() { return $this->url; }
	public function setUrl($data) { $this->url = $data; }

	/**
	 * Time out.
	 *
	 * @var int
	 */
	protected $timeout;

	/**
	 * Get time out.
	 *
	 * @return int
	 */
	public function getTimeout()
	{
		return $this->timeout;
	}

	/**
	 * Set time out.
	 *
	 * @param int $data
	 * @return void
	 */
	public function setTimeout($data)
	{
		$this->timeout = (int)$data;
	}

	/**
	 * The response object
	 *
	 * @var LenderAPI_Response
	 */
	protected $response;
	public function getResponse() { return $this->response; }
	public function setResponse($data) { $this->response = $data; }

	/**
	 * Array of paramaters
	 *
	 * @var array
	 */
	protected $param;
	public function getParam() { return $this->param; }
	public function setParam($data) { $this->param = $data; }

	/**
	 * The user agent.
	 *
	 * @var LenderAPI_Http_Client
	 */
	protected $agent;
	public function getAgent() { return $this->agent; }
	public function setAgent($data) { $this->agent = $data; }

	protected $headers;
	public function getHeaders() { return $this->headers; }
	public function setHeaders($data) { $this->headers = $data; }


	public function __construct($url = NULL, $method = NULL, $timeout = NULL, $response = NULL, $param = NULL, $agent = NULL)
	{
		if (!empty($url))
		{
			$this->setUrl($url);
		}
		else
		{
			throw new LenderAPI_ConfigurationException(
				'url must be provided for this post method'
			);
		}
		
		$this->setMethod($method);
		$this->setTimeout($timeout);
		$this->setResponse($response);
		$this->setParam($param);

		$this->agent = $agent === NULL ? new LenderAPI_Http_Client() : $agent;
	}


	/**
	 * send the data
	 *
	 * @param array|string $data
	 * @return LenderAPI_Response
	 */
	public function send($data)
	{
		$this->agent->Reset_State();
		if (!empty($this->timeout))
		{
			$this->agent->Set_Timeout($this->timeout);
		}
		
		$start = microtime(TRUE);
		
		$h = array();
		if (! empty($this->headers))
		{
			foreach ($this->headers as $k => $v)
			{
				$h[] = "{$k}: {$v}";
			}
			$this->agent->Set_Headers($h);
		}
		
		$this->response->setDataSent(implode("\n", $h)."\n\n".$data);
		
		switch ($this->method)
		{
			case 'get':
				if (! (is_array($data) || $data instanceOf Traversable)) $data = (array) new SimpleXMLElement($data);
				$body = $this->agent->Http_Get($this->url, $data);
				break;
			
			case 'post':
				if (! (is_array($data) || $data instanceOf Traversable)) $data = (array) new SimpleXMLElement($data);
			case 'post_xml':
				$body = $this->agent->Http_Post($this->url, $data);
				break;

			case 'post_soap':
				$data = $this->makeSoapRequest($data);
				$this->response->setDataSent(implode("\n", $h)."\n\n".$data);  // reset for new data
				$body = $this->agent->Http_Post($this->url, $data);
				break;

			case 'soap':
			default:
				throw new Exception("unknown method '$this->method'");
		}
		
		$this->response->setPostTime($this->agent->getResponseTime());
		$this->response->cookieJar = $this->agent->Get_Cookies();
		$this->response->setDataReceived($body);
		$this->response->timeoutExceeded = ((bool)$this->agent->timeout_exceeded || ($this->response->getDecision() == 'TIMEOUT'));
		
		if ($this->agent->getErrorCode())
		{
			$this->response->setDecision(
				$this->agent->getErrorCode() == CURLE_OPERATION_TIMEOUTED 
					? 'TIMEOUT' 
					: 'ERROR'
			);
			$this->response->setReason(
				$this->agent->getErrorCode() . ': ' . $this->agent->getErrorMessage()
			);
		}

		return $this->response;
	}

	/**
	 * Wrap $xml in a SOAP Envelope and Body
	 *
	 * @param string $xml
	 * @return string
	 */
	public function makeSoapRequest($xml)
	{
		$doc = new DOMDocument('1.0','utf-8');

		$env = $doc->createElement('soap:Envelope');
		$env->setAttribute('SOAP-ENV:encodingStyle',"http://schemas.xmlsoap.org/soap/encoding/");
		$env->setAttribute('xmlns:SOAP-ENV', "http://schemas.xmlsoap.org/soap/envelope/");
		$env->setAttribute('xmlns:xsd', "http://www.w3.org/2001/XMLSchema");
		$env->setAttribute('xmlns:xsi', "http://www.w3.org/2001/XMLSchema-instance");
		$env->setAttribute('xmlns:SOAP-ENC', "http://schemas.xmlsoap.org/soap/encoding/");
		$env->setAttribute('xmlns:soap', "http://schemas.xmlsoap.org/soap/envelope/");

		$doc->appendChild($env);
		$body = $doc->createElement('soap:Body');
		$env->appendChild($body);

		$f = $doc->createDocumentFragment();

		// we use @ here because sometimes Lenders specify stuff which will throw
		// warnings or notices which can break output
		$appended = @$f->appendXML($xml);
		if (!$appended)
		{
			throw new LenderAPI_XMLParseException('Unable to append xml: ' . $xml);
		}

		$body->appendChild($f);

		return $doc->saveXML();
	}
}


?>
