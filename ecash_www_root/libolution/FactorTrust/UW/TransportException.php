<?php

/**
 * FactorTrust exceptions (not available, time out, etc.) are generally treated as errors, not fails
 *
 * In such cases, a loan action is added to the account which forces the
 * application into the verification queue and a recheck.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class FactorTrust_UW_TransportException extends RuntimeException
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
	public function __construct($message, $code, FactorTrust_UWIResponse $response = NULL)
	{
		$this->response = $response;
		parent::__construct($message, $code);
	}

	/**
	 * @return null|FactorTrust_UW_IResponse
	 */
	public function getResponse()
	{
		return $this->response;
	}
}

?>