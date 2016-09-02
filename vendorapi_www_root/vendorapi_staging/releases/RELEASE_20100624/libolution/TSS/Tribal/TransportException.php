<?php

/**
 * Tribal exceptions (not available, time out, etc.) are generally treated as errors, not fails
 *
 * In such cases, a loan action is added to the account which forces the
 * application into the verification queue and a recheck.
 */
class TSS_Tribal_TransportException extends RuntimeException
{
	/**
	 * @var TSS_Tribal_IResponse
	 */
	protected $response;

	/**
	 * Constructor
	 *
	 * @param string $message
	 * @param int $code
	 * @param TSS_Tribal_IResponse $response
	 */
	public function __construct($message, $code, TSS_Tribal_IResponse $response = NULL)
	{
		$this->response = $response;
		parent::__construct($message, $code);
	}

	/**
	 * @return null|TSS_Tribal_IResponse
	 */
	public function getResponse()
	{
		return $this->response;
	}
}

?>
