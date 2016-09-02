<?php

/**
 * Used to send a packet to the cra api and return a response.
 *
 * @package Clarity ECashCra
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
	 * @var string
	 */
	protected $group;
	
	/**
	 * Creates a new api object
	 *
	 * @param string $target
	 * @param string $username
	 * @param string $password
	 */
	public function __construct($target, $username, $password, $store, $merchant, $group)
	{
		$this->target = $target;
		$this->username = $username;
		$this->password = $password;
		$this->store = $store;
		$this->merchant = $merchant;
		$this->group = $group;
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
print_r("Sending:\n");
print_r($dom->saveXML());
print_r("\n");
		$response_xml = $this->runCurl($dom->saveXML());
print_r("Received:\n");
print_r($response_xml);
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
		curl_setopt($curl, CURLOPT_VERBOSE,0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, (string)$request);
		//curl_setopt($curl, CURLOPT_HEADER, 0);
		//curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/xml;charset=UTF-8','Accept: text/xml;charset=UTF-8'));
		
		curl_setopt($curl, CURLOPT_URL, $this->target);
		curl_setopt($curl, CURLOPT_FAILONERROR, TRUE);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($curl, CURLOPT_MAXREDIRS, 10);

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
		$child_nodes = $cra_inquiry->getElementsByTagName('*');
        if (count($child_nodes) > 0) {
            $first_child_node = $cra_inquiry->firstChild;
            $cra_inquiry->insertBefore($xml->createElement('group-id', $this->group),$first_child_node);
            $cra_inquiry->insertBefore($xml->createElement('account-id', $this->merchant),$first_child_node);
            $cra_inquiry->insertBefore($xml->createElement('location-id', $this->store),$first_child_node);
            $cra_inquiry->insertBefore($xml->createElement('username', $this->username),$first_child_node);
            $cra_inquiry->insertBefore($xml->createElement('password', htmlentities($this->password)),$first_child_node);
        } else {
            $cra_inquiry->appendChild($xml->createElement('group-id', $this->group));
            $cra_inquiry->appendChild($xml->createElement('account-id', $this->merchant));
            $cra_inquiry->appendChild($xml->createElement('location-id', $this->store));
            $cra_inquiry->appendChild($xml->createElement('username', $this->username));
            $cra_inquiry->appendChild($xml->createElement('password', htmlentities($this->password)));
        }
	}
}

?>
