<?php

	/**
	 * client.php
	 * 
	 * acts as a version independant interface to the multiple 
	 * version-dependant nirvana client base classes
	 * 
	 * @author John Hargrove
	 */
	
	if (!function_exists('phpversion'))
	{
		trigger_error("PHP versions prior to 4.1.0 are not supported.", E_ERROR);
	}
	
	// php 4.1.0 - <5.0.0
	if (version_compare(phpversion(), "5.0.0", "<"))
	{
		require_once('php4/client.php');
	}
	
	// php 5.0.0+
	else if (version_compare(phpversion(), "5.0.0", ">="))
	{		
		require_once('php5/client.php');
	}
?>
