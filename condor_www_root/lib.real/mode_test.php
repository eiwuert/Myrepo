<?php
/**
 * @publicsection
 * @brief The Mode Test will return the current mode for the server
 *
 * Use the Get_Mode function to return the current mode
 *
 * @author Jason Gabriele <jason.gabriele@sellingsource.com>
 *
 * @version
 * 	    1.0.0 Mar 29, 2006 - Jason Gabriele <jason.gabriele@sellingsource.com>
 */

class Mode_Test {
	/**
	 * Unknown Mode
	 *
	 * This means could not determine which mode it's in
	 * @var int
	 */
	public static $UNKNOWN = 0;

	/**
	 * Local Mode
	 * @var int
	 */
	public static $LOCAL = 1;

	/**
	 * RC Mode
	 * @var int
	 */
	public static $RC = 2;

	/**
	 * Live Mode
	 * @var int
	 */
	public static $LIVE = 3;

	/**
	 * New World
	 * @var int
	 */
	public static $NW = 4;

	/**
	 * Command Line Mode
	 * @var int
	 */
	public static $CLI = 5;

	/**
	 * @public
	 * @brief Get Mode
	 *
	 * Grab the current mode. Defaults to LOCAL
	 * @return int Current Mode as INT
	 */
	public static function Get_Mode()
	{
		if (preg_match('/^rc\d*\./i', $_SERVER['SERVER_NAME'])
			|| preg_match('/^demo./i', $_SERVER['SERVER_NAME'])
			|| preg_match('/\.dev\d\.clkonline\.com$/i', $_SERVER['SERVER_NAME'])
			|| preg_match('/^live\.mqrc\./i', $_SERVER['SERVER_NAME'])
			|| preg_match('/rc\d+\.tss/i', $_SERVER['SERVER_NAME']))
			{
			return self::$RC;
		}
		elseif (preg_match('/\.([^.]+?)\.tss$/i', $_SERVER['SERVER_NAME']))
		{
			return self::$LOCAL;
		}
		elseif (preg_match('/^(bfw|nms|olp)\./i', $_SERVER['SERVER_NAME'])
			|| preg_match('/^secure\.edataserver\.com/i', $_SERVER['SERVER_NAME'])
			|| preg_match('/condor_signature\.com$/i', $_SERVER['SERVER_NAME']))
		{
			return self::$LIVE;
		}
		elseif (preg_match('/^nw\./i', $_SERVER['SERVER_NAME']))
		{
			return self::$NW;
		}
		elseif (isset($_SERVER['argv'][0]) && $_SERVER['argv'][0] != "")
		{
			return self::$CLI;
		}

		return self::$UNKNOWN;
	}

	/**
	 * @public
	 * @brief Get Mode As String
	 *
	 * Grab the current mode as a string
	 * @return string Current Mode as string
	 */
	public static function Get_Mode_As_String()
	{
		$mode = self::Get_Mode();

		switch($mode)
		{
			case self::$RC:
				return "RC";
			case self::$LIVE:
				return "LIVE";
			case self::$LOCAL:
				return "LOCAL";
			case self::$NW:
				return "NW";
			case self::$CLI:
				return "CLI";
		}

		return "UNKNOWN";
	}

	/**
	 * @public
	 * @brief Get Local Machine Name
	 *
	 * Get the local machine's name
	 * @return string Local Machine's Name
	 */
	public static function Get_Local_Machine_Name()
	{
		if (preg_match('/\.([^.]+?)\.tss$/i', $_SERVER['SERVER_NAME'],$matches))
		{
			return $matches[1];
		}
		return false;
	}
}
?>
