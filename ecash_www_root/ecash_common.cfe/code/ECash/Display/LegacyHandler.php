<?php

class ECash_Display_LegacyHandler
{
	public static function loadAll(array $db_row, &$response)
	{
		foreach($db_row as $column => $value)
		{
			//a hack to not use numerically indexed columns
			if(!is_numeric($column))
			{
				$response->$column = $value;
			}
		}
	}	
	
	public static function loadByKVPs(array $db_row, $key_value_pairs, $location, &$response)
	{
		$kvps = array();
		foreach($key_value_pairs as $key => $value)
		{
			$kvps[$db_row->key] = $db_row->value;
		}
		$response->{$location} = $kvps;
	}

	public static function loadLimited(array $db_row, $columns, &$response)
	{
		foreach($columns as $column)
		{
			$response->$column = $db_row[$column];
		}
	}
	
	public static function setFormattedDate($column, $value, &$response)
	{
		//format the date (remembering it's mysql format)
		$response->$column = date('m-d-Y', strtotime($value));		
	}
	
	public static function setFormattedDateFromTS($column, $value, &$response)
	{
		//format the date (from unixtime)
		$response->$column = date('m-d-Y', $value);		
	}

	/** PLEASE PLEASE PLEASE avoid the use of these 'MDY' functions in
	 *  the future!  They are for converting legacy queries over only.
	 *  I may go as far as adding a warning or notice to inhibit it's
	 *  use!  [JustinF]
	 */
	public static function setMDYFromTS($column_prefix, $ts, &$response)
	{
		$month = date('m', $ts);
		$day = date('d', $ts);
		$year = date('Y', $ts);		
		$response->{$column_prefix . '_month'} = $month;
		$response->{$column_prefix . '_day'} = $day;
		$response->{$column_prefix . '_year'} = $year;
	}
	
	public static function setMDY($column_prefix, $value, &$response)
	{
		$ts = strtotime($value);
		self::setMDYFromTS($column_prefix, $ts, $response);
	}

	public static function parseDateMDY($mdy)
	{
		if (preg_match('#^\s*(\d{2,2})\D*(\d{2,2})\D*(\d{4,4})\s*$#', $mdy, $matches))
		{
			return $matches[3] . '-' . $matches[1] . '-' . $matches[2];
		}

		throw new InvalidArgumentException('Unable to parse date string.');

	}

}

?>