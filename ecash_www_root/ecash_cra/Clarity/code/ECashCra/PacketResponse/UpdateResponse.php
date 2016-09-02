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
//print_r("\n-------------------------------------\n");
//print_r($xml);
//print_r("\n-------------------------------------\n");
//print_r(get_class_methods($xml));
//print_r("\n-------------------------------------\n");
			
			$this->transaction_id = (string)$xml['tracking-number'];
			$response = $xml->ReportLoanResult;

			$tracking_number = (string)$response['tracking-number'];
			$errors = $xml->errors;
			$children_errors = $errors->children();
			if (count($children_errors) == 0)
			{
				$this->is_success = TRUE;
			}
			else
			{
				$this->is_success = FALSE;
				$error_code = "";
				foreach ($children_errors as $error) {
					foreach ($error as $code) {
						$error_code .= (string)$code."|";
					}
					if (strlen($error_code) > 0) {
						$error_code = substr($error_code,-1);
					}
					$this->error_code = $error_code;
					$this->error_msg = $error_code;
				}
			}
//print_r($this);
//print_r("\n");
		}
		catch (Exception $e)
		{
			$this->is_success = FALSE;
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
