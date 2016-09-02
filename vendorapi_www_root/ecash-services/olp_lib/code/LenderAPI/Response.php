<?php
/**
 * LenderAPI_Response
 *
 * @uses Object_1
 * @package LendorAPI
 * @version $Id: Response.php 38665 2009-08-20 03:21:42Z olp_release $
 */
class LenderAPI_Response extends Object_1 implements LenderAPI_IResponse
{
	// properties from bfw/vendor_post_result

	public function getMetaData()
	{
		return array(
			'message' => $this->message,
			'success' => $this->success,
			'next_page' => $this->next_page,
			'decision' => $this->decision,
			'reason' => $this->reason,
			'redirect_url' => $this->redirect_url,
			'timeout_exceeded' => $this->timeout_exceeded,
			'cookie_jar' => $this->cookie_jar,
			'thank_you_content' => $this->thank_you_content,
			'persistent_data' => $this->persistent_data,
		);
	}

	/**
	 * @var int
	 */
	protected $post_time;
	public function getPostTime() { return $this->post_time; }
	public function setPostTime($data) { $this->post_time = $data; }

	/**
	 * @var string
	 */
	protected $message;
	public function getMessage() { return $this->message; }
	public function setMessage($data) { $this->message = $data; }

	/**
	 * @var string
	 */
	protected $success;
	public function getSuccess() { return $this->success; }
	public function setSuccess($data) { $this->success = $data; }

	/**
	 * @var string
	 */
	protected $empty_response;
	public function getEmptyResponse() { return $this->empty_response; }
	public function setEmptyResponse($data) { $this->empty_response = $data; }

	/**
	 * @var string
	 */
	protected $exception;
	public function getException() { return $this->exception; }
	public function setException($data) { $this->exception = $data; }

	/**
	 * @var string
	 */
	protected $data_sent;
	public function getDataSent() { return $this->data_sent; }
	public function setDataSent($data) { $this->data_sent = $data; }

	/**
	 * @var string
	 */
	protected $thank_you_content;
	public function getThankYouContent() { return $this->thank_you_content; }
	public function setThankYouContent($data) { $this->thank_you_content = $data; }

	/**
	 * @var string
	 */
	protected $next_page;
	public function getNextPage() { return $this->next_page; }
	public function setNextPage($data) { $this->next_page = $data; }

	/**
	 * @var decision
	 */
	protected $decision;
	public function getDecision() { return $this->decision; }
	public function setDecision($data) { $this->decision = $data; }

	/**
	 * @var string
	 */
	protected $reason;
	public function getReason() { return $this->reason; }
	public function setReason($data) 
	{
		$this->reason = $data;
	}

	/**
	 * @var string
	 */
	protected $redirect_url;
	public function getRedirectUrl() { return $this->redirect_url; }
	public function setRedirectUrl($data) { $this->redirect_url = $data; }

	/**
	 * Indicates if the http client timed out
	 *
	 * @var bool
	 */
	protected $timeout_exceeded;
	public function getTimeoutExceeded() { return $this->timeout_exceeded; }
	public function setTimeoutExceeded($data) { $this->timeout_exceeded = $data; }

	/**
	 * The data_received
	 *
	 * @var mixed
	 */
	protected $data_received;
	public function getDataReceived() 
	{ 
		return $this->data_received; 
	}
	public function setDataReceived($data) 
	{
		$this->onDataReceived($data);
	}
	
	/**
	 * The persistent data
	 *
	 * @var mixed
	 */
	protected $persistent_data;
	public function getPersistentData() { return $this->persistent_data; }
	public function setPersistentData($data) { $this->persistent_data = $data; }

	/**
	 * @var mixed
	 */
	protected $cookie_jar;
	public function getCookieJar() { return $this->cookie_jar; }
	public function setCookieJar($data) { $this->cookie_jar = $data; }

	/**
	 * The transformer
	 *
	 * @var LenderAPI_XslTransformer
	 */
	protected $transform;

	/**
	* @return LenderAPI_XslTransformer
	*/
	public function getTransform() 
	{ 
		return $this->transform; 
	}

	/**
	* @param LenderAPI_XslTransformer $data
	*/
	public function setTransform(LenderAPI_XslTransformer $data) 
	{ 
		$this->transform = $data; 
	}
	
	/**
	 * Initialize default values.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->post_time = 0.0;
		$this->decision = 'BLANK';
	}
	
	/**
	 * Describe this response object.
	 *
	 * @return string
	 */
	public function __toString()
	{
		$meta = array();
		foreach ($this->getMetaData() as $key => $value)
		{
			if (!$value) continue;
			
			if (!is_string($value) || strlen($value) > 40 || stripos($value, "\n") !== FALSE) 
			{
				$value = substr(str_replace("\n", ' ', strval($value)), 0, 40);
			}
			$meta[] = "\t$key => $value\n";
		}
		
		if ($meta)
		{
			$meta = ":\n" . implode("\n", $meta) . "\n\t";
		}
		else
		{
			$meta = '';
		}
		
		return sprintf("<%s at %s%s>", 
			get_class($this), 
			spl_object_hash($this), 
			$meta
		);
	}
	
	/**
	 * Returns this object as XML
	 *
	 * @return string
	 */
	public function toXml()
	{
		$e = new SimpleXmlElement('<'.__CLASS__.'/>');
		foreach ($this as $k => $v)
		{
			if (!($v === NULL || is_object($v) || is_array($v)))
			{
				$e->$k = $v;
			}
		}
		return $e->asXML();
	}

	/**
	 * Populates this object from XML
	 *
	 * @param mixed $xml
	 * @return void
	 */
	public function fromXml($xml)
	{
		$e = new SimpleXMLElement($xml);
		foreach($e as $k => $v)
		{
			if (strcasecmp($k, 'PERSISTENT') == 0)
			{
				$this->persistent_data = $this->simpleXMLToArray($v);
			}
			elseif (property_exists($this, $k))
			{
				$this->$k = trim((string) $v);
			}
		}
	}
	
	protected function simpleXMLToArray($obj)
	{
		$array = (array)$obj;
		foreach ($array as $k => $v)
		{
			if ($v instanceof SimpleXMLElement)
			{
				$array[$k] = $this->simpleXMLToArray($v);
			}
		}
		
		return $array;
	}

	/**
	 * onDataReceived event, triggered when data_received is set.
	 *
	 * @param mixed $data
	 * @return void
	 */
	protected function onDataReceived($data)
	{
		$this->data_received = $data;
		
		if (!trim($data))
		{
			$this->Empty_Response();
			return;
		}

		if ($this->transform)
		{
			try
			{
				$data = $this->transform->transform(
					$this->makeDOMDocumentFrom($data)
				);
			}
			catch (InvalidArgumentException $e)
			{
				throw new LenderAPI_XMLParseException(
					'Failed to transform Data Received', 'Data Received'
				);
			}
		}
		try
		{
			$this->fromXml($data);
		}
		catch (Exception $e)
		{
			throw new LenderAPI_XMLParseException(
				'Failed to parse output of the response XSL', 'Response XML'
			);	
		}
	}
	
	/**
	 * Takes a string which may be XML, invalid XML or junk and turns it into
	 * a valid DOMDocument.
	 *
	 * @param string $data
	 * @return DOMDocument
	 */
	public function makeDOMDocumentFrom($data)
	{
		$doc = new DOMDocument();
		$data = trim($data);
		$loaded = @$doc->loadXML($data);
		
		// if data is not valid xml, try to fix it
		if (!$loaded)
		{		
			$data = $this->wrapInvalidTagsInCDATA($data);
			$loaded = @$doc->loadXML($data);
		}
		if (!$loaded)
		{
			$data = $this->wrapMultipleRootNodes($data);
			$loaded = @$doc->loadXML($data);
		}
		if (!$loaded)
		{
			// data is non-XML, or we just ran out of ways to fix it,
			// so we wrap it in XML to allow XSLT to transform it.
			$root = $doc->createElement('non-xml-response');
			$root->appendChild($doc->createTextNode($data));
			$doc->appendChild($root);
		}		
		
		return $doc;
	}
	
	/**
	 * Loop through all the text nodes and wrap invalid ones in cdata.
	 * This will cover vendors who send back otherwise valid xml, but put
	 * an ampersand in a redirect url for example
	 * 
	 * @param string $data
	 * @return string
	 */
	protected function wrapInvalidTagsInCDATA($data)
	{
		$doc = new DOMDocument();
		
		// match all xml tags that do not contain any children
		preg_match_all('/<((\S+)[^\>]*)>([^\<]*)<\/\2>/isU', $data, $matches, PREG_SET_ORDER);
		foreach ($matches as $match)
		{
			$fragment = $doc->createDocumentFragment();
			$loaded = @$fragment->appendXML($match[0]);
			
			// if this is an invalid tag, wrap its contents in cdata
			if (!$loaded)
			{
				$wrapped = "<{$match[1]}><![CDATA[".trim($match[3])."]]></{$match[2]}>";
				$data = str_replace($match[0], $wrapped, $data);
			}
		}
		
		return $data;
	}
	
	/**
	 * If data looks like semi-valid xml then wrap it in a single root node,
	 * some vendors send stuff with multiple root nodes, etc.
	 * 
	 * @param string $data
	 * @return string
	 */
	protected function wrapMultipleRootNodes($data)
	{
		$doc = new DOMDocument();
		
		// data looks like semi-valid xml, some vendors send stuff with multiple 
		// root nodes, etc.
		if (preg_match('/<([-_:\.a-z0-9]+)>/i', $data))
		{
			$root = $doc->createElement('root');

			// get rid of xml declaration, createDocumentFragment will freak out if its there
			$data = preg_replace('/<\?xml.*\?>/iU', '', $data);
			
			$fragment = $doc->createDocumentFragment();
			$loaded = @$fragment->appendXML($data);
			
			if ($loaded)
			{
				$root->appendChild($fragment);
				$doc->appendChild($root);
				$data = $doc->saveXml();
			}
		}
		
		return $data;
	}

	/**
	 * Not sure what this is for...
	 *
	 * @param mixed $winner
	 * @return void
	 */
	public function Set_Winner($winner)
	{
		// If we have a lender specified sales target, record it here as new_winner
		// Only specific implementations will use this. [LR]
		if ($_SESSION['blackbox']['winner'] != $winner)
		{
			$_SESSION['blackbox']['new_winner'] = $winner;
		}
	}

	/*
	** OLP Style - deprecated
	*/
	public function Get_Message()
	{
		return $this->getMessage();
	}

	public function Set_Message($message)
	{
		$this->setMessage($message);
	}

	public function Set_Data_Sent($data_sent)
	{
		$this->setDataSent($data_sent);
	}

	public function Get_Data_Sent()
	{
		return $this->getDataSent();
	}

	public function Set_Data_Received($data_received)
	{
		$this->setDataReceived($data_received);
	}

	public function Get_Data_Received()
	{
		return $this->getDataReceived();
	}

	public function Set_Next_Page($page)
	{
		$this->setNextPage($page);
	}

	public function Is_Next_Page()
	{
		return $this->getNextPage();
	}

	public function Is_Success()
	{
		return $this->getSuccess();
	}

	public function Is_Empty_Response()
	{
		return $this->getEmptyResponse();
	}

	/**
	 * Better name for {@see Is_Empty_Response}.
	 * @return bool
	 */
	public function isEmpty()
	{
		return $this->Is_Empty_Response();
	}

	public function Set_Success($is_success)
	{
		$this->setSuccess((boolean)$is_success);
	}

	public function Set_Post_Time($post_time)
	{
		$this->setPostTime($post_time);
	}

	public function Get_Post_Time()
	{
		return $this->getPostTime();
	}

	public function Set_Thank_You_Content($thank_you_content)
	{
		$this->setThankYouContent($thank_you_content);
	}

	public function Get_Thank_You_Content()
	{
		return $this->getThankYouContent();
	}

	public function Empty_Response()
	{
		$this->setSuccess(FALSE);
		$this->setMessage("No response from vendor's server");
		$this->setEmptyResponse(TRUE);
		if ($this->getTimeoutExceeded())
		{
			$this->setDecision('TIMEOUT');
		}
	}

	public function Set_Vendor_Decision($decision = 'FAILED')
	{
		$this->setDecision($decision);
	}

	public function Set_Vendor_Reason($reason = '')
	{
		$this->setReason($reason);
	}

	public function Get_Vendor_Decision()
	{
		return $this->getDecision();
	}

	public function Get_Vendor_Reason()
	{
		return $this->getReason();
	}

	public function Post_Timeout_Exceeded()
	{
		return $this->getTimeoutExceeded();
	}
}

?>
