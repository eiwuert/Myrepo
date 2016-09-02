<?php

/**
 * Object representing a single datax call
 * @author stephan soileau <stephan.soileau@sellingsource.com>
 */
class TSS_DataX_Call
{
	/**
	 * A DataX Request transformer
	 *
	 * @var TSS_DataX_IRequest
	 */
	protected $request;

	/**
	 * A DataX Response Transformer
	 *
	 * @var TSS_DataX_IResponse
	 */
	protected $response;

	/**
	 * The URL to post the xml to
	 *
	 * @var string
	 */
	protected $url;

	/**
	 * @var int
	 */
	protected $timeout;

	/**
	 * Constructor
	 *
	 * @param string $url
	 * @param TSS_DataX_IRequest $request
	 * @param TSS_DataX_IResponse $response
	 * @param int $timeout
	 */
	public function __construct($url, TSS_DataX_IRequest $request, TSS_DataX_IResponse $response, $timeout = 15)
	{
		$this->url = $url;
		$this->request = $request;
		$this->response = $response;
		$this->timeout = $timeout;
	}

	/**
	 * Set the url
	 *
	 * @param string $url
	 * @return void
	 */
	public function setUrl($url)
	{
		$this->url = $url;
	}

	/**
	 * Set a request
	 *
	 * @param TSS_DataX_IRequest $request
	 * @return void
	 */
	public function setRequest(TSS_DataX_IRequest $request)
	{
		$this->request = $request;
	}

	/**
	 * Get a request
	 *
	 * @return $request
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * Set a response
	 *
	 * @param TSS_DataX_IResponse $response
	 * @return void
	 */
	public function setResponse(TSS_DataX_IResponse $response)
	{
		$this->response = $response;
	}

	/**
	 * @return string
	 */
	public function getCallType()
	{
		return $this->request->getCallType();
	}

	/**
	 * Make a new datax call and set the response
	 * 
	 * @param array $data
	 * @return TSS_DataX_Result
	 */
	public function execute(array $data)
	{
		$request_xml = $this->request->transformData($data);
file_put_contents('/tmp/datacallout', $request_xml);
		$start_time = microtime(TRUE);
		$response_xml = $this->makeRequest($request_xml);
		$elapsed = (microtime(TRUE) - $start_time);
file_put_contents('/tmp/datacallback', $response_xml);

		$this->response->parseXML($response_xml);

		return new TSS_DataX_Result(
			$this->request->getCallType(),
			$elapsed,
			$request_xml,
			$response_xml,
			$this->response
		);
	}

	/**
	 * Make a curl request, and return the response
	 * or throw a RuntimeException if the request fails
	 *
	 * @throws RuntimeException
	 * @param string $request
	 * @return string
	 */
	protected function makeRequest($request)
	{
		$opt = array(
			CURLOPT_URL => $this->url,
			CURLOPT_VERBOSE => 0,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_POST => 1,
			CURLOPT_POSTFIELDS => $request,
			CURLOPT_HTTPHEADER => array('Content-Type: text/xml'),
			CURLOPT_TIMEOUT => $this->timeout,
			CURLOPT_FAILONERROR => TRUE,
			CURLOPT_FOLLOWLOCATION => TRUE,
			CURLOPT_MAXREDIRS => 10
		);

		$curl = curl_init();
		curl_setopt_array($curl, $opt);
		$response =	curl_exec($curl);

		if (!$response)
		{
			throw new TSS_DataX_TransportException(curl_error($curl), curl_errno($curl));
		}

		return $response;
	}

}
