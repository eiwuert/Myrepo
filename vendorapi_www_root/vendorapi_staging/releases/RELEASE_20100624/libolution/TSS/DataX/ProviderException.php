<?php

/**
 * Provider exceptions are typically treated as fails, not errors.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class TSS_DataX_ProviderException extends Exception
{
	/**
	 * @var TSS_DataX_IResponse
	 */
	protected $response;

	/**
	 * Constructor
	 *
	 * @param string $message
	 * @param int $code
	 * @param TSS_DataX_IResponse $response
	 */
	public function __construct($message, $code, TSS_DataX_IResponse $response)
	{
		$this->response = $response;
		parent::__construct($message, $code);
	}

	/**
	 * If present, returns the response
	 * @return null|TSS_DataX_IResponse
	 */
	public function getResponse()
	{
		return $this->response;
	}
}

?>