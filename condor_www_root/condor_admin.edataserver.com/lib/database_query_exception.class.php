<?php

class Database_Query_Exception extends Exception
{
	public $object;
	
	public function __construct($message, $object = NULL)
	{
		$this->message = $message;
		if($object != NULL)
		{
			$this->object = $object;
			if($object instanceof Exception)
			{
				$this->code = $object->getCode();
				$this->file = $object->getFile();
				$this->line = $object->getLine();
				$this->trace = $object->getTrace();
			}
			elseif($object instanceof Error_2)
			{
				$this->trace = $object->backtrace;
			}
		}
	}
}

?>