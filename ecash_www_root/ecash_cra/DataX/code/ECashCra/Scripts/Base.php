<?php

/**
 * The base script class
 *
 * @package ECashCra
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class ECashCra_Scripts_Base
{
	/**
	 * @var ECashCra_Api
	 */
	private $api;
	
	/**
	 * @var string YYYY-MM-DD
	 */
	protected $date;
	
	/**
	 * creates a new script object
	 *
	 * @param ECashCra_Api $api
	 */
	public function __construct(ECashCra_Api $api)
	{
		$this->api = $api;
	}
	
	/**
	 * Returns the api connection
	 *
	 * @return ECashCra_Api
	 */
	public function getApi()
	{
		return $this->api;
	}
	
	/**
	 * Sets the export date of the application
	 *
	 * @param string $date YYYY-MM-DD
	 * @return null
	 */
	public function setExportDate($date)
	{
		$this->date = $date;
	}

	/**
	 * Outputs a message to stdout
	 *
	 * <b>Revision History</b>
 	 * <ul>
 	 *     <li><b>2008-10-29 - alexanderl</b><br>
 	 *         added application id as an argument [#18902]
 	 *     </li>
 	 * </ul>
	 *
	 * @param bool $success
	 * @param int $external_id
	 * @param ECashCRA_IPacketResponse $response
	 * @param int $application_id
	 * @return null
	 */
	protected function logMessage($success, $external_id, ECashCRA_IPacketResponse $response, $application_id = NULL)
	{
		if (!$success)
		{
			echo "FAIL - externalid: {$external_id}\ttransactionid: {$response->getTransactionId()}\tapplicationid: {$application_id}\n\t[{$response->getErrorCode()}] {$response->getErrorMsg()}\n"; //#18902
		}
	}
	
	/**
	 * Creates a new response object
	 *
	 * @return ECashCra_PacketResponse_UpdateResponse
	 */
	protected function createResponse()
	{
		return new ECashCra_PacketResponse_UpdateResponse();
	}
}

?>
