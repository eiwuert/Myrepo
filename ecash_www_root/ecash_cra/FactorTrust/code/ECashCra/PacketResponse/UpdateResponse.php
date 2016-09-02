<?php

/**
 * The standard update packet response.
 *
 * @package ECashCra
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class ECashCra_PacketResponse_UpdateResponse implements ECashCra_IPacketResponse
{
	/**
	 * @var int
	 */
	protected $transaction_id;
	
	/**
	 * @var bool
	 */
	protected $is_success;
	
	/**
	 * @var int
	 */
	protected $error_code;
	
	/**
	 * @var string
	 */
	protected $error_msg;
	
	/**
	 * Loads an xml string into the response.
	 *
	 * @param string $xml_string
	 * @return null
	 * @throws ECashCRA_PacketResponse_Exception
	 */
	public function loadXml($xml_string)
	{
		try
		{
			$xml = new SimpleXMLElement($xml_string);
			
			$this->transaction_id = (string)$xml->TransactionID;
            $response = $xml->ReportLoanResult;

			$code = (string)$response->Code;
			
			if ($code === '100')
			{
				$this->is_success = TRUE;
			}
			else
			{
				$this->is_success = FALSE;
				$this->error_code = $code;
				$this->error_msg = (string)$response->Description;
			}
		}
		catch (Exception $e)
		{
			throw new ECashCra_PacketResponse_Exception($e->getMessage(), $e->getCode());
		}
	}
	
	/**
	 * Returns the transaction id.
	 *
	 * @return int
	 */
	public function getTransactionId()
	{
		return $this->transaction_id;
	}
	
	/**
	 * Returns true on packet success, false otherwise.
	 *
	 * @return bool
	 */
	public function isSuccess()
	{
		return $this->is_success;
	}
	
	/**
	 * Returns the error cod
	 *
	 * @return int
	 */
	public function getErrorCode()
	{
		return !$this->isSuccess()
			? (string)$this->error_code
			: NULL;
	}
	
	/**
	 * Returns the error message.
	 *
	 * @return string
	 */
	public function getErrorMsg()
	{
		return !$this->isSuccess()
			? (string)$this->error_msg
			: NULL;
	}
}
?>
