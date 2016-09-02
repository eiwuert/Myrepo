<?php

/**
 * Thrown to tell callers of blackbox that something in the app needs reworking.
 *
 * NOTE: This does not subclass Blackbox_Exception on purpose. If it did not
 * it would be caught in the try ... catch in Blackbox_Rule and trigger
 * onError() instead of stopping the operation of Blackbox.
 *
 * This was introduced to satisfy legacy rework "stuff" which was requested by
 * CLK. When IDV calls fail, CLK would like to give customers a "second chance"
 * to fill in the IDV information in the application. Therefore we have to
 * "pause" blackbox and let them re-enter information. (Hooray!)
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class VendorAPI_Blackbox_FactorTrust_ReworkException extends Exception
{
	/**
	 * The FactorTrust response that triggered the rework condition
	 * @var TSS_DataX_IResponse
	 */
	protected $response;

	/**
	 * @param string $message
	 * @param array $info FactorTrust call that failed
	 *
	 * @return void
	 */
	public function __construct($message, TSS_DataX_IResponse $response)
	{
		$this->response = $response;
		parent::__construct($message);
	}

	/**
	 * The FactorTrust response that triggered the rework
	 * @return string
	 */
	public function getResponse()
	{
		return $this->response;
	}
}

?>
