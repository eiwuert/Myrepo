<?php

/**
 * Used to send a packet to the cra api and return a response.
 *
 * @package ECashCra
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class ECashCra_Api
{
	/**
	 * @var string
	 */
	protected $target;
	
	/**
	 * @var string
	 */
	protected $username;
	
	/**
	 * @var string
	 */
	protected $password;
	
	/**
	 * Creates a new api object
	 *
	 * @param string $target
	 * @param string $username
	 * @param string $password
	 */
	public function __construct($target, $username, $password)
	{
		$this->target = $target;
		$this->username = $username;
		$this->password = $password;
	}
	
	/**
	 * Sends the packet to the CRA api.
	 * 
	 * The $response object is populated with the appropriate data.
	 *
	 * @param ECashCra_IPacket $packet
	 * @param ECashCRA_IPacketResponse $response
	 * @return null
	 * @throws ECashCra_ApiException When the request could not be sent or the 
	 *         response could not be parsed.
	 */
	public function sendPacket(ECashCra_IPacket $packet, ECashCRA_IPacketResponse $response)
	{
		$dom = $packet->getXml();
		$this->injectAuthentication($dom);

		$response_xml = $this->runCurl($dom->saveXML());

print_r("Sending:\n");
print_r($dom->saveXML());
print_r("\n");
		
		try
		{
			$response->loadXml($response_xml);
		}
		catch (ECashCRA_PacketResponse_Exception $exception)
		{
			throw new ECashCra_ApiException("There was an error parsing the response: "
				. "{$exception->getMessage()}\n"
				. "{$exception->getTraceAsString()}"
			);
		}
	}
	
	/**
	 * Executes a curl request.
	 *
	 * @param string $request The data to send
	 * @return string The result of the https request
	 * @throws ECashCra_ApiException When the request could not be sent
	 */
	protected function runCurl($request)
	{
		$curl = curl_init($this->target);
		curl_setopt($curl, CURLOPT_VERBOSE, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, (string)$request);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
		
		$response = curl_exec($curl);
		
		if (curl_errno($curl))
		{
			$error = curl_error($curl);
			curl_close($curl);
			throw new ECashCra_ApiException("Could not send packet: " . $error);
		}
		
		curl_close($curl);
		return $response;
	}
	
	/**
	 * Injects the authentication xml into the given document.
	 *
	 * @param DOMDocument $xml
	 * @return null
	 */
	protected function injectAuthentication(DOMDocument $xml)
	{
		$cra_inquiry = $xml->documentElement;
		$authentication = $xml->createElement('AUTHENTICATION');
		$authentication->appendChild($xml->createElement('USERNAME', $this->username));
		$authentication->appendChild($xml->createElement('PASSWORD', htmlentities($this->password)));
		
		$cra_inquiry->insertBefore($authentication, $cra_inquiry->firstChild);
	}
}

?>
