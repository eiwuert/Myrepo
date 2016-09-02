<?php
	// Version 1.0.0
	/**
		@publicsection
		@public
		@brief
			A class to handle warnings

		Set a warning value when an application succeds, but not in the way planned.
		This is less severe than a error which could stop operations.

		@version
			1.0.0 2003-01-04 - Paul Strange
				- Created the warning to handle non-critical errors
	*/

	require_once ("debug.1.php");

	class Warning_1
	{
		/**
			@publicsection
			@public
			@fn boolean Warning_1 ()
			@brief
				Class constructor

			Class constructor

			@return
				boolean
		*/
		function Warning_1 ()
		{
			return TRUE;
		}
		
		/**
			@publicsection
			@public
			@fn boolean Warning_Test ($result)
			@brief
				Test a variable to see if it is a warning

			Test the variable result to see if it is a warning object or just a varaible

			@param result mixed \n The variable to test.

			@return
				boolean
		*/
		function Warning_Test ($result)
		{
			// Error checking
			if (is_a ($result, "Warning_1"))
			{
				return TRUE;
			}

			return FALSE;
		}
		
		/**
			@publicsection
			@public
			@fn string Get_Version ()
			@brief
				Display version information about the object

			Display version information about the object for compatibility issues.

			@return
				string
		*/
		function Get_Version ()
		{
			$version = new stdClass ();

			$version->api = 1;
			$version->feature = 0;
			$version->bug = 0;
			$version->version = $version->api.".".$version->feature.".".$version->bug;

			return $version;
		}
	}
?>
