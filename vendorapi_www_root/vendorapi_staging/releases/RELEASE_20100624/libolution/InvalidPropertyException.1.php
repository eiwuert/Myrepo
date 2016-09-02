<?php

	/**
	 * An exception when an invalid property is accessed
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	class InvalidPropertyException_1 extends Exception
	{
		/**
		 * @param string $name Property name
		 */
		public function __construct($name)
		{
			parent::__construct('Attempt to access a non-existent or non-public property, '.$name);
		}
	}

?>