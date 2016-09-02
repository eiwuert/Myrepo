<?php

/**
 * Thrown to tell callers of blackbox that something in the app needs reworking.
 *
 * This was introduced to satisfy legacy rework "stuff" which was requested by
 * CLK. When IDV calls fail, CLK would like to give customers a "second chance"
 * to fill in the IDV information in the application. Therefore we have to
 * "pause" blackbox and let them re-enter information. (Hooray!)
 * 
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_ReworkException extends Exception implements OLPBlackbox_IPropagatingException
{
	/**
	 * Information that helps callers handle this exception.
	 *
	 * Blackbox callers will need to know what/who/how the exception was
	 * caused so they can rework the application in the correct manner.
	 * 
	 * @var array
	 */
	public $Info = array();
	
	/**
	 * Stores information about the origin of the exception as well as a message.
	 *
	 * @param string $message Human readable message about the exception.
	 * @param array $info Specifics needed for the caller to handle the exception.
	 * 
	 * @return void
	 */
	public function __construct($message, array $info = array())
	{
		$this->Info = $info;
		parent::__construct($message);
	}
}

?>
