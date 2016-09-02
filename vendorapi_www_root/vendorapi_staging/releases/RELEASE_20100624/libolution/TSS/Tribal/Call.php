<?php

/**
 * Object representing a single call to tribal server
 */
class TSS_Tribal_Call
{
	const TIMEOUT = 15;


	/**
	 * A Tribal Request transformer
	 *
	 * @var TSS_Tribal_IRequest
	 */
	protected $request;

	/**
	 * A Tribal Response Transformer
	 *
	 * @var TSS_IResponse
	 */
	protected $response;

	protected $timeout;
	protected $url;

	/**
	 * Constructor
	 *
	 * @param string $url
	 * @param TSS_Tribal_IRequest $request
	 * @param TSS_Tribal_IResponse $response
	 * @param int $timeout
	 */
	public function __construct(TSS_Tribal_IRequest $request, TSS_Tribal_IResponse $response)
	{
		$this->request = $request;
		$this->response = $response;
		$this->timeout = self::TIMEOUT;
		$this->setUrl();
	}

	public function setUrl()
	{
		$tribal_username = ECash::getConfig()->TRIBAL_USERNAME;
		$tribal_password = ECash::getConfig()->TRIBAL_PASSWORD;
		$tribal_url = ECash::getConfig()->TRIBAL_URL;
		
		$url = "https://" . $tribal_username . ":" . $tribal_password . "@" . $tribal_url . "/leads/post";
		
		$this->url = $url;
	}

	/**
	 * Set a request
	 *
	 * @param TSS_Tribal_IRequest $request
	 * @return void
	 */
	public function setRequest(TSS_Tribal_IRequest $request)
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
	 * @param TSS_Tribal_IResponse $response
	 * @return void
	 */
	public function setResponse(TSS_Tribal_IResponse $response)
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
	 * @return TSS_Tribal_Result
	 */
	public function execute(array $data)
	{
		//for curl
		$request_xml = $this->request->transformData($data);
		file_put_contents('/tmp/tribal_request.html', $request_xml);
		$start_time = microtime(TRUE);
		$response_xml = $this->makeRequest($request_xml);
		$elapsed = (microtime(TRUE) - $start_time);
		file_put_contents('/tmp/tribal_response.html', $response_xml);
		/////////////
	
		/*
		//for soapClient
		$xml_array = $this->request->transformData($data);
		$client = new SoapClient(null,
				array(
				'location' => self::URL,
				//'uri'      => "http://test-uri/",
				'uri'      => "https://vendorapi.loanservicingcompany.com/soap.php",
				'trace'    => 1,
				));
		$response_xml = $client->__soapCall("Function1", $xml_array);
		//$request = $client->__getLastRequest();
		//var_dump($request);v();
		/////////////
		*/

		$this->response->parseXML($response_xml);

		return new TSS_Tribal_Result(
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
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_POST => 1,
			CURLOPT_POSTFIELDS => $request,
			CURLOPT_HTTPHEADER => array('Content-Type: text/xml'),
			CURLOPT_TIMEOUT => $this->timeout,
			CURLOPT_SSL_VERIFYPEER => false,
		);

		$curl = curl_init();
		curl_setopt_array($curl, $opt);
		$response = curl_exec($curl);

		if (!$response)
		{
			//throw new TSS_Tribal_TransportException(curl_error($curl), curl_errno($curl));
			$response = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<response><decision>0</decision><code>ERROR</code><message>Tribal Server Down</message></response>";
		}

		return $response;
	}
}

?>
