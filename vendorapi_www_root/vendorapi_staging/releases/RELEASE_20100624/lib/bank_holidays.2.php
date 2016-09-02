<?php

## Bank Holidays Class (Version 2)
## Matt Piper
## The original bank holidays class only returned dates for one year from the
##   the current day.  This class allows you to specify a range of dates
##   so you can get dates from the past, or well into the future.

class Bank_Holidays
{
	var $start_year;
	var $end_year;
	
	function Bank_Holidays($start_year, $end_year)
	{
		$this->start_year = $start_year!="" ? $start_year : date("Y");
		$this->end_year = $end_year!="" ? $end_year : (date("Y")+1);
	}

	function Get_Holidays($format = NULL, $s = "-")
	{
		$holidays = array();
		$current_year = $this->start_year;
		while($current_year <= $this->end_year) {
			$holidays[] = $this->New_Years_Day($current_year);
			$holidays[] = $this->MLK_Observed($current_year);
			$holidays[] = $this->Presidents_Day($current_year);
			$holidays[] = $this->Memorial_Day($current_year);
			$holidays[] = $this->Independence_Day($current_year);
			$holidays[] = $this->Labor_Day($current_year);
			$holidays[] = $this->Columbus_Day($current_year);
			$holidays[] = $this->Veterans_Day($current_year);
			$holidays[] = $this->Thanksgiving_Day($current_year);
			$holidays[] = $this->Christmas_Observed($current_year);
			
			$current_year++;
		}
		
		// Not passing a format will cause the default timestamps to return
		if( !is_null($format) )
		{
			$format = strtolower($format);
			
			foreach($holidays as $key => $holiday)
			{
				switch($format)
				{
					case "us"; // mm-dd-yyyy
						$holidays[$key] = date("m{$s}d{$s}Y", $holiday);
					break;
					
					case "eur"; // dd-mm-yyyy
						$holidays[$key] = date("d{$s}m{$s}Y", $holiday);
					break;

					default:
					case "iso"; // yyyy-mm-dd
						$holidays[$key] = date("Y{$s}m{$s}d", $holiday);
					break;
					
					
				}
			}
		}
		
		return $holidays;
	}
	
	function New_Years_Day($year)
	{
		$new_years = strtotime("1 January {$year}");
		return $new_years;
	}

	function Nth_DOW($month, $day, $plus = NULL, $year)
	{
		$date = strtotime("1 {$month} {$year}");
		
		if(date("l", $date) != "{$day}")
		{
			$date = strtotime("this {$day}", $date);
		}
		
		if($plus != NULL)
		{
			$date = strtotime("+{$plus} weeks", $date);
		}

		return $date;
	}
	
	function MLK_Observed($year)
	{
		return $this->Nth_DOW('January', 'Monday', 2, $year);
	}

	function Presidents_Day($year)
	{
		return $this->Nth_DOW('February', 'Monday', 2, $year);		
	}

	function Memorial_Day($year)
	{
		$may31 = strtotime("31 May {$year}");
		$memorial = NULL;
		if(date("D", $may31) == "Mon")
		{
			$memorial = $may31;
		}
		else
		{
			$memorial = strtotime("last Monday", $may31);
		}
		return $memorial;		
	}

	function Independence_Day($year)
	{
		$july4 = strtotime("4 July {$year}");
		return $july4;		
	}

	function Labor_Day($year)
	{
		return $this->Nth_DOW('September', 'Monday', NULL, $year);		
	}

	function Columbus_Day($year)
	{
		return $this->Nth_DOW('October', 'Monday', 1, $year);		
	}

	function Veterans_Day($year)
	{
		$veterans = strtotime("11 November {$year}");
		return $veterans;		
	}

	function Thanksgiving_Day($year)
	{
		return $this->Nth_DOW('November', 'Thursday', 3, $year);		
	}

	function Christmas_Observed($year)
	{
		$xmas = strtotime("25 December {$year}");
		$observed = NULL;
		if(date("D", $xmas) == "Sat")
		{
			$observed = strtotime("24 December {$year}");
		}
		elseif(date("D", $xmas) == "Sun")
		{
			$observed = strtotime("26 December {$year}");
		}
		else
		{
			$observed = $xmas;
		}
		return $observed;
	}
}

?>