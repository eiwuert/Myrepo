<?php

/**
 * An exception thrown to indicate a Rule has failed in an exceptional manner.
 * 
 * @see OLPBlackbox_Enterprise_CLK_UFC_Rule_WinnerVerifiedStatus
 * @see OLPBlackbox_Enterprise_CLK_Rule_DataX
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_FailException extends Exception
{
	/**
	 * Code is optional, so put up some defaults.
	 *
	 * @param string $message Exception message.
	 * @param int $code Exception code.
	 * 
	 * @return void
	 */
	public function __construct($message = '', $code = 0)
	{
		parent::__construct($message, $code);
	}
}

?>
