<?php

/**
 * Information about the Tribal request made, and the response
 */
class TSS_Tribal_Result
{
	/**
	 * @var string
	 */
	protected $type;

	/**
	 * @var int
	 */
	protected $elapsed;

	/**
	 * @var string
	 */
	protected $request_xml;

	/**
	 * @var string
	 */
	protected $response_xml;

	/**
	 * @var TSS_Tribal_IResponse
	 */
	protected $response;

	/**
	 *
	 * @param string $call_type Call type that was made
	 * @param int $elapsed Length of time the request took
	 * @param string $request_xml Raw request XML
	 * @param string $response_xml Raw response XML
	 * @param TSS_Tribal_IResponse $response Response parser
	 */
	public function __construct($call_type, $elapsed, $request_xml, $response_xml, TSS_Tribal_IResponse $response = NULL)
	{
		$this->type = $call_type;
		$this->elapsed = $elapsed;
		$this->request_xml = $request_xml;
		$this->response_xml = $response_xml;
		$this->response = $response;
	}

	/**
	 * Whether the call passed
	 * @return bool
	 */
	public function isValid()
	{
		return $this->response
			&& $this->response->isValid();
	}

	/**
	 * The type of call that was made
	 * @return string
	 */
	public function getCallType()
	{
		return $this->type;
	}

	/**
	 * Length of time the entire request took
	 * @return int
	 */
	public function getRequestLength()
	{
		return $this->elapsed;
	}

	/**
	 * Raw XML sent to Tribal
	 * @return string
	 */
	public function getRequestXML()
	{
		return $this->request_xml;
	}

	/**
	 * Raw XML received from Tribal
	 * @return string
	 */
	public function getResponseXML()
	{
		return $this->response_xml;
	}

	/**
	 * Response parser
	 * @return TSS_Tribal_IResponse
	 */
	public function getResponse()
	{
		return $this->response;
	}
}

?>
