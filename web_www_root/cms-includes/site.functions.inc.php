<?php
	
	function lum_getDateFormat()
	{
		global $lumRegistry;
		$date_format = lum_call('Strings', 'getByCode', array('string_code'=>'[DATE_FORMAT]', 'lang_code'=>$lumRegistry->language->lang_code));
		
	        $format = str_replace('dd', 'd', $date_format);
	        $format = str_replace('mm', 'm', $format);
	        $format = str_replace('yyyy', 'Y', $format);
		
		return $format;
	}
	
	function lum_getMysqlDate($time)
	{
		if (is_string($time))
			$time = lum_parseDate($time, true);
			
		return date('Y-m-d', $time);
	}		
	
	function lum_checkMysqlDate($date)
	{
	    if(!preg_match('/^(\d\d\d\d)-(\d\d?)-(\d\d?)$/', $date, $matches))
	    {
		return false;
	    }
	    return true;
	}
	
	// basically this will sanitize date and make sure it's valid before we use it
	// returns either the date in the correct format or returns false
	function lum_parseDate($date, $return_time = false, $force_format = false)
	{
		if (!$date)
			return false;
		
		$format = lum_getDateFormat();

		$day_index = 2;
		$month_index = 1;
		$year_index = 0;

		if (lum_checkMysqlDate($date) && !$force_format)
		{
			$format = 'Y-m-d';
		}

		$separator = '/';
		if (strpos($format, '-') !== false)
		{
			$separator = '-';
		}
		
		$date_separator = '/';
		if (strpos($date, '-') !== false)
		{
			$date_separator = '-';
		}

			
		$format_parts = split($separator, $format);
		$date_parts = split($date_separator, $date);
		
		if ($date_separator == $separator)
		{
			$day_index = array_search('d', $format_parts);
			$month_index = array_search('m', $format_parts);
			$year_index = array_search('Y', $format_parts);
		}
		
		if (count($format_parts) == 3 && count($date_parts) == 3)
		{
			if (checkdate($date_parts[$month_index], $date_parts[$day_index], $date_parts[$year_index]))
			{
				if ($return_time)
				{
					return strtotime($date_parts[$year_index].'-'. $date_parts[$month_index].'-'.$date_parts[$day_index]);
				}
				else
					return str_replace('Y', $date_parts[$year_index], str_replace('d', $date_parts[$day_index], str_replace('m', $date_parts[$month_index], $format)));
			}
		}
		return false;

	}
	
	function lum_subtractDates($start, $end)
	{
		// we are already getting a timestamp?
		if (is_integer($start) && is_integer($end))
		{
			return $end - $start;
		}
		return lum_parseDate($end, true) - lum_parseDate($start, true);
	}	

	function lum_sendErrorEmail($subject, $msg)
	{

	}
?>
