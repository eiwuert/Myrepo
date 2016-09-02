<?php

/**
 * Used to send a packet to the cra api and return a response.
 *
 * @package FactorTrust ECashCra
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
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
	 * @var string
	 */
	protected $store;
	
	/**
	 * @var string
	 */
	protected $merchant;
	
	/**
	 * Creates a new api object
	 *
	 * @param string $target
	 * @param string $username
	 * @param string $password
	 */
	public function __construct($target, $username, $password, $store, $merchant)
	{
		$this->target = $target;
		$this->username = $username;
		$this->password = $password;
		$this->store = $store;
		$this->merchant = $merchant;
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
print_r($dom->saveXML());
print_r(chr(13));
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
		$authentication = $xml->createElement('LoginInfo');
		$authentication->appendChild($xml->createElement('Username', $this->username));
		$authentication->appendChild($xml->createElement('Password', htmlentities($this->password)));
		$authentication->appendChild($xml->createElement('LenderIdentifier', $this->merchant));
		$authentication->appendChild($xml->createElement('MerchantIdentifier', $this->merchant));
		$authentication->appendChild($xml->createElement('StoreIdentifier', $this->store));
		
		$cra_inquiry->insertBefore($authentication, $cra_inquiry->firstChild);
	}
}

?>
