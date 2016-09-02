<?php
/**
 * LenderAPI_Transport_Loopback
 *
 * @package LenderAPI
 * @version $Id: Transport.php 36911 2009-06-17 02:02:11Z dan.ostrowski $
 */
class LenderAPI_Transport_Loopback extends LenderAPI_Transport
{
	/**
	 * The request xsl object
	 *
	 * @var LenderAPI_Response
	 */
	protected $response_xsl;
	public function getResponseXsl() { return $this->response_xsl; }
	public function setResponsetXsl($data) { $this->response_xsl = $data; }
	
	public function __construct(LenderAPI_Response $response, LenderAPI_XslTransformer $response_xsl)
	{
		$this->setMethod('loopback');
		$this->setResponse($response);
		$this->setResponsetXsl($response_xsl);
	}

	/**
	 * send the data
	 *
	 * @param array|string $data
	 * @return LenderAPI_Response
	 */
	public function send($data)
	{
		$this->response->setDataSent($data);
		$this->response->setDataReceived(
			$this->response_xsl->transform($data)
		);

		return $this->response;
	}
}

?>