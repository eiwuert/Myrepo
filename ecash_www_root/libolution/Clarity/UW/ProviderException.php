<?php

/**
 * Provider exceptions are typically treated as fails, not errors.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class Clarity_UW_ProviderException extends Exception
{
	/**
	 * @var Clarity_UW_IResponse
	 */
	protected $response;

	/**
	 * Constructor
	 *
	 * @param string $message
	 * @param int $code
	 * @param Clarity_UW_IResponse $response
	 */
	public function __construct($message, $code, Clarity_UW_IResponse $response)
	{
		$this->response = $response;
		parent::__construct($message, $code);
	}

	/**
	 * If present, returns the response
	 * @return null|Clarity_UW_IResponse
	 */
	public function getResponse()
	{
		return $this->response;
	}
}

?>