<?php

/**
	A message used to invoke a method.
*/

class SourcePro_Prpc_Message_Call extends SourcePro_Prpc_Message_Base
{
	public $method;
	public $arg;

	function __construct ($method, $arg)
	{
		$this->method = $method;
		$this->arg = $arg;
	}
}

?>
