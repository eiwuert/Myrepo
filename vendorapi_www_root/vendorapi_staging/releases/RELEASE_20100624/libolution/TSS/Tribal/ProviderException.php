<?php

/**
 * Provider exceptions are typically treated as fails, not errors.
 */
class TSS_Tribal_ProviderException extends Exception
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
	public function __construct($message, $code, TSS_Tribal_IResponse $response)
	{
		$this->response = $response;
		parent::__construct($message, $code);
	}

	/**
	 * If present, returns the response
	 * @return null|TSS_Tribal_IResponse
	 */
	public function getResponse()
	{
		return $this->response;
	}
}

?>
