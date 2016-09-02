<?php
/**
	A basic exception.
*/

class SourcePro_Exception extends Exception
{
	/// The node (host) that the exception occured on.
	public $m_node;
	
	/**
		Initializes an instance of this class.

		@param message	The exception message.
		@param code		The exception code.
	*/
	function __construct ($message = NULL, $code = 0)
	{
		parent::__construct($message, $code);
		$this->m_node = SourcePro::$node;
	}
}

?>