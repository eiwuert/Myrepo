<?php
	
	/**
	 *
	 * Various and sundry exceptions used by the framework.
	 * @author Andrew Minerd
	 *
	 */
	
	class Source_Not_Prepared extends Exception
	{
		
		public function __construct()
		{
			parent::__construct('Source not prepared.');
		}
		
	}
	
?>