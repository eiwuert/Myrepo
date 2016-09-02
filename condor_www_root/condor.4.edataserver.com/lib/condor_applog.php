<?php
require_once('applog.1.php');
require_once('applog.singleton.class.php');

/**
 * A Wrapper for Applog for condor to 
 * use to log things.
 *
 */


class Condor_Applog
{
	const APPLOG_SUBDIR = 'condor';
	const APPLOG_SIZELIMIT = '1000000';
	const APPLOG_FILELIMIT = 20;
	const APPLOG_SITENAME = 'condor.4.edataserver.com';
	const APPLOG_ROTATE = true;
		
	protected $applog_object;
	
	protected static $instance;
	
	/**
	 * Create the Condor_Applog object for writing stuff
	 *
	 * @param string $subdir
	 * @param int $sizelimit
	 * @param int $filelimit
	 * @param string $sitename
	 * @param boolean $rotate
	 */
	public function __construct(
		$subdir = NULL, 
		$sizelimit = NULL, 
		$filelimit = NULL, 
		$sitename = NULL, 
		$rotate = NULL)
	{
		//First setup all the default values
		if(!is_string($subdir))
		{
			$subdir = self::APPLOG_SUBDIR;
		}
		if(!is_numeric($sizelimit))
		{
			$sizelimit = self::APPLOG_SIZELIMIT;
		}
		if(!is_numeric($filelimit))
		{
			$filelimit = self::APPLOG_FILELIMIT;
		}
		if(!is_string($sitename))
		{
			$sitename = self::APPLOG_SITENAME;
		}
		if(!is_bool($rotate))
		{
			$rotate = self::APPLOG_ROTATE;
		}
		$this->applog_object = Applog_Singleton::Get_Instance(
			$subdir, 
			$sizelimit, 
			$filelimit, 
			$sitename, 
			$rotate
		);
	}
	
	/**
	 * Static shortcut for creating a Condor_Applog 
	 * object and writing to it
	 *
	 * @param string $str
	 * @param int $debug_level
	 */
	public static function Log($str, $debug_level = 0)
	{
		if(!defined('DEBUG_LEVEL') || DEBUG_LEVEL >= $debug_level)
		{
			Condor_Applog::getInstance()->Write($str, $debug_level);
		}
	}
	
	/**
	 * Write to the applog object. If a constant 'DEBUG_LEVEL"
	 * is defined, it will only write out if the $debug_level 
	 * is greater or equal to 'DEBUG_LEVEL.' Otherwise it'll write it out.
	 *
	 * @param string $str
	 * @param int $debug_level
	 */
	public function Write($str, $debug_level = 0)
	{
		if(!defined('DEBUG_LEVEL') || DEBUG_LEVEL >= $debug_level)
		{
			$this->applog_object->Write($str,  $debug_level);
		}
	}
	
	/**
	 * Create a new instance of Condor_Applog
	 *
	 * @param string $subdir
	 * @param int $sizelimit
	 * @param int $filelimit
	 * @param string $sitename
	 * @param boolean $rotate
	 * @return Condor_Applog
	 */
	public static function getInstance(
		$subdir = NULL,
		$sizelimit = NULL,
		$filelimit = NULL,
		$sitename = NULL,
		$rotate = NULL
	)
	{
		//Set default applog values
		if(!isset(self::$instance) || !self::$instance instanceof Condor_Applog)
		{
			self::$instance = new Condor_Applog($subdir, $sizelimit, $filelimit, $sitename, $rotate);
		}
		return self::$instance;
	}
		
		
	
	
	
}