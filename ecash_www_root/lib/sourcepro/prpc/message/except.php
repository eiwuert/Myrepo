<?php

/**
	A message used to throw an exception.
*/

class SourcePro_Prpc_Message_Except extends SourcePro_Prpc_Message_Base
{
	public $except;
	public $output;

	function __construct ($except, $output = NULL)
	{
		$this->except = $except;
		$this->output = $output;
	}
}

?>