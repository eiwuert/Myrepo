<?php

require_once("general_exception.1.php");

class MySQL_Exception extends General_Exception
{

	public $query;
	
	public function __construct($message, $debug = FALSE)
	{
		parent::__construct($message,$debug);
	}
	
	public function Clean_Up()
	{
		echo "Your In Clean Up:\n\n";
	}
}
?>