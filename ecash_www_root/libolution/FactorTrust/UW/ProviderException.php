<?php

/**
 * Provider exceptions are typically treated as fails, not errors.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class FactorTrust_UW_ProviderException extends Exception
{
	/**
	 * @var FactorTrust_UW_IResponse
	 */
	protected $response;

	/**
	 * Constructor
	 *
	 * @param string $message
	 * @param int $code
	 * @param FactorTrust_UW_IResponse $response
	 */
	public function __construct($message, $code, FactorTrust_UW_IResponse $response)
	{
		$this->response = $response;
		parent::__construct($message, $code);
	}

	/**
	 * If present, returns the response
	 * @return null|FactorTrust_UW_IResponse
	 */
	public function getResponse()
	{
		return $this->response;
	}
}

?>