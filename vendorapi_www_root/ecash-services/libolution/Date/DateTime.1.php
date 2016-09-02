<?php
/**
 * @package Date
 */

  /**
   * Serializable DateTime Object
   * 
   * Example For a DateTime from JavaScript milliseconds:
   * @example new Date_DateTime_1(date(DATE_RFC850, Date_Util_1::millisToSeconds($milliseconds));
   */

class Date_DateTime_1 extends DateTime implements Serializable
{
	const DATE_MYSQL = 'Y-m-d H:i:s';
	
	public function serialize()
	{
		return serialize(array(
							 $this->format(self::DATE_MYSQL),
							 $this->getTimezone()->getName()
							 ));
	}

	public function unserialize($serialized)
	{
		list($time, $timezone) = unserialize($serialized);
		$this->__construct($time, new DateTimeZone($timezone));
	}

	public function __toString()
	{
        return $this->format(self::DATE_MYSQL) . ' ' . $this->getTimezone()->getName();
	}

}

?>
