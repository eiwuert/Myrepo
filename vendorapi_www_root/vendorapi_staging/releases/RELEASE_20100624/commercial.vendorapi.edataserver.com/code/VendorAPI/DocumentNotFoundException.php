<?php
class VendorAPI_DocumentNotFoundException extends Exception
{
	/**
	 * Provide a better default message
	 * @param string $msg
	 * @param Integer $code
	 * @return void
	 */
	public function __construct($msg='Document not found.', $code=0)
	{
		parent::__construct($msg, $code);
	}
}