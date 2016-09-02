<?php

	/**
	 * A runtime exception occurs during rule evaluation
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	class ECash_CFE_RuntimeException extends Exception
	{
		protected $trace;

		public function setBacktrace(array $trace)
		{
			$this->trace = $trace;
		}

		public function getBacktrace()
		{
			$this->trace;
		}

		// @todo implement this and add the CFE trace
		/*public function __toString()
		{
		}*/
	}

?>