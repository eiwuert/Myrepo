<?php

/**
 * Indicates something fatal has happened with the XML parsing in the LenderAPI.
 * 
 * @package LenderAPI
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class LenderAPI_XMLParseException extends LenderAPI_Exception
{
	public $operation;

	public function __construct($msg, $operation = NULL, $code = 0)
	{
		$this->operation = $operation;
		parent::__construct($msg, $code);	
	}

}
?>
