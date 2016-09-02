<?php
/**
 * Exception thign for document creation errors
 * @author stephan soileau <stephan.soileau@sellingsource.com>
 *
 */
class VendorAPI_DocumentCreateException extends Exception
{
	/**
	 *
	 * @param String $msg
	 * @param Integer $code
	 * @return void
	 */
	public function __construct($msg='Unable to create document.', $code=0)
	{
		parent::__construct($msg, $code);
	}
}