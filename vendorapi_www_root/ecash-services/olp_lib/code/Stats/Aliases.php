<?php
/**
 * Stats_Aliases class will define aliases for stats with a way to retrieve the aliases
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class Stats_Aliases
{
	/**
	 * The constants below will consistently identify they "key" name for a stat alias
	 *
	 */
	const STAT_PREQUAL 	= 'prequal';
	const STAT_SUBMIT 	= 'submit';
	const STAT_VISITOR 	= 'visitor';
	
	/**
	 * Array to link stat with aliases
	 *
	 * @var array
	 */
	protected static $stat_defs = array(
		self::STAT_PREQUAL 	=> array('prequal','base'),
		self::STAT_SUBMIT	=> array('submit','income'),
		self::STAT_VISITOR	=> array('visitor','visitors'),
		);
	
	/**
	 * Function to get an array of stat aliases for a stat 
	 * If no alias is defined, the array contains a single
	 * item with the stat passed to the function
	 *
	 * @param string $stat
	 * @return array
	 */
	public static function getAliases($stat)
	{
		$retval = array($stat);
		if (isset(self::$stat_defs[$stat]))
		{
			$retval = self::$stat_defs[$stat];
		}
		return $retval;
	}
	
}
?>