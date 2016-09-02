<?php

/**
 * @package Util
 */

/**
 * Utility class for generating guids
 *
 */
class Util_Guid_1
{
	/**
	* Guid prefix
	*
	* @var string
	*/
	protected static $pre;

	/**
	* Guid sequence
	*
	* @var int
	*/
	protected static $seq;

	/**
	* Static constructor
	*
	* @return null
	*/
	protected static function staticInit()
	{
		if (!self::$pre)
		{
			self::$pre = trim(`hostid`).getmypid().microtime().uniqid(mt_rand(), TRUE);
			self::$seq = 1;
		}
	}

	/**
	* Create the next id
	*
	* @return string
	*/
	public static function newId()
	{
		self::staticInit();
		return Util_Convert_1::bin2String(sha1(self::$pre.self::$seq++, TRUE));
	}
}

?>
