<?php

/**
 * Clarity exceptions (not available, time out, etc.) are generally treated as errors, not fails
 *
 * In such cases, a loan action is added to the account which forces the
 * application into the verification queue and a recheck.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class Clarity_UW_TransportException extends RuntimeException
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
	public function __construct($message, $code, Clarity_UW_IResponse $response = NULL)
	{
		$this->response = $response;
		parent::__construct($message, $code);
	}

	/**
	 * @return null|Clarity_UW_IResponse
	 */
	public function getResponse()
	{
		return $this->response;
	}
}

?>