<?php
/**
 * Blackbox_Utils class file.
 * 
 * @author Matt Piper <matt.piper@sellingsource.com>
 */

/**
 * Blackbox utils class.
 * 
 * @author Matt Piper <matt.piper@sellingsource.com>
 */
class Blackbox_Utils
{
	/**
	 * Instance of the Blackbox_Utils.
	 *
	 * @var Blackbox_Utils
	 */
	protected static $instance;
	
	/**
	 * A way to override the current day for unit testing.
	 *
	 * @var string
	 */
	protected static $today;
	
	/**
	 * Returns an instance of the Blackbox_Utils object.
	 *
	 * @return Blackbox_Utils
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = new Blackbox_Utils();
		}
		
		return self::$instance;
	}
	
	/**
	 * Returns the current day. This function really only exists to be able
	 * to mock the return for unit testing purposes.
	 * 
	 * @return void
	 */
	public static function getToday()
	{
		return self::$today ? strtotime(self::$today) : time();
	}
	
	/**
	 * Sets the "current day". This function really only exists to be able
	 * to mock the return for unit testing purposes.
	 * 
	 * @param array $today String format of the date you want to be returned.
	 *
	 * @return void
	 */
	public static function setToday($today)
	{
		self::$today = $today;
	}
	
	/**
	 * Unsets the today var so next time its called it returns the normal
	 * expected response. This function really only exists to be able
	 * to mock the return for unit testing purposes.
	 * 
	 * @return void
	 */
	public static function resetToday()
	{
		self::$today = NULL;
	}
}
?>
