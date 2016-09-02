<?php

/**
	A message used to return the result of a call.
*/

class SourcePro_Prpc_Message_Return extends SourcePro_Prpc_Message_Base
{
	public $args;
	public $output;

	function __construct ($args, $output = NULL)
	{
		$this->args = $args;
		$this->output = $output;
	}
}


?>