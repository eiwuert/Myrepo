<?php

/**
 * DataX exceptions (not available, time out, etc.) are generally treated as errors, not fails
 *
 * In such cases, a loan action is added to the account which forces the
 * application into the verification queue and a recheck.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class TSS_DataX_TransportException extends RuntimeException
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
	public function __construct($message, $code, TSS_DataX_IResponse $response = NULL)
	{
		$this->response = $response;
		parent::__construct($message, $code);
	}

	/**
	 * @return null|TSS_DataX_IResponse
	 */
	public function getResponse()
	{
		return $this->response;
	}
}

?>