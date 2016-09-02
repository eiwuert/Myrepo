<?php

/**
 * Applog - Singleton Wrapper
 * applog wrapper using the singleton pattern
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */

/** Singleton around OLP_Applog
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_Applog_Singleton
{
	static private $instance = array();
	
	/** Initialize Applog
	 */
	function __construct($sub_dir, $size_limit, $file_limit, $site_name, $rotate="")
	{
		include_once('OLP_Applog.php');
		self::$instance[$sub_dir] = new OLP_Applog($sub_dir, $size_limit, $file_limit, $site_name, $rotate);
	}
	
	/** Do singleton magic here.
	 *
	 * @return OLP_Applog
	 */
	static public function Get_Instance($sub_dir, $size_limit, $file_limit, $site_name, $rotate="")
	{
		if ( !isset(self::$instance[$sub_dir]) )
		{
			new OLP_Applog_Singleton($sub_dir, $size_limit, $file_limit, $site_name, $rotate);
		}
		
		return self::$instance[$sub_dir];
	}
	
	/** Fast-write to OLP_Applog.
	 */
	static public function quickWrite($text, $sub_dir = APPLOG_SUBDIRECTORY, $level = LOG_DEBUG)
	{
		// Initialize OLP_Applog with the common defaults, so if not created yet, uses those.
		$applog = self::Get_Instance($sub_dir, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, NULL, APPLOG_ROTATE);
		
		// Write to the log
		$applog->Write($text, $level);
	}
}

?>
